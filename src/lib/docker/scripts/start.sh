#!/bin/bash

## Verificações iniciais
cd $(dirname $0);__DIR__=$(pwd)
source _init.sh 

# Criar diretorio de persistencia de dados
if [ ! -f ${PERSISTPATH} ]; then
    mkdir $PERSISTPATH -m 1777 -p
    chown root:root $PERSISTPATH
    chmod 1777 $PERSISTPATH
fi

# Validar se precisa esperar a criação dos banco de dados
WAIT_DATABASE_CREATE=${WAIT_DATABASE_CREATE:-10}
if [ -d ${PERSISTPATH}/${COMPOSE_PROJECT_NAME}/pg ]; then
    WAIT_DATABASE_CREATE=5;
fi

# Limpar os containers
echo 'y' | docker system prune

# Baixar as imagens e subir novamente caso existam
docker-compose pull
docker-compose down --volumes --remove-orphans
docker-compose up -d --build
echo ""
while [ $WAIT_DATABASE_CREATE -gt 0 ]; do
   printf "   >>> Please wait: running database data creation and restore: %02d seconds\033[0K <<< \r " $WAIT_DATABASE_CREATE
   sleep 1
   WAIT_DATABASE_CREATE=$((WAIT_DATABASE_CREATE-1))
done

echo ""
echo "#🏃‍♂️ Composer update"
docker exec "${COMPOSE_PROJECT_NAME}_app" composer update --quiet

echo ""
echo "🏃‍♂️ NPM update"
docker exec "${COMPOSE_PROJECT_NAME}_app" npm update --quiet

# Builder
echo "🏃‍♂️ Builder"
./docker/scripts/build.sh
