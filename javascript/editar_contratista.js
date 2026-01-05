document.addEventListener('DOMContentLoaded', function() {
    // === HACER EL CAMPO DE DURACIÓN DE SOLO LECTURA ===
    const duracionContratoInput = document.getElementById('duracion_contrato');
    if (duracionContratoInput) {
        duracionContratoInput.readOnly = true;
        duracionContratoInput.style.backgroundColor = '#f8f9fa';
        duracionContratoInput.style.cursor = 'not-allowed';
        duracionContratoInput.title = 'Este campo no se puede editar directamente';
    }
    
    // Validación del formulario
    const form = document.getElementById('formEditarContratista');
    form.addEventListener('submit', function(e) {
        let valid = true;
        
        // Validar campos requeridos
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = '#dc3545';
            } else {
                field.style.borderColor = '';
            }
        });
        
        // Validar tamaño de archivos
        const fileInputs = form.querySelectorAll('input[type="file"]');
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        fileInputs.forEach(input => {
            if (input.files.length > 0) {
                const file = input.files[0];
                if (file.size > maxSize) {
                    valid = false;
                    alert('El archivo "' + file.name + '" es demasiado grande. Tamaño máximo: 5MB');
                    input.focus();
                }
            }
        });
        
        // Validar fechas
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFinal = document.getElementById('fecha_final');
        
        if (fechaInicio.value && fechaFinal.value) {
            const inicio = new Date(fechaInicio.value);
            const final = new Date(fechaFinal.value);
            
            if (final < inicio) {
                valid = false;
                alert('La fecha final no puede ser anterior a la fecha de inicio');
                fechaFinal.focus();
            }
        }
        
        if (!valid) {
            e.preventDefault();
            alert('Por favor complete todos los campos requeridos correctamente');
        }
    });
    
    // === FORMATO AUTOMÁTICO PARA TELÉFONO (SOLO NÚMEROS) ===
    const telefono = document.getElementById('telefono');
    if (telefono) {
        telefono.addEventListener('input', function(e) {
            // Primero, remover cualquier carácter que no sea número
            let value = this.value.replace(/\D/g, '');
            
            // Limitar a máximo 10 dígitos
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            // Aplicar formato automático solo si hay números
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                } else {
                    value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6);
                }
            }
            
            this.value = value;
        });
        
        // Prevenir que se peguen caracteres no numéricos
        telefono.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            
            // Extraer solo números del texto pegado
            const numbersOnly = pastedText.replace(/\D/g, '');
            
            // Insertar los números en la posición actual del cursor
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            // Crear nuevo valor con los números insertados
            const newValue = currentValue.substring(0, startPos) + 
                            numbersOnly + 
                            currentValue.substring(endPos);
            
            // Aplicar el mismo formato que en el evento input
            let formattedValue = newValue.replace(/\D/g, '');
            
            if (formattedValue.length > 10) {
                formattedValue = formattedValue.substring(0, 10);
            }
            
            if (formattedValue.length > 0) {
                if (formattedValue.length <= 3) {
                    formattedValue = formattedValue;
                } else if (formattedValue.length <= 6) {
                    formattedValue = formattedValue.substring(0, 3) + '-' + formattedValue.substring(3);
                } else {
                    formattedValue = formattedValue.substring(0, 3) + '-' + 
                                    formattedValue.substring(3, 6) + '-' + 
                                    formattedValue.substring(6);
                }
            }
            
            this.value = formattedValue;
            
            // Posicionar el cursor después del texto insertado
            const newCursorPos = startPos + numbersOnly.length;
            this.setSelectionRange(newCursorPos, newCursorPos);
        });
        
        // Prevenir que se arrastren y suelten caracteres no numéricos
        telefono.addEventListener('drop', function(e) {
            e.preventDefault();
            const droppedText = e.dataTransfer.getData('text');
            
            // Extraer solo números del texto arrastrado
            const numbersOnly = droppedText.replace(/\D/g, '');
            
            // Insertar en la posición actual
            const startPos = this.selectionStart;
            const endPos = this.selectionEnd;
            const currentValue = this.value;
            
            const newValue = currentValue.substring(0, startPos) + 
                            numbersOnly + 
                            currentValue.substring(endPos);
            
            // Aplicar formato
            let formattedValue = newValue.replace(/\D/g, '');
            
            if (formattedValue.length > 10) {
                formattedValue = formattedValue.substring(0, 10);
            }
            
            if (formattedValue.length > 0) {
                if (formattedValue.length <= 3) {
                    formattedValue = formattedValue;
                } else if (formattedValue.length <= 6) {
                    formattedValue = formattedValue.substring(0, 3) + '-' + formattedValue.substring(3);
                } else {
                    formattedValue = formattedValue.substring(0, 3) + '-' + 
                                    formattedValue.substring(3, 6) + '-' + 
                                    formattedValue.substring(6);
                }
            }
            
            this.value = formattedValue;
        });
        
        // Validar cuando pierde el foco (opcional)
        telefono.addEventListener('blur', function() {
            // Asegurar que el formato esté correcto al salir del campo
            let value = this.value.replace(/\D/g, '');
            
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                } else {
                    value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6);
                }
                this.value = value;
            }
        });
    }
    
    // Vista previa de foto de perfil
    const fotoInput = document.getElementById('foto_perfil');
    const fotoPreview = document.querySelector('.foto-preview img');
    
    if (fotoInput && fotoPreview) {
        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    fotoPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Mostrar/ocultar campos de dirección según selección de municipio
    const ubicacionSelects = document.querySelectorAll('.ubicacion-select');
    
    ubicacionSelects.forEach(select => {
        // Configurar estado inicial
        toggleDireccionField(select);
        
        // Escuchar cambios
        select.addEventListener('change', function() {
            toggleDireccionField(this);
        });
    });
    
    function toggleDireccionField(select) {
        const targetId = select.getAttribute('data-target');
        const targetField = document.getElementById(targetId);
        
        if (!targetField) return;
        
        const container = targetField.closest('.form-group');
        
        if (select.value && select.value !== '') {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
            targetField.value = ''; // Limpiar campo si se deselecciona
        }
    }
    
    // Configurar estado inicial de los campos de dirección
    const idMunicipioSecundario = document.getElementById('id_municipio_secundario');
    const idMunicipioTerciario = document.getElementById('id_municipio_terciario');
    const direccionSecundariaContainer = document.querySelector('.direccion-secundaria-container');
    const direccionTerciariaContainer = document.querySelector('.direccion-terciaria-container');
    
    if (idMunicipioSecundario && idMunicipioSecundario.value && direccionSecundariaContainer) {
        direccionSecundariaContainer.style.display = 'block';
    }
    
    if (idMunicipioTerciario && idMunicipioTerciario.value && direccionTerciariaContainer) {
        direccionTerciariaContainer.style.display = 'block';
    }
});