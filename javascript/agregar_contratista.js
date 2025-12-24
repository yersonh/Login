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
    
    // === CALCULAR DURACIÓN DEL CONTRATO ===
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
    
    // === CONFIGURACIÓN DE ARCHIVOS - NUEVO: FOTO DE PERFIL ===
    setupFotoPerfil();
    setupFileInput('adjuntar_cv', 'cvPreview', 'cvFilename', ['pdf', 'doc', 'docx']);
    setupFileInput('adjuntar_contrato', 'contratoPreview', 'contratoFilename', ['pdf']);
    setupFileInput('adjuntar_acta_inicio', 'actaPreview', 'actaFilename', ['pdf']);
    setupFileInput('adjuntar_rp', 'rpPreview', 'rpFilename', ['pdf']);
    
    // === FUNCIÓN PARA MANEJAR FOTO DE PERFIL ===
    function setupFotoPerfil() {
        const fotoInput = document.getElementById('foto_perfil');
        const fotoPreview = document.getElementById('fotoPreviewImg');
        const fotoPlaceholder = document.querySelector('.foto-placeholder');
        const fotoError = document.getElementById('fotoError');
        const fotoErrorMessage = document.getElementById('fotoErrorMessage');
        
        if (fotoInput) {
            fotoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Validar tamaño máximo (10MB para fotos)
                    const maxSize = 10 * 1024 * 1024;
                    if (file.size > maxSize) {
                        mostrarErrorFoto('La imagen excede el tamaño máximo de 10MB');
                        this.value = '';
                        resetearFotoPreview();
                        return;
                    }
                    
                    // Validar tipo de imagen
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        mostrarErrorFoto('Solo se permiten imágenes JPG, JPEG, PNG o GIF');
                        this.value = '';
                        resetearFotoPreview();
                        return;
                    }
                    
                    // Leer y mostrar la imagen
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fotoPreview.src = e.target.result;
                        fotoPreview.style.display = 'block';
                        if (fotoPlaceholder) fotoPlaceholder.style.display = 'none';
                        ocultarErrorFoto();
                    };
                    reader.onerror = function() {
                        mostrarErrorFoto('Error al cargar la imagen');
                        resetearFotoPreview();
                    };
                    reader.readAsDataURL(file);
                } else {
                    resetearFotoPreview();
                }
            });
            
            // Efecto de arrastrar y soltar
            const fotoContainer = document.querySelector('.foto-container');
            if (fotoContainer) {
                fotoContainer.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('highlight');
                });
                
                fotoContainer.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('highlight');
                });
                
                fotoContainer.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('highlight');
                    
                    if (e.dataTransfer.files.length) {
                        const file = e.dataTransfer.files[0];
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fotoInput.files = dataTransfer.files;
                        
                        // Disparar evento change
                        const event = new Event('change', { bubbles: true });
                        fotoInput.dispatchEvent(event);
                    }
                });
            }
        }
        
        function mostrarErrorFoto(mensaje) {
            if (fotoError && fotoErrorMessage) {
                fotoErrorMessage.textContent = mensaje;
                fotoError.style.display = 'flex';
            }
        }
        
        function ocultarErrorFoto() {
            if (fotoError) {
                fotoError.style.display = 'none';
            }
        }
        
        function resetearFotoPreview() {
            if (fotoPreview) {
                fotoPreview.src = '';
                fotoPreview.style.display = 'none';
            }
            if (fotoPlaceholder) {
                fotoPlaceholder.style.display = 'flex';
                fotoPlaceholder.style.flexDirection = 'column';
                fotoPlaceholder.style.alignItems = 'center';
                fotoPlaceholder.style.justifyContent = 'center';
            }
            ocultarErrorFoto();
        }
        
        // Inicializar correctamente el placeholder
        if (fotoPlaceholder) {
            fotoPlaceholder.style.display = 'flex';
            fotoPlaceholder.style.flexDirection = 'column';
            fotoPlaceholder.style.alignItems = 'center';
            fotoPlaceholder.style.justifyContent = 'center';
        }
        
        // Botón para eliminar foto
        window.removeFotoPerfil = function() {
            if (fotoInput) fotoInput.value = '';
            resetearFotoPreview();
        };
    }
    
    function setupFileInput(inputId, previewId, filenameId, allowedExtensions) {
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

                    const fileName = file.name.toLowerCase();
                    const isValidExtension = allowedExtensions.some(ext => fileName.endsWith('.' + ext));
                    
                    if (!isValidExtension) {
                        const formatos = allowedExtensions.map(ext => ext.toUpperCase()).join(', ');
                        alert(`Solo se permiten archivos: ${formatos}`);
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
    
    // Funciones para eliminar archivos
    window.removeCV = function() {
        const input = document.getElementById('adjuntar_cv');
        const preview = document.getElementById('cvPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
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
    
    // === FUNCIÓN: CALCULAR DURACIÓN DEL CONTRATO ===
    function calcularDuracionContrato() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFinal = document.getElementById('fecha_final').value;
        const duracionInput = document.getElementById('duracion_contrato');
        
        if (fechaInicio && fechaFinal) {
            try {
                const [diaInicio, mesInicio, anioInicio] = fechaInicio.split('/');
                const [diaFinal, mesFinal, anioFinal] = fechaFinal.split('/');
                
                const fechaInicioDate = new Date(anioInicio, mesInicio - 1, diaInicio);
                const fechaFinalDate = new Date(anioFinal, mesFinal - 1, diaFinal);
                
                if (fechaFinalDate <= fechaInicioDate) {
                    alert('La fecha final debe ser mayor a la fecha de inicio');
                    duracionInput.value = '';
                    return;
                }
                
                const diferenciaMs = fechaFinalDate - fechaInicioDate;
                const diferenciaDias = Math.floor(diferenciaMs / (1000 * 60 * 60 * 24));
                
                const meses = Math.floor(diferenciaDias / 30);
                const diasRestantes = diferenciaDias % 30;
                
                const años = Math.floor(meses / 12);
                const mesesRestantes = meses % 12;
                
                let duracionTexto = '';
                
                if (años > 0) {
                    duracionTexto += `${años} ${años === 1 ? 'año' : 'años'}`;
                    if (mesesRestantes > 0) {
                        duracionTexto += ` ${mesesRestantes} ${mesesRestantes === 1 ? 'mes' : 'meses'}`;
                    }
                } else if (meses > 0) {
                    duracionTexto += `${meses} ${meses === 1 ? 'mes' : 'meses'}`;
                    if (diasRestantes > 0 && meses < 3) {
                        duracionTexto += ` ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
                    }
                } else {
                    duracionTexto += `${diferenciaDias} ${diferenciaDias === 1 ? 'día' : 'días'}`;
                }
                
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
    
    // === VALIDACIONES EN TIEMPO REAL ===
    function setupValidacionesEnTiempoReal() {
        const camposRequeridos = document.querySelectorAll('[required]');
        
        camposRequeridos.forEach(campo => {
            const errorElement = document.createElement('div');
            errorElement.className = 'validation-message';
            errorElement.style.color = '#dc3545';
            errorElement.style.fontSize = '12px';
            errorElement.style.marginTop = '5px';
            errorElement.style.display = 'none';
            
            campo.parentNode.appendChild(errorElement);
            
            // Remover validación automática al cargar la página
            campo.addEventListener('focus', function() {
                // Solo validar cuando el usuario haya interactuado
                this.dataset.touched = 'true';
            });
            
            campo.addEventListener('blur', function() {
                if (this.dataset.touched === 'true') {
                    validarCampo(this, errorElement);
                }
            });
            
            campo.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e0e0e0';
                    errorElement.style.display = 'none';
                }
            });
            
            if (campo.tagName === 'SELECT') {
                campo.addEventListener('change', function() {
                    if (this.dataset.touched !== 'true') {
                        this.dataset.touched = 'true';
                    }
                    validarCampo(this, errorElement);
                });
            }
        });
        
        // Campos de dirección opcionales
        if (direccionSecundario) {
            direccionSecundario.addEventListener('focus', function() {
                this.dataset.touched = 'true';
            });
            
            direccionSecundario.addEventListener('blur', function() {
                if (this.dataset.touched === 'true') {
                    if (grupoDireccionSecundario.style.display === 'block' && !this.value.trim()) {
                        this.style.borderColor = '#dc3545';
                        mostrarError(this, 'Este campo es requerido cuando se selecciona un municipio secundario');
                    } else {
                        this.style.borderColor = '#e0e0e0';
                        ocultarError(this);
                    }
                }
            });
            
            direccionSecundario.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e0e0e0';
                    ocultarError(this);
                }
            });
        }
        
        if (direccionTerciario) {
            direccionTerciario.addEventListener('focus', function() {
                this.dataset.touched = 'true';
            });
            
            direccionTerciario.addEventListener('blur', function() {
                if (this.dataset.touched === 'true') {
                    if (grupoDireccionTerciario.style.display === 'block' && !this.value.trim()) {
                        this.style.borderColor = '#dc3545';
                        mostrarError(this, 'Este campo es requerido cuando se selecciona un municipio terciario');
                    } else {
                        this.style.borderColor = '#e0e0e0';
                        ocultarError(this);
                    }
                }
            });
            
            direccionTerciario.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e0e0e0';
                    ocultarError(this);
                }
            });
        }
        
        const camposEspecificos = {
            'cedula': {
                validar: function(valor) {
                    if (valor.length < 5) return 'La cédula debe tener al menos 5 dígitos';
                    if (!/^\d+$/.test(valor)) return 'La cédula solo debe contener números';
                    return null;
                }
            },
            'correo': {
                validar: function(valor) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(valor)) return 'Ingrese un correo electrónico válido';
                    return null;
                }
            },
            'celular': {
                validar: function(valor) {
                    if (valor.length < 10) return 'El celular debe tener al menos 10 dígitos';
                    if (!/^\d+$/.test(valor)) return 'El celular solo debe contener números';
                    return null;
                }
            },
            'nombre_completo': {
                validar: function(valor) {
                    if (valor.split(' ').length < 2) return 'Ingrese nombre y apellido completos';
                    return null;
                }
            },
            'profesion': {
                validar: function(valor) {
                    if (valor.length > 100) return 'La profesión no puede exceder los 100 caracteres';
                    return null;
                }
            }
        };
        
        Object.keys(camposEspecificos).forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.addEventListener('focus', function() {
                    this.dataset.touched = 'true';
                });
                
                campo.addEventListener('blur', function() {
                    if (this.dataset.touched === 'true') {
                        const error = camposEspecificos[id].validar(this.value.trim());
                        if (error) {
                            this.style.borderColor = '#dc3545';
                            mostrarError(this, error);
                        }
                    }
                });
                
                campo.addEventListener('input', function() {
                    const error = camposEspecificos[id].validar(this.value.trim());
                    if (!error && this.value.trim()) {
                        this.style.borderColor = '#e0e0e0';
                        ocultarError(this);
                    }
                });
            }
        });
    }
    
    function validarCampo(campo, errorElement) {
        if (!campo.value.trim()) {
            campo.style.borderColor = '#dc3545';
            errorElement.textContent = 'Este campo es requerido';
            errorElement.style.display = 'block';
            return false;
        } else {
            campo.style.borderColor = '#e0e0e0';
            errorElement.style.display = 'none';
            return true;
        }
    }
    
    function mostrarError(campo, mensaje) {
        let errorElement = campo.parentNode.querySelector('.validation-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'validation-message';
            errorElement.style.color = '#dc3545';
            errorElement.style.fontSize = '12px';
            errorElement.style.marginTop = '5px';
            campo.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = mensaje;
        errorElement.style.display = 'block';
    }
    
    function ocultarError(campo) {
        const errorElement = campo.parentNode.querySelector('.validation-message');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }
    
    // Inicializar validaciones en tiempo real
    setupValidacionesEnTiempoReal();
    
    // === CREAR MODAL DE CONFIRMACIÓN (ACTUALIZADO) ===
    // (El código del modal se mantiene igual, solo se muestra la parte relevante)
    
    // Función para resetear el estado de "touched" en los campos
    function resetearEstadoTouched() {
        document.querySelectorAll('input, select, textarea').forEach(campo => {
            delete campo.dataset.touched;
        });
    }
    
    // === FUNCIÓN PARA GUARDAR CONTRATISTA (ACTUALIZADA) ===
    async function guardarContratista() {
        const btnOriginalHTML = guardarBtn.innerHTML;
        guardarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        guardarBtn.disabled = true;
        
        const formData = new FormData();
        
        // Agregar todos los campos del formulario (excepto archivos que se manejan aparte)
        const formElements = document.querySelectorAll('input:not([type="file"]), select, textarea');
        formElements.forEach(element => {
            if (element.name && element.value !== undefined) {
                if (!element.name.includes('adjuntar_') && element.name !== 'foto_perfil') {
                    if ((element.name === 'id_municipio_secundario' || element.name === 'id_municipio_terciario') 
                        && element.value === '0') {
                        formData.append(element.name, '');
                    } else {
                        formData.append(element.name, element.value);
                    }
                }
            }
        });

        // Agregar archivos (incluye foto_perfil)
        const archivos = [
            'foto_perfil', 'adjuntar_cv', 'adjuntar_contrato', 
            'adjuntar_acta_inicio', 'adjuntar_rp'
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

                // Primero resetear el estado de touched
                resetearEstadoTouched();
                
                // Luego limpiar el formulario
                limpiarFormulario();

                guardarBtn.innerHTML = btnOriginalHTML;
                guardarBtn.disabled = false;
                
                // Hacer scroll al inicio del formulario suavemente
                const formContainer = document.querySelector('.form-container');
                if (formContainer) {
                    formContainer.scrollIntoView({ behavior: 'smooth' });
                }
                
                // Dar foco al primer campo
                setTimeout(() => {
                    const primerCampo = document.getElementById('nombre_completo');
                    if (primerCampo) primerCampo.focus();
                }, 300);

                // Limpiar todos los mensajes de error
                document.querySelectorAll('.validation-message').forEach(el => {
                    el.style.display = 'none';
                });
                
            } else {
                alert('Error: ' + resultado.error);
                
                guardarBtn.innerHTML = btnOriginalHTML;
                guardarBtn.disabled = false;
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión con el servidor. Por favor intente nuevamente.');
            
            guardarBtn.innerHTML = btnOriginalHTML;
            guardarBtn.disabled = false;
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
            let valid = true;
            let primerCampoInvalido = null;
            
            // Marcar todos los campos como "touched" para validación
            document.querySelectorAll('input, select, textarea').forEach(campo => {
                if (campo.required) {
                    campo.dataset.touched = 'true';
                }
            });
            
            // Validar campos requeridos
            const requiredFields = document.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    mostrarError(field, 'Este campo es requerido');
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = field;
                    }
                } else {
                    field.style.borderColor = '#e0e0e0';
                    ocultarError(field);
                }
            });
            
            // Validar campos de dirección opcionales
            if (grupoDireccionSecundario && grupoDireccionSecundario.style.display === 'block') {
                if (!direccionSecundario.value.trim()) {
                    direccionSecundario.style.borderColor = '#dc3545';
                    mostrarError(direccionSecundario, 'Este campo es requerido cuando se selecciona un municipio secundario');
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = direccionSecundario;
                    }
                }
            }
            
            if (grupoDireccionTerciario && grupoDireccionTerciario.style.display === 'block') {
                if (!direccionTerciario.value.trim()) {
                    direccionTerciario.style.borderColor = '#dc3545';
                    mostrarError(direccionTerciario, 'Este campo es requerido cuando se selecciona un municipio terciario');
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = direccionTerciario;
                    }
                }
            }
            
            // Validar que la duración del contrato esté calculada
            const duracionContrato = document.getElementById('duracion_contrato');
            if (duracionContrato && !duracionContrato.value.trim()) {
                mostrarError(duracionContrato, 'Complete las fechas de inicio y final para calcular la duración');
                duracionContrato.style.borderColor = '#dc3545';
                valid = false;
                if (!primerCampoInvalido) {
                    primerCampoInvalido = duracionContrato;
                }
            }
            
            // Validaciones específicas adicionales
            const camposValidar = {
                'cedula': {
                    validar: function(valor) {
                        if (valor.length < 5) return 'La cédula debe tener al menos 5 dígitos';
                        if (!/^\d+$/.test(valor)) return 'La cédula solo debe contener números';
                        return null;
                    }
                },
                'correo': {
                    validar: function(valor) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(valor)) return 'Ingrese un correo electrónico válido';
                        return null;
                    }
                },
                'celular': {
                    validar: function(valor) {
                        if (valor.length < 10) return 'El celular debe tener al menos 10 dígitos';
                        if (!/^\d+$/.test(valor)) return 'El celular solo debe contener números';
                        return null;
                    }
                },
                'nombre_completo': {
                    validar: function(valor) {
                        if (valor.split(' ').length < 2) return 'Ingrese nombre y apellido completos';
                        return null;
                    }
                },
                'profesion': {
                    validar: function(valor) {
                        if (valor.length > 100) return 'La profesión no puede exceder los 100 caracteres';
                        return null;
                    }
                }
            };
            
            Object.keys(camposValidar).forEach(id => {
                const campo = document.getElementById(id);
                if (campo && campo.value.trim()) {
                    const error = camposValidar[id].validar(campo.value.trim());
                    if (error) {
                        mostrarError(campo, error);
                        campo.style.borderColor = '#dc3545';
                        valid = false;
                        if (!primerCampoInvalido) {
                            primerCampoInvalido = campo;
                        }
                    }
                }
            });
            
            if (!valid) {
                alert('Por favor complete todos los campos obligatorios (*) correctamente');
                if (primerCampoInvalido) {
                    // Hacer scroll al campo con error
                    primerCampoInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => {
                        primerCampoInvalido.focus();
                    }, 300);
                }
                return;
            }
            
            // Validar fechas
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFinal = document.getElementById('fecha_final').value;
            
            if (fechaInicio && fechaFinal) {
                const inicio = new Date(fechaInicio.split('/').reverse().join('-'));
                const final = new Date(fechaFinal.split('/').reverse().join('-'));
                
                if (inicio > final) {
                    alert('La fecha de inicio no puede ser mayor a la fecha final');
                    document.getElementById('fecha_inicio').style.borderColor = '#dc3545';
                    document.getElementById('fecha_final').style.borderColor = '#dc3545';
                    mostrarError(document.getElementById('fecha_inicio'), 'La fecha de inicio debe ser menor a la fecha final');
                    mostrarError(document.getElementById('fecha_final'), 'La fecha final debe ser mayor a la fecha de inicio');
                    return;
                }
            }
            
            // Validar foto de perfil (tamaño y tipo)
            const fotoInput = document.getElementById('foto_perfil');
            if (fotoInput && fotoInput.files[0]) {
                const file = fotoInput.files[0];
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('La foto de perfil excede el tamaño máximo de 10MB');
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Solo se permiten imágenes JPG, JPEG, PNG o GIF para la foto de perfil');
                    return;
                }
            }
            
            // Mostrar modal de confirmación en lugar de guardar directamente
            modal.mostrar();
        });
    }
    
    function limpiarFormulario() {
        // 1. Campos de texto principales (incluye profesion)
        const camposTextoLimpios = [
            'nombre_completo', 'cedula', 'correo', 'celular', 'direccion', 'profesion',
            'numero_contrato', 'duracion_contrato', 'numero_registro_presupuestal',
            'direccion_municipio_principal', 'direccion_municipio_secundario', 
            'direccion_municipio_terciario'
        ];
        
        camposTextoLimpios.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.value = '';
                campo.style.borderColor = '#e0e0e0';
                ocultarError(campo);
            }
        });

        // 2. Campos de fecha
        const fechaCampos = ['fecha_contrato', 'fecha_inicio', 'fecha_final', 'fecha_rp'];
        fechaCampos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.value = '';
                campo.style.borderColor = '#e0e0e0';
                if (campo._flatpickr) {
                    campo._flatpickr.clear();
                }
                ocultarError(campo);
            }
        });
        
        // 3. Selects
        const selectsLimpiar = [
            'id_tipo_vinculacion', 'id_area', 
            'id_municipio_secundario', 'id_municipio_terciario'
        ];
        
        selectsLimpiar.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.selectedIndex = 0;
                select.style.borderColor = '#e0e0e0';
                ocultarError(select);
            }
        });

        // 4. Municipio principal (restaurar a Villavicencio)
        const municipioPrincipal = document.getElementById('id_municipio_principal');
        if (municipioPrincipal) {
            municipioPrincipal.selectedIndex = 0;
            Array.from(municipioPrincipal.options).forEach(option => {
                if (option.text.includes('Villavicencio')) {
                    option.selected = true;
                }
            });
            municipioPrincipal.style.borderColor = '#e0e0e0';
            ocultarError(municipioPrincipal);
        }

        // 5. Archivos (incluye foto_perfil)
        const archivos = [
            'foto_perfil', 'adjuntar_cv', 'adjuntar_contrato', 
            'adjuntar_acta_inicio', 'adjuntar_rp'
        ];
        
        archivos.forEach(id => {
            const fileInput = document.getElementById(id);
            if (fileInput) {
                fileInput.value = '';
                fileInput.style.borderColor = '#e0e0e0';
                ocultarError(fileInput);
            }
        });
        
        // 6. Resetear vista previa de foto
        const fotoPreview = document.getElementById('fotoPreviewImg');
        const fotoPlaceholder = document.querySelector('.foto-placeholder');
        if (fotoPreview) {
            fotoPreview.src = '';
            fotoPreview.style.display = 'none';
        }
        if (fotoPlaceholder) {
            fotoPlaceholder.style.display = 'flex';
            fotoPlaceholder.style.flexDirection = 'column';
            fotoPlaceholder.style.alignItems = 'center';
            fotoPlaceholder.style.justifyContent = 'center';
        }
        
        // 7. Ocultar vistas previas de otros archivos
        const previews = ['cvPreview', 'contratoPreview', 'actaPreview', 'rpPreview'];
        previews.forEach(id => {
            const preview = document.getElementById(id);
            if (preview) preview.style.display = 'none';
        });
        
        // 8. Ocultar grupos de dirección
        if (grupoDireccionSecundario) {
            grupoDireccionSecundario.style.display = 'none';
            direccionSecundario.value = '';
            direccionSecundario.style.borderColor = '#e0e0e0';
            ocultarError(direccionSecundario);
        }
        
        if (grupoDireccionTerciario) {
            grupoDireccionTerciario.style.display = 'none';
            direccionTerciario.value = '';
            direccionTerciario.style.borderColor = '#e0e0e0';
            ocultarError(direccionTerciario);
        }
        
        // 9. Restablecer estilo del campo de duración
        const duracionContratoInput = document.getElementById('duracion_contrato');
        if (duracionContratoInput) {
            duracionContratoInput.value = '';
            duracionContratoInput.style.borderColor = '#e0e0e0';
            ocultarError(duracionContratoInput);
        }
        
        // 10. Limpiar todos los mensajes de error
        document.querySelectorAll('.validation-message').forEach(el => {
            el.style.display = 'none';
        });
    }

    // Funcionalidad adicional para municipios
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
                    toggleDireccionOpcional(secundario, grupoDireccionSecundario, direccionSecundario);
                }
            }
            
            if (principalId && terciario && !terciario.value) {
                if (confirm(`¿Desea asignar "${principalNombre}" como municipio terciario?`)) {
                    terciario.value = principalId;
                    toggleDireccionOpcional(terciario, grupoDireccionTerciario, direccionTerciario);
                }
            }
        });
    }

    // Prevenir submit con Enter
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
    
    // Actualizar hora en tiempo real
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