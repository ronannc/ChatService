<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Adaptador `Authenticatable` em volta de `TokenClienteValidado`, usado
 * **exclusivamente** pelo guard `cliente-broadcast` (ver
 * `AppServiceProvider::boot()` e `config/auth.php`), que por sua vez só é
 * usado pela autorização de canal privado de broadcasting
 * (`routes/channels.php`).
 *
 * Não existe em lugar nenhum fora disso — a autenticação do cliente final
 * continua sendo, em todo o resto da aplicação, o mecanismo sem guard
 * documentado em `EnsureValidTokenCliente` (JWT validado por
 * `ValidarTokenClienteService`, resolvido via request attribute, não via
 * `Auth`). Este wrapper existe só porque o broadcaster do Laravel
 * (`Broadcaster::retrieveUser()`) exige que pelo menos um guard nomeado na
 * opção `guards` do canal resolva algo não nulo antes mesmo de chamar o
 * callback de autorização — a decisão de confiança em si continua 100% em
 * `ValidarTokenClienteService`; esta classe não valida nada, só empacota um
 * resultado já validado.
 */
final readonly class ClienteAutenticadoBroadcast implements Authenticatable
{
    public function __construct(public TokenClienteValidado $token) {}

    public function getAuthIdentifierName(): string
    {
        return 'sub';
    }

    public function getAuthIdentifier(): string
    {
        return "{$this->token->iss}:{$this->token->sub}";
    }

    public function getAuthPasswordName(): string
    {
        return '';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return '';
    }
}
