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
        $this->db = $database->conectar();
        
        $this->contratistaModel = new ContratistaModel($this->db);
        $this->areaModel = new AreaModel($this->db);
        $this->municipioModel = new MunicipioModel($this->db);
        $this->tipoModel = new TipoVinculacionModel($this->db);
    }

    public function mostrarFormulario() {
        session_start();
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'asistente') {
            header('Location: ../index.php');
            exit;
        }

        $areas = $this->areaModel->obtenerAreasActivas();
        $municipios = $this->municipioModel->obtenerMunicipiosActivos();
        $tipos = $this->tipoModel->obtenerTiposActivos();

        $consecutivo = $this->generarConsecutivo();
        
        $nombreCompleto = $_SESSION['nombres'] . ' ' . $_SESSION['apellidos'];

        require_once '../views/CPS/agregar_contratista.php';
    }

    public function procesarRegistro($datos) {
        session_start();
        if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'asistente') {
            return ['success' => false, 'error' => 'Acceso no autorizado'];
        }

        $datosValidados = $this->validarDatos($datos);

        if (!$datosValidados) {
            return ['success' => false, 'error' => 'Datos inválidos o incompletos'];
        }

        if (!isset($datosValidados['sej'])) {
            $datosValidados['sej'] = $this->generarConsecutivo();
        }

        $resultado = $this->contratistaModel->registrarContratistaCompleto($datosValidados);

        return $resultado;
    }

    private function validarDatos($datos) {
        $requiredFields = [
            'nombre_completo', 'cedula', 'correo', 'celular',
            'id_area', 'id_tipo_vinculacion', 'id_municipio_principal',
            'numero_contrato', 'fecha_contrato', 'fecha_inicio',
            'fecha_final', 'duracion_contrato'
        ];

        foreach ($requiredFields as $field) {
            if (empty($datos[$field])) {
                return false;
            }
        }

        $validados = [
            'nombre_completo' => trim($datos['nombre_completo']),
            'cedula' => preg_replace('/[^0-9]/', '', $datos['cedula']),
            'correo' => filter_var($datos['correo'], FILTER_SANITIZE_EMAIL),
            'celular' => preg_replace('/[^0-9]/', '', $datos['celular']),
            'direccion' => isset($datos['direccion']) ? trim($datos['direccion']) : '',
            'id_area' => (int)$datos['id_area'],
            'id_tipo_vinculacion' => (int)$datos['id_tipo_vinculacion'],
            'id_municipio_principal' => (int)$datos['id_municipio_principal'],
            'id_municipio_secundario' => isset($datos['id_municipio_secundario']) && $datos['id_municipio_secundario'] != '0' ? (int)$datos['id_municipio_secundario'] : null,
            'id_municipio_terciario' => isset($datos['id_municipio_terciario']) && $datos['id_municipio_terciario'] != '0' ? (int)$datos['id_municipio_terciario'] : null,
            'numero_contrato' => trim($datos['numero_contrato']),
            'fecha_contrato' => $datos['fecha_contrato'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_final' => $datos['fecha_final'],
            'duracion_contrato' => trim($datos['duracion_contrato']),
            'numero_registro_presupuestal' => isset($datos['numero_registro_presupuestal']) ? trim($datos['numero_registro_presupuestal']) : '',
            'fecha_rp' => isset($datos['fecha_rp']) ? $datos['fecha_rp'] : '',
        ];

        if (!filter_var($validados['correo'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (strlen($validados['cedula']) < 5) {
            return false;
        }

        return $validados;
    }

    private function generarConsecutivo() {
        $anio = date('Y');
        $mes = date('m');
        
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

    public function listarContratistas() {
        session_start();
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ../index.php');
            exit;
        }

        $contratistas = $this->contratistaModel->obtenerTodosContratistas();
        
        require_once '../views/contratistas/listar_contratistas.php';
    }
}

if (isset($_GET['action'])) {
    $controller = new ContratistaController();
    
    switch ($_GET['action']) {
        case 'formulario':
            $controller->mostrarFormulario();
            break;
        case 'registrar':
            $controller->procesarRegistro($_POST);
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