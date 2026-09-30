.DEFAULT_GOAL := help

.PHONY: help init up down stop restart build ps logs logs-php logs-nginx logs-db logs-rabbitmq logs-worker \
        shell db-shell composer console cache-clear migrate migration-diff migration-status \
        test test-filter worker clean

## ---- Arranque y ciclo de vida ----

init: ## Construir imágenes, levantar todo, instalar dependencias y preparar BD (dev + test)
	docker compose up -d --build
	@echo "Waiting for MySQL to be ready..."
	@until docker compose exec -T database mysqladmin ping -uroot -p'!ChangeMeRoot!' --silent >/dev/null 2>&1; do sleep 1; done
	docker compose exec -T php composer install
	docker compose exec -T php php bin/console doctrine:database:create --if-not-exists
	docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec -T database mysql -uroot -p'!ChangeMeRoot!' -e "CREATE DATABASE IF NOT EXISTS viterbit_test CHARACTER SET utf8mb4; GRANT ALL PRIVILEGES ON viterbit_test.* TO 'app'@'%'; FLUSH PRIVILEGES;"
	docker compose exec -T -e APP_ENV=test php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "Ready: http://localhost:8080"

up: ## Levantar los contenedores ya construidos
	docker compose up -d

down: ## Parar y eliminar los contenedores
	docker compose down

stop: ## Parar los contenedores sin eliminarlos
	docker compose stop

restart: ## Reiniciar todos los contenedores
	docker compose restart

build: ## Reconstruir las imágenes (sin caché)
	docker compose build --no-cache

ps: ## Ver el estado de los contenedores
	docker compose ps

clean: ## Parar y eliminar contenedores, redes y el volumen de datos de MySQL
	docker compose down -v

## ---- Logs ----

logs: ## Ver logs de todos los servicios
	docker compose logs -f

logs-php: ## Ver logs del contenedor PHP-FPM
	docker compose logs -f php

logs-nginx: ## Ver logs de Nginx
	docker compose logs -f nginx

logs-db: ## Ver logs de MySQL
	docker compose logs -f database

logs-rabbitmq: ## Ver logs de RabbitMQ
	docker compose logs -f rabbitmq

logs-worker: worker ## Alias de `worker`

## ---- Shells y utilidades ----

shell: ## Abrir una shell dentro del contenedor PHP
	docker compose exec php sh

db-shell: ## Abrir un cliente MySQL dentro del contenedor de base de datos
	docker compose exec database mysql -uapp -p'!ChangeMe!' viterbit

composer: ## Ejecutar composer dentro del contenedor, ej: make composer ARGS="require foo/bar"
	docker compose exec php composer $(ARGS)

console: ## Ejecutar bin/console dentro del contenedor, ej: make console ARGS="cache:clear"
	docker compose exec php php bin/console $(ARGS)

cache-clear: ## Limpiar la cache de Symfony
	docker compose exec php php bin/console cache:clear

## ---- Base de datos / migraciones ----

migrate: ## Ejecutar las migraciones pendientes (BD de dev)
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

migration-diff: ## Generar una nueva migración a partir de los cambios de mapping
	docker compose exec php php bin/console doctrine:migrations:diff

migration-status: ## Ver el estado de las migraciones
	docker compose exec php php bin/console doctrine:migrations:status

## ---- Tests ----

test: ## Ejecutar toda la suite de tests
	docker compose exec php vendor/bin/phpunit

test-filter: ## Ejecutar tests filtrados por nombre, ej: make test-filter ARGS=EnrichApplication
	docker compose exec php vendor/bin/phpunit --filter $(ARGS)

## ---- Messenger ----

worker: ## Ver logs del worker de Messenger (enriquecimiento IA)
	docker compose logs -f messenger-worker

## ---- Ayuda ----

help: ## Mostrar esta ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
