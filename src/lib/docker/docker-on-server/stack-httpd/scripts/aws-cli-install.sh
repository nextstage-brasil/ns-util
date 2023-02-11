#!/bin/bash

clear
if [ ! $(id -u) -eq 0 ]; then
    echo "## ERROR ## "
    echo "To continue, run this script with sudo"
    echo ""
    exit 2
fi

cd /tmp
apt update && apt install curl zip unzip -y
rm -R aws
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
./aws/install --update

echo ""
echo ">>> Lets configure: "
sleep 2

aws configure