<?php
require_once '../config/database.php';
require_once '../models/ContratistaModel.php';
require_once '../models/AreaModel.php';
require_once '../models/MunicipioModel.php';
require_once '../models/TipoVinculacionModel.php';

class ContratistaController {
    private $db;
    private $contratistaModel;
    private $areaModel;
    private $municipioModel;
    private $tipoModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        $this->contratistaModel = new ContratistaModel($this->db);
        $this->areaModel = new AreaModel($this->db);
        $this->municipioModel = new MunicipioModel($this->db);
        $this->tipoModel = new TipoVinculacionModel($this->db);
    }

    /**
     * Muestra el formulario para agregar contratista
     */
    public function mostrarFormulario() {
        // Verificar permisos (solo asistentes y administradores)
        session_start();
        if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo_usuario'], ['asistente', 'administrador'])) {
            header('Location: ../login.php');
            exit;
        }

        // Obtener datos para los combos
        $areas = $this->areaModel->obtenerAreasActivas();
        $municipios = $this->municipioModel->obtenerMunicipiosActivos();
        $tipos = $this->tipoModel->obtenerTiposActivos();

        // Generar SEJ/Consecutivo
        $consecutivo = $this->generarConsecutivo();
        
        // Datos del usuario en sesión
        $nombreCompleto = $_SESSION['nombre_completo'] ?? 'Usuario';

        // Incluir la vista
        require_once '../views/contratistas/agregar_contratista.php';
    }

    /**
     * Procesa el formulario de registro
     */
    public function procesarRegistro() {
        // Verificar permisos
        session_start();
        if (!isset($_SESSION['usuario']) || !in_array($_SESSION['tipo_usuario'], ['asistente', 'administrador'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
            exit;
        }

        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        // Validar y sanitizar datos
        $datos = $this->validarDatos($_POST);

        if (!$datos) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos inválidos o incompletos']);
            exit;
        }

        // Agregar SEJ si no viene
        if (!isset($datos['sej'])) {
            $datos['sej'] = $this->generarConsecutivo();
        }

        // Procesar registro
        $resultado = $this->contratistaModel->registrarContratistaCompleto($datos);

        // Enviar respuesta JSON
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }

    /**
     * Valida y sanitiza los datos del formulario
     */
    private function validarDatos($postData) {
        $requiredFields = [
            'nombre_completo', 'cedula', 'correo', 'celular',
            'id_area', 'id_tipo_vinculacion', 'id_municipio_principal',
            'numero_contrato', 'fecha_contrato', 'fecha_inicio',
            'fecha_final', 'duracion_contrato'
        ];

        // Verificar campos requeridos
        foreach ($requiredFields as $field) {
            if (empty($postData[$field])) {
                return false;
            }
        }

        // Sanitizar datos
        $datos = [
            'nombre_completo' => trim($postData['nombre_completo']),
            'cedula' => preg_replace('/[^0-9]/', '', $postData['cedula']),
            'correo' => filter_var($postData['correo'], FILTER_SANITIZE_EMAIL),
            'celular' => preg_replace('/[^0-9]/', '', $postData['celular']),
            'direccion' => isset($postData['direccion']) ? trim($postData['direccion']) : '',
            
            // IDs de catálogos
            'id_area' => (int)$postData['id_area'],
            'id_tipo_vinculacion' => (int)$postData['id_tipo_vinculacion'],
            'id_municipio_principal' => (int)$postData['id_municipio_principal'],
            'id_municipio_secundario' => isset($postData['id_municipio_secundario']) ? (int)$postData['id_municipio_secundario'] : null,
            'id_municipio_terciario' => isset($postData['id_municipio_terciario']) ? (int)$postData['id_municipio_terciario'] : null,
            
            // Datos del contrato
            'numero_contrato' => trim($postData['numero_contrato']),
            'fecha_contrato' => $postData['fecha_contrato'],
            'fecha_inicio' => $postData['fecha_inicio'],
            'fecha_final' => $postData['fecha_final'],
            'duracion_contrato' => trim($postData['duracion_contrato']),
            'numero_registro_presupuestal' => isset($postData['numero_registro_presupuestal']) ? trim($postData['numero_registro_presupuestal']) : '',
            'fecha_rp' => isset($postData['fecha_rp']) ? $postData['fecha_rp'] : '',
        ];

        // Validaciones adicionales
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (strlen($datos['cedula']) < 5) {
            return false;
        }

        return $datos;
    }

    /**
     * Genera un consecutivo/SEJ
     */
    private function generarConsecutivo() {
        $anio = date('Y');
        $mes = date('m');
        
        // Contar contratistas del mes actual
        $sql = "SELECT COUNT(*) as total FROM detalle_contrato 
                WHERE EXTRACT(YEAR FROM created_at) = :anio 
                AND EXTRACT(MONTH FROM created_at) = :mes";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':mes', $mes);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $numero = $resultado['total'] + 1;
        
        return "SEJ-{$anio}{$mes}-" . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Lista todos los contratistas
     */
    public function listarContratistas() {
        session_start();
        if (!isset($_SESSION['usuario'])) {
            header('Location: ../login.php');
            exit;
        }

        $contratistas = $this->contratistaModel->obtenerTodosContratistas();
        
        require_once '../views/contratistas/listar_contratistas.php';
    }
}

// Uso del controlador
if (isset($_GET['action'])) {
    $controller = new ContratistaController();
    
    switch ($_GET['action']) {
        case 'formulario':
            $controller->mostrarFormulario();
            break;
        case 'registrar':
            $controller->procesarRegistro();
            break;
        case 'listar':
            $controller->listarContratistas();
            break;
        default:
            header('Location: ../index.php');
            break;
    }
}
?>