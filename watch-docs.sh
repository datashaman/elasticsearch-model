#/usr/bin/env bash

inotifywait -m -r -e close_write src/ | while read LINE
do
    echo $LINE
    phpdoc -d src/ -t docs/api/ --template responsive
done
