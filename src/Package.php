<?php

namespace NsUtil;

class Package {

    public function __construct() {
        
    }

    /**
     * 
     * @param string $origem Path de origem dos arquivos a ser empacotados. 
     * @param array $excluded_x array com os patterns a ser excluido no zip. 
     * @param string $dirOutput Path onde devo salvar o .zip de saida
     * @param string $ioncube_post Path para salvar o .bat de post-encoded para ioncube
     * @param string $patch7zip Path para o aplicativo de ZIP
     */
    public static function run(string $origem,
            array $excluded_x,
            string $dirOutput,
            string $ioncube_post,
            string $patch7zip = 'C:\Program Files\7-Zip\7z.exe') {
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

        // versao
        //X é a versão Maior, Y é a versão Menor, e Z é a versão de Correção.
        $file = $fontes . '/version';
        if (!file_exists($file)) {
            file_put_contents($file, '1.0.0');
        }
        $v = explode('.', file_get_contents($file));
        $X = filter_var($v[0], FILTER_SANITIZE_NUMBER_INT);
        $Y = $v[1];
        $Z = (int) $v[2] + 1;
        $Z = (int) $v[2];
        $D = date('c');
        $versao = "$X.$Y.$Z." . date('YmdHi');
        file_put_contents($file, $versao);

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
                die('Diretorio build não definido');
                break;
        }
        echo " - Construindo aplicacao ... ";
        $ret = Helper::curlCall("https://localhost/$projectName/$build/builder.php?pack=true", [], 'GET', [], false);
        echo $ret->status;
        if ((int) $ret->status !== 200) {
            //var_export($ret);
            die("\n################## ERROR!!: #################### \n\n STATUS BUILDER <> 200 \n\n###########################################\n");
        }

        echo "\n - Compilando JS e components ...";
        $ret = Helper::curlCall("https://localhost/$projectName/$build/compile.php?pack=true&compileToBuild=YES&recompile=ALL", [], 'GET', [], false);
        echo $ret->status;
        if ((int) $ret->status !== 200) {
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
            '*OLD*',
            '*/samples/*',
            '*/docs/*',
            '*/.github/*',
            '*/example/*',
            '*/demo/*',
            'info.php',
            '*teste.php',
            '*composer.lock*',
        ];
        $excluded_x = array_merge([
            'sch.php',
            'ingest/',
            'storage/',
            'st/',
            '_app/',
            'app/',
            'test/'
                ], $excluded_x);

        $command = '"' . $patch7zip . '"' . ' a ' . $zip . ' ' . $fontes . '\* ';
        foreach ($excluded_xr as $item) {
            $ex .= " -xr!$item";
        }
        foreach ($excluded_x as $item) {
            $ex .= " -x!$item";
        }

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

        echo "\n Version '$versao' criada com sucesso!  \n";
        echo "------------- \n";
    }

}
