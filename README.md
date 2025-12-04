PublicSquare Financial
=====================

PublicSquare provides a software platform for retailers to access third-party providers for lease-to-own financing and other lending products based on a consumer's credit profile.

## INSTALLATION

### Manual Installation
* extract files from an archive

* deploy files into Magento2 folder `app/code/`

### Enabled Extension
* enable extension (in command line, see manual: `http://devdocs.magento.com/guides/v2.0/config-guide/cli/config-cli-subcommands.html`):
>`$> bin/magento module:enable PublicSquare_Payments`

* to make sure that the enabled module is properly registered, run 'setup:upgrade':
>`$> bin/magento setup:upgrade`

* [if needed] re-deploy static view files:
>`$> bin/magento setup:static-content:deploy`

### Docker Installation

If you need a local installation of Magento2, the cleanest way is to use Docker. [This repo](https://github.com/markshust/docker-magento?tab=readme-ov-file#automated-setup-new-project) provides an out of the box installation with just a few commands.

* Make sure docker engine is running

* One-liner to install Magento

```bash
$ curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup | bash -s -- magento.test 2.4.7 community
```

*Replace `2.4.7` with the version of Magento you want to install.*

*Replace `magento.test` with the local domain you want to use.*

* Open (https://magento.test)[https://magento.test] to confirm installation

```bash
$ open https://magento.test
```

* Seed sample data

```bash
$ bin/magento sampledata:deploy
$ bin/magento setup:upgrade
```

### Local environment using this repo (Docker + sample data)

This repository also includes a self-contained Docker setup and helper scripts to provision a full local environment (containers, Magento, this plugin, and sample data).

1. **Start / provision the IT stack (first time or after reset)**

   ```bash
   # Will bring up containers, install Magento, deploy static content,
   # configure PublicSquare keys, and install Magento sample data.
   make it-complete-build
   ```

   - If you don't pass any arguments, `it-install` (called under the hood) will **prompt for your PublicSquare PUBLIC and SECRET keys**.
   - In CI or when scripting, you can pass the keys explicitly:

   ```bash
   make it-complete-build <PUBLICSQUARE_PUBLIC_KEY> <PUBLICSQUARE_SECRET_KEY>
   ```

2. **Subsequent runs**

   - To restart the stack without reinstalling Magento:

     ```bash
     make it-down
     make it-up
     ```

   - To tear everything down and reset volumes:

     ```bash
     make it-reset
     ```

### Deploying plugin changes in the IT/local environment

When you change this plugin's code and want Magento to pick up the changes (DI, static content, etc.) inside the IT Docker stack, use:

```bash
make deploy
```

This runs `bin/deploy` inside the `web` container, which will:

- Run `composer install` inside the container
- Enable maintenance mode
- Clear generated code and preprocessed view files
- Run `bin/magento setup:upgrade`
- Recompile DI (`bin/magento setup:di:compile`)
- Redeploy static content (`bin/magento setup:static-content:deploy en_US -f`)
- Disable maintenance mode and flush caches

Use this after code changes to ensure Magento is fully rebuilt and serving updated assets.

### Automated tests

#### Running acceptance tests locally

```bash
composer install
php vendor/bin/codecept run Acceptance --steps
```

#### Running integration/acceptance tests against the IT stack

Once your IT environment is up (for example via `make it-complete-build`), you can execute the full Codeception test suite wired for the Docker stack with:

```bash
make it-test
```

To run codecept with detail breakdown of steps and debugging add these flags:

```bash
./vendor/bin/codecept run tests/Acceptance/ --steps --debug
```

using the configuration in `codeception.yml` and `tests/Acceptance.suite.yml`, against `http://magento.test` via Selenium/WebDriver.