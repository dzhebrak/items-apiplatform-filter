export APP_NAME=items-apiplatform-filter
export PHP_CONTAINER_NAME=${APP_NAME}_php_1
export DB_CONTAINER_NAME=${APP_NAME}_mariadb_1

restart:down up

up:
	docker-compose -p $$APP_NAME up -d

down:
	docker-compose -p $$APP_NAME stop

build:
	docker-compose -p $$APP_NAME build

remove: remove_volume
	docker-compose -p $$APP_NAME rm --force

logs:
	docker-compose -p $$APP_NAME logs --follow

bash:
	docker exec -it $$PHP_CONTAINER_NAME /bin/bash

db:
	docker exec -it $$DB_CONTAINER_NAME mysql

test:
	docker exec -it $$PHP_CONTAINER_NAME php bin/console --env=test doctrine:schema:update --force --quiet && \
    docker exec -it $$PHP_CONTAINER_NAME php -d memory_limit=1G bin/console --env=test doctrine:fixtures:load --quiet && \
	docker exec -it $$PHP_CONTAINER_NAME php bin/phpunit
