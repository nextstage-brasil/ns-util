#!/bin/bash

# Configuração do diretorio HOME da aplicação. A pasta www estara abaixo disso
DIR="/home/nextstag/public_html/trilhasbr.com"
OWNER="nextstag"
FILENAME="trilhasbr-backend-package.zip"
APPNAME="aws_homolog"

# Sudo para pedir senha caso precise
cd $DIR
ls
clear

# Não sera necessário alteracoes aqui para baixo
RELEASES_DIR="$DIR/releases"
RELEASE_NAME=$(date +%s)
RELEASE="$RELEASES_DIR/$RELEASE_NAME" 
PACKAGE="$DIR/build/$FILENAME"


echo "### Deployer APP: $APPNAME ###\n\n"
# validar se existe o build aqui
if [ ! -f $PACKAGE ]; then
    echo "Instalação abortada. Pacote não localizado!"
    exit
fi

# directory create
if [ ! -d ${RELEASES_DIR} ]; then
     mkdir ${RELEASES_DIR}
fi
if [ ! -d "$DIR/app" ]; then
     mkdir "$DIR/app"
     chmod 0777 -R "$DIR/app"
fi
if [ ! -d "$DIR/.trash" ]; then
     mkdir "$DIR/.trash"
     chmod 0777 -R "$DIR/.trash"
fi
if [ ! -d "$DIR/storage" ]; then
     mkdir "$DIR/storage"
     chmod 0775 -R "$DIR/storage"
     chown "${OWNER}:www-data" "$DIR/storage"
fi


# clear
 if [ -f "$DIR/www/cron/crontab" ]; then
 rm -R "$DIR/app/.tmp"
 rm -R "$DIR/app/file"
 rm -R "$DIR/app/cookie.txt"


# Criando diretorio do release
 mkdir ${RELEASE};
 mkdir "$DIR/app/.tmp"
 mkdir "$DIR/app/file"

# deploy
echo "- Copiar arquivos"
 unzip -o "$PACKAGE" -d "$RELEASE" > /dev/null
#  mv "$RELEASE/.htaccess-server" "$RELEASE/.htaccess"
 rm "$PACKAGE"

# versionando o release
echo "- Versionar release"
RELEASE_NAME=$(cat "$RELEASE/version") 
 mv $RELEASE "$RELEASES_DIR/$RELEASE_NAME"
RELEASE="$RELEASES_DIR/$RELEASE_NAME" 

# links simbolicos
echo "- Criar links"
 ln -nfs "$DIR/app" "$RELEASE/app"
 ln -nfs "$DIR/storage" "$RELEASE/storage"
# ln -nfs "$DIR/.env" "$RELEASE/.env"
 ln -nfs "$RELEASE" "$DIR/www"

# Permissoes de pastas
 chown -R "${OWNER}:www-data" "$RELEASES_DIR"
 chown "${OWNER}:www-data" "$DIR/app"
 chmod 0777 "$DIR/app" -R

# Licenciamento e config
# cp ${DIR}/cs_licence ${RELEASE}/.cs_licence.bkp
# cp ${DIR}/cscfg ${RELEASE}/.cscfg.bkp

# crontab
if [ -f "$DIR/www/cron/crontab" ]; then
    echo "- Atualizar crontab"
     chmod -R 0775 "$RELEASE/cron"
     crontab -l -u ${OWNER} | echo "" |  crontab -u ${OWNER} -
     crontab -l -u ${OWNER} | cat - "$DIR/www/cron/crontab" |  crontab -u ${OWNER} -
fi

# Composer
if [ -f "$DIR/www/composer.json" ]; then
    echo "- Atualizar pacotes via composer"
     composer install -q --prefer-dist --optimize-autoloader --no-dev --working-dir="$DIR/www"
fi

# Manter somente as 5 ultimas versoes
echo "- Remover releases anteriores"
cd "$RELEASES_DIR"
 ls -dt ${RELEASES_DIR}/* | tail -n +6 | xargs -d "\n"  rm -rf;

# finalizar
echo "- Reiniciar serviços"
 service apache2 restart > /dev/null
 service php7.*-fpm restart > /dev/null

# clear
echo "\n### Versão $RELEASE_NAME instalada com sucesso!\n"
exit