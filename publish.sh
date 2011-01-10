#!/bin/sh

rsync -Iavz . root@192.168.0.11:/var/www/cas/ --exclude .git/ --exclude publish.sh
