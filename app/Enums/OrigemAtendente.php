<?php

namespace App\Enums;

/**
 * Distingue os dois mecanismos de autenticação de atendente (nunca se
 * confundem): `Interno` autentica via Sanctum com e-mail/senha (CHAT-005A);
 * `Externo` autentica via o mesmo JWT/JWKS do cliente final, com
 * `role=atendente`, e é provisionado just-in-time por
 * `ProvisionarAtendenteExternoService` (CHAT-005B) — sem e-mail/senha, tem
 * `sub_externo` no lugar.
 */
enum OrigemAtendente: string
{
    case Interno = 'interno';
    case Externo = 'externo';
}
