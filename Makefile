vendor/autoload.php:
	composer install

sniff: vendor/autoload.php
	vendor/bin/phpcs --standard=PSR2 src tests --colors

fix: vendor/autoload.php
	vendor/bin/phpcbf --standard=PSR2 src tests

phpstan: vendor/autoload.php
	vendor/bin/phpstan analyse

test: vendor/autoload.php
	vendor/bin/phpunit

test-coverage: vendor/autoload.php
	vendor/bin/phpunit --coverage-html=coverage-report --coverage-text

ci: sniff phpstan test

.PHONY: sniff fix phpstan test test-coverage ci
