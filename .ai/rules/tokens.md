---
paths:
  - 'app/Enums/ClaimTokenCliente.php'
  - 'app/Support/ContratoTokenCliente.php'
  - 'app/Services/Auth/**'
  - 'app/Http/Middleware/**'
  - 'tests/Support/GeradorTokenTeste.php'
---

# Token do cliente (JWT/JWKS)

O contrato normativo está em [docs/contratos/token-cliente.md](../../docs/contratos/token-cliente.md). Leia antes de mexer em validação de token. Os valores vivem em `App\Enums\ClaimTokenCliente` e `App\Support\ContratoTokenCliente` — leia de lá em vez de repetir literais, e trate qualquer mudança neles como mudança de contrato publicado (atualizar o documento e avisar os times emissores).

## Três invariantes que se perde de vista

**A URL do JWKS vem de `sistemas.jwks_url`, nunca de config.** Cada sistema integrado publica e gira as próprias chaves; uma `JWKS_URL` no `.env` ou no `config/chat.php` quebraria o multi-sistema inteiro. Se um teste precisar de um JWKS, ele vem do registro do sistema.

**`alg` é fixo em RS256, comparado contra a constante — nunca lido do header para escolher o verificador.** Aceitar o `alg` do token permite forjar assinatura com `none` ou com HMAC usando a chave pública do JWKS (que é pública) como segredo. Header com `alg` fora de RS256 é rejeitado antes de qualquer tentativa de verificação.

**`iss` sem sistema cadastrado, ou com sistema `inativo`, é rejeitado antes de checar a assinatura.** Não é otimização: sem sistema ativo não existe JWKS a consultar, e desativar um sistema precisa derrubar o acesso na hora, sem esperar os tokens já emitidos expirarem. Para não pagar um `SELECT` por request no hot path sem quebrar essa promessa, o cadastro é cacheado com **invalidação explícita** (`Cache::forget` nos endpoints admin que alteram `status`/`jwks_url`), nunca com TTL.

## Duas armadilhas de tempo que só funcionam juntas

Teto de `exp - iat` **e** `iat` não estar no futuro. Verificar só o primeiro não protege nada: `iat = agora + 1 ano` com `exp = iat + 600` mantém a diferença dentro do limite e dá um token válido por um ano.

## `role` falha fechada

Só `cliente` é aceito hoje (`ContratoTokenCliente::rolesAceitos()`). `atendente` está no vocabulário mas rejeita o token até CHAT-005B existir — é claim de privilégio escolhida pelo emissor. Em CHAT-005B, o papel terá de casar com o vínculo real em `atendente_sistema`; a claim indica qual verificação fazer, não é a verificação.

## `cliente_unificado_ref` não fura o isolamento

A claim correlaciona o mesmo cliente entre sistemas, mas **não amplia o escopo de leitura**: o token segue limitado ao `sistema_id` do seu `iss`. A visão consolidada é de atendente/admin (CHAT-021). O valor é escolhido pelo emissor — se concedesse leitura cruzada, um sistema comprometido leria dados de outro só emitindo a referência alheia.

## O JWKS é URL de terceiro no caminho quente

Cache com TTL, cache negativo, refetch em `kid` desconhecido com rate limit por sistema e lock contra stampede — sem isso, ou a rotação de chave quebra, ou um `kid` aleatório vira amplificação de request contra o servidor do integrador. A busca é superfície de SSRF: timeout curto, teto de bytes, teto de chaves e recusa de redirect para IP privado/link-local. Valores em `ContratoTokenCliente`, racional na §3.2/§3.3 do documento.

## Não confundir os três mecanismos de auth

Cliente final = JWT RS256 validado via JWKS do sistema de origem. Atendente interno = Sanctum. Endpoints administrativos = `CHAT_ADMIN_API_KEY`. São isolados; nenhum valida o outro.

## Chaves em tests/Fixtures/Token

São de teste, exclusivamente. O par é versionado de propósito (os tokens é que são gerados em runtime, porque `exp` é relativo ao agora). Nenhuma dessas chaves é aceita em ambiente real.
