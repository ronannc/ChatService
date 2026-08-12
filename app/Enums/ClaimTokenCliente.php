<?php

namespace App\Enums;

/**
 * Claims reconhecidas no token assinado que cada sistema integrado emite
 * para o chat service. Fonte única dos nomes de claim — o documento
 * normativo em docs/contratos/token-cliente.md descreve o mesmo contrato
 * em prosa para os times externos.
 */
enum ClaimTokenCliente: string
{
    case Iss = 'iss';
    case Sub = 'sub';
    case Aud = 'aud';
    case Scope = 'scope';
    case Exp = 'exp';
    case Iat = 'iat';
    case Role = 'role';
    case ClienteUnificadoRef = 'cliente_unificado_ref';
    case Jti = 'jti';

    /**
     * Tipo JSON exigido pelo contrato. Uma claim presente com tipo
     * diferente invalida o token — `"exp": "1786500600"` como string é o
     * erro mais comum de emissor e não pode ser aceito por coerção.
     */
    public function tipo(): string
    {
        return match ($this) {
            self::Exp, self::Iat => 'int',
            default => 'string',
        };
    }

    /**
     * Claims sem as quais o token é inválido. A ausência de qualquer uma
     * delas rejeita o token independentemente de a assinatura conferir.
     *
     * @return array<int, self>
     */
    public static function obrigatorias(): array
    {
        return [self::Iss, self::Sub, self::Aud, self::Scope, self::Exp, self::Iat];
    }

    /**
     * @return array<int, self>
     */
    public static function opcionais(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $claim): bool => ! $claim->obrigatoria(),
        ));
    }

    public function obrigatoria(): bool
    {
        return in_array($this, self::obrigatorias(), true);
    }
}
