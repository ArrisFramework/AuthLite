<?php

namespace Arris\AuthLite;

class Config
{
    public array $config;
    public ?\PDO $pdo = null;
    public ?\Redis $redis = null;

    // Конфиг по умолчанию
    private array $defaultConfig = [
        'session_key' => 'auth_user_id',
        'cookie_name' => 'auth_token',
        'password_hash_algo' => PASSWORD_BCRYPT,
        'cache_ttl' => 3600,
        'permissions_table' => 'user_permissions',
    ];

    /**
     *
     */
    public function __construct()
    {
        $this->config = $this->defaultConfig;
    }

    /**
     * Установка PDO-коннектора
     *
     * @param \PDO $pdo
     * @return $this
     */
    public function withPDO(\PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * Установка Redis-коннектора
     *
     * @param \Redis $redis
     * @return $this
     */
    public function withRedis(\Redis $redis): self
    {
        $this->redis = $redis;
        return $this;
    }

    /**
     * Загрузка конфига из JSON-файла
     *
     * @param string $filePath
     * @return $this
     */
    public function loadFromJson(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("JSON config file not found: {$filePath}");
        }
        $jsonConfig = json_decode(file_get_contents($filePath), true);
        $this->config = array_merge($this->config, $jsonConfig);
        return $this;
    }

    /**
     * Загрузка конфига из INI-файла
     *
     * @param string $filePath
     * @return $this
     */
    public function loadFromIni(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("INI config file not found: {$filePath}");
        }
        $iniConfig = parse_ini_file($filePath, true);
        $this->config = array_merge($this->config, $iniConfig);
        return $this;
    }

    /**
     * Загрузка конфига из Redis
     *
     * @param string $key
     * @return $this
     * @throws \RedisException
     */
    /*public function loadFromRedis(string $key): self
    {
        if (!$this->redis) {
            throw new \RuntimeException("Redis is not configured");
        }
        $cachedConfig = $this->redis->get($key);
        if ($cachedConfig) {
            $this->config = array_merge($this->config, json_decode($cachedConfig, true));
        }
        return $this;
    }*/

    /**
     * Загрузка конфига из БД
     *
     * @param string $table_name
     * @return $this
     */
    public function loadFromDatabase(string $table_name): self
    {
        if (!$this->pdo) {
            throw new \RuntimeException("PDO is not configured");
        }

        $stmt = $this->pdo->query("SELECT `property`, `value` FROM {$table_name} ORDER BY value", \PDO::FETCH_KEY_PAIR);
        $dbConfig = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($dbConfig) {
            $this->config = array_merge($this->config, $dbConfig);
        }

        return $this;
    }

    /**
     * Переопределение параметра вручную
     *
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Получить весь конфиг
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Получить значение по ключу
     *
     * @param string $key
     * @param $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }



}