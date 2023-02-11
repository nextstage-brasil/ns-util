#!/bin/bash

if [ ! $(id -u) -eq 0 ]; then
   echo "## ERROR ## "
   echo "To continue, run this script with sudo"
   echo ""
   exit 2
fi

docker system prune -a --force

docker kill $(docker ps -q)
docker rm $(docker ps -a -q)
docker rmi $(docker images -q)
echo 'y' | docker network prune
echo ""
