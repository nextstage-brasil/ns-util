#!/bin/bash

set -e
set -u

echo "###################### >> seed.sh"



if [ -n "$PG_MULTIPLE_DATABASES" ]; then
	echo "Restore database requested: $PG_MULTIPLE_DATABASES"
	for db in $(echo $PG_MULTIPLE_DATABASES | tr ',' ' '); do
                if [ -f "/backup/$db.seed.sql" ]; then 
                    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" -d $db -f "/backup/$db.seed.sql";
                    echo "Database $db seed is ok!";
                fi                
	done
	echo "Multiple databases seeders ok!"
fi