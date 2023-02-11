#!/bin/bash
echo ""

if [ ! $(id -u) -eq 0 ]; then
   echo "## ERROR ## "
   echo "To continue, run this script with sudo"
   echo ""
   exit 2
fi
apt install postgresql-client

## navegar para dir da aplicacao
cd $(dirname $0); __DIR__=$(pwd)
cd ../
export $(grep -v '^#' .env | xargs -d '\n')
clear

read -p "Enter username (no space): " username; 
read -p "Enter password: " password; 
# read -p "Porta do serviço postgresql: " PGPORT;
# read -p "Senha para usuario postgres: " SENHA;

# Liberar postgres sem senha
cp ./postgres/extras/pg_hba_trust.conf ${PERSISTPATH}/postgresql/${PGVERSION}/data/pg_hba.conf
docker-compose restart
sleep 3

clear
echo "Criacao de novo user"
psql -h localhost -U postgres -p ${PGPORT} -q -w -c "create user ${username} with password '${password}'"
psql -h localhost -U postgres -p ${PGPORT} -q -w -c "alter user ${username} createdb"
psql -h localhost -U postgres -p ${PGPORT} -q -w -c "create database ${username}_db"
psql -h localhost -U postgres -p ${PGPORT} -q -w -c "grant all privileges on database ${username}_db to ${username}"
psql -h localhost -U postgres -p ${PGPORT} -q -w -c "alter database ${username}_db owner to ${username};"

# # adicionar o usuario na tabela do pg_hba.conf
echo "
## Config to user ${username} on database ${username}_db
host   ${username}_db   ${username}   0.0.0.0/0   ${PG_AUTHMODE}
host   ${username}_db   ${username}   ::1/128     ${PG_AUTHMODE}
local  ${username}_db   ${username}               ${PG_AUTHMODE}

" | sudo tee -a ./postgres/extras/pg_hba.conf

echo "Configuração do pgbouncer"
sh scripts/pgbouncer_userlist.sh
