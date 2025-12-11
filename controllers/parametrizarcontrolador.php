<?php
session_start();

// Solo administradores
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}

require_once '../models/Configuracion.php';

$configModel = new Configuracion();
$config = $configModel->obtenerConfiguracion();

// Si no hay configuración, crear una por defecto
if (!$config) {
    // Podrías crear un método para inicializar la configuración
    $config = [
        'version_sistema' => '1.0.0',
        'tipo_licencia' => 'Evaluación',
        'valida_hasta' => '2026-03-31',
        'desarrollado_por' => 'SisgonTech',
        'direccion' => 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta',
        'correo_contacto' => 'gobernaciondelmeta@meta.gov.co',
        'telefono' => '(57 -608) 6 818503',
        'ruta_logo' => '../../imagenes/logo.png',
        'enlace_web' => 'https://www.meta.gov.co',
        'texto_alternativo' => 'Logo Gobernación del Meta'
    ];
}

// Calcular días restantes
$fechaVencimiento = new DateTime($config['valida_hasta']);
$hoy = new DateTime();
$diferencia = $hoy->diff($fechaVencimiento);
$diasRestantes = ($fechaVencimiento > $hoy) ? $diferencia->days : 0;

// Datos del usuario (manteniendo tu código original)
$nombreUsuario = $_SESSION['nombres'] ?? '';
$apellidoUsuario = $_SESSION['apellidos'] ?? '';
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
if (empty($nombreCompleto)) {
    $nombreCompleto = 'Usuario del Sistema';
}

$tipoUsuario = $_SESSION['tipo_usuario'] ?? '';
$correoUsuario = $_SESSION['correo'] ?? '';
?>