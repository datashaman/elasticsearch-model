#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Collection;

Collection::make([7.1, 7.2, 7.3, 7.4])
    ->each(
        function ($php) {
            Collection::make(5.8, 6)
                ->each(
                    function ($laravel) use ($php) {
                        $testbench = $laravel - 2;
                        $phpunit = $laravel >= 6 ? '~7.0' : '~6.0';

                        Collection::make([5, 6, 7])
                            ->each(
                                function ($elasticsearch) use ($laravel, $php, $testbench, $phpunit) {
                                    $composer = "php${php} `which composer`";
                                    `
                                    git checkout composer.json
                                    rm -rf composer.lock vendor

                                    $composer require --no-suggest --prefer-dist --profile \
                                        elasticsearch/elasticsearch:^$elasticsearch \
                                        illuminate/support:$laravel \
                                        orchestra/testbench:$testbench \
                                        phpunit/phpunit:$phpunit &&

                                    mv composer.lock "lockfiles/php-$php-laravel-$laravel-es-$elasticsearch.lock"
                                    `;
                                }
                            );
                    }
                );
        }
    );
