<?php
// controllers/AreaController.php
require_once '../models/AreaModel.php';

class AreaController {
    private $areaModel;
    
    public function __construct() {
        $this->areaModel = new AreaModel();
    }
    
    // Método para obtener todas las áreas
    public function obtenerTodas() {
        try {
            $areas = $this->areaModel->obtenerTodasAreas();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener áreas: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para obtener áreas activas
    public function obtenerActivas() {
        try {
            $areas = $this->areaModel->obtenerAreasActivas();
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener áreas activas: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para obtener área por ID
    public function obtenerPorId($id) {
        try {
            $area = $this->areaModel->obtenerPorId($id);
            
            if ($area) {
                return [
                    'success' => true,
                    'data' => $area
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Área no encontrada'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener área: ' . $e->getMessage()
            ];
        }
    }
}
?>