#!/bin/bash

## Verificações iniciais
cd $(dirname $0);__DIR__=$(pwd)
source _init.sh

# Down containers
docker-compose down --volumes --remove-orphans
docker ps