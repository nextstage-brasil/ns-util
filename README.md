# Library para gestão de arquivos em Storage

#Exemplo de uso
// teste de configurações
$cfg = new \League\Flysystem\Config(NsStorageLibrary\Config::init());
echo 'URL: ' . $cfg->get('url') . '<br/>';
echo 'Storage private: ' . $cfg->get('StoragePrivate') . '<br/>';

$st = new \NsStorageLibrary\Storage\Storage('Local');
$ret = $st->loadFile(__FILE__, true)->upload();


if ($ret) {
    echo "Envio de arquivo para Storage retornou TRUE<br/>";
} else {
    echo "Retorno FALSE para envio do arquivo em storage<br/>";
}
if ($st->has('nsTESTESTORAGE.php'))   {
    echo "Arquivo encontrado no storage<br/>";
} else {
    echo "Arquivo NÃO ENCONTRADO no storage<br/>";
}



