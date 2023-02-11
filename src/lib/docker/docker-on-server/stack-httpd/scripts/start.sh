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

echo "### Docker Application Starting"

# clear
echo 'y' | docker system prune

## Autenticar repositório
sh ./scripts/auth.sh

# obter novas imagens antes para eficiencia do deploy
docker-compose pull

## Parar docker
sh ./scripts/stop.sh

## Subir aplicação
docker-compose up -d --build

# Permissão de pastas
for d in homolog main; do
    docker exec app_${d} chmod 1777 /var/www/html/app -R
    chmod 1777 ${PERSISTPATH}/${d}/app -R
    echo "Path permission: ${PERSISTPATH}/${d}/app"
    # docker exec app_${d} curl localhost/version?k=migrations
done

DEFAULT_INDEX=${LOCAL_HTDOCS}/index.html
if [ ! -f "$DEFAULT_INDEX" ]; then
    echo "Created index.html default on htdocs"
    cp httpd/htdocs/index.html $DEFAULT_INDEX
fi



docker ps
echo "docker logs--- Docker application is running"