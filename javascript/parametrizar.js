// =======================================
// VARIABLES GLOBALES
// =======================================
let pendingAction = null;
let actionData = null;
window.currentConfig = {};

// Variables para el estado del municipio
let municipioEstadoId = null;
let municipioEstadoAction = null;

// Variables para el estado del área
let areaEstadoId = null;
let areaEstadoAction = null;

// Variables para el estado del tipo vinculación (NUEVO)
let vinculacionEstadoId = null;
let vinculacionEstadoAction = null;
let currentVinculacionNombre = null;
let currentVinculacionCodigo = null;
let currentVinculacionDescripcion = null;

// =======================================
// INICIALIZACIÓN
// =======================================
document.addEventListener('DOMContentLoaded', function () {

    // 1. Cargar datos al abrir la página
    cargarConfiguracion();
    
    // 2. EVENT LISTENERS (BOTONES Y ACCIONES)
    
    // --- Botón Guardar: Configuración del SISTEMA ---
    const saveConfigBtn = document.getElementById('saveConfigBtn');
    if (saveConfigBtn) {
        saveConfigBtn.addEventListener('click', actualizarConfiguracionSistema);
    }
    
    // --- Botón Guardar: Configuración del LOGO ---
    const saveLogoBtn = document.getElementById('saveLogoBtn');
    if (saveLogoBtn) {
        saveLogoBtn.addEventListener('click', actualizarConfiguracionLogo);
    }
    
    // --- Botón Restaurar: Logo Predeterminado ---
    const restoreLogoBtn = document.getElementById('restoreLogoBtn');
    if (restoreLogoBtn) {
        restoreLogoBtn.addEventListener('click', restaurarLogoPredeterminado);
    }
    
    // --- Botón Restaurar: Configuración de Fábrica ---
    const resetConfigBtn = document.getElementById('resetConfigBtn');
    if (resetConfigBtn) {
        resetConfigBtn.addEventListener('click', restaurarConfiguracionPredeterminada);
    }

    // --- Input File: Vista previa al seleccionar imagen ---
    const newLogoInput = document.getElementById('newLogo');
    if (newLogoInput) {
        newLogoInput.addEventListener('change', function(e) {
            handleFileSelection(e);
        });
    }

    // --- Modal: Eventos de cierre y confirmación ---
    setupModalEvents();
    setupEstadoModalEvents();
    setupEstadoAreaModalEvents();
    setupEstadoVinculacionModalEvents(); // NUEVO
    
    // 3. EVENT LISTENERS PARA MUNICIPIOS
    
    // --- Búsqueda de municipios ---
    const searchMunicipioInput = document.getElementById('searchMunicipio');
    if (searchMunicipioInput) {
        searchMunicipioInput.addEventListener('input', function(e) {
            buscarMunicipios(e.target.value);
        });
    }
    
    // --- Botón agregar municipio ---
    const addMunicipioBtn = document.getElementById('addMunicipioBtn');
    if (addMunicipioBtn) {
        addMunicipioBtn.addEventListener('click', function() {
            abrirModalMunicipio('agregar');
        });
    }
    
    // 4. EVENT LISTENERS PARA ÁREAS
    
    // --- Búsqueda de áreas ---
    const searchAreaInput = document.getElementById('searchArea');
    if (searchAreaInput) {
        searchAreaInput.addEventListener('input', function(e) {
            buscarAreas(e.target.value);
        });
    }
    
    // --- Botón agregar área ---
    const addAreaBtn = document.getElementById('addAreaBtn');
    if (addAreaBtn) {
        addAreaBtn.addEventListener('click', function() {
            abrirModalArea('agregar');
        });
    }
    
    // 5. EVENT LISTENERS PARA TIPO VINCULACIÓN (NUEVO)
    
    // --- Búsqueda de tipos vinculación ---
    const searchVinculacionInput = document.getElementById('searchVinculacion');
    if (searchVinculacionInput) {
        searchVinculacionInput.addEventListener('input', function(e) {
            buscarTiposVinculacion(e.target.value);
        });
    }
    
    // --- Botón agregar tipo vinculación ---
    const addVinculacionBtn = document.getElementById('addVinculacionBtn');
    if (addVinculacionBtn) {
        addVinculacionBtn.addEventListener('click', function() {
            abrirModalVinculacion('agregar');
        });
    }
    
    // 6. EVENT LISTENERS GENERALES
    
    // --- Botón Guardar en Modal CRUD ---
    const saveCrudBtn = document.getElementById('saveCrudBtn');
    if (saveCrudBtn) {
        saveCrudBtn.addEventListener('click', guardarCrud);
    }
    
    // --- Botón Cerrar Modal CRUD ---
    const modalCloseBtn = document.querySelector('#crudModal .modal-close');
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeCrudModal);
    }
    
    // --- Click fuera del modal para cerrar ---
    const crudModal = document.getElementById('crudModal');
    if (crudModal) {
        crudModal.addEventListener('click', function(e) {
            if (e.target === crudModal) closeCrudModal();
        });
    }
    
    // --- ESC para cerrar modal CRUD ---
    document.addEventListener('keydown', function(e) {
        const crudModal = document.getElementById('crudModal');
        if (e.key === 'Escape' && crudModal && crudModal.style.display === 'flex') {
            closeCrudModal();
        }
    });
    
    // 7. CARGAR DATOS DINÁMICOS
    setTimeout(() => {
        cargarMunicipios();
        cargarAreas();
        cargarTiposVinculacion(); // NUEVO
    }, 500);
});

// =======================================
// FUNCIONES PARA GESTIÓN DE TIPOS VINCULACIÓN (NUEVO)
// =======================================

function cargarTiposVinculacion() {
    const tablaBody = document.getElementById('vinculacionesTable');
    if (!tablaBody) return;
    
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="5">Cargando tipos de vinculación...</td></tr>';
    
    fetch('../../api/tipo_vinculacion.php')
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">${data.error || 'Error al cargar tipos de vinculación'}</td></tr>`;
                return;
            }
            
            if (data.data.length === 0) {
                tablaBody.innerHTML = '<tr><td colspan="5" class="empty-row">No hay tipos de vinculación registrados</td></tr>';
                return;
            }
            
            // Generar filas de la tabla
            tablaBody.innerHTML = '';
            data.data.forEach(tipo => {
                const fila = document.createElement('tr');
                
                // Determinar botón según estado
                let botonEstado = '';
                if (tipo.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="mostrarConfirmacionEstadoVinculacion(${tipo.id_tipo}, false, '${escapeHtml(tipo.nombre)}', '${escapeHtml(tipo.codigo || '')}', '${escapeHtml(tipo.descripcion || '')}')" title="Desactivar">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="mostrarConfirmacionEstadoVinculacion(${tipo.id_tipo}, true, '${escapeHtml(tipo.nombre)}', '${escapeHtml(tipo.codigo || '')}', '${escapeHtml(tipo.descripcion || '')}')" title="Activar">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }
                
                // Truncar descripción si es muy larga
                const descripcionCorta = tipo.descripcion && tipo.descripcion.length > 50 ? 
                    tipo.descripcion.substring(0, 50) + '...' : 
                    (tipo.descripcion || '');
                
                fila.innerHTML = `
                    <td>${tipo.nombre}</td>
                    <td>${tipo.codigo || '--'}</td>
                    <td title="${tipo.descripcion || ''}">${descripcionCorta}</td>
                    <td><span class="status-badge ${tipo.activo ? 'status-active' : 'status-inactive'}">${tipo.activo ? 'Activo' : 'Inactivo'}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-edit" onclick="editarVinculacion(${tipo.id_tipo})" title="Editar">
                            <i class="fas fa-edit"></i> 
                        </button>
                        ${botonEstado}
                    </td>
                `;
                tablaBody.appendChild(fila);
            });
        })
        .catch(error => {
            console.error('Error cargando tipos de vinculación:', error);
            tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">Error de conexión</td></tr>`;
        });
}

function buscarTiposVinculacion(termino) {
    const tablaBody = document.getElementById('vinculacionesTable');
    if (!tablaBody) return;
    
    if (!termino || termino.trim() === '') {
        cargarTiposVinculacion();
        return;
    }
    
    // Mostrar estado de carga
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="5">Buscando tipos de vinculación...</td></tr>';
    
    // Usar endpoint de búsqueda del backend
    fetch(`../../api/tipo_vinculacion.php?buscar=${encodeURIComponent(termino)}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                tablaBody.innerHTML = `<tr><td colspan="5" class="empty-row">No se encontraron tipos con "${termino}"</td></tr>`;
                return;
            }
            
            // Mostrar resultados de búsqueda
            tablaBody.innerHTML = '';
            data.data.forEach(tipo => {
                const fila = document.createElement('tr');
                let botonEstado = '';
                if (tipo.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="mostrarConfirmacionEstadoVinculacion(${tipo.id_tipo}, false, '${escapeHtml(tipo.nombre)}', '${escapeHtml(tipo.codigo || '')}', '${escapeHtml(tipo.descripcion || '')}')" title="Desactivar">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="mostrarConfirmacionEstadoVinculacion(${tipo.id_tipo}, true, '${escapeHtml(tipo.nombre)}', '${escapeHtml(tipo.codigo || '')}', '${escapeHtml(tipo.descripcion || '')}')" title="Activar">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }

                const descripcionCorta = tipo.descripcion && tipo.descripcion.length > 50 ? 
                    tipo.descripcion.substring(0, 50) + '...' : 
                    (tipo.descripcion || '');

                fila.innerHTML = `
                    <td>${tipo.nombre}</td>
                    <td>${tipo.codigo || '--'}</td>
                    <td title="${tipo.descripcion || ''}">${descripcionCorta}</td>
                    <td><span class="status-badge ${tipo.activo ? 'status-active' : 'status-inactive'}">${tipo.activo ? 'Activo' : 'Inactivo'}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-edit" onclick="editarVinculacion(${tipo.id_tipo})" title="Editar">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        ${botonEstado}
                    </td>
                `;
                tablaBody.appendChild(fila);
            });
        })
        .catch(error => {
            console.error('Error buscando tipos de vinculación:', error);
            tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">Error en la búsqueda</td></tr>`;
        });
}

function mostrarConfirmacionEstadoVinculacion(id, activar, nombre, codigo, descripcion) {
    // Guardar datos para usar después
    vinculacionEstadoId = id;
    vinculacionEstadoAction = activar;
    currentVinculacionNombre = nombre;
    currentVinculacionCodigo = codigo;
    currentVinculacionDescripcion = descripcion;
    
    const accion = activar ? 'activar' : 'desactivar';
    const mensaje = activar ? 
        '¿Está seguro de que desea ACTIVAR este tipo de vinculación?<br><br>El tipo volverá a estar disponible en el sistema.' :
        '¿Está seguro de que desea DESACTIVAR este tipo de vinculación?<br>';
    
    // Actualizar mensaje del modal
    document.getElementById('estadoVinculacionMensaje').innerHTML = mensaje;
    
    // Actualizar detalles del tipo de vinculación
    document.getElementById('detailNombre').textContent = nombre;
    document.getElementById('detailCodigo').textContent = codigo || '--';
    
    // Truncar descripción si es muy larga para el modal
    const descripcionCorta = descripcion && descripcion.length > 80 ? 
        descripcion.substring(0, 80) + '...' : 
        (descripcion || '--');
    
    document.getElementById('detailDescripcion').textContent = descripcionCorta;
    document.getElementById('detailDescripcion').title = descripcion || '';
    
    // Estado actual y nuevo
    document.getElementById('detailEstadoActual').textContent = activar ? 'Inactivo' : 'Activo';
    document.getElementById('detailEstadoActual').className = activar ? 'status-inactive' : 'status-active';
    
    document.getElementById('detailNuevoEstado').textContent = activar ? 'Activo' : 'Inactivo';
    document.getElementById('detailNuevoEstado').className = activar ? 'status-active' : 'status-inactive';
    
    // Mostrar modal
    document.getElementById('confirmEstadoVinculacionModal').style.display = 'flex';
}

function ejecutarCambioEstadoVinculacion() {
    if (!vinculacionEstadoId || vinculacionEstadoAction === null) {
        showError('No se pudo completar la acción');
        return;
    }
    
    const accion = vinculacionEstadoAction ? 'activar' : 'desactivar';
    const datos = {
        id_tipo: vinculacionEstadoId,
        activo: vinculacionEstadoAction
    };
    
    fetch(`../../api/tipo_vinculacion.php`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(data.message || `Tipo de vinculación ${accion}do exitosamente`);
            cargarTiposVinculacion(); // Recargar la tabla
        } else {
            showError(data.error || `Error al ${accion} tipo de vinculación`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(`Error de conexión al ${accion} tipo de vinculación`);
    })
    .finally(() => {
        closeEstadoVinculacionModal();
    });
}

function closeEstadoVinculacionModal() {
    document.getElementById('confirmEstadoVinculacionModal').style.display = 'none';
    vinculacionEstadoId = null;
    vinculacionEstadoAction = null;
    currentVinculacionNombre = null;
    currentVinculacionCodigo = null;
    currentVinculacionDescripcion = null;
}

function setupEstadoVinculacionModalEvents() {
    const modal = document.getElementById('confirmEstadoVinculacionModal');
    const confirmBtn = document.getElementById('confirmEstadoVinculacionBtn');
    
    if (!modal) return;

    // Click fuera cierra
    modal.addEventListener('click', (e) => { 
        if (e.target === modal) closeEstadoVinculacionModal(); 
    });
    
    // ESC cierra
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeEstadoVinculacionModal();
        }
    });

    // Botón confirmar
    if (confirmBtn) confirmBtn.addEventListener('click', ejecutarCambioEstadoVinculacion);
}

// Abrir modal para agregar/editar tipo vinculación
function abrirModalVinculacion(modo = 'agregar', id = null) {
    const modal = document.getElementById('crudModal');
    const titulo = document.getElementById('modalTitle');
    const vinculacionFields = document.getElementById('vinculacionFields');
    const recordId = document.getElementById('recordId');
    const recordType = document.getElementById('recordType');
    
    // Ocultar otros formularios y mostrar solo el de tipos vinculación
    document.querySelectorAll('.form-fields').forEach(field => {
        field.style.display = 'none';
        field.classList.remove('active');
    });
    
    // Crear el contenedor de campos si no existe
    if (!vinculacionFields) {
        crearCamposVinculacion();
    }
    
    const camposVinculacion = document.getElementById('vinculacionFields');
    camposVinculacion.style.display = 'block';
    camposVinculacion.classList.add('active');
    
    // Limpiar formulario
    document.getElementById('nombreVinculacion').value = '';
    document.getElementById('codigoVinculacion').value = '';
    document.getElementById('descripcionVinculacion').value = '';
    document.getElementById('estadoVinculacion').value = '1';
    
    if (modo === 'agregar') {
        titulo.textContent = 'Agregar Nuevo Tipo de Vinculación';
        recordId.value = '';
        recordType.value = 'vinculacion';
    } else if (modo === 'editar' && id) {
        titulo.textContent = 'Editar Tipo de Vinculación';
        recordId.value = id;
        recordType.value = 'vinculacion';
        
        // Cargar datos del tipo
        cargarDatosVinculacion(id);
    }
    
    modal.style.display = 'flex';
}

// Crear campos para tipo vinculación (si no existen en el HTML)
function crearCamposVinculacion() {
    const campos = `
        <div id="vinculacionFields" class="form-fields" style="display: none;">
            <div class="form-group">
                <label for="nombreVinculacion">
                    <i class="fas fa-user-tie"></i> Nombre del Tipo *
                </label>
                <input type="text" id="nombreVinculacion" class="form-control" 
                       placeholder="Ej: De planta, Por contrato, Consultor..." required>
            </div>
            
            <div class="form-group">
                <label for="codigoVinculacion">
                    <i class="fas fa-code"></i> Código
                </label>
                <input type="text" id="codigoVinculacion" class="form-control" 
                       placeholder="Ej: PLT-01, CNT-02, CON-03...">
            </div>
            
            <div class="form-group">
                <label for="descripcionVinculacion">
                    <i class="fas fa-file-alt"></i> Descripción
                </label>
                <textarea id="descripcionVinculacion" class="form-control" rows="3"
                          placeholder="Descripción detallada del tipo de vinculación..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="estadoVinculacion">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select id="estadoVinculacion" class="form-control">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
        </div>
    `;
    
    // Insertar después de los campos de área
    const areaFields = document.getElementById('areaFields');
    if (areaFields) {
        areaFields.insertAdjacentHTML('afterend', campos);
    } else {
        // Si no hay campos de área, insertar después del municipioFields
        const municipioFields = document.getElementById('municipioFields');
        if (municipioFields) {
            municipioFields.insertAdjacentHTML('afterend', campos);
        }
    }
}

// Cargar datos de tipo vinculación para editar
function cargarDatosVinculacion(id) {
    fetch(`../../api/tipo_vinculacion.php?id_tipo=${id}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data.success && data.data) {
                const tipo = data.data;
                document.getElementById('nombreVinculacion').value = tipo.nombre || '';
                document.getElementById('codigoVinculacion').value = tipo.codigo || '';
                document.getElementById('descripcionVinculacion').value = tipo.descripcion || '';
                document.getElementById('estadoVinculacion').value = tipo.activo ? '1' : '0';
            } else {
                showError(data.error || 'Error al cargar datos del tipo');
                closeCrudModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error de conexión al cargar tipo de vinculación');
            closeCrudModal();
        });
}

// Guardar tipo vinculación (crear o actualizar)
function guardarVinculacion(recordId, btn, originalText) {
    // Obtener datos del formulario
    const datos = {
        nombre: document.getElementById('nombreVinculacion').value.trim(),
        codigo: document.getElementById('codigoVinculacion').value.trim() || null,
        descripcion: document.getElementById('descripcionVinculacion').value.trim(),
        activo: document.getElementById('estadoVinculacion').value === '1'
    };
    
    // Validaciones
    if (!datos.nombre) {
        showError('El nombre del tipo es requerido');
        return;
    }
    
    // Configurar petición
    const url = '../../api/tipo_vinculacion.php';
    const metodo = recordId ? 'PUT' : 'POST';
    const bodyData = recordId ? { ...datos, id_tipo: parseInt(recordId) } : datos;
    
    // Mostrar estado de carga
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;
    
    fetch(url, {
        method: metodo,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(bodyData)
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Tipo de vinculación guardado exitosamente');
            closeCrudModal();
            cargarTiposVinculacion(); // Recargar la tabla
        } else {
            showError(data.error || 'Error al guardar tipo de vinculación');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error de conexión al guardar tipo de vinculación');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function editarVinculacion(id) {
    abrirModalVinculacion('editar', id);
}

// =======================================
// ACTUALIZAR FUNCIÓN GUARDAR CRUD PARA INCLUIR TIPO VINCULACIÓN
// =======================================

// Función unificada para guardar (municipios, áreas y tipos vinculación)
function guardarCrud() {
    const recordId = document.getElementById('recordId').value;
    const recordType = document.getElementById('recordType').value;
    const btn = document.getElementById('saveCrudBtn');
    const originalText = btn.innerHTML;
    
    if (recordType === 'municipio') {
        guardarMunicipio(recordId, btn, originalText);
    } else if (recordType === 'area') {
        guardarArea(recordId, btn, originalText);
    } else if (recordType === 'vinculacion') { // NUEVO
        guardarVinculacion(recordId, btn, originalText);
    }
}

// =======================================
// FUNCIÓN HELPER PARA ESCAPAR HTML (NUEVO)
// =======================================

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// =======================================
// (El resto de tu código permanece igual desde aquí hacia abajo)
// =======================================

// 1. CARGAR DATOS DE CONFIGURACIÓN
function cargarConfiguracion() {
    fetch("../../api/parametrizacion_obtener.php")
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showError(data.error || "No se pudo cargar la configuración.");
                return;
            }

            const config = data.data;
            window.currentConfig = config; // Guardar referencia

            // Llenar formulario de Sistema
            setValue('version', config.version_sistema);
            setValue('tipoLicencia', config.tipo_licencia);
            setValue('validaHasta', config.valida_hasta);
            setValue('desarrolladoPor', config.desarrollado_por);
            setValue('direccion', config.direccion);
            setValue('contacto', config.correo_contacto);
            setValue('telefono', config.telefono);

            // Llenar formulario de Logo
            setValue('logoAltText', config.entidad);
            setValue('logoLink', config.enlace_web);
            setValue('logoNit', config.nit);

            // Actualizar imagen del logo actual
            if (config.ruta_logo) {
                updateLogoImages(config.ruta_logo);
            }
            
            // UI Limpia
            hideImagePreview();
        })
        .catch(error => {
            console.error("Error cargando config:", error);
            showError("Error de conexión al cargar datos.");
        });
}

// 2. LÓGICA DE ACTUALIZACIÓN DEL LOGO
function actualizarConfiguracionLogo() {
    const entidad = document.getElementById('logoAltText').value.trim();
    const nit = document.getElementById('logoNit').value.trim();
    const enlaceWeb = document.getElementById('logoLink').value.trim();
    const logoFile = document.getElementById('newLogo').files[0];

    // Validaciones
    if (!entidad || !nit || !enlaceWeb) {
        showError('La Entidad, NIT y el Enlace Web son campos obligatorios.');
        return;
    }

    // Datos a enviar
    const datos = {
        entidad: entidad,
        nit: nit,
        enlace_web: enlaceWeb,
        logoFile: logoFile || null
    };

    // Detectar cambios para el resumen del modal
    const changes = [];
    const current = window.currentConfig;

    if (entidad !== current.entidad) changes.push({ field: 'Entidad', value: entidad });
    if (nit !== current.nit) changes.push({ field: 'NIT', value: nit });
    if (enlaceWeb !== current.enlace_web) changes.push({ field: 'Web', value: enlaceWeb });
    if (logoFile) changes.push({ field: 'Logo', value: `Nuevo archivo: ${logoFile.name}` });

    if (changes.length === 0) {
        showError('No hay cambios para guardar.');
        return;
    }

    showConfirmationModal('logo', datos, changes);
}

function executeLogoUpdate(datos) {
    const btn = document.getElementById('saveLogoBtn');
    const originalText = setButtonLoading(btn, true);

    const formData = new FormData();
    formData.append('entidad', datos.entidad);
    formData.append('enlace_web', datos.enlace_web);
    formData.append('nit', datos.nit);
    if (datos.logoFile) {
        formData.append('logo', datos.logoFile);
    }

    fetch('../../api/parametrizacion_actualizar_logo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            cleanupLogoForm();
            cargarConfiguracion();
        } else {
            showError(data.error);
        }
    })
    .catch(err => showError("Error de conexión: " + err.message))
    .finally(() => setButtonLoading(btn, false, originalText));
}

// 3. LÓGICA DE ACTUALIZACIÓN DEL SISTEMA
function actualizarConfiguracionSistema() {
    const version_sistema = document.getElementById('version').value.trim();
    const tipo_licencia = document.getElementById('tipoLicencia').value.trim();
    const valida_hasta = document.getElementById('validaHasta').value;
    const desarrollado_por = document.getElementById('desarrolladoPor').value.trim();
    const direccion = document.getElementById('direccion').value.trim();
    const correo_contacto = document.getElementById('contacto').value.trim();
    const telefono = document.getElementById('telefono').value.trim();

    const datos = {
        version_sistema: version_sistema,
        tipo_licencia: tipo_licencia,
        valida_hasta: valida_hasta,
        desarrollado_por: desarrollado_por,
        direccion: direccion,
        correo_contacto: correo_contacto,
        telefono: telefono
    };
    
    // Validación básica
    if (!datos.version_sistema || !datos.desarrollado_por || !datos.correo_contacto || 
        !datos.telefono || !datos.direccion || !datos.valida_hasta || !datos.tipo_licencia) {
        showError('Complete los campos obligatorios.');
        return;
    }
    
    const changes = [];
    const current = window.currentConfig;
    
    if(version_sistema !== current.version_sistema) changes.push({ field: 'Versión del Sistema', value: version_sistema });
    if(tipo_licencia !== current.tipo_licencia) changes.push({ field: 'Tipo de Licencia', value: tipo_licencia });
    if(valida_hasta !== current.valida_hasta) changes.push({ field: 'Válida Hasta', value: valida_hasta });
    if(desarrollado_por !== current.desarrollado_por) changes.push({ field: 'Desarrollado Por', value: desarrollado_por });
    if(direccion !== current.direccion) changes.push({ field: 'Dirección', value: direccion });
    if(correo_contacto !== current.correo_contacto) changes.push({ field: 'Correo de Contacto', value: correo_contacto });
    if(telefono !== current.telefono) changes.push({ field: 'Teléfono', value: telefono });

    if (changes.length === 0) {
        showError('No hay cambios para guardar.');
        return;
    }

    showConfirmationModal('system', datos, changes);
}

function executeSystemUpdate(datos) {
    const btn = document.getElementById('saveConfigBtn');
    const originalText = setButtonLoading(btn, true);

    fetch('../../api/parametrizacion_actualizar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSuccess("Configuración del sistema actualizada.");
            cargarConfiguracion();
        } else {
            showError(data.error);
        }
    })
    .catch(err => showError("Error: " + err.message))
    .finally(() => setButtonLoading(btn, false, originalText));
}

// 4. GESTIÓN DEL MODAL DE CONFIRMACIÓN
function setupModalEvents() {
    const modal = document.getElementById('confirmationModal');
    const confirmBtn = document.getElementById('confirmSaveBtn');
    const cancelBtn = document.querySelector('.modal-actions .btn-secondary');

    if (!modal) return;

    // Click fuera cierra
    modal.addEventListener('click', (e) => { 
        if (e.target === modal) closeModal(); 
    });
    
    // ESC cierra
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape') closeModal(); 
    });

    // Botones
    if (confirmBtn) confirmBtn.addEventListener('click', executePendingAction);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
}

function showConfirmationModal(type, data, changes) {
    if (type === 'logo') pendingAction = executeLogoUpdate;
    else if (type === 'system') pendingAction = executeSystemUpdate;
    
    actionData = data;

    const changesList = document.getElementById('changesList');
    changesList.innerHTML = '';
    
    changes.forEach(c => {
        const li = document.createElement('li');
        li.innerHTML = `<strong>${c.field}:</strong> ${c.value}`;
        changesList.appendChild(li);
    });

    document.getElementById('modalDetails').style.display = changes.length ? 'block' : 'none';
    document.getElementById('confirmationModal').style.display = 'flex';
}

function executePendingAction() {
    if (pendingAction && actionData) pendingAction(actionData);
    closeModal();
}

function closeModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    pendingAction = null;
    actionData = null;
}

// 5. LÓGICA PARA MUNICIPIOS
function cargarMunicipios(scrollPosition = null) {
    const tablaBody = document.getElementById('municipiosTable');
    if (!tablaBody) return;
    
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="5">Cargando municipios...</td></tr>';
    
    fetch('../../api/ObtenerMunicipio.php')
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">${data.error || 'Error al cargar municipios'}</td></tr>`;
                return;
            }
            
            if (data.data.length === 0) {
                tablaBody.innerHTML = '<tr><td colspan="5" class="empty-row">No hay municipios registrados</td></tr>';
                return;
            }
            
            // Generar filas de la tabla (sin columna ID)
            tablaBody.innerHTML = '';
            data.data.forEach(municipio => {
                const fila = document.createElement('tr');
                
                // Determinar botón según estado
                let botonEstado = '';
                if (municipio.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="mostrarConfirmacionEstado(${municipio.id_municipio}, false, '${municipio.nombre.replace(/'/g, "\\'")}', '${municipio.codigo_dane}', '${municipio.departamento.replace(/'/g, "\\'")}')" >
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="mostrarConfirmacionEstado(${municipio.id_municipio}, true, '${municipio.nombre.replace(/'/g, "\\'")}', '${municipio.codigo_dane}', '${municipio.departamento.replace(/'/g, "\\'")}')">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }
                
                fila.innerHTML = `
                    <td>${municipio.nombre}</td>
                    <td>${municipio.codigo_dane || '--'}</td>
                    <td>${municipio.departamento}</td>
                    <td><span class="status-badge ${municipio.activo ? 'status-active' : 'status-inactive'}">${municipio.activo ? 'Activo' : 'Inactivo'}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-edit" onclick="editarMunicipio(${municipio.id_municipio})" title="Editar">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        ${botonEstado}
                    </td>
                `;
                tablaBody.appendChild(fila);
            });
            
            // Restaurar posición de scroll si se proporcionó
            if (scrollPosition !== null) {
                setTimeout(() => {
                    window.scrollTo(0, scrollPosition);
                }, 50);
            }
        })
        .catch(error => {
            console.error('Error cargando municipios:', error);
            tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">Error de conexión</td></tr>`;
            
            // Restaurar scroll incluso en error
            if (scrollPosition !== null) {
                setTimeout(() => {
                    window.scrollTo(0, scrollPosition);
                }, 50);
            }
        });
}

// 6. FUNCIÓN PARA CAMBIAR ESTADO DE MUNICIPIOS (CON MODAL)
function mostrarConfirmacionEstado(id, activar, nombre, codigoDane, departamento) {
    // Guardar datos para usar después
    municipioEstadoId = id;
    municipioEstadoAction = activar;
    
    const accion = activar ? 'activar' : 'desactivar';
    const mensaje = activar ? 
        '¿Está seguro de que desea ACTIVAR este municipio?<br><br>El municipio volverá a estar disponible en el sistema.' :
        '¿Está seguro de que desea DESACTIVAR este municipio?<br>';
    
    // Actualizar mensaje del modal
    document.getElementById('estadoMensaje').innerHTML = mensaje;
    
    // Actualizar detalles del municipio
    const detailsList = document.getElementById('municipioDetails');
    detailsList.innerHTML = `
        <li><strong>Sitios:</strong> ${nombre}</li>
        <li><strong>Código DANE:</strong> ${codigoDane || '--'}</li>
        <li><strong>Departamento:</strong> ${departamento}</li>
        <li><strong>Estado nuevo:</strong> <span class="${activar ? 'status-active' : 'status-inactive'}">${activar ? 'Activo' : 'Inactivo'}</span></li>
    `;
    
    // Mostrar modal
    document.getElementById('confirmEstadoModal').style.display = 'flex';
}

function ejecutarCambioEstado() {
    if (!municipioEstadoId || municipioEstadoAction === null) {
        showError('No se pudo completar la acción');
        return;
    }
    
    const accion = municipioEstadoAction ? 'activar' : 'desactivar';
    const datos = {
        id: municipioEstadoId,
        activo: municipioEstadoAction
    };
    
    // Guardar la posición actual de desplazamiento
    const scrollPosition = window.scrollY;
    
    fetch(`../../api/GestionMunicipio.php`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(data.message || `Municipio ${accion}do exitosamente`);
            // Recargar la tabla pasando la posición de scroll
            cargarMunicipios(scrollPosition);
        } else {
            showError(data.error || `Error al ${accion} municipio`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(`Error de conexión al ${accion} municipio`);
    })
    .finally(() => {
        closeEstadoModal();
    });
}

function closeEstadoModal() {
    document.getElementById('confirmEstadoModal').style.display = 'none';
    municipioEstadoId = null;
    municipioEstadoAction = null;
}

// Configurar eventos del modal de estado
function setupEstadoModalEvents() {
    const modal = document.getElementById('confirmEstadoModal');
    const confirmBtn = document.getElementById('confirmEstadoBtn');
    
    if (!modal) return;

    // Click fuera cierra
    modal.addEventListener('click', (e) => { 
        if (e.target === modal) closeEstadoModal(); 
    });
    
    // ESC cierra
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeEstadoModal();
        }
    });

    // Botón confirmar
    if (confirmBtn) confirmBtn.addEventListener('click', ejecutarCambioEstado);
}

// 7. FUNCIONES PARA GESTIÓN DE ÁREAS

function cargarAreas() {
    const tablaBody = document.getElementById('areasTable');
    if (!tablaBody) return;
    
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="5">Cargando áreas...</td></tr>';
    
    fetch('../../api/areas.php')
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">${data.error || 'Error al cargar áreas'}</td></tr>`;
                return;
            }
            
            if (data.data.length === 0) {
                tablaBody.innerHTML = '<tr><td colspan="5" class="empty-row">No hay áreas registradas</td></tr>';
                return;
            }
            
            // Generar filas de la tabla
            tablaBody.innerHTML = '';
            data.data.forEach(area => {
                const fila = document.createElement('tr');
                
                // Determinar botón según estado
                let botonEstado = '';
                if (area.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="mostrarConfirmacionEstadoArea(${area.id_area}, false, '${area.nombre.replace(/'/g, "\\'")}', '${area.codigo_area}')" title="Desactivar">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="mostrarConfirmacionEstadoArea(${area.id_area}, true, '${area.nombre.replace(/'/g, "\\'")}', '${area.codigo_area}')" title="Activar">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }
                
                // Truncar descripción si es muy larga
                const descripcionCorta = area.descripcion && area.descripcion.length > 50 ? 
                    area.descripcion.substring(0, 50) + '...' : 
                    (area.descripcion || '');
                
                fila.innerHTML = `
                    <td>${area.nombre}</td>
                    <td>${area.codigo_area}</td>
                    <td title="${area.descripcion || ''}">${descripcionCorta}</td>
                    <td><span class="status-badge ${area.activo ? 'status-active' : 'status-inactive'}">${area.activo ? 'Activo' : 'Inactivo'}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-edit" onclick="editarArea(${area.id_area})" title="Editar">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        ${botonEstado}
                    </td>
                `;
                tablaBody.appendChild(fila);
            });
        })
        .catch(error => {
            console.error('Error cargando áreas:', error);
            tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">Error de conexión</td></tr>`;
        });
}

function buscarAreas(termino) {
    const tablaBody = document.getElementById('areasTable');
    if (!tablaBody) return;
    
    if (!termino || termino.trim() === '') {
        cargarAreas();
        return;
    }
    
    // Mostrar estado de carga
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="5">Buscando áreas...</td></tr>';
    
    // Usar endpoint de búsqueda del backend
    fetch(`../../api/areas.php?buscar=${encodeURIComponent(termino)}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                tablaBody.innerHTML = `<tr><td colspan="5" class="empty-row">No se encontraron áreas con "${termino}"</td></tr>`;
                return;
            }
            
            // Mostrar resultados de búsqueda
            tablaBody.innerHTML = '';
            data.data.forEach(area => {
                const fila = document.createElement('tr');
                let botonEstado = '';
                if (area.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="mostrarConfirmacionEstadoArea(${area.id_area}, false, '${area.nombre.replace(/'/g, "\\'")}', '${area.codigo_area}')" title="Desactivar">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="mostrarConfirmacionEstadoArea(${area.id_area}, true, '${area.nombre.replace(/'/g, "\\'")}', '${area.codigo_area}')" title="Activar">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }

                const descripcionCorta = area.descripcion && area.descripcion.length > 50 ? 
                    area.descripcion.substring(0, 50) + '...' : 
                    (area.descripcion || '');

                fila.innerHTML = `
                    <td>${area.nombre}</td>
                    <td>${area.codigo_area}</td>
                    <td title="${area.descripcion || ''}">${descripcionCorta}</td>
                    <td><span class="status-badge ${area.activo ? 'status-active' : 'status-inactive'}">${area.activo ? 'Activo' : 'Inactivo'}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-edit" onclick="editarArea(${area.id_area})" title="Editar">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        ${botonEstado}
                    </td>
                `;
                tablaBody.appendChild(fila);
            });
        })
        .catch(error => {
            console.error('Error buscando áreas:', error);
            tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">Error en la búsqueda</td></tr>`;
        });
}

// 8. MODALES Y FUNCIONES PARA ÁREAS

// Abrir modal para agregar/editar área
function abrirModalArea(modo = 'agregar', id = null) {
    const modal = document.getElementById('crudModal');
    const titulo = document.getElementById('modalTitle');
    const areaFields = document.getElementById('areaFields');
    const recordId = document.getElementById('recordId');
    const recordType = document.getElementById('recordType');
    
    // Ocultar otros formularios y mostrar solo el de áreas
    document.querySelectorAll('.form-fields').forEach(field => {
        field.style.display = 'none';
        field.classList.remove('active');
    });
    
    areaFields.style.display = 'block';
    areaFields.classList.add('active');
    
    // Limpiar formulario
    document.getElementById('nombreArea').value = '';
    document.getElementById('codigoArea').value = '';
    document.getElementById('descripcionArea').value = '';
    document.getElementById('estadoArea').value = '1';
    
    if (modo === 'agregar') {
        titulo.textContent = 'Agregar Nueva Área';
        recordId.value = '';
        recordType.value = 'area';
    } else if (modo === 'editar' && id) {
        titulo.textContent = 'Editar Área';
        recordId.value = id;
        recordType.value = 'area';
        
        // Cargar datos del área
        cargarDatosArea(id);
    }
    
    modal.style.display = 'flex';
}

// Cargar datos de área para editar
function cargarDatosArea(id) {
    fetch(`../../api/areas.php?id=${id}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data.success && data.data) {
                const area = data.data;
                document.getElementById('nombreArea').value = area.nombre || '';
                document.getElementById('codigoArea').value = area.codigo_area || '';
                document.getElementById('descripcionArea').value = area.descripcion || '';
                document.getElementById('estadoArea').value = area.activo ? '1' : '0';
            } else {
                showError(data.error || 'Error al cargar datos del área');
                closeCrudModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error de conexión al cargar área');
            closeCrudModal();
        });
}

// Función para mostrar confirmación de cambio de estado de área
function mostrarConfirmacionEstadoArea(id, activar, nombre, codigo) {
    // Guardar datos para usar después
    areaEstadoId = id;
    areaEstadoAction = activar;
    
    const accion = activar ? 'activar' : 'desactivar';
    const mensaje = activar ? 
        '¿Está seguro de que desea ACTIVAR esta área?<br><br>El área volverá a estar disponible en el sistema.' :
        '¿Está seguro de que desea DESACTIVAR esta área?<br><br>Nota: Se realizará un borrado lógico (cambiará a estado inactivo).';
    
    // Actualizar mensaje del modal
    document.getElementById('estadoAreaMensaje').innerHTML = mensaje;
    
    // Actualizar detalles del área
    const detailsList = document.getElementById('areaDetails');
    detailsList.innerHTML = `
        <li><strong>Área:</strong> ${nombre}</li>
        <li><strong>Código:</strong> ${codigo || '--'}</li>
        <li><strong>Estado nuevo:</strong> <span class="${activar ? 'status-active' : 'status-inactive'}">${activar ? 'Activo' : 'Inactivo'}</span></li>
    `;
    
    // Mostrar modal
    document.getElementById('confirmEstadoAreaModal').style.display = 'flex';
}

// Ejecutar cambio de estado de área
function ejecutarCambioEstadoArea() {
    if (!areaEstadoId || areaEstadoAction === null) {
        showError('No se pudo completar la acción');
        return;
    }
    
    const accion = areaEstadoAction ? 'activar' : 'desactivar';
    const datos = {
        id: areaEstadoId,
        activo: areaEstadoAction
    };
    
    fetch(`../../api/areas.php`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(data.message || `Área ${accion}da exitosamente`);
            cargarAreas(); // Recargar la tabla para actualizar botones
        } else {
            showError(data.error || `Error al ${accion} área`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(`Error de conexión al ${accion} área`);
    })
    .finally(() => {
        closeEstadoAreaModal();
    });
}

// Cerrar modal de estado de área
function closeEstadoAreaModal() {
    document.getElementById('confirmEstadoAreaModal').style.display = 'none';
    areaEstadoId = null;
    areaEstadoAction = null;
}

// Configurar eventos del modal de estado de área
function setupEstadoAreaModalEvents() {
    const modal = document.getElementById('confirmEstadoAreaModal');
    const confirmBtn = document.getElementById('confirmEstadoAreaBtn');
    
    if (!modal) return;

    // Click fuera cierra
    modal.addEventListener('click', (e) => { 
        if (e.target === modal) closeEstadoAreaModal(); 
    });
    
    // ESC cierra
    document.addEventListener('keydown', (e) => { 
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeEstadoAreaModal();
        }
    });

    // Botón confirmar
    if (confirmBtn) confirmBtn.addEventListener('click', ejecutarCambioEstadoArea);
}

// 9. FUNCIONES CRUD UNIFICADAS

// Función específica para guardar área
function guardarArea(recordId, btn, originalText) {
    // Obtener datos del formulario
    const datos = {
        nombre: document.getElementById('nombreArea').value.trim(),
        codigo_area: document.getElementById('codigoArea').value.trim(),
        descripcion: document.getElementById('descripcionArea').value.trim(),
        activo: document.getElementById('estadoArea').value === '1'
    };
    
    // Validaciones
    if (!datos.nombre) {
        showError('El nombre del área es requerido');
        return;
    }
    
    if (!datos.codigo_area) {
        showError('El código del área es requerido');
        return;
    }
    
    // Configurar petición
    const url = '../../api/areas.php';
    const metodo = recordId ? 'PUT' : 'POST';
    const bodyData = recordId ? { ...datos, id: parseInt(recordId) } : datos;
    
    // Mostrar estado de carga
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;
    
    fetch(url, {
        method: metodo,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(bodyData)
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Área guardada exitosamente');
            closeCrudModal();
            cargarAreas(); // Recargar la tabla
        } else {
            showError(data.error || 'Error al guardar área');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error de conexión al guardar área');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function editarArea(id) {
    abrirModalArea('editar', id);
}

// 10. CRUD COMPLETO PARA MUNICIPIOS

// Abrir modal para agregar/editar municipio
function abrirModalMunicipio(modo = 'agregar', id = null) {
    const modal = document.getElementById('crudModal');
    const titulo = document.getElementById('modalTitle');
    const municipioFields = document.getElementById('municipioFields');
    const recordId = document.getElementById('recordId');
    const recordType = document.getElementById('recordType');
    
    // Ocultar otros formularios y mostrar solo el de municipios
    document.querySelectorAll('.form-fields').forEach(field => {
        field.style.display = 'none';
        field.classList.remove('active');
    });
    
    municipioFields.style.display = 'block';
    municipioFields.classList.add('active');
    
    // Limpiar formulario
    document.getElementById('nombreMunicipio').value = '';
    document.getElementById('codigoDane').value = '';
    document.getElementById('departamentoMunicipio').value = 'Meta';
    
    if (modo === 'agregar') {
        titulo.textContent = 'Agregar Nuevo Municipio';
        recordId.value = '';
        recordType.value = 'municipio';
    } else if (modo === 'editar' && id) {
        titulo.textContent = 'Editar Municipio';
        recordId.value = id;
        recordType.value = 'municipio';
        
        // Cargar datos del municipio
        cargarDatosMunicipio(id);
    }
    
    modal.style.display = 'flex';
}

// Cargar datos de municipio para editar
function cargarDatosMunicipio(id) {
    fetch(`../../api/GestionMunicipio.php?id=${id}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data.success && data.data) {
                const municipio = data.data;
                document.getElementById('nombreMunicipio').value = municipio.nombre || '';
                document.getElementById('codigoDane').value = municipio.codigo_dane || '';
                document.getElementById('departamentoMunicipio').value = municipio.departamento || 'Meta';
            } else {
                showError(data.error || 'Error al cargar datos del municipio');
                closeCrudModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error de conexión al cargar municipio');
            closeCrudModal();
        });
}

// Guardar municipio (crear o actualizar)
function guardarMunicipio(recordId, btn, originalText) {
    // Obtener datos del formulario
    const datos = {
        nombre: document.getElementById('nombreMunicipio').value.trim(),
        codigo_dane: document.getElementById('codigoDane').value.trim(),
        departamento: document.getElementById('departamentoMunicipio').value.trim(),
        activo: true
    };
    
    // Validaciones
    if (!datos.nombre) {
        showError('El nombre del municipio es requerido');
        return;
    }
    
    if (!datos.departamento) {
        showError('El departamento es requerido');
        return;
    }
    
    // Guardar posición de scroll
    const scrollPosition = window.scrollY;
    
    // Configurar petición
    const url = '../../api/GestionMunicipio.php';
    const metodo = recordId ? 'PUT' : 'POST';
    const bodyData = recordId ? { ...datos, id: parseInt(recordId) } : datos;
    
    // Mostrar estado de carga
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    btn.disabled = true;
    
    fetch(url, {
        method: metodo,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(bodyData)
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Municipio guardado exitosamente');
            closeCrudModal();
            // Recargar manteniendo posición
            cargarMunicipios(scrollPosition);
        } else {
            showError(data.error || 'Error al guardar municipio');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error de conexión al guardar municipio');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function editarMunicipio(id) {
    abrirModalMunicipio('editar', id);
}

function closeCrudModal() {
    document.getElementById('crudModal').style.display = 'none';
}

function buscarMunicipios(termino) {
    const tablaBody = document.getElementById('municipiosTable');
    if (!tablaBody) return;
    
    // Guardar posición de scroll antes de buscar
    const scrollPosition = window.scrollY;
    
    if (!termino || termino.trim() === '') {
        // Usar recarga con scroll
        cargarMunicipios(scrollPosition);
        return;
    }
    
    // Mostrar estado de carga
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="5">Buscando municipios...</td></tr>';
    
    fetch(`../../api/GestionMunicipio.php?buscar=${encodeURIComponent(termino)}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                tablaBody.innerHTML = `<tr><td colspan="5" class="empty-row">No se encontraron municipios con "${termino}"</td></tr>`;
                // Restaurar scroll
                setTimeout(() => {
                    window.scrollTo(0, scrollPosition);
                }, 50);
                return;
            }
            
            // Mostrar resultados de búsqueda
            tablaBody.innerHTML = '';
            data.data.forEach(municipio => {
                const fila = document.createElement('tr');
                let botonEstado = '';
                if (municipio.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="mostrarConfirmacionEstado(${municipio.id_municipio}, false, '${municipio.nombre.replace(/'/g, "\\'")}', '${municipio.codigo_dane}', '${municipio.departamento.replace(/'/g, "\\'")}')" title="Desactivar">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="mostrarConfirmacionEstado(${municipio.id_municipio}, true, '${municipio.nombre.replace(/'/g, "\\'")}', '${municipio.codigo_dane}', '${municipio.departamento.replace(/'/g, "\\'")}')" title="Activar">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }

                fila.innerHTML = `
                    <td>${municipio.nombre}</td>
                    <td>${municipio.codigo_dane || '--'}</td>
                    <td>${municipio.departamento}</td>
                    <td><span class="status-badge ${municipio.activo ? 'status-active' : 'status-inactive'}">${municipio.activo ? 'Activo' : 'Inactivo'}</span></td>
                    <td class="action-buttons">
                        <button class="btn-action btn-edit" onclick="editarMunicipio(${municipio.id_municipio})" title="Editar">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        ${botonEstado}
                    </td>
                `;
                tablaBody.appendChild(fila);
            });
            
            // Restaurar posición de scroll
            setTimeout(() => {
                window.scrollTo(0, scrollPosition);
            }, 50);
        })
        .catch(error => {
            console.error('Error buscando municipios:', error);
            tablaBody.innerHTML = `<tr><td colspan="5" class="error-row">Error en la búsqueda</td></tr>`;
            // Restaurar scroll incluso en error
            setTimeout(() => {
                window.scrollTo(0, scrollPosition);
            }, 50);
        });
}

// 11. UTILIDADES Y AYUDAS UI

// Manejo de Input File y Preview
function handleFileSelection(e) {
    const file = e.target.files[0];
    const fileNameDisplay = document.getElementById('fileName');
    
    if (file) {
        fileNameDisplay.textContent = file.name;
        if (file.type.startsWith('image/')) {
            previewImage(file);
        } else {
            showError("Por favor seleccione un archivo de imagen válido.");
            e.target.value = '';
            hideImagePreview();
        }
    } else {
        fileNameDisplay.textContent = "Seleccionar archivo...";
        hideImagePreview();
    }
}

function previewImage(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('currentLogo').style.display = 'none';
        
        let preview = document.getElementById('imagePreview');
        if (!preview) {
            preview = document.createElement('img');
            preview.id = 'imagePreview';
            document.querySelector('.logo-preview').appendChild(preview);
        }
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function hideImagePreview() {
    const preview = document.getElementById('imagePreview');
    if (preview) preview.style.display = 'none';
    
    const current = document.getElementById('currentLogo');
    if (current) current.style.display = 'block';
}

function cleanupLogoForm() {
    document.getElementById('newLogo').value = '';
    document.getElementById('fileName').textContent = 'Seleccionar archivo...';
    hideImagePreview();
}

function updateLogoImages(url) {
    if (!url) return;
    
    const timestamp = new Date().getTime();
    let fullUrl = url + '?t=' + timestamp;
    
    const mainLogo = document.getElementById('currentLogo');
    if (mainLogo) {
        mainLogo.src = fullUrl;
        mainLogo.onerror = function() {
            if (url.startsWith('/')) {
                mainLogo.src = url.substring(1) + '?t=' + timestamp;
            }
        };
    }

    const footerLogos = document.querySelectorAll('.footer-logo');
    footerLogos.forEach(img => {
        img.src = fullUrl;
    });
}

// Helpers generales
function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val || '';
}

function setButtonLoading(btn, isLoading, originalText = '') {
    if (isLoading) {
        const text = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        btn.disabled = true;
        return text;
    } else {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function showSuccess(msg) {
    const alert = document.getElementById("successAlert");
    if (alert) {
        alert.innerHTML = `<i class="fas fa-check-circle"></i> ${msg}`;
        alert.style.display = "block";
        setTimeout(() => alert.style.display = "none", 4000);
    }
}

function showError(msg) {
    const alert = document.getElementById("errorAlert");
    if (alert) {
        alert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${msg}`;
        alert.style.display = "block";
        setTimeout(() => alert.style.display = "none", 5000);
    }
}

// Funciones placeholder para los botones de Restaurar
function restaurarLogoPredeterminado() {
    if(!confirm("¿Restaurar logo por defecto?")) return;
    // Fetch a parametrizacion_restaurar_logo.php...
}

function restaurarConfiguracionPredeterminada() {
    if(!confirm("¿Restaurar toda la configuración?")) return;
    // Fetch a parametrizacion_restaurar.php...
}