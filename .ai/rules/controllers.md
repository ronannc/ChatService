---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Um Service por ação, controller magro
Cada ação de escrita (store, update, etc.) tem sua própria classe em `app/Services/{Feature}/{Acao}{Feature}Service.php` (ex.: `app/Services/Sistema/StoreSistemaService.php`, `UpdateSistemaService.php`) com um único método público `handle()` contendo a regra de negócio.

O controller só recebe o FormRequest (já validado) e a autenticação (via middleware), injeta o Service correspondente no método da action, e delega — não deve conter lógica de negócio. Repetir esse padrão em toda feature nova do domínio (chamados, mensagens, atendentes, etc.), não só em Sistema.
