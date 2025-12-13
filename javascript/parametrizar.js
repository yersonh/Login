document.addEventListener('DOMContentLoaded', function () {
    // ==========================
    // CARGAR CONFIGURACIÓN AL INICIAR
    // ==========================
    cargarConfiguracion();
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
            setValueIfExists("logoAltText", config.texto_alternativo);
            setValueIfExists("logoLink", config.enlace_web);

            // Actualizar logo si existe
            if (config.ruta_logo) {
                const currentLogo = document.getElementById("currentLogo");
                if (currentLogo) {
                    currentLogo.src = config.ruta_logo;
                    currentLogo.alt = config.texto_alternativo || "Logo del sistema";
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