#/usr/bin/env bash

# inotifywait -m -r -e close_write src/ tests/ | while read LINE
inotifywait -m -r -e close_write src/ ReadmeTest.php | while read LINE
do
    echo $LINE
    # phpunit
    phpunit ReadmeTest.php
done
