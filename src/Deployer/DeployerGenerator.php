<?php

namespace NsUtil\Deployer;

class DeployerGenerator
{

    private $configs = [];
    private $gitlabCI = [];

    public function __construct($packageName, $packagePath, $templateShInstallPath = false)
    {
        $this->configs['packageName'] = $packageName;
        $this->configs['packagePath'] = $packagePath;
        $templateFile = __DIR__ . '/lib/deployDefault.sh';
        if ($templateShInstallPath && file_exists($templateShInstallPath)) {
            $templateFile = $templateShInstallPath;
        }
        $this->configs['deployTemplate'] = file_get_contents($templateFile);

        // gitlabCI template init
        $this->gitlabCI[0] = 'image: alpine:latest


stages:
    - deploy
  
variables:
   PACKAGE_NAME: ' . $packageName . '
   SSH_USER: deployer
';
    }

    /**
     * 
     * @param string $clientName Prefixo + nome da branch a ser processada no CI
     * @param string $pathOnServer caminho absoluto noservidor antes da pasta build, ex.: /var/www/nome_app (build esta em /var/www/nome_app/build)
     * @param string $ownerOnServer Dono dos arquivos no servidor (Ex.: ubuntu, debian, usuario especifico)
     * @param string $pathToKeySSH Caminho do arquivo para o SSH quando deploy for feito localmente
     * @param string $userDeployer Ususario que executará o deploy. Não necessáriamente precisa ser o proprietario. Ex.: deployer
     * @param mixed $host host para instalação ex.: www.meuapp.com.br, ou 172.0.0.1
     * @param bool $installCrontab default false, decide se deve instalar o arquivo em /cron/crontab para este deploy. Não deve ser utilizado para homologações. 
     * ATENÇÃO: Será instalado para o usuario dono dos arquivos. ex.: se for ubuntu e tiver varios apps, apagara os demais crontabs.
     * @param bool $sudoRequire Se true (default), sera aplicado sudo antes das instalaçãoes. Pode pedir senha caso usuario não tenha acesso ao sudo sem senha.
     * @return $this
     */
    public function addConfig($clientName, $pathOnServer, $ownerOnServer, $pathToKeySSH, $userDeployer, $host, bool $installCrontab = false, bool $sudoRequire = true)
    {
        // version
        $name = str_replace(' ', '_', $clientName);
        $this->configs['deployers'][] = [
            'cliente' => $name,
            'path' => $pathOnServer,
            'usuario' => $ownerOnServer,
            'key' => $pathToKeySSH,
            'userhost' => $userDeployer . '@' . $host,
            'host' => $host,
            'userDeployer' => $userDeployer,
            'sudo' => (($sudoRequire) ? 'sudo ' : ''),
            'decideinstallCrontab' => (($installCrontab) ? 'yes' : 'no')
        ];
        $this->gitlabCI[0] .= '
   # Configs to ' . $name . '
   SSH_HOST_' . $name . ': ' . $host . '
   SSH_PATH_' . $name . ': ' . $pathOnServer . '
   SSH_USER_' . $name . ': ' . $userDeployer . '
';
        return $this;
    }

    private function setDefaultScritps($pathDeployer)
    {
        $scripts = \NsUtil\DirectoryManipulation::openDir(__DIR__ . '/lib');
        \NsUtil\Helper::mkdir($pathDeployer . '/deploy/scripts');
        foreach ($scripts as $script) {
            $from = __DIR__ . '/lib/' . $script;
            $dest = $pathDeployer . '/deploy/scripts/' . $script;
            copy($from, $dest);
        }
    }

    /**
     * Gera os arquivo sh e runners
     * @param type $pathDeployer: Deve ser o mesmo diretório onde se encontra o "builder.php"
     */

    /**
     * Gera os arquivo sh e runners
     *
     * @param string $pathDeployer
     * @param string $phpVersion
     * @return void
     */
    public function run($pathDeployer, $phpVersion = '7.2')
    {
        $this->configs['pathDeployer'] = $pathDeployer;
        $this->setDefaultScritps($pathDeployer);
        foreach ($this->configs['deployers'] as $key => $val) {
            // Deployer default
            $val['packageName'] = $this->configs['packageName'];
            $val['php7.2-fpm'] = 'php' . $phpVersion . '-fpm';
            $template = str_replace(array_keys($val), array_values($val), $this->configs['deployTemplate']);

            // Validar se eh em producao
            $confirm = 'y';
            if (stripos($val['cliente'], 'producao') !== false || stripos($val['cliente'], 'master') !== false) {
                $confirm = 'Sim, producao';
            }

            // Save DeployerFile
            \NsUtil\Helper::saveFile($pathDeployer . '/deploy/sh/' . $val['cliente'] . '.sh', false, $template, 'SOBREPOR');

            // Add stage do gitlabCI
            $branchName = explode('_', $val['cliente'])[1];
            $geraZipCommand = '';
            $sshHost = '$SSH_HOST_' . $val['cliente']; // . ': $val['host'];
            $sshPath = '$SSH_PATH_' . $val['cliente']; //$val['path'];
            $sshDeployerFilename = '_build/install/deploy/sh/' . $val['cliente'] . '.sh';
            $sshUserDeployer = '$SSH_USER_' . $val['cliente'];
            $this->gitLabCI_AddStage($branchName, $sshHost, $sshPath, $sshDeployerFilename, $sshUserDeployer, $val);

            // Runner
            $template = "cd " . $val['path'] . "/build;
sudo ls
clear
sudo sh deploy.sh
/bin/bash
";
            \NsUtil\Helper::saveFile($pathDeployer . '/deploy/sh/' . $val['cliente'] . '-run.sh', false, $template, 'SOBREPOR');

            // Bat file
            $deployerFile = $pathDeployer . '/deploy/' . $val['cliente'] . '.bat';
            if (!file_exists($deployerFile)) {
                // Gerar o .bat para deployer
                $template = "@echo off
rem configurações básicas:
set deployname=$val[cliente]
set keyfile=$val[key]
set userhost=$val[userhost]
set destino=$val[path]

rem Não é necessário alterar aqui para baixo

SET /P AREYOUSURE=Continuar com deploy em %userhost% (%deployname%)? ($confirm/[n])?
IF /I \"%AREYOUSURE%\" NEQ \"$confirm\" GOTO END
    
rem Password to KeyFile SSH
SET /p KEYFILE_PWD= Passphrase for key \"imported-openssh-key\":
cls
echo ### %deployname% ###

php " . $pathDeployer . "\builder.php

echo Enviar para servidor: %deployname%
pscp -P 22 -i %keyfile% -pw \"%KEYFILE_PWD%\" " . $this->configs['packagePath'] . "\\" . $this->configs['packageName'] . " %userhost%:%destino%/build
pscp -P 22 -i %keyfile% -pw \"%KEYFILE_PWD%\" " . $pathDeployer . "\deploy\sh\%deployname%.sh %userhost%:%destino%/build/deploy.sh
plink -batch -i %keyfile% -pw \"%KEYFILE_PWD%\" %userhost% \"chmod +x %destino%/build/deploy.sh\"

rem V1: Quando sudo precisa de senha. Ira abrir o SSH terminal e executar o arquivo local no servidor
rem putty -ssh -i %keyfile% -pw \"%KEYFILE_PWD%\" %userhost% -m \"" . $pathDeployer . "\deploy\sh\%deployname%-run.sh\" -t
    
rem V2: Quando sudo é liberado sem senha. Igual ao CI Executa direto o comando no servidor, sem a necessidade de abrir um terminal
plink -batch -i %keyfile% -pw \"%KEYFILE_PWD%\" %userhost%  \"" . $val['sudo'] . "sh %destino%/build/deploy.sh\"

echo Concluido
timeout /t 15";

                \NsUtil\Helper::saveFile($deployerFile, false, $template, 'SOBREPOR');
            }
        }

        // Gerar o CI File
        $CIFILE = $this->configs['pathDeployer'] . '/../../.gitlab-ci.yml';
        if (!file_exists($CIFILE)) {
            \NsUtil\Helper::saveFile($CIFILE, false, implode("\n", $this->gitlabCI), 'SOBREPOR');
        }
    }

    private function gitLabCI_AddStage($branchName, $sshHost, $sshPath, $sshDeployerFilename, $sshUserDeployer, $item)
    {
        // Gerado pelo Package
        $zipCommand = file_get_contents($this->configs['pathDeployer'] . '/deploy/scripts/zipCommandToCI.sh');

        // Template to VM
        $this->gitlabCI[] = 'deploy_' . $branchName . ':
    stage: deploy
    only:
        - ' . $branchName . '
    before_script:
         - sh ./_build/install/deploy/scripts/before_simple.sh
         #- sh ./_build/install/deploy/scripts/before_php.sh
         #- alias composer="php composer.phar"
    script:
        # Preparar pacote
        - sh ./_build/install/deploy/scripts/zipCommandToCI.sh

        # Enviar arquivos
        - scp -p $CI_COMMIT_SHA.zip ' . $sshUserDeployer . '@' . $sshHost . ':' . $sshPath . '/build/$PACKAGE_NAME
        - scp -p ' . $sshDeployerFilename . ' ' . $sshUserDeployer . '@' . $sshHost . ':' . $sshPath . '/build/deploy.sh
        - ssh ' . $sshUserDeployer . '@' . $sshHost . ' "' . $item['sudo'] . 'chmod +x ' . $sshPath . '/build/deploy.sh"
        # Executar instalação
        ### - ssh ' . $sshUserDeployer . '@' . $sshHost . ' "' . $item['sudo'] . ' tr -d \'\r\' < ' . $sshPath . '/build/deploy.sh > ' . $sshPath . '/build/deploy.sh"
        - ssh ' . $sshUserDeployer . '@' . $sshHost . ' "' . $item['sudo'] . 'sh ' . $sshPath . '/build/deploy.sh"        
';
    }

}
