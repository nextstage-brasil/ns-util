<?php

namespace NsUtil;

use Exception;

class LoadCSVToArray
{

    private $file;

    public function __construct($filename)
    {
        $this->file = realpath($filename);
    }

    public function getFile()
    {
        return $this->file;
    }



    /**
     * Executa a conversão e retorna um array o resultado
     * @throws Exception Se ocorrer um erro durante a conversão.
     * @return array
     */
    public function run()
    {
        $types = array('csv', 'xlsx');
        $ext = strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
        if (in_array($ext, $types)) {
            // Caso seja xlsx, converter para CSV usando lib python
            if ($ext === 'xlsx' || $ext === 'xls') {
                $ret = shell_exec('type xlsx2csv');
                if (stripos($ret, 'xlsx2csv is') === false) {
                    $error = "### ATENÇÃO ### \nBiblioteca xlsx2csv não esta instalada. "
                        . "\nPara continuar, execute: 'sudo apt-get update && sudo apt-get install -y xlsx2csv'"
                        . "\n"
                        . "###############"
                        . "\n\n\n";
                    throw new Exception($error);
                }
                $t = explode(DIRECTORY_SEPARATOR, $this->file);
                $csv = '/tmp/' . str_ireplace('.xlsx', '.csv', array_pop($t));
                $csv_error = shell_exec("xlsx2csv " . $this->file . " $csv");
                if (!file_exists($csv)) {
                    $error = 'Ocorreu erro ao converter arquivo XLSX para CSV: ' . $csv_error;
                    throw new Exception($error);
                }
                $this->file = $csv;
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
