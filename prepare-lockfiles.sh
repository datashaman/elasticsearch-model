#!/usr/bin/env bash

set -E

for PHP_VERSION in 7.0 7.1
do
    COMPOSER_CMD="php${PHP_VERSION} `which composer`"

    for ILLUMINATE_SUBVERSION in 4 5 6 7
    do
        ILLUMINATE_VERSION="5.${ILLUMINATE_SUBVERSION}.*"
        TESTBENCH_VERSION="3.${ILLUMINATE_SUBVERSION}.*"

        if [ $ILLUMINATE_SUBVERSION -ge 6 ]; then
            PHPUNIT_VERSION="~7.0"
        else
            if [ $ILLUMINATE_SUBVERSION -ge 5 ]; then
                PHPUNIT_VERSION="~6.0"
            else
                PHPUNIT_VERSION="~5.0"
            fi
        fi

        for ELASTICSEARCH_VERSION in 2 5 6
        do
            if [ $PHP_VERSION = "7.0" ]; then
                [ $ILLUMINATE_SUBVERSION -eq 6 -o $ILLUMINATE_SUBVERSION -eq 7 ] && continue
            fi

            git checkout composer.json
            rm -rf composer.lock vendor

            $COMPOSER_CMD require --no-suggest --prefer-dist --profile \
                elasticsearch/elasticsearch:^${ELASTICSEARCH_VERSION} \
                illuminate/support:${ILLUMINATE_VERSION} \
                orchestra/testbench:${TESTBENCH_VERSION} \
                phpunit/phpunit:${PHPUNIT_VERSION} &&

            mv composer.lock "lockfiles/php-${PHP_VERSION}-laravel-5.${ILLUMINATE_SUBVERSION}-es-${ELASTICSEARCH_VERSION}.lock"
        done
    done
done

git checkout composer.json
