<?php

namespace NsUtil;

class Package {

    static $zipExcluded = '';
    private static $urlLocalApplication = '';
    private static $projectName = null;
    private static $createFrontendFiles = false;
    private static $dockerBuildParams = [];

    public function __construct() {
        
    }

    public static function setCreateFrontendFiles(bool $createFrontendFiles): void {
        self::$createFrontendFiles = $createFrontendFiles;
    }

    /**
     * Prepara o cenário para execução do Dockerfile
     * @param string $pathDockerfile Caminho absoluto para o arquivo Dockerfile
     * @param string $dockerHubUser Nome do usuário no docker hub
     * @param string $packageName nome do pacote. Ex.: myapp
     * @param string $tag Versão da imagem
     * @param array $args Argumentos para construção da imagem
     * @return void
     */
    public static function setDockerBuildParams(string $pathDockerfile, string $dockerHubUser, string $packageName, string $tag = 'latest', array $args = []): void {
        self::$dockerBuildParams = [
            'Dockerfile' => $pathDockerfile,
            'Username' => $dockerHubUser . ((strlen($dockerHubUser) > 0) ? '/' : '') . $packageName,
            'Args' => $args,
            'Tag' => $tag
        ];
    }

    /**
     * 
     * @param string $urlLocalApplication URL local da aplicação (Completo, ex.: http://localhost:5088). Default: https://localhost/{PATH_APP}
     * @return void
     */
    public static function setUrlLocalApplication(string $urlLocalApplication = ''): void {
        self::$urlLocalApplication = $urlLocalApplication;
    }

    public static function getProjectName() {
        return self::$projectName;
    }

    public static function setProjectName($projectName): void {
        self::$projectName = $projectName;
    }

    public static function setVersion($file, $message = 'default/Not defined', int $major_increment = null, int $minor_increment = null, int $path_increment = null) {
        if (!file_exists($file)) {
            file_put_contents($file, '1.0.0');
        }

        // $versionamento com base nas mensagens
        $exp = explode('/', $message);
        $content = file_get_contents($file);
        $createTag = true;

        $v = explode('.', $content);

        $X = (int) filter_var($v[0], FILTER_SANITIZE_NUMBER_INT);
        $Y = (int) filter_var($v[1], FILTER_SANITIZE_NUMBER_INT);
        $Z = (int) filter_var($v[2], FILTER_SANITIZE_NUMBER_INT);

        switch ($exp[0]) {
            case 'version':
            case 'release':
                $X += (($major_increment !== null) ? $major_increment : 1);
                $Y = $Z = 0;
                break;
            case 'feature':
                $Y += (($minor_increment !== null) ? $minor_increment : 1);
                $Z = 0;
                break;
            case 'bugfix':
            case 'fix':
                $Z += (($path_increment !== null) ? $path_increment : 1);
                break;
            default:
                $createTag = false;
                break;
        }

        $versao = "$X.$Y.$Z." . date('YmdHi');
        file_put_contents($file, $versao);

        // GIT
        $path_version = $file;
        Helper::directorySeparator($path_version);
        $itens = explode(DIRECTORY_SEPARATOR, $path_version);
        array_pop($itens);
        $path_versionNew = implode(DIRECTORY_SEPARATOR, $itens);

        if (Helper::getSO() === 'windows') {
            $init = substr((string) $path_versionNew, 0, 2);
        }

        $tag = $createTag ? " git tag -a $X.$Y.$Z HEAD" : "type ls)";
        return [
            'version' => "$X.$Y.$Z",
            'version_full' => $versao,
            'path' => $path_versionNew,
            'init' => (isset(($init)) ? $init . " &&" : ""),
            'bat' => ""
            . (isset(($init)) ? $init . " &&" : "")
            . " cd $path_versionNew &&"
            . " git add .  &&"
            . " git commit -m \"$message\" && "
            . $tag . " &&"
            . "timeout /t 10",
            'git' => [
                'local' => (isset(($init)) ? true : false),
                'cd' => "cd $path_versionNew",
                'add' => "git add . ",
                'commit' => "git commit -m \"$message\" ",
                'tag' => $tag,
                'push' => "git push --tags",
                'timeout' => 'timeout /t 10'
            ]
        ];
    }

    public static function git($file, $message = 'default/Not defined', $major = null, $minor = null, $path = null) {
        $ret = self::setVersion($file, $message, $major, $minor, $path);
        //        $template = implode(PHP_EOL, $ret['git']);
        //        $filegit = $ret['path'] . '/__git.bat';
        //        Helper::saveFile($filegit, false, $template, 'SOBREPOR');
        shell_exec($ret['bat']);
        return $ret;
    }

    /**
     * 
     * @param string $origem Path de origem dos arquivos a ser empacotados. 
     * @param array $excluded_x array com os patterns a ser excluido no zip. 
     * @param string $dirOutput Path onde devo salvar o .zip de saida
     * @param string $ioncube_post Path para salvar o .bat de post-encoded para ioncube
     * @param string $patch7zip Path para o aplicativo de ZIP
     */
    public static function run(
            string $origem,
            array $excluded_x,
            string $dirOutput,
            string $ioncube_post,
            string $patch7zip = ''
    ) {

        $cmdsConfig = [
            'linux' => [
                'clearFiles' => 'find %1$s -name "*XPTO*" -delete && find %1$s/app/_45h -name "*.php" -delete && find %1$s/app/45h -name "*.php" -delete',
                'move' => 'mv'
            ],
            'windows' => [
                'clearFiles' => 'del "%1$s\*XPTO*" /s /q > nul && del "%1$s\app\_45h\*.php" > nul && del "%1$s\app\45h\*.php" > nul',
                'move' => 'move'
            ]
        ];

//        if (Helper::getSO() !== 'windows') {
//            die('ERROR: Este método é exclusivo para uso em ambiente Windows');
//        }

        if (strlen($patch7zip) === 0) {
            $patch7zip = ((\NsUtil\Helper::getSO() === 'windows') ? 'C:\Program Files\7-Zip\7z.exe' : 'zip');
        }
        if (
                (\NsUtil\Helper::getSO() === 'windows' && !file_exists($patch7zip)) ||
                (\NsUtil\Helper::getSO() === 'linux' && stripos(shell_exec('type ' . $patch7zip), 'not found') > -1 )
        ) {
            die('ERROR: Executável do 7z não localizado. Path: ' . $patch7zip);
        }

        date_default_timezone_set('America/Recife');
        Helper::directorySeparator($dirOutput);
        $dirOutput = realpath($dirOutput);

        // Fontes
        $fontes = str_replace('/', DIRECTORY_SEPARATOR, $origem);

        // projectName
        if (null === self::getProjectName()) {
            $t = explode(DIRECTORY_SEPARATOR, $fontes);
            $projectName = mb_strtolower(array_pop($t));
            self::setProjectName($projectName);
        }
        $projectName = self::getProjectName();

        // versao: nunca irá incrementar além da data e hora
        $file = $fontes . '/version';
        $versao = self::setVersion($file, 'default/Package', 0, 0, 0)['version_full'];

        // builder and compile
        switch (true) {
            case (is_dir($fontes . DIRECTORY_SEPARATOR . '_build')):
                $build = '_build';
                break;
            case (is_dir($fontes . DIRECTORY_SEPARATOR . '.build')):
                $build = '.build';
                break;
            case (is_dir($fontes . DIRECTORY_SEPARATOR . 'build')):
                $build = 'build';
                break;
            default:
                die('Build directory not found!');
                break;
        }
        $buildDir = realpath($fontes . DIRECTORY_SEPARATOR . $build);

        // Definição do URL da aplicação
        $urlLocalApplication = self::$urlLocalApplication;
        if ($urlLocalApplication === '') {
            $urlLocalApplication = "https://localhost/$projectName";
        }

        // zip file
        $zip = $dirOutput . DIRECTORY_SEPARATOR . $projectName . '-package.zip';
        $encodedFile = $dirOutput . DIRECTORY_SEPARATOR . $projectName . '-encoded';
        Helper::deleteFile($zip);
        Helper::deleteFile($encodedFile);

        // Nomes
        echo "\n### NSUtil Package Generator ###"
        . "\n - Configurations: "
        . "\n Running on $urlLocalApplication"
        . "\n PHP Version: " . PHP_VERSION . " on " . Helper::getSO()
        . "\n Package output: " . $dirOutput . DIRECTORY_SEPARATOR . $projectName . '-package.zip'
        . "\n Create docker image?: " . ((count(self::$dockerBuildParams) > 0) ? 'yes' : 'no')
        . "\n Copy frontend files?: " . ((self::$createFrontendFiles === true) ? 'yes' : 'no')
        . "\n Alright, let's run!"
        . "\n\n"
        ;

        // composer
        $last = 0;
        if (file_exists($buildDir . '/.lastComposerUpdate')) {
            $last = (int) file_get_contents($buildDir . '/.lastComposerUpdate');
        }
        $composerIsOld = (!file_exists($buildDir . '/.lastComposerUpdate')) || $last < time() - (60 * 60 * 2);
        echo "\n - Atualizando pacotes via composer ... ";
        if (file_exists($origem . '/composer.json') && $composerIsOld) {
            shell_exec('composer update -q --prefer-dist --optimize-autoloader --no-dev --working-dir="' . $origem . '"');
            file_put_contents($buildDir . '/.lastComposerUpdate', time());
        }
        echo "OK! Is updated at " . date('Y-m-d H:i:s', (int) file_get_contents($buildDir . '/.lastComposerUpdate'));

        echo "\n - Construindo aplicacao ... ";
        $ret = Helper::curlCall("$urlLocalApplication/$build/builder.php?pack=true", [], 'GET', [], false, (60 * 10));
        if ((int) $ret->status !== 200 || stripos(json_encode($ret), 'Fatal error') !== false) {
            var_export($ret);
            die("\n################## ERROR!!: #################### \n\n STATUS BUILDER <> 200 \n\n###########################################\n");
        }
        echo 'OK!';

        echo "\n - Construindo JS e Componentes ... ";
        $ret = Helper::curlCall("$urlLocalApplication/$build/compile.php?pack=true&compileToBuild=YES&recompile=ALL", [], 'GET', [], false, (60 * 10));
        if ((int) $ret->status !== 200 || stripos(json_encode($ret), 'Fatal error') !== false) {
            var_export($ret);
            die("\n################## ERROR!!: #################### \n\n STATUS COMPILE <> 200 \n\n###########################################\n");
        }
        echo 'OK!';

        // Lista de exclusões que não devem constar em builds
        $excluded_xr = [
            //'*.htaccess',
            '*/phpunit/*',
            '*/.*/',
            '*.dev*',
            '*.git*',
            '*/test/*',
            '*/teste/*',
            '*teste.*',
            '*.test',
            '*.phpintel*',
            '*.trash*',
            '*_build*',
            '*.build*',
            '*.github*',
            '*nbproject*',
            '*.gitignore*',
            '*XPTO*',
            '*__NEW__*',
            '*_OLD*',
            '*/samples/*',
            '*/docs/*',
            '*/.github/*',
            '*/example/*',
            '*/demo/*',
            'info.php',
            '*teste.php',
//            '*composer.lock*',
            '/.env',
            '/.env.example',
            '*serverless*'
        ];
        $excluded_x = array_merge([
            'sch.php',
            'ingest/',
            'storage/',
            'st/',
            '_app/',
            'app/',
            'test/',
            '.gitlab/'
                ], $excluded_x);
        $ex = $exCI = '';
        $command = '"' . $patch7zip . '"' . ' a ' . $zip . ' ' . $fontes . '\* ';
        foreach ($excluded_xr as $item) {
            $ex .= " -xr!$item";
            $exCI .= ' -x "' . $item . '"';
        }
        foreach ($excluded_x as $item) {
            $ex .= " -x!$item";
            $exCI .= ' -x "' . $item . '*"';
        }

        // Salvar o comando para gerar o ZIP limpo tbem no CI
        self::$zipExcluded = (object) [
                    'zipCi' => "#!/bin/bash\nzip -qr \$CI_COMMIT_SHA.zip . $exCI",
                    'ex' => $exCI
        ];
        Helper::saveFile("$origem/$build/install/deploy/scripts/zipCommandToCI.sh", false, self::$zipExcluded->zipCi, 'SOBREPOR');

        // salvar o comand para o pos ioncube
        echo "\n - Criando arquivo post encode para ioncube ... ";
        Helper::directorySeparator($ioncube_post);
        Helper::deleteFile($ioncube_post);
        $contentIoncubepost = '@echo OFF
    del ' . $encodedFile . '.zip /q
    "' . $patch7zip . '" a ' . $encodedFile . '.zip ' . $encodedFile . '\* ' . $ex . ' > nul
    rmdir ' . $encodedFile . ' /s /q
	';
        Helper::saveFile($ioncube_post, false, $contentIoncubepost, "SOBREPOR");
        sleep(0.2);
        echo ((file_exists($ioncube_post)) ? "OK!" : "Erro ao gerar arquivo $ioncube_post");

        echo "\n - Criando pacote ... ";
        $command .= $ex;
        shell_exec($command);
        echo "OK!";

        echo "\n - Limpando arquivos ... ";
        $cmd = sprintf($cmdsConfig[Helper::getSO()]['clearFiles'], $fontes);
        shell_exec($cmd);
        echo "OK!";

        // Abrir diretorio de saida
        $zipdir = explode(DIRECTORY_SEPARATOR, $zip);
        array_pop($zipdir);

        // Criação do frontend
        if (self::$createFrontendFiles === true) {
            echo "\n Criação dos Path de frontend";
            self::copyFilesToAppView($origem, $buildDir . '/@WebAPP');
        }

        // Criação do Dockerfile
        self::dockerBuilder();

        echo "\n\n ### Package '$versao' was created successfully!! ###"
        . "\n--------------------------------------\n";
        return $projectName;
    }

    static function getZipExcluded() {
        return self::$zipExcluded;
    }

    /**
     * 
     * @param string $applicationPath Caminho absoluto da raiz da aplicação
     * @param string $destPath Caminho absoluto para salvar os arquivos
     * @param array $pathsToCopy Diretorios a ser copiados, com razao a partir da raiz. Ex.: ['view/images', 'view/css']
     * @param bool $clearOldVersion Se deve limpar o diretório destino antes de iniciar
     * @return void
     */
    static function copyFilesToAppView(string $applicationPath, string $destPath, array $pathsToCopy = [], bool $clearOldVersion = true): void {
        echo "\n - Criando aplicação frontend local ...";
        $listToCopy = array_merge(
                ['view/css', 'view/images', 'view/fonts', 'view/audio', 'view/angular-file-upload-full_3', 'auto/components', 'node_modules', 'package.json']
                , $pathsToCopy
        );

        // Limpar instalações anteriores
        if ($clearOldVersion && is_dir($destPath)) {
            Helper::deleteDir($destPath);
        }
        Helper::mkdir($destPath);

        // Copiar
        foreach ($listToCopy as $directory) {
            echo "\n\t $directory ... ";
            $src = $applicationPath . DIRECTORY_SEPARATOR . $directory;
            $dst = $destPath . DIRECTORY_SEPARATOR . $directory;
            Helper::directorySeparator($src);
            Helper::directorySeparator($dst);
            switch (true) {
                case is_file($src) && file_exists($src):
                    copy($src, $dst);
                    echo ((file_exists($dst)) ? 'OK!' : 'ERROR!');
                    break;
                case is_dir($src):
                    \NsUtil\DirectoryManipulation::recurseCopy($src, $dst);
                    echo ((is_dir($dst)) ? 'OK!' : 'ERROR!');
                    break;
                default:
                    echo " ERROR!";
                    break;
            }
        }


        // Copiar envs
        $files = [
            'auto/webapp.html' => 'index.html',
            '_build/env/env.develop.js' => 'env.js',
        ];
        foreach ($files as $orig => $dest) {
            $origem = $applicationPath . DIRECTORY_SEPARATOR . $orig;
            $destino = $destPath . DIRECTORY_SEPARATOR . $dest;
            Helper::directorySeparator($origem);
            Helper::directorySeparator($destino);
            if (is_file($origem)) {
                copy($origem, $destino);
            }
        }

        echo "\n\t OK! Frontend files is created!";
        sleep(2);
    }

    static function dockerBuilder(): void {
        if (count(self::$dockerBuildParams) > 0) {
            $arguments = '';
            foreach (self::$dockerBuildParams['Args'] as $key => $val) {
                $arguments .= " --build-arg $key=\"$val\"";
            }

            // Gerar imagem Docker local
            echo "\n - Construindo imagem docker ... ";
            $dockerCMD = 'docker build '
                    . $arguments
                    . " --quiet"
                    . ' -t ' . self::$dockerBuildParams['Username'] . ":" . self::$dockerBuildParams['Tag']
                    . ' '
                    . '"' . self::$dockerBuildParams['Dockerfile'] . '/."'
            ;
            exec($dockerCMD);
            echo "OK!";
        }
    }

}
