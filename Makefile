install: composer-install
	@cp Cache.ini.dist Cache.ini

install-dev: composer-install-dev dev-configure

composer-install:
	@test ! -f vendor/autoload.php && composer install --no-dev || true

composer-install-dev:
	@test ! -d vendor/phpunit/phpunit && composer install || true

composer-update:
	@composer update --no-dev

composer-update-dev:
	@composer update

dev-doc: composer-install-dev
	@test -f doc/API/search.html && rm -Rf doc/API || true
	@php vendor/ceus-media/doc-creator/doc.php --config-file=doc.xml

dev-test: composer-install-dev
	@vendor/bin/phpunit -v || true

dev-test-syntax:
	@find src -type f -print0 | xargs -0 -n1 xargs php -l
	@find test -type f -print0 | xargs -0 -n1 xargs php -l

dev-phpstan: composer-install-dev
	@vendor/bin/phpstan analyse --configuration phpstan.neon --xdebug || true

dev-phpstan-clear-cache: composer-install-dev
	@vendor/bin/phpstan clear-result-cache

dev-phpstan-save-baseline: composer-install-dev composer-update-dev
	@vendor/bin/phpstan analyse --configuration phpstan.neon --generate-baseline phpstan-baseline.neon || true
