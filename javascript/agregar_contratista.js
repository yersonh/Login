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
    
    document.getElementById('duracion_contrato').addEventListener('input', calcularFechaFinal);
    document.getElementById('fecha_inicio').addEventListener('change', calcularFechaFinal);
    
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
    
    function calcularFechaFinal() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const duracion = document.getElementById('duracion_contrato').value;
        const fechaFinalInput = document.getElementById('fecha_final');
        
        if (fechaInicio && duracion) {
            try {
                const [dia, mes, anio] = fechaInicio.split('/');
                const fechaInicioDate = new Date(anio, mes - 1, dia);
                
                const match = duracion.match(/(\d+)\s*(meses?|años?|semanas?|días?)/i);
                
                if (match) {
                    const cantidad = parseInt(match[1]);
                    const unidad = match[2].toLowerCase();
                    
                    let fechaFinalDate = new Date(fechaInicioDate);
                    
                    if (unidad.includes('mes')) {
                        fechaFinalDate.setMonth(fechaFinalDate.getMonth() + cantidad);
                    } else if (unidad.includes('año')) {
                        fechaFinalDate.setFullYear(fechaFinalDate.getFullYear() + cantidad);
                    } else if (unidad.includes('semana')) {
                        fechaFinalDate.setDate(fechaFinalDate.getDate() + (cantidad * 7));
                    } else if (unidad.includes('día')) {
                        fechaFinalDate.setDate(fechaFinalDate.getDate() + cantidad);
                    }
                    
                    const diaFinal = String(fechaFinalDate.getDate()).padStart(2, '0');
                    const mesFinal = String(fechaFinalDate.getMonth() + 1).padStart(2, '0');
                    const anioFinal = fechaFinalDate.getFullYear();
                    
                    if (fechaFinalInput) {
                        fechaFinalInput.value = `${diaFinal}/${mesFinal}/${anioFinal}`;
                    }
                    
                } else {
                    if (fechaFinalInput) {
                        fechaFinalInput.value = '';
                    }
                }
                
            } catch (error) {
                console.error('Error calculando fecha final:', error);
                if (fechaFinalInput) {
                    fechaFinalInput.value = '';
                }
            }
        } else {
            if (fechaFinalInput) {
                fechaFinalInput.value = '';
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