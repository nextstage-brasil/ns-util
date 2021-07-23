#!/bin/bash

# Configuração do diretorio HOME da aplicação. A pasta www estara abaixo disso
DIR="path"
OWNER="usuario"
FILENAME="packageName"
APPNAME="cliente"

# Sudo para pedir senha caso precise
cd $DIR
sudo ls
clear

# Não sera necessário alteracoes aqui para baixo
RELEASES_DIR="$DIR/releases"
RELEASE_NAME=$(date +%s)
RELEASE="$RELEASES_DIR/$RELEASE_NAME" 
PACKAGE="$DIR/build/$FILENAME"


echo -e "### Deployer APP: $APPNAME ###\n\n"
# validar se existe o build aqui
if [ ! -f $PACKAGE ]; then
    echo -e "Instalação abortada. Pacote não localizado!"
    exit
fi

# directory create
if [ ! -d ${RELEASES_DIR} ]; then
    sudo mkdir ${RELEASES_DIR}
fi
if [ ! -d "$DIR/app" ]; then
    sudo mkdir "$DIR/app"
    sudo chmod 0777 -R "$DIR/app"
    sudo mkdir "$DIR/app/.tmp"
fi
if [ ! -d "$DIR/.trash" ]; then
    sudo mkdir "$DIR/.trash"
    sudo chmod 0777 -R "$DIR/.trash"
fi
if [ ! -d "$DIR/storage" ]; then
    sudo mkdir "$DIR/storage"
    sudo chmod 0775 -R "$DIR/storage"
    sudo chown "${OWNER}:www-data" "$DIR/storage"
fi


# clear
rm -R "$DIR/app/.tmp"
sudo mkdir "$DIR/app/.tmp"

# Criando diretorio do release
sudo mkdir ${RELEASE};

# deploy
echo -e "- Copiar arquivos"
sudo unzip -o "$PACKAGE" -d "$RELEASE" > /dev/null
sudo mv "$RELEASE/.htaccess-server" "$RELEASE/.htaccess"
sudo rm "$PACKAGE"

# versionando o release
echo -e "- Versionar release"
RELEASE_NAME=$(cat "$RELEASE/version") 
sudo mv $RELEASE "$RELEASES_DIR/$RELEASE_NAME"
RELEASE="$RELEASES_DIR/$RELEASE_NAME" 

# links simbolicos
echo -e "- Criar links"
sudo ln -nfs "$DIR/app" "$RELEASE/app"
sudo ln -nfs "$DIR/storage" "$RELEASE/storage"
# ln -nfs "$DIR/.env" "$RELEASE/.env"
sudo ln -nfs "$RELEASE" "$DIR/www"

# Permissoes de pastas
sudo chmod -R 0775 "$RELEASE/cron"
sudo chown -R "${OWNER}:www-data" "$RELEASES_DIR"
sudo chown "${OWNER}:www-data" "$DIR/app"
sudo chmod 0775 "$DIR/app" -R

# Licenciamento e config
# cp ${DIR}/cs_licence ${RELEASE}/.cs_licence.bkp
# cp ${DIR}/cscfg ${RELEASE}/.cscfg.bkp

# crontab
sudo crontab -l -u $OWNER | echo -e "" | sudo crontab -u $OWNER -
sudo crontab -l -u $OWNER | cat - "$DIR/www/cron/crontab" | sudo crontab -u $OWNER -

# Manter somente as 5 ultimas versoes
echo -e "- Remover releases anteriores"
cd "$RELEASES_DIR"
sudo ls -dt ${RELEASES_DIR}/* | tail -n +6 | xargs -d "\n" sudo rm -rf;

# finalizar
echo -e "- Reiniciar serviços"
sudo service apache2 restart > /dev/null
sudo service php7.2-fpm restart > /dev/null

# clear
echo -e "\n### Versão $RELEASE_NAME instalada com sucesso!\n"
exit