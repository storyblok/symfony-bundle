# vim: set tabstop=8 softtabstop=8 noexpandtab:
.PHONY: static-code-analysis
static-code-analysis:
	symfony php vendor/bin/phpstan analyse --configuration phpstan.neon.dist --no-progress --memory-limit=-1

.PHONY: static-code-analysis-baseline
static-code-analysis-baseline:
	symfony php vendor/bin/phpstan analyse --configuration phpstan.neon.dist --generate-baseline=phpstan-baseline.neon --no-progress

.PHONY: cs
cs:
	symfony php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose

.PHONY: tests
tests:
	symfony php vendor/bin/phpunit

.PHONY: coverage
coverage:
	symfony php vendor/bin/phpunit --coverage-html=.build/phpunit/

.PHONY: refactoring
refactoring:
	symfony php vendor/bin/rector process --config rector.php
