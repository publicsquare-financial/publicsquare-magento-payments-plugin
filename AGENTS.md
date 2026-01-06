# AGENTS.md

This file provides guidance for agentic coding assistants (like opencode) working in the PublicSquare Magento Payments Plugin repository. It includes essential commands, code style guidelines, and best practices to maintain consistency, quality, and efficiency. Follow these guidelines to ensure changes align with the project's standards and Magento 2 conventions.

## Build/Setup Commands

Use these commands to set up the development environment, install dependencies, and prepare the Magento site.

### Docker and Magento Setup
- **Full Setup with Keys**: `make setup PUBLICSQUARE_PUBLIC_KEY PUBLICSQUARE_SECRET_KEY` - Installs Magento, configures Docker, and sets up the payment plugin.
- **Docker Compose**: `make docker-compose` - Manages containers; supports both v1 (`docker-compose`) and v2 (`docker compose`) with custom configs.
- **Start Containers**: `make start` - Starts all project containers.
- **Stop Containers**: `make stop` - Stops project containers; `make stopall` stops all running containers.
- **Fix Permissions/Ownership**: `make fixperms` / `make fixowns` - Corrects filesystem permissions and ownership within containers.
- **Domain/SSL Setup**: `make setup-domain DOMAIN` / `make setup-ssl DOMAIN` - Configures domain and generates SSL certificates.
- **Composer Auth**: `make setup-composer-auth` - Sets up authentication for Composer dependencies.
- **Verify Installation**: `make verify` - Downloads latest Magento, installs plugins, and sets up custom domain.

### Dependencies
- **Install Composer Deps**: `composer install --prefer-dist --no-progress --no-interaction --no-suggest` - Installs PHP dependencies.
- **Magento CLI**: `make magento COMMAND` - Runs Magento CLI commands (e.g., `make magento setup:upgrade`).
- **Cache Clean**: `make cache-clean` - Accesses cache-clean CLI for clearing caches.

## Testing Commands

The project uses PHPUnit for unit tests and Codeception for acceptance/integration tests. Run tests locally before committing.

### Unit Tests
- **Run All Unit Tests**: `make unit-test` - Executes PHPUnit on `tests/unit/` using `tests/unit/phpunit.xml`.
- **Verbose Output**: `make unit-test-verbose` - Runs with `--testdox` for readable test output.
- **Single Test**: `php vendor/bin/phpunit -c tests/unit/phpunit.xml --filter TestClass::testMethod` - Runs a specific test method.
- **Coverage**: Add `--coverage-html tests/_output/coverage` for HTML coverage reports.

### Integration/Acceptance Tests
- **Run All Integration Tests**: `make it-test` - Executes Codeception on `tests/Acceptance/` using `codeception.yml`.
- **Single Test**: `php vendor/bin/codecept run tests/Acceptance/TestFile.php:TestMethod` - Runs a specific acceptance test.
- **With Verbose/Debug**: `php vendor/bin/codecept run -v` or `php vendor/bin/codecept run --debug`.
- **Setup for Integration Tests**: `make it-complete-build` - Builds full integration environment with sample data.
- **Reset/Up/Down**: `make it-reset` / `make it-up` / `make it-down` - Manages integration test environment.
- **Sample Data**: `make it-sample-data` - Installs sample data for testing.
- **Verify Integration**: `make it-verify` - Verifies integration setup.

### CI/CD
GitHub Actions (`.github/workflows/test.yml`) automates:
- Docker build with PHP 8.3.
- Composer install.
- Selenium setup for browser tests.
- Runs `php vendor/bin/codecept run -f` for full acceptance tests.
- Uploads test artifacts on failure.

Always run `make unit-test` and `make it-test` locally before pushing.

## Linting and Code Quality

No linting tools are currently configured in the root project. To improve code quality, add these tools. Install via Composer dev dependencies, create configs, and add Makefile targets.

### Recommended Tools
- **PHPStan (Static Analysis)**: Detects bugs and enforces types.
  - Install: `composer require --dev phpstan/phpstan`
  - Config: Create `phpstan.neon` with baseline and level (start at 5 for Magento).
  - Run: `php vendor/bin/phpstan analyse PublicSquare/` (add to Makefile: `lint-phpstan: php vendor/bin/phpstan analyse`).
- **PHP CodeSniffer (PHPCS) for Style**: Enforces PSR-12 and custom rules.
  - Install: `composer require --dev squizlabs/php_codesniffer`
  - Config: Create `phpcs.xml` with `<rule ref="PSR12"/>` and exclude vendor/.
  - Run: `php vendor/bin/phpcs PublicSquare/` (add to Makefile: `lint-phpcs: php vendor/bin/phpcs PublicSquare/`).
  - Fix: `php vendor/bin/phpcbf PublicSquare/` for auto-fixes.
- **PHPMD (Mess Detector)**: Finds code smells.
  - Install: `composer require --dev phpmd/phpmd`
  - Config: Create `phpmd.xml` with rulesets (e.g., codesize, unusedcode).
  - Run: `php vendor/bin/phpmd PublicSquare/ text phpmd.xml` (add to Makefile: `lint-phpmd: php vendor/bin/phpmd PublicSquare/ text phpmd.xml`).
- **Combined Linting**: Add `make lint: make lint-phpstan && make lint-phpcs && make lint-phpmd` to run all.

Integrate into CI: Add lint steps to `.github/workflows/test.yml` before tests. Use pre-commit hooks if needed.

## Code Style Guidelines

Follow PSR-12 for PHP standards. The codebase is mostly consistent; adhere to these patterns for new code.

### File Structure and Headers
- Start with `<?php` on line 1.
- Use full docblocks for classes: `@category`, `@package`, `@author`, `@copyright`, `@license`, `@link` (OSL 3.0).
- Namespace immediately after.
- No closing `?>` tags (PSR-12).

### Imports and Use Statements
- Group after namespace; separate with blank lines.
- Fully qualified names (e.g., `use Magento\Framework\UrlInterface;`).
- Use aliases for clarity (e.g., `use PublicSquare\Payments\Helper\Config as GatewayConfig;`).
- Order: Magento core, then custom; not strictly alphabetical but logical.

### Formatting and Layout
- **Indentation**: 4 spaces (no tabs).
- **Line Length**: No strict limit; aim for readability (100-120 chars).
- **Spacing**: Around operators, after commas, before/after control structures.
- **Curly Braces**: Opening on same line (e.g., `public function __construct(`).
- **Blank Lines**: Separate methods, classes, logical blocks.
- **Semicolons**: Always at statement ends.

### Types, Type Hints, and Annotations
- **Parameters/Returns**: Use type hints (e.g., `public function getConfig(string $scopeType): bool`).
- **Nullable**: `?Json $serializer = null`.
- **Mixed**: For flexible data (e.g., `mixed $data`).
- **Properties**: PHPDoc `@var` (e.g., `/** @var Context */ protected $context;`).
- **PHPDoc**: For methods: `@param Type $name Description`, `@return Type Description`. Multiline for details.

### Naming Conventions
- **Classes**: PascalCase (e.g., `ConfigProvider`).
- **Methods/Properties**: camelCase (e.g., `getConfig`, `$checkoutSession`).
- **Constants**: UPPER_SNAKE_CASE (e.g., `CODE`, `VAULT_CODE`).
- **Variables**: camelCase (e.g., `$additionalData`).
- **Files**: Match class name (e.g., `ConfigProvider.php`).

### Error Handling and Exceptions
- Use try-catch for critical ops (e.g., `try { ... } catch (\Exception $e) { return false; }`).
- Throw custom exceptions (e.g., `ApiRejectedResponseException`).
- Log with `$this->logger->error/info` (include context arrays).
- Use `LocalizedException` with translated messages: `__( "The payment could not be completed..." )`.

### Constructors and DI
- Dependency injection: Explicit assignments (e.g., `$this->checkoutSession = $checkoutSession;`).
- No constructor property promotion.

### Other Patterns
- Access modifiers: Always explicit (`public`, `private`, `protected`).
- Static methods: Sparingly (e.g., utility functions).
- Translated strings: For user-facing text (`__(...)`).
- Avoid inconsistencies: Match existing file styles.

### Examples
```php
<?php
/**
 * @category PublicSquare
 * @package  PublicSquare_Payments
 * @author   PublicSquare <support@publicsquare.com>
 * @copyright Copyright (c) 2024 PublicSquare
 * @license  https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.publicsquare.com/
 */

namespace PublicSquare\Payments\Api;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use PublicSquare\Payments\Helper\Config;

class PaymentCreate
{
    /** @var Session */
    protected $checkoutSession;

    public function __construct(Session $checkoutSession, Config $config)
    {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    public function executeAuthorize(array $data): bool
    {
        try {
            // Process payment
            return true;
        } catch (\Exception $e) {
            $this->config->getLogger()->error("PSQ Payment failed", ['error' => $e->getMessage()]);
            throw new LocalizedException(__('The payment could not be completed.'));
        }
    }
}
```

## Cursor/Copilot Rules

No .cursorrules or .cursor/rules/ files found. No .github/copilot-instructions.md. Add if needed for IDE-specific guidance.

## Best Practices

- **Magento Conventions**: Follow Magento 2 docs; use DI, avoid direct model instantiation.
- **Security**: Never log secrets/keys; use Magento's encryption for sensitive data.
- **Consistency**: Match existing patterns; review PRs for style.
- **Testing**: Write tests for new features; ensure 100% coverage for critical paths.
- **Commits**: Use conventional commits (e.g., "feat: add payment method"); run tests/lints pre-commit.
- **Performance**: Avoid N+1 queries; use caching where appropriate.
- **Documentation**: Update PHPDoc for public APIs; comment complex logic.

For feedback or updates, see https://github.com/sst/opencode/issues.</content>
<parameter name="filePath">/Users/btilford/Projects/publicsq/payments/publicsquare-magento-payments-plugin/AGENTS.md