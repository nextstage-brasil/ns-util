#!/bin/bash

set -e
set -u

echo "###################### >> config.sh"



echo "include_dir = '/etc/postgresql/${PGVERSION}/main/conf.d'" >> /var/lib/postgresql/data/postgresql.conf
echo "Config path is setted!"