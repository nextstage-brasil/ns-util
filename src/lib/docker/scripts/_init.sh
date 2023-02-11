#!/bin/bash

if [ ! $(id -u) -eq 0 ]; then
    echo "## ERROR ## "
    echo "To continue, run this script with sudo"
    echo ""
    exit 2
fi

branch=$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
if [ $branch = 'main' ]; then 
    echo "## ERROR ## "
    echo "Change to the working branch. The main branch is not permitted on development" 
    echo ""
    exit 2
fi

## navegar para dir da aplicacao
cd ../../

# Verificar se o arquivo de configuração existe
if [ ! -f ./.env ]; then
    echo "## ERROR ## "
    echo "To continue, edit the file .env with you configurations" 
    echo ""
    exit 2
fi

set -a      # turn on automatic exporting
. .env  # source test.env
set +a      # turn off automatic exporting

echo "Everthing is ok!"