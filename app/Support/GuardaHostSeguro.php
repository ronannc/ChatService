<?php

namespace App\Support;

use RuntimeException;

/**
 * Proteção contra SSRF (§3.3 do contrato do token do cliente) na busca do
 * JWKS: resolve o host para um IP e recusa endereço privado, link-local ou
 * reservado — incluindo o endpoint de metadados de cloud
 * (`169.254.169.254`) — antes de qualquer conexão, na inicial e em cada
 * redirect.
 *
 * A resolução de DNS é injetável para que os testes não dependam de rede
 * real nem de nomes de host reais: por padrão usa `gethostbyname`.
 */
class GuardaHostSeguro
{
    /**
     * @param  (callable(string): array<int, string>)|null  $resolvedor  Recebe o host e devolve os IPs resolvidos.
     */
    public function __construct(private $resolvedor = null) {}

    /**
     * Devolve o IP público validado para o host, ou lança se o host não
     * resolver para nenhum IP utilizável (todos privados/reservados).
     */
    public function ipPublicoDoHost(string $host): string
    {
        $ip = filter_var($host, FILTER_VALIDATE_IP) !== false
            ? $host
            : $this->resolverPrimeiroIp($host);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new RuntimeException("IP resolvido para \"{$host}\" é privado, link-local ou reservado.");
        }

        return $ip;
    }

    private function resolverPrimeiroIp(string $host): string
    {
        $resolvedor = $this->resolvedor ?? function (string $host): array {
            $ip = gethostbyname($host);

            return $ip === $host ? [] : [$ip];
        };

        $ips = ($resolvedor)($host);

        if (empty($ips)) {
            throw new RuntimeException("Não foi possível resolver o host \"{$host}\".");
        }

        return $ips[0];
    }
}
