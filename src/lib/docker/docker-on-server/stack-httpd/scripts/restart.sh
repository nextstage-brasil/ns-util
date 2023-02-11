#!/bin/bash

if [ ! $(id -u) -eq 0 ]; then
   echo "## ERROR ## "
   echo "To continue, run this script with sudo"
   echo ""
   exit 2
fi

echo "\n   ### Docker Restart"

## navegar para dir da aplicacao
cd $(dirname $0); __DIR__=$(pwd)
cd ../

docker-compose restart