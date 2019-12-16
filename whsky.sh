#!/bin/bash

OPTIND=1
migrate=0

while getopts "m:" opt; do
    case "$opt" in
    m)  migrate=$OPTARG
        ;;
    esac
done

if [ "$migrate" == "up" ]; then
    php public_html/index.php /_whsky/cli/migrate-up/
else
    php public_html/index.php /_whsky/cli/
fi