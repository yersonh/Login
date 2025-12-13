<?php
require_once __DIR__ . '/../models/parametrizar.php';

class ConfiguracionControlador {
    private $modelo;

    public function __construct() {
        $this->modelo = new Configuracion();
    }

    public function obtenerDatos() {
        $data = $this->modelo->obtenerConfiguracion();
        // Asegurar que siempre retorne array
        return is_array($data) ? $data : [];
    }
    
    public function actualizarDatos($datos) {
        try {
            // Validar datos mínimos
            if (empty($datos) || !is_array($datos)) {
                throw new Exception("Datos no válidos para actualización");
            }
            
            // Asegurar que campos requeridos tengan valores
            $datosCompletos = $this->completarDatosFaltantes($datos);
            
            // Validar datos antes de actualizar
            $validacion = $this->validarDatos($datosCompletos);
            if ($validacion !== true) {
                throw new Exception(implode(', ', $validacion));
            }
            
            // Actualizar en el modelo
            $resultado = $this->modelo->actualizarConfiguracion($datosCompletos);
            
            if (!$resultado) {
                throw new Exception("Error al actualizar en la base de datos");
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error en Controlador (actualizarDatos): " . $e->getMessage());
            return false;
        }
    }
    
    // =======================================
    // RESTAURAR CONFIGURACIÓN PREDETERMINADA
    // =======================================
    public function restaurarConfiguracion() {
        try {
            // Datos predeterminados - NOTA: usar ruta relativa consistente
            $datosPredeterminados = [
                'version_sistema' => '1.0.0',
                'tipo_licencia' => 'Evaluación',
                'valida_hasta' => '2026-03-31',
                'desarrollado_por' => 'SisgonTech',
                'direccion' => 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta',
                'correo_contacto' => 'gobernaciondelmeta@meta.gov.co',
                'telefono' => '(57 -608) 6 818503',
                'ruta_logo' => 'imagenes/gobernacion.png', // RUTA RELATIVA CORREGIDA
                'entidad' => 'Logo Gobernación del Meta',
                'enlace_web' => 'https://www.meta.gov.co'
            ];
            
            return $this->modelo->actualizarConfiguracion($datosPredeterminados);
            
        } catch (Exception $e) {
            error_log("Error en Controlador (restaurarConfiguracion): " . $e->getMessage());
            return false;
        }
    }
    
    // =======================================
    // RESTAURAR LOGO PREDETERMINADO
    // =======================================
    public function restaurarLogo() {
        try {
            // Obtener configuración actual
            $configActual = $this->obtenerDatos();
            
            // Preparar datos manteniendo la configuración actual excepto el logo
            $datosActualizar = [
                'ruta_logo' => 'imagenes/gobernacion.png', // RUTA RELATIVA CORREGIDA
                'entidad' => 'Logo Gobernación del Meta',
                'enlace_web' => 'https://www.meta.gov.co'
            ];
            
            // Mantener los otros datos existentes
            if (!empty($configActual)) {
                $datosActualizar['version_sistema'] = $configActual['version_sistema'] ?? '';
                $datosActualizar['tipo_licencia'] = $configActual['tipo_licencia'] ?? '';
                $datosActualizar['valida_hasta'] = $configActual['valida_hasta'] ?? null;
                $datosActualizar['desarrollado_por'] = $configActual['desarrollado_por'] ?? '';
                $datosActualizar['direccion'] = $configActual['direccion'] ?? '';
                $datosActualizar['correo_contacto'] = $configActual['correo_contacto'] ?? '';
                $datosActualizar['telefono'] = $configActual['telefono'] ?? '';
            }
            
            return $this->modelo->actualizarConfiguracion($datosActualizar);
            
        } catch (Exception $e) {
            error_log("Error en Controlador (restaurarLogo): " . $e->getMessage());
            return false;
        }
    }
    
    // =======================================
    // MÉTODO AUXILIAR: Completar datos faltantes
    // =======================================
    private function completarDatosFaltantes($datos) {
        // Obtener configuración actual para completar datos faltantes
        $configActual = $this->obtenerDatos();
        
        // Definir estructura completa con valores por defecto
        $estructuraCompleta = [
            'version_sistema' => '',
            'tipo_licencia' => '',
            'valida_hasta' => null,
            'desarrollado_por' => '',
            'direccion' => '',
            'correo_contacto' => '',
            'telefono' => '',
            'entidad' => '',
            'enlace_web' => '',
            'ruta_logo' => 'imagenes/gobernacion.png' // Valor por defecto
        ];
        
        // Combinar: estructura → actual → nuevos datos (nuevos datos tienen prioridad)
        $datosCompletos = array_merge($estructuraCompleta, $configActual, $datos);
        
        // Limpiar espacios en blanco
        foreach ($datosCompletos as $key => $value) {
            if (is_string($value)) {
                $datosCompletos[$key] = trim($value);
            }
        }
        
        return $datosCompletos;
    }
    
    // =======================================
    // VALIDAR DATOS DE CONFIGURACIÓN
    // =======================================
    public function validarDatos($datos) {
        $errores = [];
        
        // Validar campos requeridos
        $camposRequeridos = [
            'version_sistema' => 'Versión del sistema',
            'desarrollado_por' => 'Desarrollado por',
            'correo_contacto' => 'Correo de contacto'
        ];
        
        foreach ($camposRequeridos as $campo => $nombre) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo '$nombre' es requerido";
            }
        }
        
        // Validar email
        if (!empty($datos['correo_contacto']) && !filter_var($datos['correo_contacto'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo electrónico no es válido";
        }
        
        // Validar URL si existe
        if (!empty($datos['enlace_web']) && !filter_var($datos['enlace_web'], FILTER_VALIDATE_URL)) {
            $errores[] = "La URL del sitio web no es válida";
        }
        
        // Validar fecha si existe
        if (!empty($datos['valida_hasta'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $datos['valida_hasta']);
            if (!$fecha || $fecha->format('Y-m-d') !== $datos['valida_hasta']) {
                $errores[] = "La fecha de validez no tiene un formato válido (YYYY-MM-DD)";
            }
        }
        
        return empty($errores) ? true : $errores;
    }
}
?>