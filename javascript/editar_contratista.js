document.addEventListener('DOMContentLoaded', function() {
    // === HACER EL CAMPO DE DURACIÓN DE SOLO LECTURA ===
    const duracionContratoInput = document.getElementById('duracion_contrato');
    if (duracionContratoInput) {
        duracionContratoInput.readOnly = true;
        duracionContratoInput.style.backgroundColor = '#f8f9fa';
        duracionContratoInput.style.cursor = 'not-allowed';
        duracionContratoInput.title = 'Campo calculado automáticamente';
    }
    
    // === CALCULAR DURACIÓN DEL CONTRATO (AUTOMÁTICO) ===
    document.getElementById('fecha_inicio')?.addEventListener('change', calcularDuracionContrato);
    document.getElementById('fecha_final')?.addEventListener('change', calcularDuracionContrato);
    
    // Calcular duración inicial si ya hay fechas
    window.addEventListener('load', calcularDuracionContrato);
    
    function calcularDuracionContrato() {
        const fechaInicio = document.getElementById('fecha_inicio')?.value;
        const fechaFinal = document.getElementById('fecha_final')?.value;
        const duracionInput = document.getElementById('duracion_contrato');
        
        if (!fechaInicio || !fechaFinal || !duracionInput) {
            return;
        }
        
        try {
            // Convertir fechas de YYYY-MM-DD (formato input date) a objetos Date
            const fechaInicioDate = new Date(fechaInicio);
            const fechaFinalDate = new Date(fechaFinal);
            
            if (fechaFinalDate <= fechaInicioDate) {
                duracionInput.value = 'Error: fecha final debe ser mayor';
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
            
            duracionInput.value = duracionTexto;
            
        } catch (error) {
            console.error('Error calculando duración del contrato:', error);
            if (duracionInput) {
                duracionInput.value = '';
            }
        }
    }
    
    // === MANEJO DE MUNICIPIOS Y DIRECCIONES CONDICIONALES ===
    const municipioSecundario = document.getElementById('id_municipio_secundario');
    const direccionSecundario = document.getElementById('direccion_municipio_secundario');
    const direccionSecundariaContainer = document.querySelector('.direccion-secundaria-container');
    
    const municipioTerciario = document.getElementById('id_municipio_terciario');
    const direccionTerciario = document.getElementById('direccion_municipio_terciario');
    const direccionTerciariaContainer = document.querySelector('.direccion-terciaria-container');
    
    function toggleDireccionOpcional(selectElement, containerElement, inputElement) {
        if (selectElement.value && selectElement.value !== '') {
            if (containerElement) containerElement.style.display = 'block';
            if (inputElement) inputElement.required = true;
        } else {
            if (containerElement) containerElement.style.display = 'none';
            if (inputElement) {
                inputElement.required = false;
                inputElement.value = '';
            }
        }
    }
    
    // Event listeners para municipio secundario
    if (municipioSecundario) {
        municipioSecundario.addEventListener('change', function() {
            toggleDireccionOpcional(this, direccionSecundariaContainer, direccionSecundario);
        });
        
        // Configurar estado inicial
        toggleDireccionOpcional(municipioSecundario, direccionSecundariaContainer, direccionSecundario);
    }
    
    // Event listeners para municipio terciario
    if (municipioTerciario) {
        municipioTerciario.addEventListener('change', function() {
            toggleDireccionOpcional(this, direccionTerciariaContainer, direccionTerciario);
        });
        
        // Configurar estado inicial
        toggleDireccionOpcional(municipioTerciario, direccionTerciariaContainer, direccionTerciario);
    }
    
    // === VALIDACIÓN DEL FORMULARIO ===
    const form = document.getElementById('formEditarContratista');
    
    // Vista previa de foto de perfil
    const fotoInput = document.getElementById('foto_perfil');
    const fotoPreview = document.querySelector('.foto-preview img');
    const fotoPreviewPlaceholder = document.getElementById('fotoPreviewPlaceholder');
    
    if (fotoInput) {
        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño máximo (5MB para fotos)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('La foto excede el tamaño máximo de 5MB');
                    this.value = '';
                    return;
                }
                
                // Validar tipo de imagen
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Solo se permiten imágenes JPG, JPEG, PNG o GIF');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (fotoPreview) {
                        fotoPreview.src = e.target.result;
                    } else if (fotoPreviewPlaceholder) {
                        fotoPreviewPlaceholder.parentElement.innerHTML = '<img src="' + e.target.result + '" id="fotoPreview" alt="Previsualización de foto" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Formato automático para teléfono
    const telefono = document.getElementById('telefono');
    if (telefono) {
        telefono.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.substring(0, 10); // Limitar a 10 dígitos
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.substring(0,3) + '-' + value.substring(3);
                } else {
                    value = value.substring(0,3) + '-' + value.substring(3,6) + '-' + value.substring(6);
                }
            }
            e.target.value = value;
        });
    }
    
    // Validación de archivos en tiempo real
    const fileInputs = document.querySelectorAll('input[type="file"]');
    const maxFileSize = 5 * 1024 * 1024; // 5MB
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > maxFileSize) {
                    alert('El archivo "' + file.name + '" es demasiado grande. Tamaño máximo: 5MB');
                    this.value = '';
                }
            }
        });
    });
    
    // === BLOQUEO DE FECHAS POSTERIORES A LA FECHA DEL CONTRATO ===
    const fechaContrato = document.getElementById('fecha_contrato');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFinal = document.getElementById('fecha_final');
    const fechaRP = document.getElementById('fecha_rp');
    
    // Función para configurar fechas mínimas
    function configurarMinFechas() {
        if (fechaContrato && fechaContrato.value) {
            const fechaContratoValue = fechaContrato.value;
            
            // Configurar mínimo para fecha de inicio
            if (fechaInicio) {
                fechaInicio.min = fechaContratoValue;
                
                // Si la fecha de inicio actual es anterior a la fecha del contrato, limpiarla
                if (fechaInicio.value && fechaInicio.value < fechaContratoValue) {
                    fechaInicio.value = '';
                    calcularDuracionContrato(); // Recalcular duración
                }
            }
            
            // Configurar mínimo para fecha final
            if (fechaFinal) {
                fechaFinal.min = fechaContratoValue;
                
                // Si la fecha final actual es anterior a la fecha del contrato, limpiarla
                if (fechaFinal.value && fechaFinal.value < fechaContratoValue) {
                    fechaFinal.value = '';
                    calcularDuracionContrato(); // Recalcular duración
                }
            }
            
            // Configurar mínimo para fecha RP (opcional)
            if (fechaRP) {
                fechaRP.min = fechaContratoValue;
                
                // Si la fecha RP actual es anterior a la fecha del contrato, limpiarla
                if (fechaRP.value && fechaRP.value < fechaContratoValue) {
                    fechaRP.value = '';
                }
            }
        } else {
            // Si no hay fecha de contrato, quitar restricciones
            if (fechaInicio) fechaInicio.min = '';
            if (fechaFinal) fechaFinal.min = '';
            if (fechaRP) fechaRP.min = '';
        }
    }
    
    // Configurar cuando cambia la fecha del contrato
    if (fechaContrato) {
        fechaContrato.addEventListener('change', configurarMinFechas);
        
        // Configurar estado inicial
        configurarMinFechas();
    }
    
    // === VALIDACIÓN DE FECHAS EN TIEMPO REAL ===
    if (fechaInicio && fechaFinal) {
        fechaInicio.addEventListener('change', function() {
            if (this.value && fechaFinal.value && this.value > fechaFinal.value) {
                alert('La fecha de inicio no puede ser posterior a la fecha final');
                this.value = '';
                calcularDuracionContrato();
            }
        });
        
        fechaFinal.addEventListener('change', function() {
            if (this.value && fechaInicio.value && this.value < fechaInicio.value) {
                alert('La fecha final no puede ser anterior a la fecha de inicio');
                this.value = '';
                calcularDuracionContrato();
            }
        });
    }
    
    // === VALIDACIÓN DE CÉDULA (SOLO NÚMEROS) ===
    const cedulaInput = document.getElementById('cedula');
    if (cedulaInput) {
        cedulaInput.addEventListener('input', function(e) {
            // Remover caracteres no numéricos
            this.value = this.value.replace(/\D/g, '');
        });
        
        cedulaInput.addEventListener('blur', function() {
            if (this.value && this.value.length < 5) {
                alert('La cédula debe tener al menos 5 dígitos');
                this.focus();
            }
        });
    }
    
    // === VALIDACIÓN DE CORREO ELECTRÓNICO ===
    const correoInput = document.getElementById('correo_personal');
    if (correoInput) {
        correoInput.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                alert('Por favor ingrese un correo electrónico válido');
                this.focus();
            }
        });
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // === VALIDACIÓN ANTES DE ENVIAR FORMULARIO ===
    function validarFormularioCompleto() {
        let valid = true;
        let mensaje = '';
        
        // Validar campos requeridos
        const requiredFields = document.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = '#dc3545';
                if (!mensaje) {
                    mensaje = 'Por favor complete todos los campos obligatorios marcados con *';
                }
            } else {
                field.style.borderColor = '';
            }
        });
        
        // Validar campos de dirección opcionales
        if (direccionSecundariaContainer && direccionSecundariaContainer.style.display === 'block') {
            if (direccionSecundario && !direccionSecundario.value.trim()) {
                valid = false;
                direccionSecundario.style.borderColor = '#dc3545';
                mensaje = 'Por favor ingrese la dirección para el municipio secundario seleccionado';
            }
        }
        
        if (direccionTerciariaContainer && direccionTerciariaContainer.style.display === 'block') {
            if (direccionTerciario && !direccionTerciario.value.trim()) {
                valid = false;
                direccionTerciario.style.borderColor = '#dc3545';
                mensaje = 'Por favor ingrese la dirección para el municipio terciario seleccionado';
            }
        }
        
        // Validar cédula
        if (cedulaInput && cedulaInput.value) {
            if (!/^\d+$/.test(cedulaInput.value)) {
                valid = false;
                cedulaInput.style.borderColor = '#dc3545';
                mensaje = 'La cédula debe contener solo números';
            } else if (cedulaInput.value.length < 5) {
                valid = false;
                cedulaInput.style.borderColor = '#dc3545';
                mensaje = 'La cédula debe tener al menos 5 dígitos';
            }
        }
        
        // Validar correo
        if (correoInput && correoInput.value && !isValidEmail(correoInput.value)) {
            valid = false;
            correoInput.style.borderColor = '#dc3545';
            mensaje = 'Por favor ingrese un correo electrónico válido';
        }
        
        // Validar teléfono (mínimo 7 dígitos)
        if (telefono && telefono.value) {
            const digitos = telefono.value.replace(/\D/g, '');
            if (digitos.length < 7) {
                valid = false;
                telefono.style.borderColor = '#dc3545';
                mensaje = 'El teléfono debe tener al menos 7 dígitos';
            }
        }
        
        // Validar archivos
        fileInputs.forEach(input => {
            if (input.files.length > 0) {
                const file = input.files[0];
                if (file.size > maxFileSize) {
                    valid = false;
                    mensaje = `El archivo "${file.name}" es demasiado grande. Tamaño máximo: 5MB`;
                }
            }
        });
        
        // Validar fechas
        if (fechaInicio && fechaFinal && fechaInicio.value && fechaFinal.value) {
            const inicio = new Date(fechaInicio.value);
            const final = new Date(fechaFinal.value);
            
            if (final <= inicio) {
                valid = false;
                fechaInicio.style.borderColor = '#dc3545';
                fechaFinal.style.borderColor = '#dc3545';
                mensaje = 'La fecha final debe ser posterior a la fecha de inicio';
            }
        }
        
        // Validar fecha del contrato como fecha mínima
        if (fechaContrato && fechaContrato.value) {
            const fechaContratoValue = new Date(fechaContrato.value);
            
            if (fechaInicio && fechaInicio.value) {
                const fechaInicioValue = new Date(fechaInicio.value);
                if (fechaInicioValue < fechaContratoValue) {
                    valid = false;
                    fechaInicio.style.borderColor = '#dc3545';
                    mensaje = 'La fecha de inicio no puede ser anterior a la fecha del contrato';
                }
            }
            
            if (fechaFinal && fechaFinal.value) {
                const fechaFinalValue = new Date(fechaFinal.value);
                if (fechaFinalValue < fechaContratoValue) {
                    valid = false;
                    fechaFinal.style.borderColor = '#dc3545';
                    mensaje = 'La fecha final no puede ser anterior a la fecha del contrato';
                }
            }
        }
        
        if (!valid && mensaje) {
            alert(mensaje);
            
            // Enfocar el primer campo con error
            const errorField = document.querySelector('[style*="border-color: #dc3545"]');
            if (errorField) {
                errorField.focus();
            }
        }
        
        return valid;
    }
    
    // === INTEGRACIÓN CON EL SCRIPT DEL MODAL ===
    // Sobreescribir la función de validación del botón "Guardar Cambios" para usar nuestra validación
    const btnMostrarCambiosOriginal = document.getElementById('btnMostrarCambios');
    if (btnMostrarCambiosOriginal) {
        const originalClickHandler = btnMostrarCambiosOriginal.onclick;
        btnMostrarCambiosOriginal.onclick = function(e) {
            e.preventDefault();
            
            // Primero validar con nuestra función
            if (!validarFormularioCompleto()) {
                return;
            }
            
            // Luego ejecutar la función original del modal
            if (originalClickHandler) {
                originalClickHandler.call(this, e);
            } else if (typeof window.detectarCambios === 'function') {
                // Si existe la función detectarCambios en el ámbito global
                const cambios = window.detectarCambios();
                window.mostrarCambiosEnModal(cambios);
                document.getElementById('modalConfirmacion').style.display = 'flex';
            }
        };
    }
    
    // === FUNCIONALIDAD ADICIONAL: LIMPIAR CAMPO DE DURACIÓN SI SE ELIMINAN FECHAS ===
    function verificarFechasParaDuracion() {
        const fechaInicio = document.getElementById('fecha_inicio')?.value;
        const fechaFinal = document.getElementById('fecha_final')?.value;
        const duracionInput = document.getElementById('duracion_contrato');
        
        if (duracionInput && (!fechaInicio || !fechaFinal)) {
            duracionInput.value = '';
        }
    }
    
    if (fechaInicio) {
        fechaInicio.addEventListener('change', verificarFechasParaDuracion);
    }
    
    if (fechaFinal) {
        fechaFinal.addEventListener('change', verificarFechasParaDuracion);
    }
    
    // === MEJORA DE USABILIDAD: SELECCIÓN AUTOMÁTICA DE MUNICIPIOS ===
    const municipioPrincipal = document.getElementById('id_municipio_principal');
    
    if (municipioPrincipal) {
        municipioPrincipal.addEventListener('change', function() {
            const principalId = this.value;
            const principalNombre = this.options[this.selectedIndex].text;
            
            // Solo sugerir si los campos secundarios/terciarios están vacíos
            if (principalId && municipioSecundario && !municipioSecundario.value) {
                setTimeout(() => {
                    if (confirm(`¿Desea asignar "${principalNombre}" también como municipio secundario?`)) {
                        municipioSecundario.value = principalId;
                        toggleDireccionOpcional(municipioSecundario, direccionSecundariaContainer, direccionSecundario);
                    }
                }, 100);
            }
            
            if (principalId && municipioTerciario && !municipioTerciario.value) {
                setTimeout(() => {
                    if (confirm(`¿Desea asignar "${principalNombre}" también como municipio terciario?`)) {
                        municipioTerciario.value = principalId;
                        toggleDireccionOpcional(municipioTerciario, direccionTerciariaContainer, direccionTerciario);
                    }
                }, 200);
            }
        });
    }
    
    // === INICIALIZACIÓN FINAL ===
    console.log('Script de edición de contratista cargado correctamente');
});