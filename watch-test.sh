#/usr/bin/env bash

inotifywait -m -r -e close_write src/ tests/ | while read LINE
do
    echo $LINE
    phpunit
done
