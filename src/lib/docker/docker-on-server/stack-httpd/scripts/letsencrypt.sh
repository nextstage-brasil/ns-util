#!/bin/bash

if [ ! $(id -u) -eq 0 ]; then
    echo "## ERROR ## "
    echo "To continue, run this script with sudo"
    echo ""
    exit 2
fi

echo "\n   ### LetsEncrypt - Develop and Homolog"

## navegar para dir da aplicacao
cd $(dirname $0);__DIR__=$(pwd)
cd ../
export $(grep -v '^#' .env | xargs -d '\n')
clear

#####################################
for file in httpd/vhosts/*.conf; do

    TO_SSL="$(basename "$file")";

    docker run -it --rm \
        --name certbot \
        -v "$(pwd)/persist/letsencrypt/certs":"/etc/letsencrypt" \
        -v "$(pwd)/persist/letsencrypt/data":"/data/letsencrypt" \
        certbot/certbot certonly --webroot --webroot-path=/data/letsencrypt --email "${LETSENCRYPT_EMAIL}" \
        -d ${TO_SSL/.conf/} \
        -n \
        --agree-tos;
done