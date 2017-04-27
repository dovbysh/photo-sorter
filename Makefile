.PHONY: all clean install uninstall clean.docker

all: docker

clean: clean.vendor

clean.docker:
	docker ps -aq | xargs docker rm -f

clean.dockeri:
	docker images -q | xargs docker rmi

clean.vendor:
	rm -rf vendor composer.lock

composer-prod: clean.vendor
	composer install --no-dev

phar: composer-prod
	phar-composer build

docker: phar
	docker build -t photo_sorter .
