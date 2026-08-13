# Canal privado de um chamado (broadcasting) — CHAT-006

Este documento descreve como um cliente do chat (atendente ou cliente final) se autoriza a assinar o canal privado de um chamado. Não repete o contrato do token — ver [token-cliente.md](token-cliente.md) para isso.

## 1. Canal

Nome do canal, do ponto de vista do cliente Echo: `private-chamado.{chamado_id}`.

```js
Echo.private(`chamado.${chamadoId}`)
    .listen('MensagemEnviada', (e) => { ... });
```

## 2. Endpoint de autorização

`POST /api/broadcasting/auth` — **não** é o path default do Laravel (`/broadcasting/auth`); configure o cliente Echo (`authEndpoint`) para apontar para `/api/broadcasting/auth`.

O corpo da requisição segue o protocolo padrão do Reverb/Pusher: `channel_name` e `socket_id`, enviados automaticamente pela biblioteca do Echo — nada específico do chat service aqui.

## 3. Autenticação aceita

O endpoint aceita **qualquer um** dos dois mecanismos de auth do chat service, no mesmo header `Authorization: Bearer <token>`:

- **Atendente**: token Sanctum (o mesmo usado no restante da API do atendente).
- **Cliente final**: o JWT assinado descrito em [token-cliente.md](token-cliente.md).

Não há um terceiro caminho: uma requisição sem um Bearer token válido em nenhum dos dois formatos recebe `403`.

## 4. Regra de autorização

A resposta é sempre um booleano de autorização (sem payload adicional) — quem decide é `App\Services\Broadcasting\AutorizarCanalChamadoService`:

- **Cliente final**: autorizado apenas se `chamado.sistema_id === iss` **e** `chamado.cliente_ref === sub` do token. A identidade do cliente é sempre o par `(iss, sub)`, nunca um dos dois isolado.
- **Atendente**: autorizado se `chamado.sistema_id` está entre os sistemas permitidos para aquele atendente (o próprio sistema-base mais os concedidos via `atendente_sistema`) — um atendente pode ter permissão em vários sistemas ao mesmo tempo.

## 5. Renovação de token

**O chat service não tem nenhuma lógica de renovação.** O token do cliente final tem TTL curto por desenho (recomendado 10 minutos, teto de 15 — ver §2.5 de token-cliente.md). Isso vale também para a assinatura de canal: se o token expirar enquanto a conexão WebSocket segue aberta, uma nova tentativa de autorização de canal (reconexão do Echo, ou renovação periódica) vai falhar com o token vencido.

**A responsabilidade de renovar é inteiramente do lado do cliente Echo**: o sistema integrado deve:

1. Emitir um novo token antes do atual expirar (o sistema de origem já sabe quando emitiu o token e qual o TTL).
2. Atualizar o Bearer token usado pelo Echo/Pusher-js (normalmente via a opção `auth.headers.Authorization` do client Echo, ou reconstruindo a conexão).
3. Reautorizar o(s) canal(is) já assinados com o token novo — o Echo faz isso automaticamente ao reconectar, mas não renova sozinho um token que ainda não expirou; é o sistema integrado que decide quando trocar.

Não existe (e não está previsto) um endpoint do chat service para "renovar" um token — o sistema integrado sempre emite um token novo do zero, do mesmo jeito que emitiu o primeiro.
