<?php

namespace NsUtil\Deployer;

class DeployerGenerator {

    private $configs = [];
    private $gitlabCI = [];

    public function __construct($packageName, $packagePath, $templateShInstallPath = false) {
        $this->configs['packageName'] = $packageName;
        $this->configs['packagePath'] = $packagePath;
        $templateFile = __DIR__ . '/lib/deployDefault.sh';
        if ($templateShInstallPath && file_exists($templateShInstallPath)) {
            $templateFile = $templateShInstallPath;
        }
        $this->configs['deployTemplate'] = file_get_contents($templateFile);

        // gitlabCI template init
        $this->gitlabCI[0] = '
image: ubuntu:latest

stages:
    - deploy
  
variables:
   PACKAGE_NAME: ' . $packageName . '
   SSH_USER: deployer
';
    }

    public function addConfig($clientName, $pathOnServer, $ownerOnServer, $pathToKeySSH, $userDeployer, $host) {
        $name = str_replace(' ', '_', $clientName);
        $this->configs['deployers'][] = [
            'cliente' => $name,
            'path' => $pathOnServer,
            'usuario' => $ownerOnServer,
            'key' => $pathToKeySSH,
            'userhost' => $userDeployer . '@' . $host,
            'host' => $host
        ];
        $this->gitlabCI[0] .= '
   # Configs to ' . $name . '
   SSH_HOST_' . $name . ': ' . $host . '
   SSH_PATH_' . $name . ': ' . $pathOnServer . '
';
        return $this;
    }

    /**
     * Gera os arquivo sh e runners
     * @param type $pathDeployer: Deve ser o mesmo diretório onde se encontra o "builder.php"
     */
    public function run($pathDeployer, $phpVersion = '7.2') {
        $this->configs['pathDeployer'] = $pathDeployer;
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
            $sshHost = '$SSH_HOST_' . $val['cliente'];// . ': $val['host'];
            $sshPath = '$SSH_PATH_' . $val['cliente'];//$val['path'];
            $sshDeployerFilename = '_build/install/deploy/sh/' . $val['cliente'] . '.sh';
            $this->gitLabCI_AddStage($branchName, $sshHost, $sshPath, $sshDeployerFilename);

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

echo ### %deployname% ###

SET /P AREYOUSURE=Continuar com deploy em %userhost% (%deployname%)? ($confirm/[n])?
IF /I \"%AREYOUSURE%\" NEQ \"$confirm\" GOTO END

php " . $pathDeployer . "\builder.php

echo Enviar para servidor: %deployname%
pscp -P 22 -i %keyfile% " . $this->configs['packagePath'] . "\\" . $this->configs['packageName'] . " %userhost%:%destino%/build
pscp -P 22 -i %keyfile% " . $pathDeployer . "\deploy\sh\%deployname%.sh %userhost%:%destino%/build/deploy.sh
plink -batch -i %keyfile% %userhost% \"chmod +x %destino%/build/deploy.sh\"

putty -ssh -i %keyfile% %userhost% -m \"" . $pathDeployer . "\deploy\sh\%deployname%-run.sh\" -t

echo Concluido
timeout /t 15";
                \NsUtil\Helper::saveFile($deployerFile, false, $template, 'SOBREPOR');
            }
        }

        // Gerar o CI File
        \NsUtil\Helper::saveFile($this->configs['pathDeployer'] . '/../../.gitlab-ci.yml', false, implode("\n", $this->gitlabCI), 'SOBREPOR');
    }

    private function gitLabCI_AddStage($branchName, $sshHost, $sshPath, $sshDeployerFilename) {
        // Gerado pelo Package
        $zipCommand = file_get_contents($this->configs['pathDeployer'] . '/deploy/zip/zipCommandToCI.sh');

        // Template
        $this->gitlabCI[] = 'deploy_' . $branchName . ':
    stage: deploy
    only:
        - ' . $branchName . '
    before_script:
        - \'which ssh-agent || ( apt-get update -y && apt-get install openssh-client zip -y )\'
        - eval $(ssh-agent -s)
        - echo "$SSH_PRIVATE_KEY" | tr -d \'\r\' | ssh-add -
        - mkdir -p ~/.ssh
        - chmod 700 ~/.ssh
        - \'[[ -f /.dockerenv ]] && echo -e "Host *\n\tStrictHostKeyChecking no\n\n" >> ~/.ssh/config\'
    script:
        # Preparar pacote
        #- cp ".env.prod.php" ".env.php"
        - ' . $zipCommand . '

        # Enviar arquivos
        - scp -p $CI_COMMIT_SHA.zip $SSH_USER@' . $sshHost . ':' . $sshPath . '/build/$PACKAGE_NAME
        - scp -p ' . $sshDeployerFilename . ' $SSH_USER@' . $sshHost . ':' . $sshPath . '/build/deploy.sh
        - ssh $SSH_USER@' . $sshHost . ' "sudo chmod +x ' . $sshPath . '/build/deploy.sh"

        # Executar instalação
        - ssh $SSH_USER@' . $sshHost . ' "sudo sh ' . $sshPath . '/build/deploy.sh"
        
        # limpeza da instalação
        - ssh $SSH_USER@' . $sshHost . ' "sudo composer install -q --prefer-dist --optimize-autoloader --no-dev --working-dir=' . $sshPath . '/www"
        # - ssh $SSH_USER@' . $sshHost . ' "sudo ln -nfs ' . $sshPath . '/www /var/www/html/util"
';
    }

}
