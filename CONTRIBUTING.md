# Contributing to Sticky Tax

Thank you for your interest in contributing the ongoing development of Sticky Tax!


## Contributing code

The dependencies for the plugin are loaded via [Composer](https://getcomposer.org) and [npm](https://docs.npmjs.com/getting-started/what-is-npm), so it's necessary to have both of those tools installed locally.

Begin by cloning the GitHub repo locally and installing the dependencies:

```bash
# Clone the repository, ideally into a wp-content/plugins directory:
$ git clone https://github.com/liquidweb/sticky-tax.git sticky-tax && cd sticky-tax

# Install local dependencies
$ composer install && npm install
```

The Sticky Tax plugin is built for PHP versions 5.3 and above (required for using PHP namespaces).


### Project structure

The main plugin file, `sticky-tax.php`, consists of a series of include files, each living in its own namespace under the base `LiquidWeb\StickyTax` namespace. New functionality should be introduced following this same scheme.


### Branching

Pull requests should be based off the `develop` branch, which represents the current development state of the plugin. The only thing ever merged into `master` should be new release branches, at the time a release is tagged.

To create a new feature branch:

```bash
# Start on develop, making sure it's up-to-date
$ git checkout develop && git pull

# Create a new branch for your feature
$ git checkout -b feature/my-cool-new-feature
```

When submitting a new pull request, your `feature/my-cool-new-feature` should be compared against `develop`.


### Coding standards

This project uses [the WordPress-Extra and WordPress-Docs rulesets for PHP_CodeSniffer](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards), as declared in `phpcs.xml`. PHP_CodeSniffer will be run automatically against any modified files on a pre-commit Git hook, thanks to [WP Enforcer](https://github.com/stevegrunwell/wp-enforcer).


#### Localization

The Sticky Tax plugin aims to be 100% localization-ready, so any user-facing strings [must use appropriate localization functions](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/).

At any time, you may regenerate the plugin's `languages/sticky-tax.pot` file by running `grunt i18n`.


### Running unit tests

Sticky Tax has a number of unit tests, using [the WordPress Core Testing Framework](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/). As the WordPress core tests require a WordPress installation, [you may consider developing for the plugin within VVV](https://varyingvagrantvagrants.org/docs/en-US/).

When submitting changes, please be sure to add or update unit tests accordingly. You may run unit tests at any time by running:

```bash
# From the root of the plugin directory
$ phpunit
```

To generate a report of code coverage for the current branch, you may run the following Composer script, which will generate an HTML report in `tests/coverage/`:

```bash
$ composer test-coverage
```

Note that [both the Xdebug and tokenizer PHP extensions must be installed and active](https://phpunit.de/manual/current/en/textui.html) on the machine running the tests. If you're building in VVV, [both are available, but Xdebug is disabled by default](https://github.com/Varying-Vagrant-Vagrants/VVV/wiki/Code-Debugging#meet-xdebug); you can activate it by SSH-ing into VVV and running `xdebug_on`.


## Contributors

Sticky Tax was built and is maintained by [Liquid Web](https://www.liquidweb.com).

[See a list of all contributors to the plugin](https://github.com/liquidweb/liquidweb/graphs/contributors).
