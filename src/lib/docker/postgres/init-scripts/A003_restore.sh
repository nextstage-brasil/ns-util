#!/bin/sh

set -e
set -u

echo "###################### >> restore.sh"



if [ -n "$PG_MULTIPLE_DATABASES" ]; then
	echo "Restore database requested: $PG_MULTIPLE_DATABASES"
	for db in $(echo $PG_MULTIPLE_DATABASES | tr ',' ' '); do
                if [ -f "/backup/$db.backup" ]; then 
                    pg_restore --clean --if-exists --no-owner -U "$POSTGRES_USER" -d $db /backup/$db.backup > /dev/null || true;
                fi                
	done
	echo "Multiple database was restored"
fi