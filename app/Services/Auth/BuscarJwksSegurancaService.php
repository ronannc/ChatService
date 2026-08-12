<?php

namespace App\Services\Auth;

use App\Support\ContratoTokenCliente;
use App\Support\GuardaHostSeguro;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Busca segura do JWKS de um sistema integrado (§3.3 do contrato do token do
 * cliente). `jwks_url` é URL de terceiro buscada server-side — superfície de
 * SSRF — então a implementação:
 *
 * - só aceita https, na URL original e em qualquer redirect;
 * - resolve e valida o IP antes de conectar, na inicial e em cada redirect
 *   (`GuardaHostSeguro`), e conecta nesse IP já validado (`CURLOPT_RESOLVE`
 *   via `curl.options` do cliente HTTP) em vez de resolver o host de novo,
 *   o que fecharia a porta para um DNS rebinding entre a checagem e a
 *   conexão;
 * - aplica timeout de conexão/total curtos e um teto de tamanho de resposta;
 * - nunca segue redirect automaticamente — cada salto é revalidado.
 *
 * Erros de rede, formato ou dos limites acima nunca são repassados ao
 * chamador com detalhe: o `RuntimeException` genérico daqui vira, mais
 * acima, um motivo de log (`RepositorioJwks`), nunca um corpo/header
 * ecoado na resposta ao cliente.
 */
class BuscarJwksSegurancaService
{
    private const MAX_REDIRECTS = 5;

    public function __construct(private readonly GuardaHostSeguro $guarda) {}

    /**
     * @return array{keys: array<int, array<string, mixed>>}
     */
    public function buscar(string $url): array
    {
        $corpo = $this->buscarComRedirects($url, 0);

        $dados = json_decode($corpo, true);

        if (! is_array($dados) || ! isset($dados['keys']) || ! is_array($dados['keys'])) {
            throw new RuntimeException('Resposta do JWKS fora do formato esperado.');
        }

        // No máximo MAXIMO_CHAVES_JWKS chaves — o excedente é descartado,
        // não motivo de rejeição (§3 do contrato).
        $dados['keys'] = array_slice(array_values($dados['keys']), 0, ContratoTokenCliente::MAXIMO_CHAVES_JWKS);

        return $dados;
    }

    private function buscarComRedirects(string $url, int $tentativa): string
    {
        if ($tentativa > self::MAX_REDIRECTS) {
            throw new RuntimeException('Excesso de redirecionamentos ao buscar o JWKS.');
        }

        $partes = parse_url($url);

        if (! is_array($partes) || ($partes['scheme'] ?? null) !== 'https' || empty($partes['host'])) {
            throw new RuntimeException('URL do JWKS inválida: apenas https é aceito.');
        }

        $porta = $partes['port'] ?? 443;
        $ip = $this->guarda->ipPublicoDoHost($partes['host']);

        $resposta = Http::withOptions([
            'curl' => [
                CURLOPT_RESOLVE => ["{$partes['host']}:{$porta}:{$ip}"],
            ],
            'allow_redirects' => false,
        ])
            ->connectTimeout(ContratoTokenCliente::TIMEOUT_CONEXAO_JWKS_SEGUNDOS)
            ->timeout(ContratoTokenCliente::TIMEOUT_TOTAL_JWKS_SEGUNDOS)
            ->get($url);

        if (in_array($resposta->status(), [301, 302, 303, 307, 308], true)) {
            $local = $resposta->header('Location');

            if (! $local) {
                throw new RuntimeException('Redirecionamento do JWKS sem header Location.');
            }

            return $this->buscarComRedirects($this->resolverUrlRedirect($url, $local), $tentativa + 1);
        }

        if ($resposta->failed()) {
            throw new RuntimeException('Falha ao buscar o JWKS.');
        }

        $corpo = $resposta->body();

        if (strlen($corpo) > ContratoTokenCliente::TAMANHO_MAXIMO_JWKS_BYTES) {
            throw new RuntimeException('Resposta do JWKS excede o tamanho máximo permitido.');
        }

        return $corpo;
    }

    private function resolverUrlRedirect(string $urlOriginal, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $base = parse_url($urlOriginal);

        return sprintf('%s://%s%s', $base['scheme'], $base['host'], $location);
    }
}
