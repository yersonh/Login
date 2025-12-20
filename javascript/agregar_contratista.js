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
    
    // Manejar selección de archivo CV
    const cvInput = document.getElementById('adjuntar_cv');
    const cvPreview = document.getElementById('cvPreview');
    const cvFilename = document.getElementById('cvFilename');
    
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
            
            // Validar tipo de archivo por extensión
            const allowedExtensions = ['.pdf', '.doc', '.docx'];
            const fileName = file.name.toLowerCase();
            const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
            
            if (!isValidExtension) {
                alert('Solo se permiten archivos PDF, DOC y DOCX');
                this.value = '';
                cvPreview.style.display = 'none';
                return;
            }
            
            // Mostrar vista previa
            cvFilename.textContent = file.name;
            cvPreview.style.display = 'block';
        } else {
            cvPreview.style.display = 'none';
        }
    });
    
    // Función para remover CV
    window.removeCV = function() {
        cvInput.value = '';
        cvPreview.style.display = 'none';
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
                    
                    fechaFinalInput.value = `${diaFinal}/${mesFinal}/${anioFinal}`;
                    
                } else {
                    fechaFinalInput.value = '';
                }
                
            } catch (error) {
                console.error('Error calculando fecha final:', error);
                fechaFinalInput.value = '';
            }
        } else {
            fechaFinalInput.value = '';
        }
    }
    
    document.getElementById('volverBtn').addEventListener('click', function() {
        window.location.href = 'menuContratistas.php';
    });
    
    document.getElementById('cancelarBtn').addEventListener('click', function() {
        if (confirm('¿Está seguro de cancelar? Los datos no guardados se perderán.')) {
            window.location.href = 'menuContratistas.php';
        }
    });
    
    document.getElementById('guardarBtn').addEventListener('click', async function() {
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
            if (element.name && element.value !== undefined && element.name !== 'adjuntar_cv') {
                if ((element.name === 'id_municipio_secundario' || element.name === 'id_municipio_terciario') 
                    && element.value === '0') {
                    formData.append(element.name, '');
                } else {
                    formData.append(element.name, element.value);
                }
            }
        });
        
        const cvFile = document.getElementById('adjuntar_cv').files[0];
        if (cvFile) {
            formData.append('adjuntar_cv', cvFile);
        }
        
        try {
            const response = await fetch('../../controllers/procesar_contratista.php', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await response.json();
            
            if (resultado.success) {
                alert(`¡Contratista registrado exitosamente!\n\nContratista N°: ${resultado.id_detalle}`);
                
                // ACTUALIZAR CONSECUTIVO AUTOMÁTICAMENTE
                if (resultado.proximo_consecutivo) {
                    document.querySelector('.consecutivo-number').textContent = resultado.proximo_consecutivo;
                }
                
                // LIMPIAR FORMULARIO
                document.querySelector('form').reset();
                document.getElementById('adjuntar_cv').value = '';
                document.getElementById('cvPreview').style.display = 'none';
                
                // RESTABLECER BOTÓN
                this.innerHTML = btnOriginalHTML;
                this.disabled = false;
                
                // ENFOCAR PRIMER CAMPO PARA NUEVO REGISTRO
                document.getElementById('nombre_completo').focus();
                
                // RESTABLECER COLORES DE VALIDACIÓN
                requiredFields.forEach(field => {
                    field.style.borderColor = '#e0e0e0';
                });
                
                // RESTAURAR VALORES POR DEFECTO DESPUÉS DE LIMPIAR
                setTimeout(() => {
                    // Restaurar Villavicencio como seleccionado por defecto
                    const municipioPrincipal = document.getElementById('id_municipio_principal');
                    if (municipioPrincipal) {
                        Array.from(municipioPrincipal.options).forEach(option => {
                            if (option.text.includes('Villavicencio')) {
                                option.selected = true;
                            }
                        });
                    }
                }, 10);
                
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
    
    document.getElementById('id_municipio_principal').addEventListener('change', function() {
        const principalId = this.value;
        const principalNombre = this.options[this.selectedIndex].text;
        const secundario = document.getElementById('id_municipio_secundario');
        const terciario = document.getElementById('id_municipio_terciario');
        
        if (principalId && !secundario.value) {
            if (confirm(`¿Desea asignar "${principalNombre}" como municipio secundario?`)) {
                secundario.value = principalId;
            }
        }
        
        if (principalId && !terciario.value) {
            if (confirm(`¿Desea asignar "${principalNombre}" como municipio terciario?`)) {
                terciario.value = principalId;
            }
        }
    });
    
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
        document.querySelector('.datetime-display').innerHTML = 
            `<i class="fas fa-clock"></i> Ahora: ${fechaHora}`;
    }
    
    setInterval(actualizarHora, 1000);
    actualizarHora();
});