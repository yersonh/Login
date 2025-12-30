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
    <style>
        /* ESTILOS TEMPORALES PARA DEBUG */
        .debug-border {
            border: 2px solid red !important;
        }
    </style>
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

    <!-- BUSCADOR (se transforma en móvil) -->
    <div class="search-container" id="searchContainer">
        <div class="card">
            <div class="card-header">
                <h6>Buscar Contratistas</h6>
                <button class="btn-close-search" id="closeSearchBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nombre del Contratista</label>
                    <input type="text" class="form-control" id="nombreContratista" 
                           placeholder="Buscar por nombre...">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tipo de Servicio</label>
                    <select class="form-select" id="tipoServicio">
                        <option value="">Todos los servicios</option>
                        <option value="construccion">Construcción</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="consultoria">Consultoría</option>
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
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // CONTROL SIMPLIFICADO Y FUNCIONAL
        document.addEventListener('DOMContentLoaded', function() {
            const searchContainer = document.getElementById('searchContainer');
            const openSearchBtn = document.getElementById('openSearchBtn');
            const closeSearchBtn = document.getElementById('closeSearchBtn');
            const volverBtn = document.getElementById('volverBtn');
            
            console.log('Iniciando control de búsqueda...');
            
            // Verificar si estamos en móvil
            function isMobile() {
                return window.innerWidth <= 768;
            }
            
            // Configurar visibilidad inicial
            function setupInitialView() {
                if (isMobile()) {
                    console.log('Modo MÓVIL activado');
                    // En móvil: ocultar buscador, mostrar botón
                    searchContainer.style.display = 'none';
                    openSearchBtn.style.display = 'flex';
                    // Añadir clase para estilos móviles
                    searchContainer.classList.add('mobile-modal');
                } else {
                    console.log('Modo PC activado');
                    // En PC: mostrar buscador, ocultar botón
                    searchContainer.style.display = 'block';
                    openSearchBtn.style.display = 'none';
                    // Quitar clase de móvil
                    searchContainer.classList.remove('mobile-modal');
                }
            }
            
            // Abrir buscador en móvil
            function openSearch() {
                console.log('Abriendo buscador móvil');
                searchContainer.style.display = 'flex';
                setTimeout(() => {
                    searchContainer.classList.add('active');
                }, 10);
                
                // Si hay un mapa, deshabilitar interacción temporalmente
                if (typeof map !== 'undefined' && map) {
                    map.dragging.disable();
                    map.scrollWheelZoom.disable();
                }
            }
            
            // Cerrar buscador en móvil
            function closeSearch() {
                console.log('Cerrando buscador móvil');
                searchContainer.classList.remove('active');
                setTimeout(() => {
                    if (isMobile()) {
                        searchContainer.style.display = 'none';
                    }
                }, 300);
                
                // Rehabilitar interacción con el mapa
                if (typeof map !== 'undefined' && map) {
                    map.dragging.enable();
                    map.scrollWheelZoom.enable();
                }
            }
            
            // Inicializar
            setupInitialView();
            
            // Event Listeners
            openSearchBtn.addEventListener('click', openSearch);
            closeSearchBtn.addEventListener('click', closeSearch);
            
            // Cerrar al hacer clic fuera (solo en móvil)
            searchContainer.addEventListener('click', function(e) {
                if (isMobile() && e.target === searchContainer) {
                    closeSearch();
                }
            });
            
            // Cerrar con Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isMobile() && searchContainer.classList.contains('active')) {
                    closeSearch();
                }
            });
            
            // Botón volver
            volverBtn.addEventListener('click', function() {
                window.location.href = 'menuContratistas.php';
            });
            
            // Re-configurar al cambiar tamaño
            window.addEventListener('resize', function() {
                setupInitialView();
            });
            
            // DEMO: Simular búsqueda
            document.getElementById('btnBuscar').addEventListener('click', function() {
                const nombre = document.getElementById('nombreContratista').value;
                const servicio = document.getElementById('tipoServicio').value;
                
                if (nombre || servicio) {
                    alert(`Buscando: ${nombre || 'Todos'} - Servicio: ${servicio || 'Todos'}`);
                    // En móvil, cerrar después de buscar
                    if (isMobile()) {
                        setTimeout(closeSearch, 500);
                    }
                } else {
                    alert('Por favor ingresa algún criterio de búsqueda');
                }
            });
            
            document.getElementById('btnLimpiar').addEventListener('click', function() {
                document.getElementById('nombreContratista').value = '';
                document.getElementById('tipoServicio').value = '';
            });
        });
    </script>

    <script src="../../javascript/sitios_asignados.js"></script>

</body>
</html>