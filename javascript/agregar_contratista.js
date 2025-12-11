
        flatpickr.localize(flatpickr.l10ns.es);
        
        const dateOptions = {
            dateFormat: "d/m/Y",
            locale: "es",
            allowInput: true
        };
        
        // Aplicar a todos los campos de fecha
        document.querySelectorAll('input[placeholder*="dd/mm/aaaa"]').forEach(input => {
            flatpickr(input, dateOptions);
        });
        
        // Script para calcular fecha final basada en fecha inicio y duración
        document.getElementById('duracion_contrato').addEventListener('change', calcularFechaFinal);
        document.getElementById('fecha_inicio').addEventListener('change', calcularFechaFinal);
        
        function calcularFechaFinal() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const duracion = document.getElementById('duracion_contrato').value;
            
            if (fechaInicio && duracion) {
                // Lógica para calcular fecha final (simplificada)
                // En producción, usaría un cálculo real de fechas
                const fechaFinalInput = document.getElementById('fecha_final');
                if (!fechaFinalInput.value) {
                    // Solo calcular si no tiene valor
                    fechaFinalInput.value = "Por calcular";
                }
            }
        }
        
        // Manejo de botones
        document.getElementById('volverBtn').addEventListener('click', function() {
            window.location.href = 'menuContratistas.php';
        });
        
        document.getElementById('cancelarBtn').addEventListener('click', function() {
            if (confirm('¿Está seguro de cancelar? Los datos no guardados se perderán.')) {
                window.location.href = 'menuContratistas.php';
            }
        });
        
        document.getElementById('guardarBtn').addEventListener('click', function() {
            // Validación básica
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
            
            // Simular guardado
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            this.disabled = true;
            
            setTimeout(() => {
                alert('Contratista registrado exitosamente');
                window.location.href = 'menu_asistente.php';
            }, 1500);
        });
        
        // Auto-completar campos relacionados
        document.getElementById('municipio_principal').addEventListener('change', function() {
            const principal = this.value;
            const secundario = document.getElementById('municipio_secundario');
            const terciario = document.getElementById('municipio_terciario');
            
            if (!secundario.value && principal) {
                secundario.innerHTML = `<option value="">Seleccione</option>
                                       <option value="${principal}" selected>${this.options[this.selectedIndex].text}</option>
                                       <option value="ninguno">Ninguno</option>`;
            }
            
            if (!terciario.value && principal) {
                terciario.innerHTML = `<option value="">Seleccione</option>
                                      <option value="${principal}" selected>${this.options[this.selectedIndex].text}</option>
                                      <option value="ninguno">Ninguno</option>`;
            }
        });
        
        // Prevenir envío accidental con Enter
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
        
        // Actualizar hora cada segundo
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
                `<i class="fas fa-clock"></i> Fecha/Hora Actual: ${fechaHora}`;
        }
        
        setInterval(actualizarHora, 1000);
