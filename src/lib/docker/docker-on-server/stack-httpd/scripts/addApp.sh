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
clear

echo "## Add new APP (Virtual Server on Apache Proxy and Path to App)"

read -p "Enter url: " url
read -p "Enter the stage of image or enter to redirect (Ex.: homolog, main..): " STAGE

if [ $STAGE = "" ]; then
  read -p "Enter url to redirect (Ex.: http://app_homolog, http://host.docker.internal:14081): " proxy
else 
  proxy="http://app_$STAGE"
fi


#Apache
echo "- Create proxy vhost on apache"
echo "
<VirtualHost *:443>
    ServerAdmin email@local
    ServerName ${url}
    ProxyPreserveHost On
    ProxyPass / ${proxy}/
    ProxyPassReverse / ${proxy}/
    Timeout 1800

    # send protocol https to proxy
    RequestHeader set X-Forwarded-Proto https

    # set to accept x-frame all
    # Header set X-Frame-Options \"ALLOW-FROM=*\"
    
    # Letsencrypt 
    Alias /.well-known /home/letsencrypt/data/.well-known
    <Directory /home/letsencrypt/data/.well-known>
        Require all granted
    </Directory>
    <Location /.well-known/acme-challenge>
        Require all granted
    </Location>

    # # antes do letsencrypt
    SSLCertificateKeyFile /usr/local/apache2/certs/server.key
    SSLCertificateFile /usr/local/apache2/certs/server.crt

    # Letsencrypt
    #SSLCertificateFile /home/letsencrypt/certs/live/${url}/cert.pem
    #SSLCertificateKeyFile /home/letsencrypt/certs/live/${url}/privkey.pem
    #SSLCertificateChainFile /home/letsencrypt/certs/live/${url}/fullchain.pem

     # SSL Protocols
    SSLProtocol ALL -SSLv2 -SSLv3
    SSLHonorCipherOrder On
    SSLCipherSuite ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:ECDH+3DES:DH+3DES:RSA+AESGCM:RSA+AES:RSA+3DES:!aNULL:!MD5
    SSLCompression Off

</VirtualHost>


<VirtualHost *:80>
    ServerAdmin email@local
    ServerName ${url}

    # Letsencrypt 
    Alias /.well-known /home/letsencrypt/data/.well-known
    <Directory /home/letsencrypt/data/.well-known>
        Require all granted
    </Directory>
    <Location /.well-known/acme-challenge>
        Require all granted
    </Location>

    # Se for utilizar letsencrypt, comente esta linha
    # Redirect / https://${url}/
</VirtualHost> " > "./httpd/vhosts/${url}.conf"

echo " Ok! Check the file: ./httpd/vhosts/${url}.conf"

if [ $STAGE != "" ]; then  

  if [ ! -f "./config/$STAGE.env" ];then 
    cp config/.env.example "config/$STAGE.env"
    chmod 0777 "config/$STAGE.env"
  fi

  echo "
  app_$STAGE:
    container_name: app_$STAGE
    hostname: ns-app-$STAGE
    restart: always
    image: \${AWS_ECR}:$STAGE
    depends_on:
      - ns_httpd
    extra_hosts:
      - \"host.docker.internal:host-gateway\"
    env_file:
      - ./config/$STAGE.env
    volumes:
      - \${PERSISTPATH}/$STAGE/app:/var/www/html/app
  " >> docker-compose.yml
  echo " Ok! Check the file: docker-compose.yml"
  echo " Update file ./config/$STAGE.env"
fi

read -p ">> Restart docker to apply configs? (n/y): " RESTART
if [ $RESTART = "y" ]; then 
  sh scripts/start.sh

  echo ""
  read -p ">> Apply Letsencrypt SSL?? (n/y): " LETSENCRYPT
  if [ $LETSENCRYPT = "y" ]; then 
    sh scripts/letsencrypt.sh
    echo "Open file ./httpd/vhosts/${url}.conf to seet LetsEncrypt path"
  fi

fi

chmod 0777 "./httpd/vhosts/${url}.conf"
