<?php
require_once __DIR__ . '/../models/MunicipioModel.php';

class MunicipioController {
    private $model;

    public function __construct() {
        $this->model = new MunicipioModel();
    }

    public function obtenerTodos() {
        try {
            $municipios = $this->model->obtenerTodosMunicipios();
            return [
                'success' => true,
                'data' => $municipios
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener municipios: ' . $e->getMessage()
            ];
        }
    }

    public function obtenerPorId($id) {
        try {
            $municipio = $this->model->obtenerPorId($id);
            
            if ($municipio) {
                return [
                    'success' => true,
                    'data' => $municipio
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Municipio no encontrado'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener municipio: ' . $e->getMessage()
            ];
        }
    }

    public function crear($data) {
        try {
            if (empty($data['nombre'])) {
                return ['success' => false, 'error' => 'El nombre es requerido'];
            }
            
            if (empty($data['departamento'])) {
                return ['success' => false, 'error' => 'El departamento es requerido'];
            }
            
            if ($this->model->existeMunicipio($data['nombre'])) {
                return ['success' => false, 'error' => 'Ya existe un municipio con ese nombre'];
            }
            
            $activo = isset($data['activo']) ? (bool)$data['activo'] : true;
            $codigo_dane = $data['codigo_dane'] ?? null;
            
            $id = $this->model->crearMunicipio(
                trim($data['nombre']),
                trim($data['departamento']),
                $activo,
                $codigo_dane
            );
            
            if ($id) {
                return [
                    'success' => true,
                    'message' => 'Municipio creado exitosamente',
                    'id' => $id
                ];
            } else {
                return ['success' => false, 'error' => 'Error al crear municipio'];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al crear municipio: ' . $e->getMessage()
            ];
        }
    }

    public function actualizar($id, $data) {
        try {
            if (empty($data['nombre'])) {
                return ['success' => false, 'error' => 'El nombre es requerido'];
            }
            
            if (empty($data['departamento'])) {
                return ['success' => false, 'error' => 'El departamento es requerido'];
            }
            
            if ($this->model->existeMunicipio($data['nombre'], $id)) {
                return ['success' => false, 'error' => 'Ya existe otro municipio con ese nombre'];
            }
            
            $municipioExistente = $this->model->obtenerPorId($id);
            if (!$municipioExistente) {
                return ['success' => false, 'error' => 'Municipio no encontrado'];
            }
            
            $activo = isset($data['activo']) ? (bool)$data['activo'] : true;
            $codigo_dane = $data['codigo_dane'] ?? null;
            
            $resultado = $this->model->actualizarMunicipio(
                $id,
                trim($data['nombre']),
                trim($data['departamento']),
                $activo,
                $codigo_dane
            );
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Municipio actualizado exitosamente'
                ];
            } else {
                return ['success' => false, 'error' => 'Error al actualizar municipio'];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al actualizar municipio: ' . $e->getMessage()
            ];
        }
    }

     public function cambiarEstado($id, $activo) {
        try {
            $municipio = $this->model->obtenerPorId($id);
            if (!$municipio) {
                return ['success' => false, 'error' => 'Municipio no encontrado'];
            }
            
            // Usar el nuevo método del modelo
            $resultado = $this->model->cambiarEstadoMunicipio($id, (bool)$activo);
            
            if ($resultado) {
                $estadoTexto = $activo ? 'activado' : 'desactivado';
                return [
                    'success' => true,
                    'message' => "Municipio {$estadoTexto} exitosamente"
                ];
            } else {
                return ['success' => false, 'error' => 'Error al cambiar estado del municipio'];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al cambiar estado: ' . $e->getMessage()
            ];
        }
    }

    public function buscar($nombre) {
        try {
            $municipios = $this->model->buscarPorNombre($nombre);
            return [
                'success' => true,
                'data' => $municipios
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al buscar municipios: ' . $e->getMessage()
            ];
        }
    }
}
?>