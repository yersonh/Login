// =======================================
// VARIABLES GLOBALES
// =======================================
let pendingAction = null; // Almacena la función a ejecutar tras confirmar en el modal
let actionData = null;    // Almacena los datos para esa función
window.currentConfig = {}; // Almacena el estado actual de la BD para comparar cambios

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
    
    // --- Botón Guardar en Modal CRUD ---
    const saveCrudBtn = document.getElementById('saveCrudBtn');
    if (saveCrudBtn) {
        saveCrudBtn.addEventListener('click', guardarMunicipio);
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
    
    // 4. CARGAR DATOS DINÁMICOS
    setTimeout(cargarMunicipios, 500);
});

// =======================================
// 1. CARGAR DATOS DE CONFIGURACIÓN
// =======================================
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

// =======================================
// 2. LÓGICA DE ACTUALIZACIÓN DEL LOGO
// =======================================
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

// =======================================
// 3. LÓGICA DE ACTUALIZACIÓN DEL SISTEMA
// =======================================
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

// =======================================
// 4. GESTIÓN DEL MODAL DE CONFIRMACIÓN
// =======================================
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

// =======================================
// 5. LÓGICA PARA MUNICIPIOS
// =======================================
function cargarMunicipios() {
    const tablaBody = document.getElementById('municipiosTable');
    if (!tablaBody) return;
    
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="6">Cargando municipios...</td></tr>';
    
    fetch('../../api/ObtenerMunicipio.php')
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                tablaBody.innerHTML = `<tr><td colspan="6" class="error-row">${data.error || 'Error al cargar municipios'}</td></tr>`;
                return;
            }
            
            if (data.data.length === 0) {
                tablaBody.innerHTML = '<tr><td colspan="6" class="empty-row">No hay municipios registrados</td></tr>';
                return;
            }
            
            // Generar filas de la tabla
            tablaBody.innerHTML = '';
            data.data.forEach(municipio => {
                const fila = document.createElement('tr');
                
                // Determinar botón según estado
                let botonEstado = '';
                if (municipio.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="cambiarEstadoMunicipio(${municipio.id_municipio}, false)" title="Desactivar">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="cambiarEstadoMunicipio(${municipio.id_municipio}, true)" title="Activar">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }
                
                fila.innerHTML = `
                    <td>${municipio.id_municipio}</td>
                    <td>${municipio.nombre}</td>
                    <td>${municipio.codigo_dane}</td>
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
        })
        .catch(error => {
            console.error('Error cargando municipios:', error);
            tablaBody.innerHTML = `<tr><td colspan="6" class="error-row">Error de conexión</td></tr>`;
        });
}

// =======================================
// 6. FUNCIÓN PARA CAMBIAR ESTADO DE MUNICIPIOS
// =======================================
function cambiarEstadoMunicipio(id, activar) {
    const accion = activar ? 'activar' : 'desactivar';
    const mensaje = activar ? 
        `¿Está seguro de que desea ACTIVAR este municipio?\n\nEl municipio volverá a estar disponible en el sistema.` :
        `¿Está seguro de que desea DESACTIVAR este municipio?\n\nNota: Se realizará un borrado lógico (cambiará a estado inactivo).`;
    
    if (!confirm(mensaje)) {
        return;
    }
    
    // Datos a enviar usando PATCH (formato correcto según endpoint)
    const datos = {
        id: id,
        activo: activar
    };
    
    fetch(`../../api/GestionMunicipio.php`, {
        method: 'PATCH',  // ✅ Usando PATCH como definiste en el endpoint
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
            cargarMunicipios(); // Recargar la tabla para actualizar botones
        } else {
            showError(data.error || `Error al ${accion} municipio`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(`Error de conexión al ${accion} municipio`);
    });
}

// =======================================
// 7. UTILIDADES Y AYUDAS UI
// =======================================

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

// =======================================
// 8. CRUD COMPLETO PARA MUNICIPIOS
// =======================================

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
function guardarMunicipio() {
    const recordId = document.getElementById('recordId').value;
    const recordType = document.getElementById('recordType').value;
    const btn = document.getElementById('saveCrudBtn');
    const originalText = btn.innerHTML;
    
    if (recordType !== 'municipio') return;
    
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
            cargarMunicipios(); // Recargar la tabla
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
    
    if (!termino || termino.trim() === '') {
        cargarMunicipios();
        return;
    }
    
    // Mostrar estado de carga
    tablaBody.innerHTML = '<tr class="loading-row"><td colspan="6">Buscando municipios...</td></tr>';
    
    fetch(`../../api/GestionMunicipio.php?buscar=${encodeURIComponent(termino)}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success || !data.data || data.data.length === 0) {
                tablaBody.innerHTML = `<tr><td colspan="6" class="empty-row">No se encontraron municipios con "${termino}"</td></tr>`;
                return;
            }
            
            // Mostrar resultados de búsqueda
            tablaBody.innerHTML = '';
            data.data.forEach(municipio => {
                const fila = document.createElement('tr');
                let botonEstado = '';
                if (municipio.activo) {
                    botonEstado = `
                        <button class="btn-action btn-deactivate" onclick="cambiarEstadoMunicipio(${municipio.id_municipio}, false)" title="Desactivar">
                            <i class="fas fa-ban"></i> Desactivar
                        </button>`;
                } else {
                    botonEstado = `
                        <button class="btn-action btn-activate" onclick="cambiarEstadoMunicipio(${municipio.id_municipio}, true)" title="Activar">
                            <i class="fas fa-check-circle"></i> Activar
                        </button>`;
                }

                fila.innerHTML = `
                    <td>${municipio.id_municipio}</td>
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
        })
        .catch(error => {
            console.error('Error buscando municipios:', error);
            tablaBody.innerHTML = `<tr><td colspan="6" class="error-row">Error en la búsqueda</td></tr>`;
        });
}