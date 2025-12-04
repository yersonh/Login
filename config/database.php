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
            echo "<b>ERROR:</b> La extensión pdo_pgsql no está instalada.<br>";
            error_log("Extensión pdo_pgsql no está instalada");
            return;
        }

        $databaseUrl = getenv('DATABASE_URL');

        if ($databaseUrl) {
            $dbParts = parse_url($databaseUrl);

            $this->host = $dbParts['host'] ?? '';
            $this->port = $dbParts['port'] ?? '5432';
            $this->dbname = ltrim($dbParts['path'] ?? '', '/');
            $this->user = $dbParts['user'] ?? '';
            $this->password = $dbParts['pass'] ?? '';
        } else {
            // Config Railway (fallback)
            $this->host = getenv('PGHOST') ?: 'postgres.railway.internal';
            $this->port = getenv('PGPORT') ?: '5432';
            $this->dbname = getenv('PGDATABASE') ?: 'railway';
            $this->user = getenv('PGUSER') ?: 'postgres';
            $this->password = getenv('PGPASSWORD') ?: 'oyAQNLFfrwhhVhcQcQXelSNbsTKaMOfJ';
        }
    }

    public function conectar() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";

            // Mensaje para depuración
            echo "<p>Intentando conectar a PostgreSQL en: <b>{$this->host}:{$this->port}/{$this->dbname}</b></p>";

            $this->conn = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            echo "<p style='color: green; font-weight: bold;'>✔ Conectado correctamente a la base de datos</p>";

        } catch (PDOException $e) {

            // Mensaje visible
            echo "<p style='color: red; font-weight: bold;'>✘ Error al conectar a la base de datos: {$e->getMessage()}</p>";

            // Log interno
            error_log("ERROR DB: " . $e->getMessage());

            $this->conn = false;
        }

        return $this->conn;
    }
}
?>
