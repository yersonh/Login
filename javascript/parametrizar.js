document.addEventListener('DOMContentLoaded', function () {
    // ==========================
    // CARGAR CONFIGURACIÓN AL INICIAR
    // ==========================
    cargarConfiguracion();
    
    // ==========================
    // EVENT LISTENERS
    // ==========================
    
    // Botón para guardar configuración del sistema
    const saveConfigBtn = document.getElementById('saveConfigBtn');
    if (saveConfigBtn) {
        saveConfigBtn.addEventListener('click', actualizarConfiguracion);
    }
    
    // Actualizar días restantes cuando cambia la fecha
    const validaHastaInput = document.getElementById('validaHasta');
    if (validaHastaInput) {
        validaHastaInput.addEventListener('change', function() {
            actualizarDiasRestantesUI(this.value);
        });
    }
    
    // Mostrar nombre de archivo seleccionado
    const newLogoInput = document.getElementById('newLogo');
    if (newLogoInput) {
        newLogoInput.addEventListener('change', function(e) {
            const fileName = document.getElementById('fileName');
            if (e.target.files.length > 0) {
                fileName.textContent = e.target.files[0].name;
            } else {
                fileName.textContent = 'Haga clic para seleccionar un archivo';
            }
        });
    }
});

// =======================================
// Cargar datos desde la BD
// =======================================
function cargarConfiguracion() {
    // Mostrar indicador de carga
    const successAlert = document.getElementById("successAlert");
    if (successAlert) {
        successAlert.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando configuración...';
        successAlert.style.display = "block";
    }
    
    // Llamar al endpoint
    fetch("../../controllers/parametrizacion_obtener.php")
        .then(res => {
            if (!res.ok) {
                throw new Error(`Error HTTP: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            // Ocultar indicador de carga
            if (successAlert) {
                successAlert.style.display = "none";
            }
            
            if (!data.success) {
                showError(data.error || "No se pudo cargar la configuración.");
                return;
            }

            const config = data.data;
            
            // Rellenar todos los campos del formulario con los datos de la BD
            setValueIfExists("version", config.version_sistema);
            setValueIfExists("tipoLicencia", config.tipo_licencia);
            setValueIfExists("validaHasta", config.valida_hasta);
            setValueIfExists("desarrolladoPor", config.desarrollado_por);
            setValueIfExists("direccion", config.direccion);
            setValueIfExists("contacto", config.correo_contacto);
            setValueIfExists("telefono", config.telefono);
            setValueIfExists("logoAltText", config.entidad);
            setValueIfExists("logoLink", config.enlace_web);

            // Actualizar días restantes
            if (config.valida_hasta) {
                actualizarDiasRestantesUI(config.valida_hasta);
            } else if (config.dias_restantes) {
                document.getElementById('diasRestantes').value = config.dias_restantes + ' días';
            }

            // Actualizar logo si existe
            if (config.ruta_logo) {
                const currentLogo = document.getElementById("currentLogo");
                if (currentLogo) {
                    currentLogo.src = config.ruta_logo;
                    currentLogo.alt = config.entidad || "Logo del sistema";
                }
                
                // También actualizar logo del footer si existe
                const footerLogo = document.querySelector(".footer-logo");
                if (footerLogo) {
                    footerLogo.src = config.ruta_logo;
                }
            }

            showSuccess("Configuración cargada correctamente");
        })
        .catch(error => {
            if (successAlert) {
                successAlert.style.display = "none";
            }
            showError("Error al conectar con el servidor: " + error.message);
            console.error("Error:", error);
        });
}

// =======================================
// Función para actualizar la configuración
// =======================================
function actualizarConfiguracion() {
    // Validar campos requeridos
    const version = document.getElementById('version').value.trim();
    const desarrolladoPor = document.getElementById('desarrolladoPor').value.trim();
    const contacto = document.getElementById('contacto').value.trim();
    
    if (!version || !desarrolladoPor || !contacto) {
        showError('Por favor complete los campos requeridos: Versión, Desarrollado por y Contacto');
        return;
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(contacto)) {
        showError('Por favor ingrese un correo electrónico válido');
        document.getElementById('contacto').focus();
        return;
    }
    
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('saveConfigBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    // Obtener todos los datos del formulario
    const datos = {
        version_sistema: version,
        tipo_licencia: document.getElementById('tipoLicencia').value.trim(),
        valida_hasta: document.getElementById('validaHasta').value,
        desarrollado_por: desarrolladoPor,
        direccion: document.getElementById('direccion').value.trim(),
        correo_contacto: contacto,
        telefono: document.getElementById('telefono').value.trim(),
        entidad: document.getElementById('logoAltText').value.trim(),
        enlace_web: document.getElementById('logoLink').value.trim()
        // ruta_logo se mantiene igual, no se envía para no sobreescribir
    };
    
    // Enviar datos al servidor
    fetch('../../controllers/parametrizacion_actualizar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess('Configuración actualizada correctamente');
            
            // Actualizar días restantes en la interfaz
            if (datos.valida_hasta) {
                actualizarDiasRestantesUI(datos.valida_hasta);
            }
            
            // Recargar los datos desde el servidor después de 1 segundo
            setTimeout(cargarConfiguracion, 1000);
        } else {
            showError(data.error || 'Error al actualizar la configuración');
        }
    })
    .catch(error => {
        showError('Error de conexión: ' + error.message);
        console.error('Error:', error);
    })
    .finally(() => {
        // Restaurar botón
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// =======================================
// Función para actualizar días restantes en la UI
// =======================================
function actualizarDiasRestantesUI(fechaValidaHasta) {
    if (!fechaValidaHasta) {
        document.getElementById('diasRestantes').value = 'N/A';
        return;
    }
    
    const hoy = new Date();
    const validaHasta = new Date(fechaValidaHasta);
    
    // Validar fecha
    if (isNaN(validaHasta.getTime())) {
        document.getElementById('diasRestantes').value = 'Fecha inválida';
        return;
    }
    
    const diferenciaTiempo = validaHasta.getTime() - hoy.getTime();
    const dias = Math.ceil(diferenciaTiempo / (1000 * 3600 * 24));
    
    let textoDias;
    if (dias > 0) {
        textoDias = dias + ' días';
    } else if (dias === 0) {
        textoDias = 'Hoy expira';
    } else {
        textoDias = '0 días (Expirada)';
    }
    
    document.getElementById('diasRestantes').value = textoDias;
}

// =======================================
// Función auxiliar para establecer valores
// =======================================
function setValueIfExists(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.value = value || "";
    }
}

// =======================================
// ALERTAS
// =======================================
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
        alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;
        alert.style.display = "block";
        setTimeout(() => alert.style.display = "none", 5000);
    }
}