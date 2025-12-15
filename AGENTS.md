# AGENTS.md

## Build/Lint/Test Commands
- **Unit tests**: `make unit-test` or `./vendor/bin/phpunit -c tests/unit/phpunit.xml`
- **Verbose unit tests**: `make unit-test-verbose` or `./vendor/bin/phpunit -c tests/unit/phpunit.xml --testdox`
- **Single unit test**: `./vendor/bin/phpunit -c tests/unit/phpunit.xml --filter TestClass::testMethod`
- **Acceptance tests**: `make it-test` or `./vendor/bin/codecept run tests/Acceptance/`
- **No explicit lint command configured**

## Code Style Guidelines
- **File structure**: Start with `<?php`, followed by docblock, namespace, use statements, class
- **Docblocks**: Required for classes, methods, properties; use @var, @param, @return
- **Naming**: Classes PascalCase, methods camelCase, constants ALL_CAPS_UNDERSCORE
- **Type hints**: Use for all parameters and return types where possible
- **Formatting**: 4-space indentation, spaces around operators, consistent array formatting
- **Imports**: Group use statements alphabetically, no unused imports
- **Error handling**: Use try-catch blocks for exceptions; throw custom exceptions from Exception/ dir
- **Comments**: `//end methodName()` at method end; avoid inline comments unless necessary
- **Consistency**: Follow Magento conventions; avoid extra spaces in assignments