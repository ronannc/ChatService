DC := docker compose
APP := app

.DEFAULT_GOAL := help

.PHONY: help up down down-v restart build ps logs logs-app logs-worker logs-reverb \
	exec-app shell bash artisan migrate migrate-fresh seed fresh key test pint \
	tinker horizon-status reverb-restart

help: ## Lista os comandos disponíveis
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

up: ## Sobe o ambiente (build + containers em background)
	$(DC) up -d --build

down: ## Para os containers (mantém os volumes)
	$(DC) down

down-v: ## Para os containers e apaga os volumes (banco, redis, minio)
	$(DC) down -v

restart: down up ## Reinicia o ambiente por completo

build: ## Rebuild das imagens sem subir os containers
	$(DC) build

ps: ## Status dos containers
	$(DC) ps

logs: ## Logs de todos os serviços (use s=<serviço> para um só, ex: make logs s=app)
	$(DC) logs -f $(s)

logs-app: ## Logs só do app
	$(DC) logs -f app

logs-worker: ## Logs só do worker (Horizon)
	$(DC) logs -f worker

logs-reverb: ## Logs só do reverb (WebSocket)
	$(DC) logs -f reverb

exec-app: ## Shell dentro do container app (alias: shell, bash)
	$(DC) exec $(APP) sh

shell: exec-app ## Alias de exec-app

bash: exec-app ## Alias de exec-app

artisan: ## Roda um comando artisan, ex: make artisan cmd="route:list"
	$(DC) exec $(APP) php artisan $(cmd)

migrate: ## Roda as migrations pendentes
	$(DC) exec $(APP) php artisan migrate

migrate-fresh: ## Recria o banco do zero (apaga tudo, roda migrations)
	$(DC) exec $(APP) php artisan migrate:fresh

seed: ## Roda os seeders
	$(DC) exec $(APP) php artisan db:seed

fresh: ## migrate:fresh + seed
	$(DC) exec $(APP) php artisan migrate:fresh --seed

key: ## Gera uma nova APP_KEY
	$(DC) exec $(APP) php artisan key:generate

test: ## Roda a suíte de testes (Pest) dentro do container
	$(DC) exec $(APP) php artisan test --compact

pint: ## Roda o Pint (formatação) dentro do container
	$(DC) exec $(APP) vendor/bin/pint

tinker: ## Abre o Tinker dentro do container
	$(DC) exec $(APP) php artisan tinker

horizon-status: ## Mostra o status do Horizon
	$(DC) exec $(APP) php artisan horizon:status

reverb-restart: ## Reinicia só o container do Reverb
	$(DC) restart reverb
