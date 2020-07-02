<?php

namespace NsUtil;

// Helper funcionrs
class LoadArgs {

    public $args;
    private $help;

    /**
      $params = [
      'h' => 'Host',
      'u' => 'User',
      'w' => 'Password',
      'p' => 'port',
      'd' => 'Database',
      's' => 'Schema',
      't' => 'Truncate table',
      'x' => 'Path to files'
      ];

      $defaultValues = [
      'h' => 'localhost',
      'u' => 'postgres',
      'w' => '',
      'p' => '5432',
      'd' => 'postgres',
      's' => 'public',
      't' => 'false',
      'x' => '~/$HOME'
      ];
      $usageExample = "php pgloader.php -h localhost -u postgres -w 123456 -p 5432 -d my_database -s public -x /dados/csv_to_import -t";

     * @param array $params
     * @param array $defaultValues
     */
    public function __construct(array $params, $usageExample = 'Não definido') {
        $newparamns = [];
        foreach ($params as $key => $val) {
            $newparamns[$key] = $val[0];
            $defaultValues[$key] = $val[1];
        }
        $newparamns['h'] = 'Help';
        //$defaultValues['h'] = -1;
        // Documentação
        $help[] = "Usage: \n\t" . $usageExample;
        $help[] = "Options: ";

        $options = [];
        foreach ($newparamns as $key => $val) {
            $help[] = "\t-$key\t" . $val . ". Default: '" . $defaultValues[$key] . "'";
            $options[$key] = "$key:";
        }
        $help [] = '';
        //var_export($options);
        $args = getopt(implode('', $options));
        //var_export($args);
        // pegar parametros mantendo o default
        $data = (object) array_replace($defaultValues, $args);
        $this->help = implode("\n", $help);

        if (isset($data->h)) {
            $this->printHelp();
        }

        $this->args = $data;
    }

    public function printHelp($extraMsg = '') {
        echo $extraMsg ? ">> ERROR:  $extraMsg" . PHP_EOL : '';
        echo $this->help;
        die();
    }

}
