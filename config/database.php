<?php
class Database {
    private $host;
    private $port;
    private $dbname;
    private $user;
    private $password;
    private $conn;

    public function __construct() {
        if (!extension_loaded('pdo_pgsql')) {
            throw new Exception("La extensión pdo_pgsql no está instalada");
        }

        // PRIMERO intentar con DATABASE_PUBLIC_URL (con SSL)
        $databasePublicUrl = getenv('DATABASE_PUBLIC_URL');
        
        if ($databasePublicUrl) {
            // URL: postgresql://postgres:password@caboose.proxy.rlwy.net:12657/railway
            $dbParts = parse_url($databasePublicUrl);
            
            $this->host = $dbParts['host'] ?? ''; // caboose.proxy.rlwy.net
            $this->port = $dbParts['port'] ?? '5432'; // 12657
            $this->dbname = ltrim($dbParts['path'] ?? '', '/'); // railway
            $this->user = $dbParts['user'] ?? ''; // postgres
            $this->password = $dbParts['pass'] ?? ''; // oyAQNLFfrwhhVhcQcQXelSNbsTKaMOfJ
        }
        // SEGUNDO intentar con DATABASE_URL (interna, sin SSL)
        else if ($databaseUrl = getenv('DATABASE_URL')) {
            $dbParts = parse_url($databaseUrl);
            $this->host = $dbParts['host'] ?? '';
            $this->port = $dbParts['port'] ?? '5432';
            $this->dbname = ltrim($dbParts['path'] ?? '', '/');
            $this->user = $dbParts['user'] ?? '';
            $this->password = $dbParts['pass'] ?? '';
        }
        // TERCERO variables individuales
        else {
            $this->host = getenv('PGHOST') ?: 'postgres.railway.internal';
            $this->port = getenv('PGPORT') ?: '5432';
            $this->dbname = getenv('PGDATABASE') ?: 'railway';
            $this->user = getenv('PGUSER') ?: 'postgres';
            $this->password = getenv('PGPASSWORD') ?: 'oyAQNLFfrwhhVhcQcQXelSNbsTKaMOfJ';
        }
    }

    public function conectar($mostrarDebug = false) {
        $this->conn = null;

        try {
            // AÑADIR SSL en Railway (requerido para conexiones externas)
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname};sslmode=require";
            
            if ($mostrarDebug) {
                echo "<p>Conectando a: <b>{$this->host}:{$this->port}/{$this->dbname}</b></p>";
                echo "<p>Usuario: <b>{$this->user}</b></p>";
            }

            $this->conn = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 30
            ]);

            if ($mostrarDebug) {
                echo "<p style='color: green;'>✔ Conectado a PostgreSQL</p>";
            }

            return $this->conn;

        } catch (PDOException $e) {
            if ($mostrarDebug) {
                echo "<p style='color: red;'>✘ Error: {$e->getMessage()}</p>";
                echo "<p><b>DSN usado:</b> pgsql:host={$this->host};port={$this->port};dbname={$this->dbname};sslmode=require</p>";
            }
            
            error_log("ERROR DB Connection: " . $e->getMessage() . " | Host: {$this->host}:{$this->port}");
            $this->conn = false;
            return false;
        }
    }
}
?>