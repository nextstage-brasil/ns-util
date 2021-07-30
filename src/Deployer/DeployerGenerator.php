<?php

namespace NsUtil\Deployer;

class DeployerGenerator {

    private $configs = [];

    public function __construct($packageName, $packagePath, $templateShInstallPath = false) {
        $this->configs['packageName'] = $packageName;
        $this->configs['packagePath'] = $packagePath;
        $templateFile = __DIR__ . '/lib/deployDefault.sh';
        if ($templateShInstallPath && file_exists($templateShInstallPath)) {
            $templateFile = $templateShInstallPath;
        }
        $this->configs['deployTemplate'] = file_get_contents($templateFile);
    }

    public function addConfig($clientName, $pathOnServer, $ownerOnServer, $pathToKeySSH, $userDeployer, $host) {
        $this->configs['deployers'][] = [
            'cliente' => \NsUtil\Helper::sanitize($clientName),
            'path' => $pathOnServer,
            'usuario' => $ownerOnServer,
            'key' => $pathToKeySSH,
            'userhost' => $userDeployer . '@' . $host
        ];
        return $this;
    }

    /**
     * Gera os arquivo sh e runners
     * @param type $pathDeployer: Deve ser o mesmo diretório onde se encontra o "builder.php"
     */
    public function run($pathDeployer, $phpVersion = '7.2') {

        foreach ($this->configs['deployers'] as $key => $val) {
            // Deployer default
            $val['packageName'] = $this->configs['packageName'];
            $val['php7.2-fpm'] = 'php' . $phpVersion . '-fpm';
            $template = str_replace(array_keys($val), array_values($val), $this->configs['deployTemplate']);

            // Validar se eh em producao
            $confirm = 'y';
            if (stripos($val['cliente'], 'producao') !== false) {
                $confirm = 'Sim, producao';
            }

            \NsUtil\Helper::saveFile($pathDeployer . '/deploy/sh/' . $val['cliente'] . '.sh', false, $template, 'SOBREPOR');

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
    }

}
