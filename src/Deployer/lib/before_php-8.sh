#!/bin/sh

echo "#### INSTALATION BEFORE SCRIPTS ####"
apk update && apk add zip openssh-client curl bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config
echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

## PHP e NPM
apk del php*
apk add php8 php8-zip php8-cli php8-curl php8-json php8-iconv php8-mbstring php8-json php8-openssl php8-phar php8-zlib 
apk add npm

## Composer e Serverless
curl -s https://getcomposer.org/installer | php
php --version
npm install -g serverless

## Instacao AWS Cli e configuracoes
apk add --no-cache python3 py3-pip && pip3 install --upgrade pip && pip3 install awscli
rm -rf /var/cache/apk/*
mkdir -p ~/.aws && chmod 700 ~/.aws
if [ -z {$AWS_PROFILE+x} ]; then 
    AWS_PROFILE=default; 
fi
echo -e "[$AWS_PROFILE]\naws_access_key_id=$AWS_KEY\naws_secret_access_key=$AWS_SECRET" > ~/.aws/credentials