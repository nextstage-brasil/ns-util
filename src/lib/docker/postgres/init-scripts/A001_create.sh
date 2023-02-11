#!/bin/bash

set -e
set -u

echo "###################### >> create.sh"

function create_user_and_database() {
	local database=$1
	echo "  Creating user and database '$database'"
	psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
	    CREATE DATABASE $database;
EOSQL

        psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" -d $database -f /backup/create_extensions.sql

        psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" -d $database -f /backup/create_dictionary.sql

        psql -v --username "$POSTGRES_USER" -d $db -f /backup/create_functions.sql

}

if [ -n "$PG_MULTIPLE_DATABASES" ]; then
	echo "Multiple database creation requested: $PG_MULTIPLE_DATABASES"
	for db in $(echo $PG_MULTIPLE_DATABASES | tr ',' ' '); do
		create_user_and_database $db
	done
	echo "Multiple databases was created"
fi