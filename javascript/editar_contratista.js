document.addEventListener('DOMContentLoaded', function() {
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
        
        // Validar cédula (solo números)
        const cedula = document.getElementById('cedula');
        if (cedula.value && !/^\d+$/.test(cedula.value)) {
            valid = false;
            alert('La cédula debe contener solo números');
            cedula.focus();
        }
        
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
    
    // Formato automático para teléfono
    const telefono = document.getElementById('telefono');
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