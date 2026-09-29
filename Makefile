.PHONY: init up down shell test worker migrate

init:
	docker compose up -d --build
	@echo "Waiting for MySQL to be ready..."
	@until docker compose exec -T database mysqladmin ping -uroot -p'!ChangeMeRoot!' --silent >/dev/null 2>&1; do sleep 1; done
	docker compose exec -T php composer install
	docker compose exec -T php php bin/console doctrine:database:create --if-not-exists
	docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec -T database mysql -uroot -p'!ChangeMeRoot!' -e "CREATE DATABASE IF NOT EXISTS viterbit_test CHARACTER SET utf8mb4; GRANT ALL PRIVILEGES ON viterbit_test.* TO 'app'@'%'; FLUSH PRIVILEGES;"
	docker compose exec -T -e APP_ENV=test php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "Ready: http://localhost:8080"

up:
	docker compose up -d

down:
	docker compose down

shell:
	docker compose exec php sh

migrate:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

test:
	docker compose exec php vendor/bin/phpunit

worker:
	docker compose exec php php bin/console messenger:consume async -vv
