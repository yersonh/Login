<?php
require_once '../models/TipoVinculacionModel.php';

class TipoVinculacionController {
    private $tipoVinculacionModel;
    
    public function __construct() {
        $this->tipoVinculacionModel = new TipoVinculacionModel();
    }
    
    // Método para obtener todos los tipos
    public function obtenerTodos() {
        try {
            $tipos = $this->tipoVinculacionModel->obtenerTodosTipos();
            
            return [
                'success' => true,
                'data' => $tipos,
                'total' => count($tipos)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener tipos de vinculación: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para obtener tipos activos
    public function obtenerActivos() {
        try {
            $tipos = $this->tipoVinculacionModel->obtenerTiposActivos();
            
            return [
                'success' => true,
                'data' => $tipos,
                'total' => count($tipos)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener tipos activos: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para obtener tipo por ID
    public function obtenerPorId($id) {
        try {
            $tipo = $this->tipoVinculacionModel->obtenerPorId($id);
            
            if ($tipo) {
                return [
                    'success' => true,
                    'data' => $tipo
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Tipo de vinculación no encontrado'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al obtener tipo: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para crear nuevo tipo
    public function crear($data) {
        try {
            // Validar campos requeridos
            if (empty($data['nombre'])) {
                return [
                    'success' => false,
                    'error' => 'El nombre es requerido'
                ];
            }
            
            // Validar que el código no exista si se proporciona
            if (!empty($data['codigo'])) {
                if ($this->tipoVinculacionModel->existeCodigo($data['codigo'])) {
                    return [
                        'success' => false,
                        'error' => 'Ya existe un tipo con este código'
                    ];
                }
            }
            
            // Establecer valores por defecto
            $data['descripcion'] = $data['descripcion'] ?? '';
            $data['activo'] = $data['activo'] ?? true;
            
            // Crear el tipo
            $id_tipo = $this->tipoVinculacionModel->crearTipo($data);
            
            return [
                'success' => true,
                'message' => 'Tipo de vinculación creado exitosamente',
                'id_tipo' => $id_tipo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al crear tipo: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para actualizar tipo existente
    public function actualizar($data) {
        try {
            // Validar campos requeridos
            if (empty($data['id_tipo'])) {
                return [
                    'success' => false,
                    'error' => 'ID del tipo es requerido'
                ];
            }
            
            if (empty($data['nombre'])) {
                return [
                    'success' => false,
                    'error' => 'El nombre es requerido'
                ];
            }
            
            // Validar que el tipo exista
            $tipoExistente = $this->tipoVinculacionModel->obtenerPorId($data['id_tipo']);
            if (!$tipoExistente) {
                return [
                    'success' => false,
                    'error' => 'Tipo de vinculación no encontrado'
                ];
            }
            
            // Validar que el código no exista (excluyendo el tipo actual)
            if (!empty($data['codigo']) && $data['codigo'] !== $tipoExistente['codigo']) {
                if ($this->tipoVinculacionModel->existeCodigo($data['codigo'], $data['id_tipo'])) {
                    return [
                        'success' => false,
                        'error' => 'Ya existe un tipo con este código'
                    ];
                }
            }
            
            // Preparar datos para actualizar
            $datosActualizar = [
                'nombre' => $data['nombre'],
                'codigo' => $data['codigo'] ?? $tipoExistente['codigo'],
                'descripcion' => $data['descripcion'] ?? $tipoExistente['descripcion']
            ];
            
            // Actualizar el tipo
            $resultado = $this->tipoVinculacionModel->actualizarTipo($data['id_tipo'], $datosActualizar);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Tipo de vinculación actualizado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo actualizar el tipo'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al actualizar tipo: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para cambiar estado de tipo
    public function cambiarEstado($data) {
        try {
            // Validar que el tipo exista
            $tipoExistente = $this->tipoVinculacionModel->obtenerPorId($data['id_tipo']);
            if (!$tipoExistente) {
                return [
                    'success' => false,
                    'error' => 'Tipo de vinculación no encontrado'
                ];
            }
            
            // Asegurarse de que activo sea booleano
            $activo = filter_var($data['activo'], FILTER_VALIDATE_BOOLEAN);
            
            // Cambiar estado
            $resultado = $this->tipoVinculacionModel->cambiarEstadoTipo($data['id_tipo'], $activo);
            
            if ($resultado) {
                $accion = $activo ? 'activado' : 'desactivado';
                return [
                    'success' => true,
                    'message' => "Tipo de vinculación {$accion} exitosamente"
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se pudo cambiar el estado del tipo'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al cambiar estado: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para buscar tipos
    public function buscar($termino) {
        try {
            $tipos = $this->tipoVinculacionModel->buscarTipos($termino);
            
            return [
                'success' => true,
                'data' => $tipos,
                'total' => count($tipos)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al buscar tipos: ' . $e->getMessage()
            ];
        }
    }
    public function eliminar($id) {
        try {
            // Validar que el tipo exista
            $tipo = $this->tipoVinculacionModel->obtenerPorId($id);
            if (!$tipo) {
                return [
                    'success' => false,
                    'error' => 'Tipo de vinculación no encontrado'
                ];
            }

            // Verificar dependencias antes de eliminar
            $dependencias = $this->tipoVinculacionModel->verificarDependencias($id);
            
            if ($dependencias['contratistas'] > 0) {
                return [
                    'success' => false,
                    'error' => 'No se puede eliminar el tipo de vinculación porque está siendo utilizado por ' . 
                              $dependencias['contratistas'] . ' contratista(s). ' .
                              'Primero debe cambiar o eliminar estos contratistas.'
                ];
            }

            // Intentar eliminar físicamente
            $resultado = $this->tipoVinculacionModel->eliminarTipo($id);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Tipo de vinculación eliminado permanentemente de la base de datos'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al eliminar el tipo de vinculación'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al eliminar tipo: ' . $e->getMessage()
            ];
        }
    }
    public function verificarEliminacion($id) {
        try {
            // Validar que el tipo exista
            $tipo = $this->tipoVinculacionModel->obtenerPorId($id);
            if (!$tipo) {
                return [
                    'success' => false,
                    'error' => 'Tipo de vinculación no encontrado'
                ];
            }

            $dependencias = $this->tipoVinculacionModel->verificarDependencias($id);
            $puedeEliminar = $dependencias['contratistas'] == 0;
            
            return [
                'success' => true,
                'data' => [
                    'tipo' => [
                        'id' => $tipo['id_tipo'],
                        'nombre' => $tipo['nombre'],
                        'activo' => $tipo['activo']
                    ],
                    'dependencias' => $dependencias,
                    'puede_eliminar' => $puedeEliminar
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al verificar dependencias: ' . $e->getMessage()
            ];
        }
    }
}
?>