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
    
    // Método para crear nueva área
    public function crear($data) {
        try {
            // Validar que el código no exista
            if ($this->areaModel->existeCodigoArea($data['codigo_area'])) {
                return [
                    'success' => false,
                    'error' => 'Ya existe un área con este código'
                ];
            }
            
            // Crear el área
            $id_area = $this->areaModel->crearArea($data);
            
            return [
                'success' => true,
                'message' => 'Área creada exitosamente',
                'id_area' => $id_area
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al crear área: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para actualizar área existente
    public function actualizar($data) {
        try {
            // Validar que el área exista
            $areaExistente = $this->areaModel->obtenerPorId($data['id']);
            if (!$areaExistente) {
                return [
                    'success' => false,
                    'error' => 'Área no encontrada'
                ];
            }
            
            // Validar que el código no exista (excluyendo el área actual)
            if ($this->areaModel->existeCodigoArea($data['codigo_area'], $data['id'])) {
                return [
                    'success' => false,
                    'error' => 'Ya existe un área con este código'
                ];
            }
            
            // Actualizar el área
            $resultado = $this->areaModel->actualizarArea($data['id'], $data);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Área actualizada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el área'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al actualizar área: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para cambiar estado de área
    public function cambiarEstado($data) {
        try {
            // Validar que el área exista
            $areaExistente = $this->areaModel->obtenerPorId($data['id']);
            if (!$areaExistente) {
                return [
                    'success' => false,
                    'error' => 'Área no encontrada'
                ];
            }
            
            // Cambiar estado
            $resultado = $this->areaModel->cambiarEstadoArea($data['id'], $data['activo']);
            
            if ($resultado) {
                $accion = $data['activo'] ? 'activada' : 'desactivada';
                return [
                    'success' => true,
                    'message' => "Área {$accion} exitosamente"
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo cambiar el estado del área'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al cambiar estado: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para buscar áreas
    public function buscar($termino) {
        try {
            $areas = $this->areaModel->buscarAreas($termino);
            
            return [
                'success' => true,
                'data' => $areas,
                'total' => count($areas)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al buscar áreas: ' . $e->getMessage()
            ];
        }
    }
}
?>