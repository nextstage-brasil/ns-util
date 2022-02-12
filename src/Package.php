<?php

namespace NsUtil;

class Package {

    static $zipExcluded = '';
    private static $urlLocalApplication = '';

    public function __construct() {
        
    }

    /**
     * 
     * @param string $urlLocalApplication URL local da aplicação (Completo, ex.: http://localhost:5088). Default: https://localhost/{PATH_APP}
     * @return void
     */
    public static function setUrlLocalApplication(string $urlLocalApplication = ''): void {
        self::$urlLocalApplication = $urlLocalApplication;
    }

    public static function setVersion($file, $message = 'default/Not defined', int $major_increment = null, int $minor_increment = null, int $path_increment = null) {
        if (!file_exists($file)) {
            file_put_contents($file, '1.0.0');
        }

        // $versionamento com base nas mensagens
        $exp = explode('/', $message);
        $v = explode('.', file_get_contents($file));
        $X = (int) filter_var($v[0], FILTER_SANITIZE_NUMBER_INT);
        $Y = (int) filter_var($v[1], FILTER_SANITIZE_NUMBER_INT);
        $Z = (int) filter_var($v[2], FILTER_SANITIZE_NUMBER_INT);

        if ($exp[0] === 'version' || $exp[0] === 'release') {
            $X += (($major_increment !== null) ? $major_increment : 1);
            $Y = $Z = 0;
        } else if ($exp[0] === 'feature') {
            $Y += (($minor_increment !== null) ? $minor_increment : 1);
            $Z = 0;
        } else {
            $Z += (($path_increment !== null) ? $path_increment : 1);
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
            $init = substr($path_versionNew, 0, 2);
        }

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
            . " git tag $X.$Y.$Z HEAD &&"
            //            . " git push --tags &&"
            //            . "echo \"VERSAO CRIADA E COMMITADA. TAG CRIADA.\""
            . "timeout /t 10",
            'git' => [
                'local' => (isset(($init)) ? true : false),
                'cd' => "cd $path_versionNew",
                'add' => "git add . ",
                'commit' => "git commit -m \"$message\" ",
                'tag' => "git tag -a $X.$Y.$Z HEAD",
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
            string $patch7zip = 'C:\Program Files\7-Zip\7z.exe'
    ) {
        if (Helper::getSO() !== 'windows') {
            die('ERROR: Este método é exclusivo para uso em ambiente Windows');
        }

        if (!file_exists($patch7zip)) {
            die('ERROR: Executável do 7z não localizado. Path: ' . $patch7zip);
        }

        date_default_timezone_set('America/Recife');
        Helper::directorySeparator($dirOutput);

        // projectName
        $fontes = str_replace('/', DIRECTORY_SEPARATOR, $origem);
        $t = explode(DIRECTORY_SEPARATOR, $fontes);
        $projectName = mb_strtolower(array_pop($t));

        // versao: nunca irá incrementar além da data e hora
        $file = $fontes . '/version';
        $versao = self::setVersion($file, 'default/Package', 0, 0, 0)['version_full'];

        // composer
        if (file_exists($origem . '/composer.json')) {
            echo " - Atualizando pacotes via composer ...";
            shell_exec('composer update -q --prefer-dist --optimize-autoloader --no-dev --working-dir="' . $origem . '"');
        }

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

        // Definição do URL da aplicação
        $urlLocalApplication = self::$urlLocalApplication;
        if ($urlLocalApplication === '') {
            $urlLocalApplication = "https://localhost/$projectName";
        }



        echo "\n - Construindo aplicacao ... ";
        $ret = Helper::curlCall("$urlLocalApplication/$build/builder.php?pack=true", [], 'GET', [], false);
        echo $ret->status;
        if ((int) $ret->status !== 200) {
            var_export($ret);
            die("\n################## ERROR!!: #################### \n\n STATUS BUILDER <> 200 \n\n###########################################\n");
        }

        echo "\n - Construindo JS e Componentes ...";
        $ret = Helper::curlCall("$urlLocalApplication/$build/compile.php?pack=true&compileToBuild=YES&recompile=ALL", [], 'GET', [], false);
        echo $ret->status;
        if ((int) $ret->status !== 200) {
            var_export($ret);
            die("\n################## ERROR!!: #################### \n\n STATUS COMPILE <> 200 \n\n###########################################\n");
        }


        // zip file
        $zip = $dirOutput . DIRECTORY_SEPARATOR . $projectName . '-package.zip';
        $encodedFile = $dirOutput . DIRECTORY_SEPARATOR . $projectName . '-encoded';
        Helper::deleteFile($zip);
        Helper::deleteFile($encodedFile);

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
                    'zipCi' => 'zip -qr $CI_COMMIT_SHA.zip . ' . $exCI,
                    'ex' => $exCI
        ];
        Helper::saveFile("$origem/$build/install/deploy/zip/zipCommandToCI.sh", false, self::$zipExcluded->zipCi, 'SOBREPOR');

        // salvar o comand para o pos ioncube
        echo "\n - Criado arquivo post encode para ioncube ...";
        Helper::directorySeparator($ioncube_post);
        $tmp = explode(DIRECTORY_SEPARATOR, $ioncube_post);
        $filenamePost = array_pop($tmp);
        $ioncube_post_dir = implode(DIRECTORY_SEPARATOR, $tmp);

        $bat = $dirOutput . DIRECTORY_SEPARATOR . $filenamePost;

        file_put_contents($bat, '@echo OFF
    del ' . $encodedFile . '.zip /q
    "' . $patch7zip . '" a ' . $encodedFile . '.zip ' . $encodedFile . '\* ' . $ex . ' > nul
    rmdir ' . $encodedFile . ' /s /q
	');
        shell_exec("move $bat $ioncube_post_dir");

        sleep(0.2);

        //## "C:\Program Files (x86)\WinSCP\WinSCP.exe"

        $command .= $ex;

        echo "\n - Criando pacote ...";
        shell_exec($command);

        echo "\nLimpando arquivos ...";
        shell_exec("del $fontes" . DIRECTORY_SEPARATOR . "*XPTO* /s > nul");
        shell_exec("del $fontes" . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "_45h" . DIRECTORY_SEPARATOR . "*.php > nul");

        // Abrir diretorio de saida
        $zipdir = explode(DIRECTORY_SEPARATOR, $zip);
        array_pop($zipdir);
        //shell_exec("explorer " . implode(DIRECTORY_SEPARATOR, $zipdir));

        echo "\n Versao '$versao' criada com sucesso!  \n";
        echo "------------- \n";

        return $projectName;
    }

    static function getZipExcluded() {
        return self::$zipExcluded;
    }

}
