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

* Disable two-factor authentication for convenience (don't do this in prod)

```bash
$ bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth Magento_TwoFactorAuth
$ bin/magento cache:flush
```

* Create new admin user

```bash
$ bin/create-user
```

* Copy this plugin into the `app/code` directory within your Docker container

```bash
$ make install-docker
```

* Navigate to the payment methods page in Magento admin (Stores > Configuration > Sales > Payment Methods) and confirm that PublicSquare Payments shows up in the "Other Payment Methods" section.

# AUTOMATED TESTS

### Running Acceptance Tests

```bash
$ composer install
$ php vendor/bin/codecept run Acceptance --steps
```