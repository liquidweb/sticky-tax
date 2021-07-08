#!/usr/bin/env bash

DIR=$(dirname $(dirname $(readlink -f "$0")));

# If we're running in VVV, `xdebug_on` will enable Xdebug.
if type -t xdebug_on > /dev/null; then
	echo -e "\n\033[0;36mEnabling Xdebug in order to generate test coverage:\033[0m"
	xdebug_on
	echo
fi

# Run PHPUnit and generate the test coverage.
phpunit --coverage-html tests/coverage --coverage-text && \
	echo -e "\033[0;36mAdditional code coverage details:\033[0m\n${DIR}/tests/coverage/\n"
