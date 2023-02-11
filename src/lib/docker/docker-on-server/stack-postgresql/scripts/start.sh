#!/bin/bash

if [ ! $(id -u) -eq 0 ]; then
    echo "## ERROR ## "
    echo "To continue, run this script with sudo"
    echo ""
    exit 2
fi

## navegar para dir da aplicacao
cd $(dirname $0);__DIR__=$(pwd)
cd ../
export $(grep -v '^#' .env | xargs -d '\n')
clear

chown ${USER_SO}:${USER_SO} . -R

# Configurações do pgbouncer
if [ ! -f "./pgbouncer/config/userlist.txt" ]; then
    mkdir ./pgbouncer/config -m 0777
    touch ./pgbouncer/config/userlist.txt
fi

# Configurações do postgresql
if [ ! -f "./postgres/extras/pg_hba.conf" ]; then
    cp ./postgres/extras/pg_hba_default.conf ./postgres/extras/pg_hba.conf
fi

## Subir docker
sh ./scripts/stop.sh
docker-compose up -d --build

# Aplicar configurações do postgresql
cp ./postgres/extras/pg_hba.conf ${PERSISTPATH}/postgresql/${PGVERSION}/data
if [ ! -f "${PERSISTPATH}/postgresql/.setconfig.ini" ]; then
    echo "include_dir = '/etc/postgresql/${PGVERSION}/main/conf.d'" >> ${PERSISTPATH}/postgresql/${PGVERSION}/data/postgresql.conf
    echo "include_dir = '/etc/postgresql/${PGVERSION}/main/conf.d'" > ${PERSISTPATH}/postgresql/.setconfig.ini
fi

# Reiniciar docker
sh ./scripts/restart.sh

echo "Docker application is running"


