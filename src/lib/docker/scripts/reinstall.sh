#!/bin/bash

## Verificações iniciais
cd $(dirname $0);__DIR__=$(pwd)
source _init.sh

clear
echo ""
echo "############################################################"
echo "################## ${COMPOSE_PROJECT_NAME} #####################"
echo "Atention!! You will lose all saved data!"
read -p "Confirm remove the database?: (yes/no) " dd
DECIDE=${dd:-NO}
if [ ! $DECIDE = 'yes' ]; then
    echo "Aborted!"
    exit 3
fi
echo ""

# stop application
docker-compose down --volumes --remove-orphans

# Clear data
echo "Remove old data"
rm -R ${PERSISTPATH} >/dev/null 2>&1
rm -R ./auto >/dev/null 2>&1
rm -R ./vendor >/dev/null 2>&1
rm -R ./composer.lock >/dev/null 2>&1
rm -R ./node_modules >/dev/null 2>&1
rm -R ./package-lock.json >/dev/null 2>&1

# start application
./docker/scripts/start.sh