<?php

namespace App\Services\Auth;

use App\Exceptions\TokenClienteInvalidoException;
use App\Models\Sistema;
use App\Support\ContratoTokenCliente;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cache, refetch e busca do JWKS de um sistema (§3.2 do contrato do token do
 * cliente). Validar um token é o caminho mais quente do produto — buscar o
 * JWKS a cada request adicionaria 50-300ms de RTT contra um servidor de
 * terceiro em toda mensagem trocada.
 *
 * Regras aplicadas aqui:
 * - TTL positivo de `TTL_CACHE_JWKS_SEGUNDOS` para o JWKS já buscado;
 * - cache negativo de `TTL_CACHE_NEGATIVO_JWKS_SEGUNDOS` para não martelar
 *   um emissor fora do ar a cada request;
 * - no máximo um refetch por `kid` desconhecido a cada
 *   `INTERVALO_MINIMO_REFETCH_JWKS_SEGUNDOS`, por sistema — sem isso, uma
 *   rotação legítima só funcionaria depois do TTL expirar, ou um atacante
 *   manda `kid` aleatório e transforma cada request nossa em uma request
 *   HTTP contra o servidor do integrador (amplificação);
 * - lock para que requests concorrentes não disparem buscas simultâneas
 *   (stampede) tanto na primeira busca quanto no refetch.
 */
class RepositorioJwks
{
    private const TEMPO_ESPERA_LOCK_SEGUNDOS = 5;

    private const TEMPO_MAXIMO_LOCK_SEGUNDOS = 10;

    public function __construct(private readonly BuscarJwksSegurancaService $buscador) {}

    /**
     * Devolve o JWKS do sistema, garantindo que o `kid` informado esteja
     * presente sempre que um refetch legítimo (dentro da janela permitida)
     * puder trazê-lo.
     *
     * @return array{keys: array<int, array<string, mixed>>}
     */
    public function obterParaKid(Sistema $sistema, string $kid): array
    {
        if (Cache::has($this->chaveNegativa($sistema->codigo))) {
            $this->falhar($sistema->codigo, 'cache_negativo_ativo');
        }

        $jwks = Cache::get($this->chaveCache($sistema->codigo));

        if ($jwks === null) {
            return $this->buscarComLock($sistema);
        }

        if (! $this->contemKid($jwks, $kid)) {
            return $this->refetchThrottled($sistema) ?? $jwks;
        }

        return $jwks;
    }

    /**
     * @return array{keys: array<int, array<string, mixed>>}|null
     */
    private function refetchThrottled(Sistema $sistema): ?array
    {
        $chaveRefetch = $this->chaveRefetch($sistema->codigo);

        if (Cache::has($chaveRefetch)) {
            return Cache::get($this->chaveCache($sistema->codigo));
        }

        $lock = Cache::lock($this->chaveLock($sistema->codigo), self::TEMPO_MAXIMO_LOCK_SEGUNDOS);

        try {
            return $lock->block(self::TEMPO_ESPERA_LOCK_SEGUNDOS, function () use ($sistema, $chaveRefetch): array {
                if (Cache::has($chaveRefetch)) {
                    return Cache::get($this->chaveCache($sistema->codigo));
                }

                // Marca a janela de throttle antes de buscar: se a busca
                // falhar, ainda não queremos permitir um novo refetch antes
                // do intervalo mínimo.
                Cache::put($chaveRefetch, true, ContratoTokenCliente::INTERVALO_MINIMO_REFETCH_JWKS_SEGUNDOS);

                return $this->buscar($sistema);
            });
        } catch (LockTimeoutException) {
            return Cache::get($this->chaveCache($sistema->codigo));
        }
    }

    /**
     * @return array{keys: array<int, array<string, mixed>>}
     */
    private function buscarComLock(Sistema $sistema): array
    {
        $lock = Cache::lock($this->chaveLock($sistema->codigo), self::TEMPO_MAXIMO_LOCK_SEGUNDOS);

        try {
            return $lock->block(self::TEMPO_ESPERA_LOCK_SEGUNDOS, function () use ($sistema): array {
                $existente = Cache::get($this->chaveCache($sistema->codigo));

                return $existente ?? $this->buscar($sistema);
            });
        } catch (LockTimeoutException) {
            $this->falhar($sistema->codigo, 'lock_jwks_nao_liberado');
        }
    }

    /**
     * @return array{keys: array<int, array<string, mixed>>}
     */
    private function buscar(Sistema $sistema): array
    {
        try {
            $jwks = $this->buscador->buscar($sistema->jwks_url);
        } catch (Throwable $e) {
            Cache::put(
                $this->chaveNegativa($sistema->codigo),
                true,
                ContratoTokenCliente::TTL_CACHE_NEGATIVO_JWKS_SEGUNDOS,
            );

            $this->falhar($sistema->codigo, 'jwks_inacessivel: '.$e->getMessage());
        }

        Cache::put($this->chaveCache($sistema->codigo), $jwks, ContratoTokenCliente::TTL_CACHE_JWKS_SEGUNDOS);

        return $jwks;
    }

    /**
     * @param  array{keys: array<int, array<string, mixed>>}  $jwks
     */
    private function contemKid(array $jwks, string $kid): bool
    {
        foreach ($jwks['keys'] as $chave) {
            if (($chave['kid'] ?? null) === $kid) {
                return true;
            }
        }

        return false;
    }

    private function falhar(string $codigo, string $motivo): never
    {
        Log::warning('token_cliente.jwks_inacessivel', ['sistema' => $codigo, 'motivo' => $motivo]);

        throw new TokenClienteInvalidoException('jwks_inacessivel');
    }

    private function chaveCache(string $codigo): string
    {
        return "jwks:{$codigo}";
    }

    private function chaveNegativa(string $codigo): string
    {
        return "jwks:negativo:{$codigo}";
    }

    private function chaveRefetch(string $codigo): string
    {
        return "jwks:refetch:{$codigo}";
    }

    private function chaveLock(string $codigo): string
    {
        return "jwks:lock:{$codigo}";
    }
}
