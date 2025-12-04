<?php
class Database {
    private $host;
    private $port;
    private $dbname;
    private $user;
    private $password;
    private $conn;

    public function __construct() {
        // Log de inicio
        error_log("=== INICIALIZANDO CONEXIÓN A BD ===");
        
        // Verificar extensión PDO PostgreSQL
        if (!extension_loaded('pdo_pgsql')) {
            error_log("❌ ERROR: Extensión pdo_pgsql no está instalada");
            error_log("   - Extensiones cargadas: " . implode(', ', get_loaded_extensions()));
            return;
        }
        error_log("✅ Extensión pdo_pgsql está disponible");

        // Obtener variables de entorno
        $databaseUrl = getenv('DATABASE_URL');
        error_log("DATABASE_URL: " . ($databaseUrl ? "PRESENTE" : "NO PRESENTE"));

        if ($databaseUrl) {
            error_log("Usando DATABASE_URL para configuración");
            $dbParts = parse_url($databaseUrl);

            $this->host = $dbParts['host'] ?? '';
            $this->port = $dbParts['port'] ?? '5432';
            $this->dbname = ltrim($dbParts['path'] ?? '', '/');
            $this->user = $dbParts['user'] ?? '';
            $this->password = $dbParts['pass'] ?? '';
        } else {
            error_log("Usando variables individuales para configuración");
            $this->host = getenv('PGHOST') ?: 'postgres.railway.internal';
            $this->port = getenv('PGPORT') ?: '5432';
            $this->dbname = getenv('PGDATABASE') ?: 'railway';
            $this->user = getenv('PGUSER') ?: 'postgres';
            $this->password = getenv('PGPASSWORD') ?: 'oyAQNLFfrwhhVhcQcQXelSNbsTKaMOfJ';
        }

        // Log de configuración obtenida
        error_log("Configuración obtenida:");
        error_log("  - Host: " . $this->host);
        error_log("  - Port: " . $this->port);
        error_log("  - DB Name: " . $this->dbname);
        error_log("  - User: " . $this->user);
        error_log("  - Password: " . (empty($this->password) ? "VACÍA" : "PRESENTE (longitud: " . strlen($this->password) . ")"));
        
        // Verificar que tenemos todos los datos necesarios
        if (empty($this->host) || empty($this->dbname) || empty($this->user)) {
            error_log("⚠️ ADVERTENCIA: Faltan datos de conexión");
        }
    }

    public function conectar() {
        error_log("=== INICIANDO CONEXIÓN ===");
        $this->conn = null;

        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            error_log("DSN construido: " . $dsn);
            error_log("Usuario: " . $this->user);

            $startTime = microtime(true);
            
            error_log("Intentando conectar a PostgreSQL...");
            $this->conn = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            error_log("✅ CONEXIÓN EXITOSA");
            error_log("   - Tiempo de conexión: {$connectionTime}ms");
            
            // Verificar versión de PostgreSQL
            $version = $this->conn->query('SELECT version()')->fetchColumn();
            error_log("   - PostgreSQL: " . substr($version, 0, 100) . "...");
            
            // Verificar base de datos actual
            $currentDB = $this->conn->query('SELECT current_database()')->fetchColumn();
            error_log("   - Base de datos: " . $currentDB);
            
            // Verificar tablas existentes
            $tables = $this->conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
            error_log("   - Tablas en BD: " . (empty($tables) ? "NINGUNA" : implode(', ', $tables)));

        } catch (PDOException $e) {
            error_log("❌ ERROR DE CONEXIÓN PDO:");
            error_log("   - Código: " . $e->getCode());
            error_log("   - Mensaje: " . $e->getMessage());
            
            // Información adicional de depuración
            $errorInfo = $this->conn ? $this->conn->errorInfo() : ['No hay conexión'];
            error_log("   - Error Info: " . implode(' | ', $errorInfo));
            
            // Intentar diagnóstico
            $this->diagnosticarConexion();
            
            // Lanzar excepción para que sea manejada por el código que llama
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("❌ ERROR GENERAL:");
            error_log("   - Tipo: " . get_class($e));
            error_log("   - Mensaje: " . $e->getMessage());
            throw $e;
        }
        
        error_log("=== CONEXIÓN COMPLETADA ===");
        return $this->conn;
    }

    private function diagnosticarConexion() {
        error_log("=== DIAGNÓSTICO DE CONEXIÓN ===");
        
        // Verificar si el host es alcanzable (solo si estamos en un entorno que permite exec)
        if (function_exists('exec')) {
            error_log("Probando conectividad al host...");
            $output = [];
            $result = 0;
            
            // Para Linux/Mac
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                exec("timeout 2 nc -z -w 2 {$this->host} {$this->port} 2>&1", $output, $result);
                if ($result === 0) {
                    error_log("   ✅ Host {$this->host}:{$this->port} es alcanzable");
                } else {
                    error_log("   ❌ Host {$this->host}:{$this->port} NO es alcanzable");
                }
            }
        }
        
        // Verificar variables de entorno
        error_log("Variables de entorno relevantes:");
        $envVars = ['DATABASE_URL', 'PGHOST', 'PGPORT', 'PGDATABASE', 'PGUSER', 'PGPASSWORD'];
        foreach ($envVars as $var) {
            $value = getenv($var);
            error_log("   - {$var}: " . ($value ? "PRESENTE (longitud: " . strlen($value) . ")" : "NO PRESENTE"));
        }
        
        error_log("=== FIN DIAGNÓSTICO ===");
    }

    public function getConnectionInfo() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'dbname' => $this->dbname,
            'user' => $this->user,
            'connected' => ($this->conn !== null),
            'error' => ($this->conn === null)
        ];
    }
}
?>