// Función para filtrar tabla - OPTIMIZADA PARA BUSCAR EN TODOS LOS MUNICIPIOS
        function filtrarTabla() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const filterStatus = document.getElementById('filterStatus').value;
            const filterArea = document.getElementById('filterArea').value.toLowerCase();
            const filterVinculacion = document.getElementById('filterVinculacion').value.toLowerCase();
            const filterMunicipio = document.getElementById('filterMunicipio').value.toLowerCase();
            
            const tbody = document.querySelector('#contratistasTable tbody');
            const allRows = tbody.querySelectorAll('.contratista-row');
            let visibleCount = 0;
            
            // Variable para almacenar si hay filtros activos
            const hayFiltros = searchTerm || filterStatus || filterArea || filterVinculacion || filterMunicipio;
            
            if (!hayFiltros) {
                // Si no hay filtros, mostrar todas las filas
                allRows.forEach(row => {
                    row.style.display = '';
                    visibleCount++;
                });
            } else {
                // Aplicar filtros
                allRows.forEach(row => {
                    const estadoContrato = row.getAttribute('data-estado-contrato');
                    const area = row.getAttribute('data-area').toLowerCase();
                    const vinculacion = row.getAttribute('data-vinculacion').toLowerCase();
                    const municipioPrincipal = row.getAttribute('data-municipio-principal')?.toLowerCase() || '';
                    const municipioSecundario = row.getAttribute('data-municipio-secundario')?.toLowerCase() || '';
                    const municipioTerciario = row.getAttribute('data-municipio-terciario')?.toLowerCase() || '';
                    
                    // Verificar si pasa cada filtro
                    let pasaFiltro = true;
                    
                    // 1. Filtro de búsqueda general (incluye todos los municipios)
                    if (searchTerm) {
                        const textoTodo = row.textContent.toLowerCase();
                        const municipiosText = [municipioPrincipal, municipioSecundario, municipioTerciario]
                            .filter(m => m)
                            .join(' ');
                        const textoCompleto = textoTodo + ' ' + municipiosText;
                        
                        if (!textoCompleto.includes(searchTerm)) {
                            pasaFiltro = false;
                        }
                    }
                    
                    // 2. Filtro de estado de contrato
                    if (pasaFiltro && filterStatus) {
                        if ((filterStatus === 'vigente' && estadoContrato !== 'vigente') ||
                            (filterStatus === 'vencido' && estadoContrato !== 'vencido')) {
                            pasaFiltro = false;
                        }
                    }
                    
                    // 3. Filtro de área
                    if (pasaFiltro && filterArea && area !== filterArea) {
                        pasaFiltro = false;
                    }
                    
                    // 4. Filtro de vinculación
                    if (pasaFiltro && filterVinculacion && vinculacion !== filterVinculacion) {
                        pasaFiltro = false;
                    }
                    
                    // 5. Filtro de municipio - BUSCA EN TODOS LOS MUNICIPIOS
                    if (pasaFiltro && filterMunicipio) {
                        const municipios = [municipioPrincipal, municipioSecundario, municipioTerciario];
                        const encontrado = municipios.some(municipio => 
                            municipio && municipio.includes(filterMunicipio)
                        );
                        
                        if (!encontrado) {
                            pasaFiltro = false;
                        }
                    }
                    
                    if (pasaFiltro) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
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