 // Función para filtrar tabla - CORREGIDA
        function filtrarTabla() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filterStatus = document.getElementById('filterStatus').value;
            const filterArea = document.getElementById('filterArea').value;
            const filterVinculacion = document.getElementById('filterVinculacion').value;
            const filterMunicipio = document.getElementById('filterMunicipio').value;
            
            const tbody = document.querySelector('#contratistasTable tbody');
            const allRows = tbody.querySelectorAll('.contratista-row');
            let visibleCount = 0;
            
            // Primero, mostrar todas las filas originales
            allRows.forEach(row => {
                row.style.display = '';
            });
            
            // Ahora filtrar
            allRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const estadoContrato = row.getAttribute('data-estado-contrato');
                const area = row.getAttribute('data-area').toLowerCase();
                const vinculacion = row.getAttribute('data-vinculacion').toLowerCase();
                const municipio = row.getAttribute('data-municipio').toLowerCase();
                
                let matchesSearch = text.includes(searchTerm);
                let matchesStatus = true;
                let matchesArea = true;
                let matchesVinculacion = true;
                let matchesMunicipio = true;
                
                // Filtrar por estado de contrato
                if (filterStatus) {
                    if (filterStatus === 'vigente' && estadoContrato !== 'vigente') matchesStatus = false;
                    if (filterStatus === 'vencido' && estadoContrato !== 'vencido') matchesStatus = false;
                }
                
                // Filtrar por área
                if (filterArea && area !== filterArea.toLowerCase()) {
                    matchesArea = false;
                }
                
                // Filtrar por tipo de vinculación
                if (filterVinculacion && vinculacion !== filterVinculacion.toLowerCase()) {
                    matchesVinculacion = false;
                }
                
                // Filtrar por municipio principal
                if (filterMunicipio && municipio !== filterMunicipio.toLowerCase()) {
                    matchesMunicipio = false;
                }
                
                if (matchesSearch && matchesStatus && matchesArea && matchesVinculacion && matchesMunicipio) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.getElementById('rowCount').textContent = visibleCount;
            
            // Mostrar mensaje si no hay resultados
            const emptyRow = document.querySelector('.empty-row');
            const originalEmptyRow = document.querySelector('tr.empty-row[data-is-original="true"]');
            
            if (visibleCount === 0 && allRows.length > 0) {
                // Eliminar fila vacía anterior si existe (pero no la original)
                if (emptyRow && !emptyRow.hasAttribute('data-is-original')) {
                    emptyRow.remove();
                }
                
                // Crear nueva fila de "no resultados"
                const newRow = document.createElement('tr');
                newRow.classList.add('empty-row');
                newRow.innerHTML = `
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h5>No se encontraron resultados</h5>
                            <p>Intenta con otros términos de búsqueda o filtros.</p>
                            <button onclick="limpiarFiltros()" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-broom"></i> Limpiar filtros
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(newRow);
            } else {
                // Eliminar fila de "no resultados" si existe (pero no la original)
                if (emptyRow && !emptyRow.hasAttribute('data-is-original')) {
                    emptyRow.remove();
                }
                
                // Si hay una fila vacía original y ahora tenemos resultados, quitarla
                if (originalEmptyRow && visibleCount > 0) {
                    originalEmptyRow.remove();
                }
            }
        }
        
        // Función para limpiar filtros - MEJORADA
        function limpiarFiltros() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterArea').value = '';
            document.getElementById('filterVinculacion').value = '';
            document.getElementById('filterMunicipio').value = '';
            
            // Forzar una nueva búsqueda
            filtrarTabla();
            
            // Enfocar el campo de búsqueda
            document.getElementById('searchInput').focus();
        }
        
        // Función para recargar datos - NUEVA
        function recargarDatos() {
            // Mostrar indicador de carga
            const refreshBtn = document.getElementById('refreshBtn');
            const originalHTML = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            refreshBtn.disabled = true;
            
            // Recargar la página después de un breve retraso para mostrar el spinner
            setTimeout(() => {
                location.reload();
            }, 500);
        }
        
        // Funciones para descargar documentos
        function descargarCV(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_cv.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarContrato(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_contrato.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarActa(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_acta.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarRP(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_rp.php?id=${idDetalle}`, '_blank');
        }
        
        // Event listeners actualizados
        document.addEventListener('DOMContentLoaded', function() {
            // Buscar
            document.getElementById('searchInput').addEventListener('input', filtrarTabla);
            
            // Filtros
            document.getElementById('filterStatus').addEventListener('change', filtrarTabla);
            document.getElementById('filterArea').addEventListener('change', filtrarTabla);
            document.getElementById('filterVinculacion').addEventListener('change', filtrarTabla);
            document.getElementById('filterMunicipio').addEventListener('change', filtrarTabla);
            
            // Botones
            document.getElementById('clearFiltersBtn').addEventListener('click', limpiarFiltros);
            document.getElementById('refreshBtn').addEventListener('click', recargarDatos);
            document.getElementById('volverBtn').addEventListener('click', () => {
                window.location.href = 'menuContratistas.php';
            });
            
            // Permitir buscar con Enter
            document.getElementById('searchInput').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') filtrarTabla();
            });
            
            // También filtrar al cargar la página
            filtrarTabla();
        });
        
        // Funciones placeholder para acciones
        function verDetalle(idDetalle) {
            if (!idDetalle || idDetalle === '0') return alert('Error: ID no válido');
            window.location.href = `ver_detalle.php?id_detalle=${idDetalle}`;
        }
        
        function editarContratista(idDetalle) {
            if (!idDetalle || idDetalle === '0') return alert('Error: ID no válido');
            window.location.href = `editar_contratista.php?id_detalle=${idDetalle}`;
        }