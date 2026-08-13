<?php

use App\Services\Broadcasting\AutorizarCanalChamadoService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Canal privado de um chamado (`private-chamado.{chamadoId}`, CHAT-006).
 *
 * A opção `guards` é essencial: sem ela, `Broadcaster::retrieveUser()` usa o
 * guard default (`web`, sempre null pros nossos dois mecanismos de auth) e o
 * framework responde 403 antes mesmo de chamar este callback — ver
 * App\Services\Broadcasting\AutorizarCanalChamadoService e
 * App\Services\Auth\ClienteAutenticadoBroadcast para o porquê disso existir.
 *
 * `$principal` é o que o primeiro guard da lista resolver: um
 * App\Models\Atendente (via `sanctum`) ou um ClienteAutenticadoBroadcast
 * (via `cliente-broadcast`). A regra de autorização em si — comparar
 * sistema_id/cliente_ref ou sistema_id/sistemasPermitidos — vive inteira no
 * Service, não aqui.
 */
Broadcast::channel('chamado.{chamadoId}', function ($principal, string $chamadoId) {
    return app(AutorizarCanalChamadoService::class)->handle($principal, $chamadoId);
}, ['guards' => ['sanctum', 'cliente-broadcast']]);
