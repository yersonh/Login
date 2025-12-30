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

    <!-- BUSCADOR (se transforma en modal en móvil) -->
    <div class="search-container" id="searchContainer">
        <div class="card">
            <div class="card-header">
                <h6>Buscar Contratistas</h6>
                <button class="btn-close-search" id="closeSearchBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body">
                <!-- FORMULARIO DE BÚSQUEDA -->
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
                
                <!-- RESULTADOS -->
                <div id="resultadosBusqueda" class="mt-4" style="display: none;">
                    <h6>
                        <i class="fas fa-list"></i> Resultados
                        <span id="contadorResultados" class="ms-2">0</span>
                    </h6>
                    <div class="resultados-list" id="listaResultados">
                        <!-- Los resultados se cargarán aquí -->
                    </div>
                </div>
                
                <!-- INDICADOR DE BÚSQUEDA -->
                <div id="indicadorBusqueda" class="alert alert-light text-center mt-3" style="display: none;">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p class="mb-0">Ingresa criterios de búsqueda para encontrar contratistas</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // CONTROL DE BÚSQUEDA RESPONSIVE
        document.addEventListener('DOMContentLoaded', function() {
            const searchContainer = document.getElementById('searchContainer');
            const openSearchBtn = document.getElementById('openSearchBtn');
            const closeSearchBtn = document.getElementById('closeSearchBtn');
            const volverBtn = document.getElementById('volverBtn');
            
            console.log('Script de búsqueda cargado');
            
            // Verificar si es móvil
            function isMobile() {
                return window.innerWidth <= 768;
            }
            
            // Configurar visibilidad inicial
            function setupInitialView() {
                console.log('Configurando vista inicial...');
                
                if (isMobile()) {
                    console.log('Móvil detectado');
                    // En móvil: ocultar buscador, mostrar botón flotante
                    searchContainer.style.display = 'none';
                    openSearchBtn.style.display = 'flex';
                    
                    // Asegurar que el botón cerrar esté visible en móvil
                    closeSearchBtn.style.display = 'flex';
                    
                } else {
                    console.log('PC detectado');
                    // En PC: mostrar buscador normal, ocultar botón flotante
                    searchContainer.style.display = 'block';
                    openSearchBtn.style.display = 'none';
                    
                    // Ocultar botón cerrar en PC
                    closeSearchBtn.style.display = 'none';
                    
                    // Posicionar buscador
                    searchContainer.style.position = 'absolute';
                    searchContainer.style.top = '70px';
                    searchContainer.style.left = '20px';
                    searchContainer.style.width = '320px';
                }
            }
            
            // Abrir buscador en móvil
            function openSearch() {
                console.log('Abriendo modal de búsqueda');
                
                // Mostrar el contenedor
                searchContainer.style.display = 'flex';
                
                // Esperar un momento para que se aplique el display
                setTimeout(() => {
                    searchContainer.classList.add('active');
                }, 10);
                
                // Deshabilitar interacción con el mapa si existe
                if (typeof map !== 'undefined' && map) {
                    map.dragging.disable();
                    map.scrollWheelZoom.disable();
                    console.log('Interacción del mapa deshabilitada');
                }
            }
            
            // Cerrar buscador en móvil
            function closeSearch() {
                console.log('Cerrando modal de búsqueda');
                
                // Remover clase active
                searchContainer.classList.remove('active');
                
                // Esperar a que termine la animación para ocultar
                setTimeout(() => {
                    if (isMobile()) {
                        searchContainer.style.display = 'none';
                    }
                }, 300);
                
                // Rehabilitar interacción con el mapa
                if (typeof map !== 'undefined' && map) {
                    map.dragging.enable();
                    map.scrollWheelZoom.enable();
                    console.log('Interacción del mapa habilitada');
                }
            }
            
            // Inicializar
            setupInitialView();
            
            // EVENT LISTENERS
            openSearchBtn.addEventListener('click', openSearch);
            closeSearchBtn.addEventListener('click', closeSearch);
            
            // Cerrar al hacer clic fuera del card (solo en móvil)
            searchContainer.addEventListener('click', function(e) {
                if (isMobile() && e.target === searchContainer) {
                    closeSearch();
                }
            });
            
            // Cerrar con tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isMobile() && searchContainer.classList.contains('active')) {
                    closeSearch();
                }
            });
            
            // Botón volver
            volverBtn.addEventListener('click', function() {
                window.location.href = 'menuContratistas.php';
            });
            
            // DEMO: Funcionalidad de búsqueda
            document.getElementById('btnBuscar').addEventListener('click', function() {
                const nombre = document.getElementById('nombreContratista').value;
                const servicio = document.getElementById('tipoServicio').value;
                
                console.log('Buscando:', { nombre, servicio });
                
                if (nombre || servicio) {
                    // Mostrar resultados
                    const resultadosDiv = document.getElementById('resultadosBusqueda');
                    const lista = document.getElementById('listaResultados');
                    const contador = document.getElementById('contadorResultados');
                    
                    // Simular resultados
                    lista.innerHTML = `
                        <div class="result-item">
                            <div class="fw-semibold">Juan Pérez</div>
                            <small class="text-muted">Construcción de vías</small>
                        </div>
                        <div class="result-item">
                            <div class="fw-semibold">María Gómez</div>
                            <small class="text-muted">Mantenimiento de infraestructura</small>
                        </div>
                    `;
                    
                    contador.textContent = '2';
                    resultadosDiv.style.display = 'block';
                    
                    // Ocultar indicador
                    document.getElementById('indicadorBusqueda').style.display = 'none';
                    
                    // En móvil, cerrar después de buscar
                    if (isMobile()) {
                        setTimeout(closeSearch, 1000);
                    }
                    
                } else {
                    // Mostrar indicador
                    document.getElementById('indicadorBusqueda').style.display = 'block';
                    document.getElementById('resultadosBusqueda').style.display = 'none';
                    alert('Por favor ingresa algún criterio de búsqueda');
                }
            });
            
            document.getElementById('btnLimpiar').addEventListener('click', function() {
                document.getElementById('nombreContratista').value = '';
                document.getElementById('tipoServicio').value = '';
                document.getElementById('resultadosBusqueda').style.display = 'none';
                document.getElementById('indicadorBusqueda').style.display = 'none';
            });
            
            // Reconfigurar al cambiar tamaño de ventana
            window.addEventListener('resize', function() {
                console.log('Ventana redimensionada');
                setupInitialView();
            });
            
            // DEBUG: Ver estado inicial
            console.log('Estado inicial - isMobile:', isMobile());
            console.log('searchContainer display:', searchContainer.style.display);
            console.log('openSearchBtn display:', openSearchBtn.style.display);
        });
    </script>

    <script src="../../javascript/sitios_asignados.js"></script>

</body>
</html>