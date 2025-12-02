<?php

/**
 * Database Connection Class
 * Handles database connection and configuration for the webshop
 */
class Database
{
    private static $instance = null;
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = 'utf8mb4';


    private function __construct()
    {
        $this->loadConfigFromEnv();
        $this->connect();
    }
    private function loadConfigFromEnv()
    {
        // If DB_HOST already set in env, prefer that. Otherwise try to load .env
        if (!getenv('DB_HOST')) {
            $root = realpath(__DIR__ . '/..');
            $envFile = $root . '/.env';
            if ($root && file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#') continue;
                    if (strpos($line, '=') === false) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    // Remove surrounding quotes
                    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                        $value = substr($value, 1, -1);
                    }
                    // Put into environment for this process
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }

        // Apply values if present; do NOT fall back to hard-coded defaults
        $this->host = getenv('DB_HOST') ?: null;
        $this->dbname = getenv('DB_NAME') ?: null;
        $this->username = getenv('DB_USER') ?: null;
        $this->password = getenv('DB_PASS') ?: null;
        $this->charset = getenv('DB_CHARSET') ?: $this->charset;

        // Minimal validation to fail fast in misconfigured environments
        if (empty($this->host) || empty($this->dbname) || empty($this->username)) {
            throw new Exception('Database configuration missing. Provide DB_HOST, DB_NAME and DB_USER via environment variables or create a .env file in the project root.');
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Update database configuration
     */
    public function setConfig($host, $dbname, $username, $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        $this->connect();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
