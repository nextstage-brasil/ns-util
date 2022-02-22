<?php

namespace NsUtil;

use Exception;

class LoadCSVToArray {

    private $file;

    public function __construct($filename) {
        $this->file = realpath($filename);
    }

    /**
     * 
     * @param string $file_or_dir Diretorio ou CSV que deve ser ingerido
     * @param string $tablename - Caso false, será utilizado o nome do arquivo CSV sanitizado
     * @return boolean
     */
    public function run() {
        $types = array('csv', 'xlsx');
        $ext = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
        if (in_array($ext, $types)) {
            // Caso seja xlsx, converter para CSV usando lib python
            if ($ext === 'xlsx') {
                $ret = shell_exec('type xlsx2csv');
                if (stripos($ret, 'not found') > -1) {
                    $error = "### ATENÇÃO ### \nBiblioteca xlsx2csv não esta instalada. "
                            . "\nPara continuar, execute: 'sudo apt-get update && sudo apt-get install -y xlsx2csv'"
                            . "\n"
                            . "###############"
                            . "\n\n\n";
                    throw new Exception($error);
                }
                $t = explode(DIRECTORY_SEPARATOR, $this->file);
                $csv = '/tmp/' . str_replace('.xlsx', '.csv', array_pop($t));
                $csv_error = shell_exec("xlsx2csv $file $csv");
                if (!file_exists($csv)) {
                    $error = 'Ocorreu erro ao converter arquivo XLSX para CSV: ' . $csv_error;
                    throw new Exception($error);
                }
                $file = $csv;
            }
        } else {
            throw new Exception('Tipo de arquivo não configurado');
        }
        
        // Converter para UTF-8
        Helper::fileConvertToUtf8($this->file);
        
        
        if (($handle = fopen($this->file, "r")) !== false) {
            // Definir explode
            $fh = fopen($this->file, "rb");
            $data = fgetcsv($fh, 1000, '\\');
            fclose($fh);
            $explode = ',';
            if (stripos($data[0], ';') > 0) {
                $explode = ';';
            }
            if (stripos($data[0], "\t") > 0) {
                $explode = "\t";
            }
            $out = [];
            while (($ret = Helper::myFGetsCsv($handle, $explode)) !== false) {
                $out[] = $ret;
            }

            return $out;
        } else {
            $error = 'Não foi possivel abrir o arquivo indicado';
            throw new Exception($error);
        }
    }

}
