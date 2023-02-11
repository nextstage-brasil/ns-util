#!/bin/bash

if [ ! $(id -u) -eq 0 ]; then
    echo "## ERROR ## "
    echo "To continue, run this script with sudo"
    echo ""
    exit 2
fi

## navegar para dir da aplicacao
cd $(dirname $0);__DIR__=$(pwd)
cd ../
export $(grep -v '^#' .env | xargs -d '\n')
clear

if [ $(id -u) -eq 0 ]; then
    echo "## Add new APP to Apache (ProxyPass)"

    read -p "Enter app url: " url
    read -p "Enter system username (ex.: ubuntu): " username

    dir="${LOCAL_HTDOCS}/${url}"
    dirdocker="."

    echo "- Directories create:"

    # Homologação
    mkdir "$dir"
    mkdir "$dir/hml"
    mkdir "$dir/hml/build"
    chmod 0777 -R "$dir/hml/build"

    #Produção
    mkdir "$dir/prod"
    mkdir "$dir/prod/build"
    chmod 0777 -R "$dir/prod/build"

    #echo "Port: ${port}" > "$dir/.config"

    # Apache - HOMOLOGAÇÃO
    echo "- Create HML: $dir/hml"
    echo "
   ##### ${url}
<VirtualHost *:80>
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://\${PHP_IP}:9000\${PHP_APP_DIR}/${url}/hml/www/\$1
    ServerName hml-${url}
    DocumentRoot \${APACHE_ROOT_DIR}/htdocs/${url}/hml/www

    <Directory \${APACHE_ROOT_DIR}/htdocs/${url}/hml/www>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ## Permissão do letsencrypt 
    Alias /.well-known /home/letsencrypt/data/.well-known
    <Directory /home/letsencrypt/data/.well-known>
        Require all granted
    </Directory>
    <Location /.well-known/acme-challenge>
        Require all granted
    </Location>


     ErrorLog \${APACHE_ROOT_DIR}/logs/error.log
    CustomLog \${APACHE_ROOT_DIR}/logs/access.log common
</VirtualHost>

<VirtualHost *:443>
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://\${PHP_IP}:9000\${PHP_APP_DIR}/${url}/hml/www/\$1
    ServerName hml-${url}
    DocumentRoot \${APACHE_ROOT_DIR}/htdocs/${url}/hml/www
    <Directory \${APACHE_ROOT_DIR}/htdocs/${url}/hml/www>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_ROOT_DIR}/logs/error.log
    CustomLog \${APACHE_ROOT_DIR}/logs/access.log combined

   # antes do letsencrypt
   SSLCertificateKeyFile \${APACHE_ROOT_DIR}/certs/server.key
   SSLCertificateFile \${APACHE_ROOT_DIR}/certs/server.crt

    # Letsencrypt
    #SSLCertificateFile /home/letsencrypt/certs/live/${url}/cert.pem
    #SSLCertificateKeyFile /home/letsencrypt/certs/live/${url}/privkey.pem
    #SSLCertificateChainFile /home/letsencrypt/certs/live/${url}/fullchain.pem

   SSLProtocol ALL -SSLv2 -SSLv3
   SSLHonorCipherOrder On
   SSLCipherSuite ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:ECDH+3DES:DH+3DES:RSA+AESGCM:RSA+AES:RSA+3DES:!aNULL:!MD5
   SSLCompression Off

   Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\"
   Header always append X-Frame-Options sameorigin
</VirtualHost>" > "${dirdocker}/apache/configs/vhosts/${url}-hml.conf"

    # Apache - PRODUCAO
    echo "- Create PROD: $dir/prod"
    echo "
   ##### ${url}
<VirtualHost *:80>
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://\${PHP_IP}:9000\${PHP_APP_DIR}/${url}/prod/www/\$1
    ServerName ${url}
    DocumentRoot \${APACHE_ROOT_DIR}/htdocs/${url}/prod/www

    <Directory \${APACHE_ROOT_DIR}/htdocs/${url}/prod/www>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ## Permissão do letsencrypt 
    Alias /.well-known /home/letsencrypt/data/.well-known
    <Directory /home/letsencrypt/data/.well-known>
        Require all granted
    </Directory>
    <Location /.well-known/acme-challenge>
        Require all granted
    </Location>


     ErrorLog \${APACHE_ROOT_DIR}/logs/error.log
    CustomLog \${APACHE_ROOT_DIR}/logs/access.log common
</VirtualHost>

<VirtualHost *:443>
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://\${PHP_IP}:9000\${PHP_APP_DIR}/${url}/prod/www/\$1
    ServerName ${url}
    DocumentRoot \${APACHE_ROOT_DIR}/htdocs/${url}/prod/www
    <Directory \${APACHE_ROOT_DIR}/htdocs/${url}/prod/www>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_ROOT_DIR}/logs/error.log
    CustomLog \${APACHE_ROOT_DIR}/logs/access.log combined

   # antes do letsencrypt
   SSLCertificateKeyFile \${APACHE_ROOT_DIR}/certs/server.key
   SSLCertificateFile \${APACHE_ROOT_DIR}/certs/server.crt


    # Letsencrypt
    #SSLCertificateFile /home/letsencrypt/certs/live/${url}/cert.pem
    #SSLCertificateKeyFile /home/letsencrypt/certs/live/${url}/privkey.pem
    #SSLCertificateChainFile /home/letsencrypt/certs/live/${url}/fullchain.pem


   SSLProtocol ALL -SSLv2 -SSLv3
   SSLHonorCipherOrder On
   SSLCipherSuite ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:ECDH+3DES:DH+3DES:RSA+AESGCM:RSA+AES:RSA+3DES:!aNULL:!MD5
   SSLCompression Off

   Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\"
   Header always append X-Frame-Options sameorigin
</VirtualHost>" >"${dirdocker}/apache/configs/vhosts/${url}-prod.conf"

    echo "- Activate ssl"
    # certbot --apache

    chown ${username}:${username} $dir -R
    chown ${username}:${username} "${dirdocker}/apache/configs/vhosts" -R

    echo "Complete! Restart DOCKER to reload configs "
    sh ./scripts/start.sh
else
    echo "## ERROR ## "
    echo "To continue, run this script with sudo"
    echo ""
    exit 2
fi
