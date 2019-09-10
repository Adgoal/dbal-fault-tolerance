.PHONY: style
style: ## executes php analizers
	./vendor/bin/phpstan analyse -l 6 -c phpstan.neon src

.PHONY: lint
lint: ## checks syntax of PHP files
	./vendor/bin/parallel-lint ./ --exclude vendor --exclude bin/.phpunit

coding-standards: ## Run check and validate code standards tests
	vendor/bin/ecs check src tests
	vendor/bin/phpmd src/ text phpmd.xml

coding-standards-fixer: ## Run code standards fixer
	vendor/bin/ecs check src tests --fix