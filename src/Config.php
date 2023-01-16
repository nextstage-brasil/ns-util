<?php

namespace NsUtil;

class Config {

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
    public function __construct(array $settings = []) {
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
    public function get($key, $default = null) {
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
    public function has($key) {
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
    protected function getDefault($key, $default) {
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
    public function set($key, $value) {
        $this->settings[$key] = $value;
        return $this;
    }

    /**
     * Define o array enviado para o conjunto de configurações
     * @param array $settings
     * @param bool $merge
     * @return void
     */
    public function setByArray(array $settings, bool $merge = true): void {
        if (!$merge) {
            $this->settings = [];
        }
        $this->settings = array_merge($this->settings, $settings);
    }

    /**
     * Faz a leitura de um arquivo tipo .env e insere nas configs atuais
     * @param type $envFilePath
     * @param type $merge
     */
    public function loadEnvFile(string $envFilePath, bool $merge = true): Config {
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
     * Set the fallback.
     *
     * @param Config $fallback
     *
     * @return $this
     */
    public function setFallback(Config $fallback) {
        $this->fallback = $fallback;
        return $this;
    }

    /**
     * Retorna toda configuração contida no objeto
     * @return array
     */
    public function getAll(): array {
        return $this->settings;
    }

    /**
     * Init para utilização da classe de forma estatica
     * @param array $data
     */
    public static function init(array $data = []) {
        self::$data = $data;
    }

    /**
     * Define um  valor para chave
     * @param type $key1
     * @param type $value
     * @param type $merge
     */
    public static function setData($key1, $value, $merge = true) {
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
    public static function getData($key, $key2 = false) {
        if ($key2) {
            return self::$data[$key][$key2];
        } else {
            return self::$data[$key];
        }
    }

    public static function getDataFull(): array {
        return self::$data;
    }

    public static function getDBErrors(): array {
        // Tratamento de erros de banco de dados
        return [
            'Undefined column' => 'Erro no sistema. (Cód Erro: ABS1001)',
            'app_usuario_un' => 'E-mail informado não disponível para uso',
            '42703' => 'Erro no sistema (DB42703)', // undefined column
            '23505' => 'Já existe registro com esses dados', // unicidade
            '23502' => 'Campo obrigatório não informado',
            'not-null constraint' => 'Verifique campos obrigatórios',
            'unique constraint' => 'Já existe registro com esses dados'
        ];
    }

    /**
     * Busca pelo profile estabelcido na configuração os valores de key e secret
     */
    public static function setAWSByProfile() {
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
