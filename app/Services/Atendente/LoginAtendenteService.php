<?php

namespace App\Services\Atendente;

use App\Enums\StatusAtendente;
use App\Models\Atendente;
use App\Support\SistemaContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginAtendenteService
{
    /**
     * Hash dummy usado quando o e-mail não existe, pra gastar o mesmo
     * tempo de um Hash::check() real — sem isso, a ausência da chamada
     * (short-circuit do `! $atendente`) cria um oráculo de timing que
     * revela se o e-mail existe, mesmo com mensagem/status idênticos.
     */
    private const HASH_DUMMY = '$2y$12$eImiTXuWVxfM37uY4JANjQZeq4E4ELk9AqhtHvmQBTvJXTfLzKB1u';

    /**
     * @param  array{email: string, senha: string}  $dados
     * @return array{atendente: Atendente, token: string}
     */
    public function handle(array $dados): array
    {
        $atendente = $this->buscarPorEmailBypassandoRls($dados['email']);

        $senhaValida = Hash::check($dados['senha'], $atendente?->senha ?? self::HASH_DUMMY);

        if (! $atendente || ! $senhaValida) {
            throw new AuthenticationException('Credenciais inválidas.');
        }

        if ($atendente->status !== StatusAtendente::Ativo) {
            throw new AuthenticationException('Atendente inativo.');
        }

        return [
            'atendente' => $atendente,
            'token' => $atendente->createToken('atendente-login')->plainTextToken,
        ];
    }

    /**
     * O login busca a linha pelo e-mail antes de saber qual sistema_id usar
     * como contexto — é o próprio login que estabelece esse contexto, não o
     * contrário. `withoutGlobalScopes()` só ignora o scope do Eloquent; a
     * RLS do Postgres continua ativa independente disso, então a busca
     * precisa da flag de sessão que a policy de `atendentes` reconhece
     * (ver migration allow_login_lookup_bypass_rls_em_atendentes).
     *
     * A flag é `SET LOCAL` (terceiro argumento `true` do set_config) dentro
     * de uma transaction — some sozinha ao fim da consulta, sem afetar mais
     * nenhuma query da request.
     */
    private function buscarPorEmailBypassandoRls(string $email): ?Atendente
    {
        return DB::transaction(function () use ($email) {
            DB::statement(
                'SELECT set_config(?, ?, true)',
                [SistemaContext::GUC_BYPASS_RESOLUCAO_ATENDENTE, 'true'],
            );

            return Atendente::withoutGlobalScopes()->where('email', $email)->first();
        });
    }
}
