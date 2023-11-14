<?php

namespace NsUtil;

// Helper funcionrs
class LoadArgs
{

    public $args;
    private $help;

    /**
     * 
     * @param array $params = ['q' => ['LABEL', 'DEFAULT']]
     * @param string $usageExample "php geocode_update.php -q 30"
     */
    public function __construct(array $params, $usageExample = 'Não definido')
    {
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
            $help[] = "\t-$key\t" . $val . ". Default: " . ((isset($defaultValues[$key])) ? $defaultValues[$key] : 'Not defined');
            $options[$key] = "$key:";
        }
        $help[] = '';
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

    public function printHelp($extraMsg = '')
    {
        echo $extraMsg ? ">> ERROR:  $extraMsg" . PHP_EOL : '';
        echo $this->help;
        die();
    }

}
