document.addEventListener('DOMContentLoaded', function() {
    flatpickr.localize(flatpickr.l10ns.es);
    
    const dateOptions = {
        dateFormat: "d/m/Y",
        locale: "es",
        allowInput: true
    };
    
    document.querySelectorAll('input[placeholder*="dd/mm/aaaa"]').forEach(input => {
        flatpickr(input, dateOptions);
    });
    
    // === CAMBIAR: Quitar eventos antiguos y agregar nuevos para calcular duración ===
    // Quitar estos:
    // document.getElementById('duracion_contrato').addEventListener('input', calcularFechaFinal);
    // document.getElementById('fecha_inicio').addEventListener('change', calcularFechaFinal);
    
    // Agregar estos:
    document.getElementById('fecha_inicio').addEventListener('change', calcularDuracionContrato);
    document.getElementById('fecha_final').addEventListener('change', calcularDuracionContrato);
    
    // Hacer el campo de duración de solo lectura
    const duracionContratoInput = document.getElementById('duracion_contrato');
    if (duracionContratoInput) {
        duracionContratoInput.readOnly = true;
        duracionContratoInput.style.backgroundColor = '#f8f9fa';
        duracionContratoInput.style.cursor = 'not-allowed';
    }
    
    // === MANEJO DE CAMPOS DE DIRECCIÓN CONDICIONALES ===
    const municipioSecundario = document.getElementById('id_municipio_secundario');
    const grupoDireccionSecundario = document.getElementById('grupo_direccion_secundario');
    const direccionSecundario = document.getElementById('direccion_municipio_secundario');
    
    const municipioTerciario = document.getElementById('id_municipio_terciario');
    const grupoDireccionTerciario = document.getElementById('grupo_direccion_terciario');
    const direccionTerciario = document.getElementById('direccion_municipio_terciario');

    function toggleDireccionOpcional(selectElement, grupoElement, inputElement) {
        if (selectElement.value && selectElement.value !== '0') {
            grupoElement.style.display = 'block';
            inputElement.required = true;
        } else {
            grupoElement.style.display = 'none';
            inputElement.required = false;
            inputElement.value = '';
        }
    }
    
    // Event listeners para municipio secundario
    if (municipioSecundario) {
        municipioSecundario.addEventListener('change', function() {
            toggleDireccionOpcional(this, grupoDireccionSecundario, direccionSecundario);
        });
    }
    
    // Event listeners para municipio terciario
    if (municipioTerciario) {
        municipioTerciario.addEventListener('change', function() {
            toggleDireccionOpcional(this, grupoDireccionTerciario, direccionTerciario);
        });
    }
    
    // Inicializar estado de los campos de dirección
    if (municipioSecundario && grupoDireccionSecundario && direccionSecundario) {
        toggleDireccionOpcional(municipioSecundario, grupoDireccionSecundario, direccionSecundario);
    }
    
    if (municipioTerciario && grupoDireccionTerciario && direccionTerciario) {
        toggleDireccionOpcional(municipioTerciario, grupoDireccionTerciario, direccionTerciario);
    }
    
    setupFileInput('adjuntar_contrato', 'contratoPreview', 'contratoFilename');
    setupFileInput('adjuntar_acta_inicio', 'actaPreview', 'actaFilename');
    setupFileInput('adjuntar_rp', 'rpPreview', 'rpFilename');
    
    function setupFileInput(inputId, previewId, filenameId) {
        const fileInput = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const filenameSpan = document.getElementById(filenameId);
        
        if (fileInput && preview && filenameSpan) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    const maxSize = 5 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert('El archivo excede el tamaño máximo de 5MB');
                        this.value = '';
                        preview.style.display = 'none';
                        return;
                    }

                    const allowedExtensions = ['.pdf'];
                    if (inputId === 'adjuntar_cv') {
                        allowedExtensions.push('.doc', '.docx');
                    }
                    
                    const fileName = file.name.toLowerCase();
                    const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                    
                    if (!isValidExtension) {
                        const formatos = inputId === 'adjuntar_cv' 
                            ? 'PDF, DOC y DOCX' 
                            : 'PDF';
                        alert(`Solo se permiten archivos ${formatos}`);
                        this.value = '';
                        preview.style.display = 'none';
                        return;
                    }
                    
                    filenameSpan.textContent = file.name;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
            });
        }
    }
    
    window.removeContrato = function() {
        const input = document.getElementById('adjuntar_contrato');
        const preview = document.getElementById('contratoPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
    window.removeActaInicio = function() {
        const input = document.getElementById('adjuntar_acta_inicio');
        const preview = document.getElementById('actaPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
    window.removeRP = function() {
        const input = document.getElementById('adjuntar_rp');
        const preview = document.getElementById('rpPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
    const cvInput = document.getElementById('adjuntar_cv');
    const cvPreview = document.getElementById('cvPreview');
    const cvFilename = document.getElementById('cvFilename');
    
    if (cvInput && cvPreview && cvFilename) {
        cvInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validar tamaño (5MB máximo)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('El archivo excede el tamaño máximo de 5MB');
                    this.value = '';
                    cvPreview.style.display = 'none';
                    return;
                }

                const allowedExtensions = ['.pdf', '.doc', '.docx'];
                const fileName = file.name.toLowerCase();
                const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                
                if (!isValidExtension) {
                    alert('Solo se permiten archivos PDF, DOC y DOCX');
                    this.value = '';
                    cvPreview.style.display = 'none';
                    return;
                }

                cvFilename.textContent = file.name;
                cvPreview.style.display = 'block';
            } else {
                cvPreview.style.display = 'none';
            }
        });
    }
    
    window.removeCV = function() {
        if (cvInput) cvInput.value = '';
        if (cvPreview) cvPreview.style.display = 'none';
    };
    
    // === NUEVA FUNCIÓN: CALCULAR DURACIÓN DEL CONTRATO ===
    function calcularDuracionContrato() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFinal = document.getElementById('fecha_final').value;
        const duracionInput = document.getElementById('duracion_contrato');
        
        if (fechaInicio && fechaFinal) {
            try {
                // Convertir fechas de formato dd/mm/aaaa a objetos Date
                const [diaInicio, mesInicio, anioInicio] = fechaInicio.split('/');
                const [diaFinal, mesFinal, anioFinal] = fechaFinal.split('/');
                
                const fechaInicioDate = new Date(anioInicio, mesInicio - 1, diaInicio);
                const fechaFinalDate = new Date(anioFinal, mesFinal - 1, diaFinal);
                
                // Validar que la fecha final sea mayor que la fecha inicio
                if (fechaFinalDate <= fechaInicioDate) {
                    alert('La fecha final debe ser mayor a la fecha de inicio');
                    duracionInput.value = '';
                    return;
                }
                
                // Calcular diferencia en días
                const diferenciaMs = fechaFinalDate - fechaInicioDate;
                const diferenciaDias = Math.floor(diferenciaMs / (1000 * 60 * 60 * 24));
                
                // Calcular duración en meses (aproximado)
                const meses = Math.floor(diferenciaDias / 30);
                const diasRestantes = diferenciaDias % 30;
                
                // Calcular duración en años
                const años = Math.floor(meses / 12);
                const mesesRestantes = meses % 12;
                
                let duracionTexto = '';
                
                // Formatear la duración
                if (años > 0) {
                    duracionTexto += `${años} ${años === 1 ? 'año' : 'años'}`;
                    if (mesesRestantes > 0) {
                        duracionTexto += ` ${mesesRestantes} ${mesesRestantes === 1 ? 'mes' : 'meses'}`;
                    }
                } else if (meses > 0) {
                    duracionTexto += `${meses} ${meses === 1 ? 'mes' : 'meses'}`;
                    if (diasRestantes > 0 && meses < 3) { // Solo mostrar días si es menos de 3 meses
                        duracionTexto += ` ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
                    }
                } else {
                    duracionTexto += `${diferenciaDias} ${diferenciaDias === 1 ? 'día' : 'días'}`;
                }
                
                // Actualizar el campo de duración
                if (duracionInput) {
                    duracionInput.value = duracionTexto;
                }
                
            } catch (error) {
                console.error('Error calculando duración del contrato:', error);
                if (duracionInput) {
                    duracionInput.value = '';
                }
            }
        } else {
            if (duracionInput) {
                duracionInput.value = '';
            }
        }
    }
    
    // === BOTONES DE NAVEGACIÓN ===
    const volverBtn = document.getElementById('volverBtn');
    if (volverBtn) {
        volverBtn.addEventListener('click', function() {
            window.location.href = 'menuContratistas.php';
        });
    }
    
    const cancelarBtn = document.getElementById('cancelarBtn');
    if (cancelarBtn) {
        cancelarBtn.addEventListener('click', function() {
            if (confirm('¿Está seguro de cancelar? Los datos no guardados se perderán.')) {
                window.location.href = 'menuContratistas.php';
            }
        });
    }
    
    const guardarBtn = document.getElementById('guardarBtn');
    if (guardarBtn) {
        guardarBtn.addEventListener('click', async function() {
            const requiredFields = document.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    valid = false;
                } else {
                    field.style.borderColor = '#e0e0e0';
                }
            });

            if (grupoDireccionSecundario && grupoDireccionSecundario.style.display === 'block') {
                if (!direccionSecundario.value.trim()) {
                    direccionSecundario.style.borderColor = '#dc3545';
                    valid = false;
                }
            }
            
            if (grupoDireccionTerciario && grupoDireccionTerciario.style.display === 'block') {
                if (!direccionTerciario.value.trim()) {
                    direccionTerciario.style.borderColor = '#dc3545';
                    valid = false;
                }
            }
            
            // Validar que la duración del contrato esté calculada
            const duracionContrato = document.getElementById('duracion_contrato');
            if (duracionContrato && !duracionContrato.value.trim()) {
                alert('Por favor complete las fechas de inicio y final para calcular la duración del contrato');
                duracionContrato.style.borderColor = '#dc3545';
                return;
            }
            
            if (!valid) {
                alert('Por favor complete todos los campos obligatorios (*)');
                return;
            }
            
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFinal = document.getElementById('fecha_final').value;
            
            if (fechaInicio && fechaFinal) {
                const inicio = new Date(fechaInicio.split('/').reverse().join('-'));
                const final = new Date(fechaFinal.split('/').reverse().join('-'));
                
                if (inicio > final) {
                    alert('La fecha de inicio no puede ser mayor a la fecha final');
                    document.getElementById('fecha_inicio').style.borderColor = '#dc3545';
                    document.getElementById('fecha_final').style.borderColor = '#dc3545';
                    return;
                }
            }
            
            const btnOriginalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            this.disabled = true;
            
            const formData = new FormData();
            
            const formElements = document.querySelectorAll('input:not([type="file"]), select, textarea');
            formElements.forEach(element => {
                if (element.name && element.value !== undefined) {
                    // Excluir campos de archivo del procesamiento manual
                    if (!element.name.includes('adjuntar_')) {
                        if ((element.name === 'id_municipio_secundario' || element.name === 'id_municipio_terciario') 
                            && element.value === '0') {
                            formData.append(element.name, '');
                        } else {
                            formData.append(element.name, element.value);
                        }
                    }
                }
            });

            const archivos = [
                'adjuntar_cv', 
                'adjuntar_contrato', 
                'adjuntar_acta_inicio', 
                'adjuntar_rp'
            ];
            
            archivos.forEach(id => {
                const fileInput = document.getElementById(id);
                if (fileInput && fileInput.files[0]) {
                    formData.append(id, fileInput.files[0]);
                }
            });
            
            try {
                const response = await fetch('../../api/procesar_contratista.php', {
                    method: 'POST',
                    body: formData
                });
                
                const resultado = await response.json();
                
                if (resultado.success) {
                    alert(`¡Contratista registrado exitosamente!\n\nContratista N°: ${resultado.id_detalle}`);

                    if (resultado.proximo_consecutivo) {
                        const consecutivoElement = document.querySelector('.consecutivo-number');
                        if (consecutivoElement) {
                            consecutivoElement.textContent = resultado.proximo_consecutivo;
                        }
                    }

                    limpiarFormulario();

                    this.innerHTML = btnOriginalHTML;
                    this.disabled = false;
                    const primerCampo = document.getElementById('nombre_completo');
                    if (primerCampo) primerCampo.focus();

                    requiredFields.forEach(field => {
                        field.style.borderColor = '#e0e0e0';
                    });
                    
                } else {
                    alert('Error: ' + resultado.error);
                    
                    this.innerHTML = btnOriginalHTML;
                    this.disabled = false;
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión con el servidor. Por favor intente nuevamente.');
                
                this.innerHTML = btnOriginalHTML;
                this.disabled = false;
            }
        });
    }
    
    function limpiarFormulario() {
        // 1. Campos de texto principales
        const camposTextoLimpios = [
            'nombre_completo', 'cedula', 'correo', 'celular', 'direccion',
            'numero_contrato', 'duracion_contrato', 'numero_registro_presupuestal',
            'direccion_municipio_principal', 'direccion_municipio_secundario', 
            'direccion_municipio_terciario'
        ];
        
        camposTextoLimpios.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.value = '';
        });

        const fechaCampos = ['fecha_contrato', 'fecha_inicio', 'fecha_final', 'fecha_rp'];
        fechaCampos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.value = '';
                if (campo._flatpickr) {
                    campo._flatpickr.clear();
                }
            }
        });
        
        const selectsLimpiar = [
            'id_tipo_vinculacion', 
            'id_area', 
            'id_municipio_secundario', 
            'id_municipio_terciario'
        ];
        
        selectsLimpiar.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.selectedIndex = 0;
            }
        });

        const municipioPrincipal = document.getElementById('id_municipio_principal');
        if (municipioPrincipal) {
            municipioPrincipal.selectedIndex = 0;
            Array.from(municipioPrincipal.options).forEach(option => {
                if (option.text.includes('Villavicencio')) {
                    option.selected = true;
                }
            });
        }

        const archivos = [
            'adjuntar_cv', 
            'adjuntar_contrato', 
            'adjuntar_acta_inicio', 
            'adjuntar_rp'
        ];
        
        archivos.forEach(id => {
            const fileInput = document.getElementById(id);
            const previewId = id === 'adjuntar_cv' ? 'cvPreview' : 
                             id === 'adjuntar_contrato' ? 'contratoPreview' :
                             id === 'adjuntar_acta_inicio' ? 'actaPreview' : 'rpPreview';
            const preview = document.getElementById(previewId);
            
            if (fileInput) fileInput.value = '';
            if (preview) preview.style.display = 'none';
        });
        
        if (grupoDireccionSecundario) grupoDireccionSecundario.style.display = 'none';
        if (grupoDireccionTerciario) grupoDireccionTerciario.style.display = 'none';
        
        // Restablecer estilo del campo de duración
        const duracionContratoInput = document.getElementById('duracion_contrato');
        if (duracionContratoInput) {
            duracionContratoInput.style.borderColor = '#e0e0e0';
        }
    }

    const municipioPrincipal = document.getElementById('id_municipio_principal');
    if (municipioPrincipal) {
        municipioPrincipal.addEventListener('change', function() {
            const principalId = this.value;
            const principalNombre = this.options[this.selectedIndex].text;
            const secundario = document.getElementById('id_municipio_secundario');
            const terciario = document.getElementById('id_municipio_terciario');
            
            if (principalId && secundario && !secundario.value) {
                if (confirm(`¿Desea asignar "${principalNombre}" como municipio secundario?`)) {
                    secundario.value = principalId;
                    // Actualizar campo de dirección secundaria
                    toggleDireccionOpcional(secundario, grupoDireccionSecundario, direccionSecundario);
                }
            }
            
            if (principalId && terciario && !terciario.value) {
                if (confirm(`¿Desea asignar "${principalNombre}" como municipio terciario?`)) {
                    terciario.value = principalId;
                    // Actualizar campo de dirección terciaria
                    toggleDireccionOpcional(terciario, grupoDireccionTerciario, direccionTerciario);
                }
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
    
    function actualizarHora() {
        const now = new Date();
        const options = { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true 
        };
        const fechaHora = now.toLocaleDateString('es-ES', options).replace(',', '');
        const datetimeElement = document.querySelector('.datetime-display');
        if (datetimeElement) {
            datetimeElement.innerHTML = `<i class="fas fa-clock"></i> Ahora: ${fechaHora}`;
        }
    }
    
    setInterval(actualizarHora, 1000);
    actualizarHora();
});