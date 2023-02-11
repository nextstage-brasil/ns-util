#!/bin/bash

## Verificações iniciais
cd $(dirname $0);__DIR__=$(pwd)
source _init.sh 

git config core.filemode false
git config --global core.filemode false

# Permissions path
rm -R _build/env
rm -R auto
rm -R app/logs
rm -R src/NsLibrary
for item in _migrations app auto _build src/config src/NsLibrary/Entities; do
    toup=/var/www/html/${item}
    echo "Updated permission to ${toup}"
    docker exec "${COMPOSE_PROJECT_NAME}_app" mkdir $toup -m 0777 -p >/dev/null 2>&1
    docker exec "${COMPOSE_PROJECT_NAME}_app" chmod 0777 $toup -R >/dev/null 2>&1
    chmod 0777 -R ./$item >/dev/null 2>&1
done

sleep 1

echo ""
echo "🏃‍♂️ Builder"
docker exec "${COMPOSE_PROJECT_NAME}_app" php _build/install/builder.php
echo ""

for item in _migrations app auto _build src/config; do
    toup=/var/www/html/${item}
    docker exec "${COMPOSE_PROJECT_NAME}_app" chmod 0777 $toup -R >/dev/null 2>&1
    chmod 0777 -R ./$item >/dev/null 2>&1
done

echo ""
echo "############################################################"
echo "############ ${COMPOSE_PROJECT_NAME} Is Running! ###############"
echo ""
echo "URL: ${APP_PROTOCOL}://${APP_CN}:${APP_EXPOSED_PORT}"
echo "Login example: 'dev@local' with password 'senha1'"
echo "############################################################"
echo ""

