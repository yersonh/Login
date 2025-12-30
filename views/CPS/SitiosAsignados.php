<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitios Asignados - Meta</title>
    
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../styles/sitios_asignados.css">
</head>
<body>

    <div class="barra-superior">
        <i class="fas fa-map-marked-alt"></i> Departamento del Meta - Colombia
    </div>

    <div id="mapa" style="height: 100vh; width: 100%;"></div>

    <!-- Botón Volver al Menú -->
    <button class="volver-btn" id="volverBtn">
        <i class="fas fa-arrow-left"></i>
        <span>Volver al Menú</span>
    </button>

    <!-- Botón para abrir búsqueda (visible solo en móvil) -->
    <button class="btn-open-search" id="openSearchBtn">
        <i class="fas fa-search"></i>
        <span>Buscar Contratistas</span>
    </button>

    <!-- Contenedor de búsqueda (modificado para móvil) -->
    <div class="search-container" id="searchContainer">
        <div class="card">
            <div class="card-header">
                <h6>Buscar Contratistas</h6>
                <button class="btn-close-search" id="closeSearchBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body">
                <!-- Aquí va el formulario de búsqueda que ya tienes -->
                <!-- Mantén tu formulario existente aquí -->
                <div class="mb-3">
                    <label class="form-label">Nombre del Contratista</label>
                    <input type="text" class="form-control" id="nombreContratista" 
                           placeholder="Buscar por nombre...">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tipo de Servicio</label>
                    <select class="form-select" id="tipoServicio">
                        <option value="">Todos los servicios</option>
                        <!-- Tus opciones -->
                    </select>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="btnBuscar">
                        <i class="fas fa-search me-2"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnLimpiar">
                        <i class="fas fa-undo me-2"></i> Limpiar filtros
                    </button>
                </div>
                
                <!-- Resultados -->
                <div id="resultadosBusqueda" class="mt-4" style="display: none;">
                    <h6>
                        <i class="fas fa-list"></i> Resultados
                        <span id="contadorResultados" class="ms-2">0</span>
                    </h6>
                    <div class="resultados-list" id="listaResultados">
                        <!-- Los resultados se cargarán aquí -->
                    </div>
                </div>
                
                <!-- Indicador de búsqueda -->
                <div id="indicadorBusqueda" class="alert alert-light text-center mt-3" style="display: none;">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p class="mb-0">Ingresa criterios de búsqueda para encontrar contratistas</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Control de búsqueda en móvil
        document.addEventListener('DOMContentLoaded', function() {
            const searchContainer = document.getElementById('searchContainer');
            const openSearchBtn = document.getElementById('openSearchBtn');
            const closeSearchBtn = document.getElementById('closeSearchBtn');
            const volverBtn = document.getElementById('volverBtn');
            
            // Función para verificar si es móvil
            function isMobile() {
                return window.innerWidth <= 768;
            }
            
            // Función para abrir buscador
            function openSearch() {
                searchContainer.classList.add('active');
                // Detener interacción con el mapa mientras el modal está abierto
                if (window.map) {
                    window.map.dragging.disable();
                    window.map.scrollWheelZoom.disable();
                }
            }
            
            // Función para cerrar buscador
            function closeSearch() {
                searchContainer.classList.remove('active');
                // Reactivar interacción con el mapa
                if (window.map) {
                    window.map.dragging.enable();
                    window.map.scrollWheelZoom.enable();
                }
            }
            
            // Inicializar visibilidad del botón de búsqueda
            function initMobileSearch() {
                if (isMobile()) {
                    // En móvil: mostrar botón flotante
                    openSearchBtn.style.display = 'flex';
                    // Ocultar buscador inicialmente
                    searchContainer.style.display = 'none';
                    
                    // Event listeners para móvil
                    openSearchBtn.addEventListener('click', openSearch);
                    closeSearchBtn.addEventListener('click', closeSearch);
                    
                    // Cerrar al hacer clic fuera del card (en el overlay)
                    searchContainer.addEventListener('click', function(e) {
                        if (e.target === searchContainer) {
                            closeSearch();
                        }
                    });
                    
                    // Cerrar con tecla Escape
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape' && searchContainer.classList.contains('active')) {
                            closeSearch();
                        }
                    });
                } else {
                    // En PC: mostrar buscador normal
                    openSearchBtn.style.display = 'none';
                    searchContainer.style.display = 'block';
                }
            }
            
            // Control del botón volver
            volverBtn.addEventListener('click', () => {
                window.location.href = 'menuContratistas.php';
            });
            
            // Inicializar al cargar
            initMobileSearch();
            
            // Revisar al cambiar tamaño de ventana
            window.addEventListener('resize', initMobileSearch);
        });
    </script>

    <script src="../../javascript/sitios_asignados.js"></script>

</body>
</html>