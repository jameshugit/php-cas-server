#!/bin/sh

rsync -avz . root@cas-erasme.erasme.lan:/var/www/cas/ --exclude .git/ --exclude publish.sh
