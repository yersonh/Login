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

    <!-- BUSCADOR PARA PC (normal) -->
    <div class="search-container" id="searchContainerPC">
        <div class="card">
            <div class="card-header">
                <h6>Buscar Contratistas</h6>
            </div>
            <div class="card-body">
                <!-- Contenido del formulario para PC -->
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
            </div>
        </div>
    </div>

    <!-- BUSCADOR PARA MÓVIL (modal) -->
    <div class="search-container mobile-modal" id="searchContainerMobile">
        <div class="card">
            <div class="card-header">
                <h6>Buscar Contratistas</h6>
                <button class="btn-close-search" id="closeSearchBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body">
                <!-- MISMO contenido que para PC -->
                <div class="mb-3">
                    <label class="form-label">Nombre del Contratista</label>
                    <input type="text" class="form-control" id="nombreContratistaMobile" 
                           placeholder="Buscar por nombre...">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tipo de Servicio</label>
                    <select class="form-select" id="tipoServicioMobile">
                        <option value="">Todos los servicios</option>
                        <!-- Tus opciones -->
                    </select>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="btnBuscarMobile">
                        <i class="fas fa-search me-2"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="btnLimpiarMobile">
                        <i class="fas fa-undo me-2"></i> Limpiar filtros
                    </button>
                </div>
                
                <!-- Resultados -->
                <div id="resultadosBusquedaMobile" class="mt-4" style="display: none;">
                    <h6>
                        <i class="fas fa-list"></i> Resultados
                        <span id="contadorResultadosMobile" class="ms-2">0</span>
                    </h6>
                    <div class="resultados-list" id="listaResultadosMobile">
                        <!-- Los resultados se cargarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Control de búsqueda en móvil - VERSIÓN SIMPLIFICADA Y FUNCIONAL
        document.addEventListener('DOMContentLoaded', function() {
            const searchContainerPC = document.getElementById('searchContainerPC');
            const searchContainerMobile = document.getElementById('searchContainerMobile');
            const openSearchBtn = document.getElementById('openSearchBtn');
            const closeSearchBtn = document.getElementById('closeSearchBtn');
            const volverBtn = document.getElementById('volverBtn');
            
            // Función para verificar si es móvil
            function isMobile() {
                return window.innerWidth <= 768;
            }
            
            // Función para inicializar visibilidad
            function initSearchVisibility() {
                if (isMobile()) {
                    // En móvil: mostrar botón flotante y modal oculto
                    openSearchBtn.style.display = 'flex';
                    searchContainerPC.style.display = 'none';
                    searchContainerMobile.style.display = 'none'; // Ocultar inicialmente
                    
                    console.log('Móvil detectado - Configurando modal');
                } else {
                    // En PC: mostrar buscador normal, ocultar botón flotante y modal
                    openSearchBtn.style.display = 'none';
                    searchContainerPC.style.display = 'block';
                    searchContainerMobile.style.display = 'none';
                    
                    console.log('PC detectado - Mostrando buscador normal');
                }
            }
            
            // Función para abrir modal en móvil
            function openSearchModal() {
                console.log('Abriendo modal de búsqueda');
                searchContainerMobile.style.display = 'flex';
                // Usar setTimeout para asegurar que el display se aplique antes de la animación
                setTimeout(() => {
                    searchContainerMobile.classList.add('active');
                }, 10);
                
                // Deshabilitar interacción con el mapa si existe
                if (window.map) {
                    window.map.dragging.disable();
                    window.map.scrollWheelZoom.disable();
                }
            }
            
            // Función para cerrar modal
            function closeSearchModal() {
                console.log('Cerrando modal de búsqueda');
                searchContainerMobile.classList.remove('active');
                
                // Esperar a que termine la animación para ocultar
                setTimeout(() => {
                    searchContainerMobile.style.display = 'none';
                }, 300);
                
                // Rehabilitar interacción con el mapa
                if (window.map) {
                    window.map.dragging.enable();
                    window.map.scrollWheelZoom.enable();
                }
            }
            
            // Inicializar al cargar
            initSearchVisibility();
            
            // Event listeners
            openSearchBtn.addEventListener('click', openSearchModal);
            closeSearchBtn.addEventListener('click', closeSearchModal);
            
            // Cerrar modal al hacer clic fuera del card
            searchContainerMobile.addEventListener('click', function(e) {
                if (e.target === searchContainerMobile) {
                    closeSearchModal();
                }
            });
            
            // Cerrar con tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && searchContainerMobile.classList.contains('active')) {
                    closeSearchModal();
                }
            });
            
            // Botón volver
            volverBtn.addEventListener('click', () => {
                window.location.href = 'menuContratistas.php';
            });
            
            // Revisar al cambiar tamaño de ventana
            window.addEventListener('resize', initSearchVisibility);
            
            // Sincronizar valores entre PC y móvil (opcional)
            function syncSearchFields() {
                const pcNombre = document.getElementById('nombreContratista');
                const mobileNombre = document.getElementById('nombreContratistaMobile');
                
                if (pcNombre && mobileNombre) {
                    pcNombre.addEventListener('input', function() {
                        mobileNombre.value = this.value;
                    });
                    
                    mobileNombre.addEventListener('input', function() {
                        pcNombre.value = this.value;
                    });
                }
            }
            
            syncSearchFields();
        });
    </script>

    <script src="../../javascript/sitios_asignados.js"></script>

</body>
</html>