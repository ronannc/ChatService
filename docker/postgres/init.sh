#!/bin/bash
set -e

# Roda como o superuser de bootstrap (POSTGRES_USER/DB_ROOT_*). Cria o role
# não-superuser que a aplicação de fato usa (DB_USERNAME/DB_PASSWORD) — é
# esse role que precisa ser não-superuser para Row Level Security ter efeito
# (superuser sempre ignora RLS, mesmo com FORCE). Ele é dono das tabelas que
# as migrations criam, então FORCE ROW LEVEL SECURITY se aplica normalmente.
#
# O delimitador do heredoc é aspeado ('EOSQL') para o shell não expandir nada
# no corpo — DB_USERNAME/DB_PASSWORD chegam ao SQL só via variável do próprio
# psql (-v), e dentro do bloco DO são aplicados com format(%I/%L), que faz o
# escaping correto. Evita interpolar credenciais direto num literal SQL/shell.
psql -v ON_ERROR_STOP=1 \
    --username "$POSTGRES_USER" \
    --dbname "$POSTGRES_DB" \
    -v app_username="$DB_USERNAME" \
    -v app_password="$DB_PASSWORD" <<-'EOSQL'
    CREATE EXTENSION IF NOT EXISTS vector;

    DO $$
    DECLARE
        app_username text := :'app_username';
        app_password text := :'app_password';
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = app_username) THEN
            EXECUTE format('CREATE ROLE %I LOGIN PASSWORD %L NOSUPERUSER', app_username, app_password);
        END IF;

        EXECUTE format('GRANT CONNECT ON DATABASE %I TO %I', current_database(), app_username);
        EXECUTE format('GRANT CREATE, USAGE ON SCHEMA public TO %I', app_username);
    END
    $$;
EOSQL
