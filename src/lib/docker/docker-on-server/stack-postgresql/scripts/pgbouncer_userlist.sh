#!/bin/bash
echo ""

if [ ! $(id -u) -eq 0 ]; then
   echo "## ERROR ## "
   echo "To continue, run this script with sudo"
   echo ""
   exit 2
fi

## navegar para dir da aplicacao
cd $(dirname $0); __DIR__=$(pwd)
cd ../
export $(grep -v '^#' .env | xargs -d '\n')

# Liberar postgres sem senha
echo "- Restartando docker"
cp ./postgres/extras/pg_hba_trust.conf ${PERSISTPATH}/postgresql/14/data/pg_hba.conf
docker-compose restart
sleep 3

echo "- Preparando exportacao de usuarios"
psql -Atq -h localhost -p ${PGPORT} -U postgres -d postgres -c "SELECT concat('\"', rolname, '\" \"', rolpassword, '\"')  FROM pg_authid WHERE rolpassword IS NOT NULL;" > /tmp/userlist.txt
mv /tmp/userlist.txt ./pgbouncer/config/userlist.txt

# Retornar postgres
echo "- Reiniciar servicos"
cp ./postgres/extras/pg_hba_trust.conf "${PERSISTPATH}/postgresql/${PGVERSION}/data/pg_hba.conf"
sh ./scripts/start.sh

echo "Arquivo atualizado em ./pgbouncer/config/userlist.txt"