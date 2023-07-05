<?php

namespace NsUtil;

class Config
{

    /**
     * @var array
     */
    protected $settings = [];
    private static $data = [];

    /**
     * @var Config|null
     */
    protected $fallback;

    /**
     * Constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    /**
     * Get a setting.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed config setting or default when not found
     */
    public function get($key, $default = null)
    {
        if (!array_key_exists($key, $this->settings)) {
            return $this->getDefault($key, $default);
        }

        return $this->settings[$key];
    }

    /**
     * Check if an item exists by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->settings)) {
            return true;
        }

        return $this->fallback instanceof Config ? $this->fallback->has($key) : false;
    }

    /**
     * Try to retrieve a default setting from a config fallback.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed config setting or default when not found
     */
    protected function getDefault($key, $default)
    {
        if (!$this->fallback) {
            return $default;
        }

        return $this->fallback->get($key, $default);
    }

    /**
     * Set a setting.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $this->settings[$key] = $value;
        return $this;
    }

    /**
     * Define o array enviado para o conjunto de configurações
     * @param array $settings
     * @param bool $merge
     * @return void
     */
    public function setByArray(array $settings, bool $merge = true): void
    {
        if (!$merge) {
            $this->settings = [];
        }
        $this->settings = array_merge($this->settings, $settings);
    }

    /**
     * Faz a leitura de um arquivo tipo .env e insere nas configs atuais
     *
     * @param string $envFilePath
     * @param boolean $merge
     * @return Config
     */
    public function loadEnvFile(string $envFilePath, bool $merge = true): Config
    {
        if (file_exists($envFilePath)) {
            $_CONFIG = parse_ini_file($envFilePath);
            if (!is_array($_CONFIG)) {
                throw new \Exception("Incorrect file configuration for .env type in file '$envFilePath'");
            }
            $this->setByArray($_CONFIG, $merge);
        } else {
            throw new \Exception('File not found: ' . $envFilePath);
        }

        return $this;
    }


    /**
     * Faz a leitura de arquivos com padrão return [] dentro do diretorio informado
     *
     * @param string $path
     * @return Config
     */
    public function loadFromPathConfig($path)
    {
        try {


            $files = DirectoryManipulation::openDir($path);
            foreach ($files as $config) {
                $key = explode('.', $config)[0];
                $this->settings[$key] = include($path . '/' . $config);
            }
        } catch (\Exception $exc) {
            // dir config is not found
        }

        return $this;
    }



    /**
     * Set the fallback.
     *
     * @param Config $fallback
     *
     * @return $this
     */
    public function setFallback(Config $fallback)
    {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * Retorna toda configuração contida no objeto
     * @return array
     */
    public function getAll(): array
    {
        return $this->settings;
    }

    /**
     * Init para utilização da classe de forma estatica
     * @param array $data
     */
    public static function init(array $data = [])
    {
        self::$data = $data;
    }

    /**
     * Define um  valor para chave
     * @param type $key1
     * @param type $value
     * @param type $merge
     */
    public static function setData($key1, $value, $merge = true)
    {
        if ($merge && is_array($value) &&  isset(self::$data[$key1])) {
            self::$data[$key1] = array_merge(self::$data[$key1], $value);
        } else {
            self::$data[$key1] = $value;
        }
    }

    /**
     * Get data
     * @param type $key
     * @param type $key2
     * @return type
     */
    public static function getData($key, $key2 = false)
    {
        if ($key2) {
            return self::$data[$key][$key2];
        } else {
            return self::$data[$key];
        }
    }

    public static function getDataFull(): array
    {
        return self::$data;
    }

    public static function getDBErrors(): array
    {
        // Tratamento de erros de banco de dados
        return [
            'Undefined column' => 'Erro no sistema. (Cód Erro: ABS1001)',
            'app_usuario_un' => 'E-mail informado não disponível para uso',
            '42703' => 'Erro no sistema (DB-42703)', // undefined column
            '23505' => 'Já existe registro com esses dados', // unicidade
            '23502' => 'Campo obrigatório não informado (DB205)',
            'not-null constraint' => 'Verifique campos obrigatórios (DB206)',
            'unique constraint' => 'Já existe registro com esses dados (DB207)',
            'constraint' => 'Verifique os dados informados (DB208)',
            '01000' => 'Warning: General warning',
            '01004' => 'Warning: String data, right-truncated',
            '01006' => 'Warning: Privilege not revoked',
            '01007' => 'Warning: Privilege not granted',
            '01S00' => 'Invalid connection string attribute',
            '07001' => 'Warning: Wrong number of parameters',
            '07002' => 'Warning: Count field incorrect',
            '07005' => 'Warning: Prepared statement not executed',
            '07006' => 'Warning: Restricted data type attribute violation',
            '07009' => 'Warning: Invalid descriptor index',
            '08001' => 'Client unable to establish connection',
            '08002' => 'Connection name in use',
            '08003' => 'Connection does not exist',
            '08004' => 'Server rejected the connection',
            '08006' => 'Connection failure',
            '08S01' => 'Communication link failure',
            '21S01' => 'Insert value list does not match column list',
            '22001' => 'String data right truncation',
            '22002' => 'Indicator variable required but not supplied',
            '22003' => 'Numeric value out of range',
            '22007' => 'Invalid datetime format',
            '22008' => 'Datetime field overflow',
            '22012' => 'Division by zero',
            '22015' => 'Interval field overflow',
            '22018' => 'Invalid character value for cast specification',
            '22025' => 'Invalid escape character',
            '23000' => 'Integrity constraint violation',
            '24000' => 'Invalid cursor state',
            '28000' => 'Invalid authorization specification',
            '34000' => 'Invalid cursor name',
            '3D000' => 'Invalid catalog name',
            '40001' => 'Serialization failure',
            '40003' => 'Statement completion unknown',
            '42000' => 'Syntax error or access violation',
            '42S01' => 'Base table or view already exists',
            '42S02' => 'Base table or view not found',
            '42S11' => 'Index already exists',
            '42S12' => 'Index not found',
            '42S21' => 'Column already exists',
            '42S22' => 'Column not found',
            'HY000' => 'General error',
            'HY001' => 'Memory allocation error',
            'HY004' => 'Invalid SQL data type',
            'HY008' => 'Operation canceled',
            'HY009' => 'Invalid use of null pointer',
            'HY010' => 'Function sequence error',
            'HY011' => 'Attribute cannot be set now',
            'HYT00' => 'Timeout expired',
            'IM001' => 'Driver does not support this function',
            'IM017' => 'Polling is disabled',
            '23502' => 'Null value not allowed - check constraint violation',
            '23503' => 'Foreign key violation',
            '23505' => 'Unique constraint violation',
            '23514' => 'Check constraint violation',
            '24000' => 'Invalid cursor state',
            '40002' => 'Transaction rollback',
            '42000' => 'Syntax error or access violation',
            '42S02' => 'Table not found',
            '42S22' => 'Column not found',
            'HY000' => 'General error',
            '25P02' => 'Transaction aborted',
        ];
    }

    /**
     * Busca pelo profile estabelcido na configuração os valores de key e secret
     */
    public static function setAWSByProfile()
    {
        if (!self::$data['fileserver']['S3']['credentials']['key']) {
            $env_vars = getenv();
            if ($env_vars['AWS_KEY']) {
                self::$data['fileserver']['S3']['credentials']['key'] = $env_vars['AWS_KEY'];
                self::$data['fileserver']['S3']['credentials']['secret'] = $env_vars['AWS_SECRET'];
            } else {
                // Storage
                $client = new \Aws\S3\S3Client(self::$data['fileserver']['S3']);
                $ret = $client->getCredentials()->wait();
                self::$data['fileserver']['S3']['credentials']['key'] = $ret->getAccessKeyId();
                self::$data['fileserver']['S3']['credentials']['secret'] = $ret->getSecretKey();
            }
        }
    }
}
