#!/bin/bash

if [ ! $(id -u) -eq 0 ]; then
    echo "## ERROR ## "
    echo "To continue, run this script with sudo"
    echo ""
    exit 2
fi
####################################################################################
# ATENCAO : EXECUTAR ESTE SCRIPT FORA DESTA PASTA. ESTA AQUI PARA APENAS FACILITAR 
####################################################################################

## navegar para dir da aplicacao
cd $(dirname $0);__DIR__=$(pwd)
DIR=$(pwd)
clear
RELEASE_NAME=$(date +%s)
DOCKERS_DIR="$DIR/_dockers";
RELEASES_DIR_ROOT="$DOCKERS_DIR/backups"
RELEASES_DIR="$RELEASES_DIR_ROOT/${RELEASE_NAME}"
DOCKER_ORIGINAL="$DOCKERS_DIR/docker-full-original";

# diretorio de backup
if [ ! -d $RELEASES_DIR ]; then
    mkdir -p $RELEASES_DIR
fi
if [ ! -d $RELEASES_DIR_ROOT ]; then
    mkdir -p $RELEASES_DIR_ROOT
fi

# Clonar repositorio novamente, sempre limpo
if [ -d $DOCKER_ORIGINAL ]; then
    rm -R $DOCKER_ORIGINAL
fi  
git clone https://gitlab.com/nextstage1/public-projects/docker-full.git $DOCKER_ORIGINAL

# Manter minhas anotações atuais
if [ -d "$(pwd)/docker-full" ]; then
    mv "$(pwd)/docker-full" "${RELEASES_DIR}/docker-full"
fi  
rsync -p -r "$DOCKER_ORIGINAL/" "$(pwd)/docker-full/"

# Copiar minhas alterações de volta para o diretorio novo
if [ -d "${RELEASES_DIR}/docker-full" ]; then
    rsync -p -r "${RELEASES_DIR}/docker-full/" ./docker-full/ 
fi
chown ubuntu:ubuntu $DOCKERS_DIR -R
chown ubuntu:ubuntu $RELEASES_DIR_ROOT -R
chown ubuntu:ubuntu ./docker-full -R

# Manter somente as 5 ultimas versoes
ls -dt ${RELEASES_DIR_ROOT}/* | tail -n +6 | xargs -d "\n" rm -rf;