.PHONY: all clean install uninstall clean.docker dev test

all: docker

clean: clean.vendor

dev: composer-dev

test: dev
	vendor/bin/phpunit --coverage-html out

clean.docker:
	docker ps -aq | xargs docker rm -f

clean.dockeri:
	docker images -q | xargs docker rmi

clean.vendor:
	rm -rf vendor composer.lock

composer-prod: clean.vendor
	composer install --no-dev

composer-dev:
	composer install

phar: composer-prod
	phar-composer build

docker: phar
	docker build -t photo_sorter .
