// Variables globales
let guardarBtn;
let fechaInicioInput;
let duracionContratoInput;
let fechaFinalInput;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar variables
    guardarBtn = document.getElementById('guardarBtn');
    fechaInicioInput = document.getElementById('fecha_inicio');
    duracionContratoInput = document.getElementById('duracion_contrato');
    fechaFinalInput = document.getElementById('fecha_final');
    
    // Configurar Flatpickr para los campos de fecha
    inicializarFlatpickr();
    
    // Asignar eventos
    asignarEventos();
    
    // Iniciar actualización de hora
    iniciarActualizacionHora();
});

function inicializarFlatpickr() {
    flatpickr.localize(flatpickr.l10ns.es);
    
    const dateOptions = {
        dateFormat: "d/m/Y",
        locale: "es",
        allowInput: true,
        onChange: function(selectedDates, dateStr, instance) {
            // Remover borde rojo si hay valor
            if (dateStr) {
                instance.input.style.borderColor = '#e0e0e0';
            }
        }
    };
    
    // Aplicar a todos los campos de fecha
    document.querySelectorAll('input[placeholder*="dd/mm/aaaa"]').forEach(input => {
        flatpickr(input, dateOptions);
    });
}

function asignarEventos() {
    // Botones de navegación
    document.getElementById('volverBtn').addEventListener('click', function() {
        window.location.href = 'menuContratistas.php';
    });
    
    document.getElementById('cancelarBtn').addEventListener('click', function() {
        confirmarCancelar();
    });
    
    // Botón guardar
    if (guardarBtn) {
        guardarBtn.addEventListener('click', validarYGuardar);
    }
    
    // Cálculo automático de fecha final
    if (fechaInicioInput && duracionContratoInput) {
        fechaInicioInput.addEventListener('change', calcularFechaFinal);
        duracionContratoInput.addEventListener('change', calcularFechaFinal);
        duracionContratoInput.addEventListener('input', calcularFechaFinal);
    }
    
    // Auto-completar municipios
    document.getElementById('municipio_principal').addEventListener('change', function() {
        autoCompletarMunicipios(this);
    });
    
    // Validación en tiempo real de campos requeridos
    document.querySelectorAll('[required]').forEach(field => {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
        
        field.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '#e0e0e0';
            }
        });
    });
    
    // Prevenir envío accidental con Enter
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
}

function calcularFechaFinal() {
    if (!fechaInicioInput || !duracionContratoInput || !fechaFinalInput) return;
    
    const fechaInicio = fechaInicioInput.value.trim();
    const duracion = duracionContratoInput.value.trim();
    
    if (!fechaInicio || !duracion) return;
    
    try {
        // Parsear fecha de inicio (formato dd/mm/yyyy)
        const [dia, mes, anio] = fechaInicio.split('/').map(num => parseInt(num, 10));
        const fechaInicioDate = new Date(anio, mes - 1, dia);
        
        // Parsear duración (buscar números en el texto)
        const mesesMatch = duracion.match(/(\d+)\s*mes/i);
        const anosMatch = duracion.match(/(\d+)\s*año/i);
        
        let meses = 0;
        if (mesesMatch) {
            meses = parseInt(mesesMatch[1], 10);
        } else if (anosMatch) {
            meses = parseInt(anosMatch[1], 10) * 12;
        }
        
        if (meses > 0) {
            // Calcular fecha final
            const fechaFinalDate = new Date(fechaInicioDate);
            fechaFinalDate.setMonth(fechaFinalDate.getMonth() + meses);
            
            // Formatear fecha final
            const diaFinal = fechaFinalDate.getDate().toString().padStart(2, '0');
            const mesFinal = (fechaFinalDate.getMonth() + 1).toString().padStart(2, '0');
            const anioFinal = fechaFinalDate.getFullYear();
            
            const fechaFinalFormateada = `${diaFinal}/${mesFinal}/${anioFinal}`;
            
            // Solo actualizar si el campo está vacío o tiene el placeholder
            if (!fechaFinalInput.value || fechaFinalInput.value === "Por calcular") {
                fechaFinalInput.value = fechaFinalFormateada;
            }
        }
    } catch (error) {
        console.error('Error calculando fecha final:', error);
    }
}

function autoCompletarMunicipios(municipioPrincipalSelect) {
    const principal = municipioPrincipalSelect.value;
    const textoPrincipal = municipioPrincipalSelect.options[municipioPrincipalSelect.selectedIndex].text;
    
    const secundario = document.getElementById('municipio_secundario');
    const terciario = document.getElementById('municipio_terciario');
    
    if (!principal) return;
    
    // Municipio secundario
    if (secundario && !secundario.value) {
        const opcionesSecundario = Array.from(secundario.options).map(opt => opt.value);
        if (!opcionesSecundario.includes(principal)) {
            const nuevaOpcion = document.createElement('option');
            nuevaOpcion.value = principal;
            nuevaOpcion.textContent = textoPrincipal;
            secundario.appendChild(nuevaOpcion);
        }
    }
    
    // Municipio terciario
    if (terciario && !terciario.value) {
        const opcionesTerciario = Array.from(terciario.options).map(opt => opt.value);
        if (!opcionesTerciario.includes(principal)) {
            const nuevaOpcion = document.createElement('option');
            nuevaOpcion.value = principal;
            nuevaOpcion.textContent = textoPrincipal;
            terciario.appendChild(nuevaOpcion);
        }
    }
}

function confirmarCancelar() {
    if (confirm('¿Está seguro de cancelar? Los datos no guardados se perderán.')) {
        window.location.href = 'menuContratistas.php';
    }
}

function validarYGuardar() {
    // Validación básica de campos requeridos
    const requiredFields = document.querySelectorAll('[required]');
    let camposInvalidos = [];
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            camposInvalidos.push(field.id || field.name);
        } else {
            field.style.borderColor = '#e0e0e0';
        }
    });
    
    // Validación específica de email
    const emailField = document.getElementById('correo');
    if (emailField && emailField.value.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value.trim())) {
            emailField.style.borderColor = '#dc3545';
            camposInvalidos.push('correo (formato inválido)');
        }
    }
    
    // Validación de teléfono
    const telefonoField = document.getElementById('celular');
    if (telefonoField && telefonoField.value.trim()) {
        const telefonoRegex = /^[0-9\s\-\+\(\)]{7,15}$/;
        if (!telefonoRegex.test(telefonoField.value.trim())) {
            telefonoField.style.borderColor = '#dc3545';
            camposInvalidos.push('celular (formato inválido)');
        }
    }
    
    if (camposInvalidos.length > 0) {
        alert('Por favor complete correctamente todos los campos obligatorios (*)\n\nCampos con error: ' + camposInvalidos.join(', '));
        return;
    }
    
    // Confirmar antes de guardar
    if (!confirm('¿Está seguro de guardar los datos del contratista?')) {
        return;
    }
    
    // Deshabilitar botón y mostrar indicador de carga
    guardarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    guardarBtn.disabled = true;
    
    // En un caso real, aquí enviarías los datos al servidor
    // Por ahora, simulamos el guardado
    setTimeout(() => {
        // Simular éxito
        alert('Contratista registrado exitosamente');
        
        // Restaurar botón
        guardarBtn.innerHTML = '<i class="fas fa-save"></i> Guardar';
        guardarBtn.disabled = false;
        
        // Redirigir (en producción, esto dependería de la respuesta del servidor)
        window.location.href = 'menu_asistente.php';
    }, 1500);
}

function iniciarActualizacionHora() {
    // Función para formatear fecha/hora
    function formatearFechaHora(fecha) {
        const dia = fecha.getDate().toString().padStart(2, '0');
        const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
        const anio = fecha.getFullYear();
        
        let horas = fecha.getHours();
        const minutos = fecha.getMinutes().toString().padStart(2, '0');
        const segundos = fecha.getSeconds().toString().padStart(2, '0');
        
        const ampm = horas >= 12 ? 'PM' : 'AM';
        horas = horas % 12;
        horas = horas ? horas : 12; // La hora '0' debe ser '12'
        horas = horas.toString().padStart(2, '0');
        
        return `${dia}/${mes}/${anio} ${horas}:${minutos}:${segundos} ${ampm}`;
    }
    
    function actualizarHora() {
        const ahora = new Date();
        const fechaHoraFormateada = formatearFechaHora(ahora);
        
        const datetimeDisplay = document.querySelector('.datetime-display');
        if (datetimeDisplay) {
            datetimeDisplay.innerHTML = 
                `<i class="fas fa-clock"></i> Fecha/Hora Actual: ${fechaHoraFormateada}`;
        }
    }
    
    // Actualizar inmediatamente y cada segundo
    actualizarHora();
    setInterval(actualizarHora, 1000);
}

// Función para obtener datos del formulario (útil para AJAX)
function obtenerDatosFormulario() {
    return {
        nombre_completo: document.getElementById('nombre_completo').value,
        cedula: document.getElementById('cedula').value,
        correo: document.getElementById('correo').value,
        celular: document.getElementById('celular').value,
        direccion: document.getElementById('direccion').value,
        tipo_vinculacion: document.getElementById('tipo_vinculacion').value,
        municipio_principal: document.getElementById('municipio_principal').value,
        municipio_secundario: document.getElementById('municipio_secundario').value,
        municipio_terciario: document.getElementById('municipio_terciario').value,
        area: document.getElementById('area').value,
        numero_contrato: document.getElementById('numero_contrato').value,
        fecha_contrato: document.getElementById('fecha_contrato').value,
        fecha_inicio: document.getElementById('fecha_inicio').value,
        duracion_contrato: document.getElementById('duracion_contrato').value,
        fecha_final: document.getElementById('fecha_final').value,
        numero_registro_presupuestal: document.getElementById('numero_registro_presupuestal').value,
        fecha_rp: document.getElementById('fecha_rp').value,
        consecutivo: document.querySelector('.consecutivo-number').textContent
    };
}