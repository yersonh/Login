<?php
// helpers/config_helper.php - VERSIÓN ACTUALIZADA
require_once __DIR__ . '/../models/Configuracion.php';

class ConfigHelper {
    private static $configCache = null;
    
    private static function cargarConfiguracion() {
        if (self::$configCache === null) {
            $model = new Configuracion();
            self::$configCache = $model->obtenerConfiguracion();
            
            if (!self::$configCache) {
                // Valores por defecto COMPLETOS
                self::$configCache = [
                    'version_sistema' => '1.0.0',
                    'tipo_licencia' => 'Evaluación',
                    'valida_hasta' => '2026-03-31',
                    'desarrollado_por' => 'SisgonTech',
                    'direccion' => 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta',
                    'correo_contacto' => 'gobernaciondelmeta@meta.gov.co',
                    'telefono' => '(57 -608) 6 818503',
                    'ruta_logo' => '/imagenes/logo.png',        // ← NUEVO
                    'entidad' => 'Gobernación del Meta',        // ← NUEVO
                    'enlace_web' => 'https://www.meta.gov.co'   // ← NUEVO
                ];
            }
        }
    }
    
    public static function obtener($campo = null, $default = '') {
        self::cargarConfiguracion();
        
        if ($campo === null) {
            return self::$configCache;
        }
        
        return self::$configCache[$campo] ?? $default;
    }
    
    // Métodos específicos para formato especial
    public static function obtenerFechaFormateada() {
        $fecha = self::obtener('valida_hasta', '2026-03-31');
        if (empty($fecha)) return '31 de Marzo de 2026';
        
        // Convertir formato YYYY-MM-DD a texto
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
            '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
            '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
            '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];
        
        list($anio, $mes, $dia) = explode('-', $fecha);
        return $dia . ' de ' . ($meses[$mes] ?? $mes) . ' de ' . $anio;
    }
    
    public static function obtenerVersionCompleta() {
        $version = self::obtener('version_sistema', '1.0.0');
        return $version;
    }
    
    // Nuevo método para obtener logo con URL completa
    public static function obtenerLogoUrl($baseUrl = '') {
        $logoPath = self::obtener('ruta_logo', '/imagenes/logo.png');
        
        if (strpos($logoPath, 'http') === 0) {
            return $logoPath;
        }
        
        if (empty($baseUrl)) {
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                       . "://" . $_SERVER['HTTP_HOST'];
        }
        
        return $baseUrl . ((strpos($logoPath, '/') === 0) ? $logoPath : '/' . $logoPath);
    }
}
?>