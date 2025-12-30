// JavaScript para mapa centrado en el Meta con buscador/filtro profesional
// VERSIÓN COMPLETA - Con procesamiento de contratistas funcional
document.addEventListener('DOMContentLoaded', function() {
    if (typeof L === 'undefined') {
        console.error('Leaflet no está cargado');
        return;
    }
    
    // Coordenadas del Meta
    const centroMeta = [3.9026, -73.0769];
    const villavicencio = [4.1420, -73.6266];
    const zoomInicial = 10;
    
    // Crear el mapa
    var mapa = L.map('mapa', {
        zoomControl: false,
        center: villavicencio,
        zoom: zoomInicial,
        tap: true,
        dragging: true,
        scrollWheelZoom: true,
        touchZoom: true,
        doubleClickZoom: true,
        boxZoom: true,
        keyboard: true
    });
    
    // Añadir capa de OpenStreetMap
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(mapa);
    
    console.log('✅ Mapa del Meta cargado');
    
    // Variables globales para el buscador
    var marcadoresContratistas = L.layerGroup().addTo(mapa);
    var municipiosCargados = [];
    var areasCargadas = [];
    var tiposVinculacionCargados = [];
    var todosContratistas = [];
    var contratistasProcesados = [];
    
    // Variables para control de búsqueda
    var busquedaEnCurso = false;
    var ultimaBusquedaAbortController = null;
    var timeoutDebounce = null;
    var procesamientoActivo = false;
    
    // Variables para control del buscador
    var searchContainer = null;
    var searchControl = null;
    var mobileSearchBtn = null;
    var isModalOpen = false;
    var keyboardVisible = false;
    var originalMobileBtnBottom = 140;
    
    // Función para verificar si es móvil
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Inicializar sistema
    function inicializarSistema() {
        // 1. Crear buscador
        inicializarBuscador();
        
        // 2. Configurar visibilidad según dispositivo
        configurarVisibilidadBuscador();
        
        // 3. Cargar datos iniciales
        Promise.all([
            cargarMunicipios(),
            cargarAreas(),
            cargarTiposVinculacion()
        ]).then(() => {
            // 4. Luego cargar todos los contratistas
            cargarContratistas();
        }).catch(error => {
            console.error('❌ Error cargando datos iniciales:', error);
            mostrarMensaje('Error al cargar datos iniciales');
        });
    }
    
    // ================= BUSCADOR RESPONSIVE =================
    
    function inicializarBuscador() {
        // Si ya existe, limpiar
        if (searchControl) {
            mapa.removeControl(searchControl);
        }
        
        // Crear contenedor para el buscador
        searchControl = L.control({ position: 'topright' });
        
        searchControl.onAdd = function(map) {
            searchContainer = L.DomUtil.create('div', 'search-container');
            searchContainer.style.cssText = 'position: relative; z-index: 1000; pointer-events: auto;';
            
            searchContainer.innerHTML = `
                <div class="card search-panel" id="searchCard" style="width: 420px; max-width: 90vw; position: relative; pointer-events: auto;">
                    <div class="card-header bg-primary text-white py-2 position-relative">
                        <h6 class="mb-0">
                            <i class="fas fa-search me-2"></i>Buscar Contratistas
                        </h6>
                        <button type="button" id="closeSearchBtn" class="btn-close-search" style="display: none;" aria-label="Cerrar buscador">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="card-body p-3" id="searchBody" style="pointer-events: auto;">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-user me-1"></i>Nombre del contratista
                            </label>
                            <input type="text" 
                                id="inputNombre" 
                                class="form-control search-input" 
                                placeholder="Ingrese nombre o apellido"
                                data-prevent-close="true">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-map-marker-alt me-1"></i>Municipio
                            </label>
                            <select id="selectMunicipio" class="form-select search-select" data-prevent-close="true">
                                <option value="">Todos los municipios</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-building me-1"></i>Área
                            </label>
                            <select id="selectArea" class="form-select search-select" data-prevent-close="true">
                                <option value="">Todas las áreas</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-handshake me-1"></i>Tipo de Vinculación
                            </label>
                            <select id="selectTipoVinculacion" class="form-select search-select" data-prevent-close="true">
                                <option value="">Todos los tipos</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="button" id="btnBuscar" onclick="buscarContratistas()" 
                                    class="btn btn-primary flex-grow-1 search-button" data-prevent-close="true">
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                            <button type="button" onclick="limpiarBusqueda()" 
                                    class="btn btn-outline-secondary search-button" data-prevent-close="true">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </button>
                        </div>
                        
                        <div id="indicadorBusqueda" class="mt-2" style="display: none;">
                            <div class="d-flex align-items-center text-primary">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Buscando...</span>
                                </div>
                                <small class="fw-medium">Buscando contratistas...</small>
                            </div>
                        </div>
                        
                        <div id="resultadosBusqueda" class="mt-4" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-list me-1"></i>Resultados de búsqueda
                                </h6>
                                <span class="badge bg-primary" id="contadorResultados">0</span>
                            </div>
                            <div id="listaResultados" class="resultados-list"></div>
                        </div>
                    </div>
                </div>
            `;
            
            L.DomEvent.disableClickPropagation(searchContainer);
            L.DomEvent.disableScrollPropagation(searchContainer);
            
            return searchContainer;
        };
        
        searchControl.addTo(mapa);
        
        crearBotonMovil();
        
        setTimeout(configurarEventosBuscador, 100);
    }
    
    function configurarEventosBuscador() {
        const inputs = document.querySelectorAll('[data-prevent-close="true"]');
        const closeBtn = document.getElementById('closeSearchBtn');
        
        const inputNombre = document.getElementById('inputNombre');
        if (inputNombre) {
            inputNombre.addEventListener('focus', function() {
                keyboardVisible = true;
                if (mobileSearchBtn) {
                    mobileSearchBtn.style.bottom = '250px';
                }
            });
            
            inputNombre.addEventListener('blur', function() {
                setTimeout(() => {
                    keyboardVisible = false;
                    if (mobileSearchBtn) {
                        mobileSearchBtn.style.bottom = originalMobileBtnBottom + 'px';
                    }
                }, 300);
            });
        }
        
        inputs.forEach(element => {
            element.addEventListener('touchstart', function(e) {
                e.stopPropagation();
            });
            
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            element.addEventListener('focus', function(e) {
                e.stopPropagation();
            });
        });
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                cerrarBuscadorMovil();
            });
        }
    }
    
    function crearBotonMovil() {
        if (document.getElementById('mobileSearchBtn')) {
            mobileSearchBtn = document.getElementById('mobileSearchBtn');
            return;
        }
        
        mobileSearchBtn = document.createElement('button');
        mobileSearchBtn.id = 'mobileSearchBtn';
        mobileSearchBtn.type = 'button';
        mobileSearchBtn.className = 'btn-open-search';
        mobileSearchBtn.innerHTML = `
            <i class="fas fa-search"></i>
            <span>Buscar Contratistas</span>
        `;
        
        const bottomPosition = 140;
        
        mobileSearchBtn.style.cssText = `
            position: fixed;
            bottom: ${bottomPosition}px;
            right: 20px;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 14px 20px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 1001;
            white-space: nowrap;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            -webkit-tap-highlight-color: transparent;
            pointer-events: auto;
        `;
        
        document.body.appendChild(mobileSearchBtn);
        
        originalMobileBtnBottom = bottomPosition;
        
        mobileSearchBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            abrirBuscadorMovil();
        });
        
        mobileSearchBtn.addEventListener('touchstart', function(e) {
            e.stopPropagation();
            e.preventDefault();
            abrirBuscadorMovil();
        }, { passive: false });
    }
    
    function configurarVisibilidadBuscador() {
        if (isMobile()) {
            if (searchContainer) {
                searchContainer.style.display = 'none';
            }
            if (mobileSearchBtn) {
                mobileSearchBtn.style.display = 'flex';
            }
            
            const closeBtn = document.getElementById('closeSearchBtn');
            if (closeBtn) {
                closeBtn.style.display = 'flex';
            }
            
        } else {
            if (searchContainer) {
                searchContainer.style.display = 'block';
                searchContainer.className = 'search-container';
                searchContainer.style.position = '';
                searchContainer.style.background = '';
            }
            
            if (mobileSearchBtn) {
                mobileSearchBtn.style.display = 'none';
            }
            
            const closeBtn = document.getElementById('closeSearchBtn');
            if (closeBtn) {
                closeBtn.style.display = 'none';
            }
            
            isModalOpen = false;
        }
    }
    
    function abrirBuscadorMovil() {
        if (searchContainer) {
            isModalOpen = true;
            
            if (mobileSearchBtn) {
                mobileSearchBtn.style.display = 'none';
            }
            
            searchContainer.style.display = 'flex';
            searchContainer.className = 'search-container modal-open';
            searchContainer.style.position = 'fixed';
            searchContainer.style.top = '0';
            searchContainer.style.left = '0';
            searchContainer.style.width = '100%';
            searchContainer.style.height = '100%';
            searchContainer.style.background = 'rgba(0, 0, 0, 0.7)';
            searchContainer.style.backdropFilter = 'blur(3px)';
            searchContainer.style.justifyContent = 'center';
            searchContainer.style.alignItems = 'center';
            searchContainer.style.padding = '15px';
            searchContainer.style.boxSizing = 'border-box';
            searchContainer.style.zIndex = '2000';
            
            if (mapa) {
                mapa.getContainer().style.pointerEvents = 'none';
            }
            
            document.body.classList.add('modal-open');
            
            setTimeout(() => {
                const inputNombre = document.getElementById('inputNombre');
                if (inputNombre) {
                    inputNombre.focus();
                }
            }, 300);
        }
    }
    
    function cerrarBuscadorMovil() {
        if (searchContainer) {
            isModalOpen = false;
            keyboardVisible = false;
            
            searchContainer.style.display = 'none';
            searchContainer.className = 'search-container';
            
            if (mapa) {
                mapa.getContainer().style.pointerEvents = 'auto';
            }
            
            document.body.classList.remove('modal-open');
            
            if (mobileSearchBtn && isMobile()) {
                mobileSearchBtn.style.display = 'flex';
                mobileSearchBtn.style.bottom = originalMobileBtnBottom + 'px';
            }
        }
    }
    
    // Añadir controles básicos
    L.control.scale().addTo(mapa);
    L.control.zoom({ position: 'bottomright' }).addTo(mapa);
    
    // Inicializar sistema
    inicializarSistema();
    
    // ================= FUNCIONES DE CARGA DE DATOS =================
    
    async function cargarMunicipios() {
        console.log('🔄 Cargando municipios...');
        
        try {
            const response = await fetch('../../api/municipiosMapa.php');
            
            if (!response.ok) {
                throw new Error('Error al cargar municipios');
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                municipiosCargados = result.data;
                llenarSelectMunicipios();
                console.log(`✅ ${municipiosCargados.length} municipios cargados`);
            }
            
        } catch (error) {
            console.error('❌ Error cargando municipios:', error);
            throw error;
        }
    }
    
    async function cargarAreas() {
        console.log('🔄 Cargando áreas...');
        
        try {
            const response = await fetch('../../api/areasMapa.php');
            
            if (!response.ok) {
                throw new Error('Error al cargar áreas');
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                areasCargadas = result.data;
                llenarSelectAreas();
                console.log(`✅ ${areasCargadas.length} áreas cargadas`);
            }
            
        } catch (error) {
            console.error('❌ Error cargando áreas:', error);
            throw error;
        }
    }
    
    function llenarSelectMunicipios() {
        const select = document.getElementById('selectMunicipio');
        if (!select) return;
        
        municipiosCargados.sort((a, b) => a.nombre.localeCompare(b.nombre));
        
        municipiosCargados.forEach(municipio => {
            const option = document.createElement('option');
            option.value = municipio.nombre;
            option.textContent = municipio.nombre;
            select.appendChild(option);
        });
    }
    
    function llenarSelectAreas() {
        const select = document.getElementById('selectArea');
        if (!select) return;
        
        areasCargadas.sort((a, b) => a.nombre.localeCompare(b.nombre));
        
        areasCargadas.forEach(area => {
            const option = document.createElement('option');
            option.value = area.nombre;
            option.textContent = area.nombre;
            select.appendChild(option);
        });
    }
    
    // ================= FUNCIONES PRINCIPALES =================
    
    async function cargarContratistas(filtros = {}) {
        // Cancelar búsqueda anterior si existe
        if (ultimaBusquedaAbortController) {
            ultimaBusquedaAbortController.abort();
            console.log('⏹️ Búsqueda anterior cancelada');
        }
        
        // Crear nuevo AbortController
        ultimaBusquedaAbortController = new AbortController();
        
        // Verificar si ya hay una búsqueda en curso
        if (busquedaEnCurso) {
            console.log('⚠️ Ya hay una búsqueda en curso, esperando...');
            return;
        }
        
        // Establecer bandera de búsqueda en curso
        busquedaEnCurso = true;
        procesamientoActivo = true;
        
        // Mostrar indicador de búsqueda
        mostrarIndicadorBusqueda(true);
        
        console.log('🔄 Cargando contratistas...', filtros);
        
        try {
            // Construir URL con parámetros de filtro
            let url = '../../api/contratistas_mapa.php';
            const params = new URLSearchParams();
            
            if (filtros.nombre) params.append('nombre', filtros.nombre);
            if (filtros.municipio) params.append('municipio', filtros.municipio);
            if (filtros.area) params.append('area', filtros.area);
            if (filtros.tipo_vinculacion) params.append('tipo', filtros.tipo_vinculacion);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url, {
                signal: ultimaBusquedaAbortController.signal
            });
            
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            
            const result = await response.json();
            console.log('📦 Respuesta de la API:', result);
            
            if (!result.success) {
                throw new Error(result.error || 'Error desconocido');
            }
            
            // Asegurarse de que contratistas sea siempre un array
            let contratistas = result.data;
            
            // Validación robusta
            if (!contratistas || !Array.isArray(contratistas)) {
                console.warn('⚠️ La API no devolvió un array válido:', contratistas);
                contratistas = [];
            }
            
            console.log(`📊 ${contratistas.length} contratista(s) cargado(s)`);
            
            // Verificar si el procesamiento sigue activo (no fue cancelado)
            if (!procesamientoActivo) {
                console.log('⏹️ Procesamiento cancelado por nueva búsqueda');
                return;
            }
            
            // Guardar todos los contratistas
            todosContratistas = contratistas;
            
            // Limpiar marcadores anteriores
            marcadoresContratistas.clearLayers();
            contratistasProcesados = [];
            
            if (contratistas.length === 0) {
                mostrarMensaje('No hay contratistas que coincidan con los filtros');
                // Solo mostrar resultados si es una búsqueda activa
                if (Object.keys(filtros).length > 0) {
                    actualizarListaResultados(contratistasProcesados);
                } else {
                    ocultarResultadosBusqueda();
                }
                
                // Ocultar indicador
                mostrarIndicadorBusqueda(false);
                busquedaEnCurso = false;
                procesamientoActivo = false;
                return;
            }
            
            // Procesar cada contratista
            for (const contratista of contratistas) {
                // Verificar si el procesamiento sigue activo
                if (!procesamientoActivo) {
                    console.log('⏹️ Procesamiento interrumpido por nueva búsqueda');
                    break;
                }
                
                const contratistaProcesado = await procesarContratista(contratista);
                contratistasProcesados.push(contratistaProcesado);
                await esperar(150); // Pausa para no saturar OSM
            }
            
            // Actualizar lista de resultados SOLO si es una búsqueda específica
            if (Object.keys(filtros).length > 0) {
                actualizarListaResultados(contratistasProcesados);
            }
            
            console.log('✅ Procesamiento completado');
            
        } catch (error) {
            // Ignorar errores de aborto
            if (error.name === 'AbortError') {
                console.log('⏹️ Búsqueda cancelada por el usuario');
                return;
            }
            
            console.error('❌ Error cargando contratistas:', error);
            mostrarMensaje('Error al cargar los contratistas: ' + error.message);
        } finally {
            // Ocultar indicador
            mostrarIndicadorBusqueda(false);
            busquedaEnCurso = false;
            procesamientoActivo = false;
        }
    }
    
    // Función para mostrar/ocultar indicador de búsqueda
    function mostrarIndicadorBusqueda(mostrar) {
        const indicador = document.getElementById('indicadorBusqueda');
        const btnBuscar = document.getElementById('btnBuscar');
        
        if (indicador && btnBuscar) {
            if (mostrar) {
                indicador.style.display = 'block';
                btnBuscar.disabled = true;
                btnBuscar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Buscando...';
            } else {
                indicador.style.display = 'none';
                btnBuscar.disabled = false;
                btnBuscar.innerHTML = '<i class="fas fa-search me-1"></i>Buscar';
            }
        }
    }
    
    async function cargarTiposVinculacion() {
        console.log('🔄 Cargando tipos de vinculación...');
        
        try {
            const response = await fetch('../../api/tiposVinculacionMapa.php');
            
            if (!response.ok) {
                throw new Error('Error al cargar tipos de vinculación');
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                tiposVinculacionCargados = result.data;
                llenarSelectTiposVinculacion();
                console.log(`✅ ${tiposVinculacionCargados.length} tipos de vinculación cargados`);
            }
            
        } catch (error) {
            console.error('❌ Error cargando tipos de vinculación:', error);
            throw error;
        }
    }
    
    // Llenar select de tipos de vinculación
    function llenarSelectTiposVinculacion() {
        const select = document.getElementById('selectTipoVinculacion');
        if (!select) return;
        
        // Agregar opciones
        tiposVinculacionCargados.forEach(tipo => {
            const option = document.createElement('option');
            option.value = tipo.nombre;
            option.textContent = tipo.nombre;
            select.appendChild(option);
        });
    }
    
    // ================= FUNCIÓN PRINCIPAL PARA PROCESAR CONTRATISTAS =================
    
    // Función principal para procesar un contratista
    async function procesarContratista(contratista) {
        console.log(`📋 Procesando contratista: ${contratista.nombre}`);
        
        // Array para almacenar todos los marcadores del contratista
        const marcadores = [];
        
        // Verificar si el contratista tiene sitios de trabajo
        if (contratista.sitios_trabajo && contratista.sitios_trabajo.length > 0) {
            console.log(`   📍 Tiene ${contratista.sitios_trabajo.length} sitio(s) de trabajo`);
            
            // Procesar cada sitio de trabajo
            for (const sitio of contratista.sitios_trabajo) {
                // Verificar si el procesamiento sigue activo
                if (!procesamientoActivo) {
                    console.log('⏹️ Procesamiento interrumpido por nueva búsqueda');
                    break;
                }
                
                console.log(`   🔍 Procesando sitio ${sitio.tipo}: ${sitio.municipio}`);
                
                let coordenadas = null;
                
                // Intentar geocodificar la dirección del sitio de trabajo
                if (sitio.direccion && sitio.municipio) {
                    console.log(`      📍 Buscando dirección: ${sitio.direccion}, ${sitio.municipio}`);
                    coordenadas = await buscarDireccionMejorada(sitio.direccion, sitio.municipio);
                }
                
                // Si no se encuentra, usar coordenadas del municipio
                if (!coordenadas && sitio.municipio) {
                    console.log(`      🏢 Usando coordenadas del municipio: ${sitio.municipio}`);
                    coordenadas = await obtenerCoordenadasMunicipio(sitio.municipio);
                }
                
                // Si todavía no hay coordenadas, usar Villavicencio como fallback
                if (!coordenadas) {
                    console.log(`      🚨 Usando Villavicencio como fallback`);
                    coordenadas = {
                        lat: villavicencio[0],
                        lng: villavicencio[1]
                    };
                }
                
                // Agregar marcador para este sitio de trabajo
                const marcador = agregarMarcadorSitioTrabajo(contratista, sitio, coordenadas);
                if (marcador) {
                    marcadores.push({
                        marcador: marcador,
                        sitio: sitio,
                        coordenadas: coordenadas
                    });
                }
                
                // Pequeña pausa para no saturar Nominatim
                await esperar(100);
            }
        } else {
            // Fallback: usar datos antiguos (para compatibilidad)
            console.log(`   ⚠️ No tiene sitios de trabajo definidos, usando datos antiguos`);
            
            let coordenadas = null;
            
            // Primero intentar con dirección principal
            if (contratista.direccion_principal && contratista.municipio_principal) {
                coordenadas = await buscarDireccionMejorada(contratista.direccion_principal, contratista.municipio_principal);
            }
            
            // Si no funciona, usar municipio
            if (!coordenadas && contratista.municipio_principal) {
                coordenadas = await obtenerCoordenadasMunicipio(contratista.municipio_principal);
            }
            
            // Último recurso
            if (!coordenadas) {
                coordenadas = {
                    lat: villavicencio[0],
                    lng: villavicencio[1]
                };
            }
            
            const marcador = agregarMarcadorContratista(contratista, coordenadas);
            if (marcador) {
                marcadores.push({
                    marcador: marcador,
                    sitio: { tipo: 'principal', municipio: contratista.municipio_principal },
                    coordenadas: coordenadas
                });
            }
        }
        
        return {
            ...contratista,
            marcadores: marcadores,
            tiene_sitios_trabajo: contratista.sitios_trabajo && contratista.sitios_trabajo.length > 0
        };
    }
    
    // ================= FUNCIONES DE FILTRADO =================
    
    // Buscar contratistas (solo cuando el usuario hace clic en Buscar)
    window.buscarContratistas = function() {
        // Limpiar timeout anterior si existe
        if (timeoutDebounce) {
            clearTimeout(timeoutDebounce);
        }
        
        // Usar debounce para evitar múltiples clics rápidos
        timeoutDebounce = setTimeout(() => {
            const inputNombre = document.getElementById('inputNombre');
            const selectMunicipio = document.getElementById('selectMunicipio');
            const selectArea = document.getElementById('selectArea');
            const selectTipoVinculacion = document.getElementById('selectTipoVinculacion');
            
            if (!inputNombre || !selectMunicipio || !selectArea || !selectTipoVinculacion) {
                console.error('Elementos del buscador no encontrados');
                return;
            }
            
            const filtros = {
                nombre: inputNombre.value.trim(),
                municipio: selectMunicipio.value,
                area: selectArea.value,
                tipo_vinculacion: selectTipoVinculacion.value
            };
            
            // Verificar si hay algún filtro activo
            const tieneFiltros = filtros.nombre || filtros.municipio || filtros.area || filtros.tipo_vinculacion;
            
            if (!tieneFiltros) {
                mostrarMensaje('Por favor, ingrese al menos un criterio de búsqueda');
                return;
            }
            
            // Detener procesamiento actual
            procesamientoActivo = false;
            
            cargarContratistas(filtros);
            
            // En móvil, cerrar el buscador después de buscar
            if (isMobile()) {
                setTimeout(() => {
                    cerrarBuscadorMovil();
                }, 500);
            }
        }, 300); // Debounce de 300ms
    };
    
    // Limpiar búsqueda (vuelve a mostrar todos los contratistas sin filtros)
    window.limpiarBusqueda = function() {
        // Limpiar timeout si existe
        if (timeoutDebounce) {
            clearTimeout(timeoutDebounce);
        }
        
        // Cancelar búsqueda actual si existe
        if (ultimaBusquedaAbortController) {
            ultimaBusquedaAbortController.abort();
        }
        
        // Detener procesamiento actual
        procesamientoActivo = false;
        
        const inputNombre = document.getElementById('inputNombre');
        const selectMunicipio = document.getElementById('selectMunicipio');
        const selectArea = document.getElementById('selectArea');
        const selectTipoVinculacion = document.getElementById('selectTipoVinculacion');
        
        if (inputNombre) inputNombre.value = '';
        if (selectMunicipio) selectMunicipio.selectedIndex = 0;
        if (selectArea) selectArea.selectedIndex = 0;
        if (selectTipoVinculacion) selectTipoVinculacion.selectedIndex = 0;
        
        // Ocultar resultados de búsqueda
        ocultarResultadosBusqueda();
        
        // Mostrar mensaje informativo
        mostrarMensaje('Mostrando todos los contratistas');
        
        // Cargar todos los contratistas sin filtros
        cargarContratistas();
    };
    
    // ================= LISTA DE RESULTADOS =================
    
    function actualizarListaResultados(contratistas) {
        const container = document.getElementById('listaResultados');
        const contador = document.getElementById('contadorResultados');
        const resultadosDiv = document.getElementById('resultadosBusqueda');
        
        if (!container || !contador || !resultadosDiv) {
            console.error('Elementos de resultados no encontrados');
            return;
        }
        
        // Mostrar contenedor de resultados (SOLO cuando se hace una búsqueda)
        resultadosDiv.style.display = 'block';
        
        // Contar el total de marcadores (no contratistas)
        let totalMarcadores = 0;
        contratistas.forEach(contratista => {
            totalMarcadores += contratista.marcadores ? contratista.marcadores.length : 1;
        });
        
        contador.textContent = totalMarcadores;
        
        // Limpiar lista anterior
        container.innerHTML = '';
        
        if (contratistas.length === 0) {
            container.innerHTML = `
                <div class="alert alert-light border mt-2 py-2">
                    <div class="text-center text-muted">
                        <i class="fas fa-search fa-lg mb-2"></i>
                        <p class="mb-0">No se encontraron contratistas</p>
                        <small class="mt-1">Intente con otros criterios de búsqueda</small>
                    </div>
                </div>
            `;
            return;
        }
        
        // Crear elementos de lista
        contratistas.forEach((contratista, index) => {
            const item = document.createElement('div');
            item.className = 'result-item';
            
            // Mostrar información de sitios de trabajo si existen
            let sitiosInfo = '';
            if (contratista.tiene_sitios_trabajo && contratista.sitios_trabajo) {
                sitiosInfo = contratista.sitios_trabajo.map(sitio => 
                    `<span class="badge ${sitio.tipo === 'principal' ? 'bg-primary' : 'bg-info'} me-1 mb-1">
                        <i class="fas fa-${sitio.tipo === 'principal' ? 'star' : 'map-marker-alt'} me-1"></i>
                        ${sitio.municipio}
                    </span>`
                ).join('');
            }
            
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-primary">${contratista.nombre}</div>
                        <div class="small text-muted mt-1">
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                ${sitiosInfo}
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-id-card me-1"></i>${contratista.cedula}
                                </span>
                                ${contratista.area ? `
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-building me-1"></i>${contratista.area}
                                </span>` : ''}
                               ${contratista.tipo_vinculacion ? `
                                <span class="badge tipo-vinculacion-badge">
                                    <i class="fas fa-handshake me-1"></i>${contratista.tipo_vinculacion}
                                </span>` : ''}
                            </div>
                        </div>
                    </div>
                    <button onclick="event.stopPropagation(); irAContratista(${index})" 
                            class="btn btn-sm btn-outline-primary ms-2"
                            title="Ver en mapa">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            `;
            
            // Al hacer clic en el item
            item.addEventListener('click', () => {
                irAContratista(index);
            });
            
            container.appendChild(item);
        });
    }
    
    // Ocultar resultados de búsqueda
    function ocultarResultadosBusqueda() {
        const resultadosDiv = document.getElementById('resultadosBusqueda');
        if (resultadosDiv) {
            resultadosDiv.style.display = 'none';
        }
    }
    
    // Ir a un contratista específico
    window.irAContratista = function(index) {
        if (contratistasProcesados[index] && contratistasProcesados[index].marcadores) {
            const marcadores = contratistasProcesados[index].marcadores;
            
            if (marcadores.length > 0) {
                // Si tiene múltiples sitios, centrar en el primero
                const primerMarcador = marcadores[0].marcador;
                
                // Centrar mapa en el marcador
                mapa.setView(primerMarcador.getLatLng(), 14);
                
                // Abrir popup
                primerMarcador.openPopup();
                
                // Resaltar sutilmente el marcador
                resaltarMarcador(primerMarcador);
            }
        }
        
        // En móvil, cerrar el buscador
        if (isMobile()) {
            cerrarBuscadorMovil();
        }
    };
    
    // Resaltar marcador sutilmente
    function resaltarMarcador(marcador) {
        const originalIcon = marcador.options.icon;
        
        // Cambiar a ícono resaltado sutilmente
        const iconoResaltado = L.divIcon({
            className: 'marcador-contratista-resaltado',
            html: '<div style="background-color: #ffc107; color: #000; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 3px 6px rgba(0,0,0,0.3);"><i class="fas fa-star"></i></div>',
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });
        
        marcador.setIcon(iconoResaltado);
        
        // Restaurar después de 2 segundos
        setTimeout(() => {
            if (marcador && marcador.setIcon) {
                marcador.setIcon(originalIcon);
            }
        }, 2000);
    }
    
    // ================= FUNCIÓN PARA AGREGAR MARCADOR DE SITIO DE TRABAJO =================
    
    // Función para agregar marcador de sitio de trabajo
    function agregarMarcadorSitioTrabajo(contratista, sitio, coordenadas) {
        // Definir colores según el tipo de sitio
        const colores = {
            'principal': '#007bff', // Azul
            'secundario': '#28a745', // Verde
            'terciario': '#fd7e14'   // Naranja
        };
        
        // Crear ícono personalizado según tipo
        const iconoSitioTrabajo = L.divIcon({
            className: 'marcador-sitio-trabajo',
            html: `<div style="background-color: ${colores[sitio.tipo] || '#6c757d'}; 
                           color: white; 
                           border-radius: 50%; 
                           width: 32px; 
                           height: 32px; 
                           display: flex; 
                           align-items: center; 
                           justify-content: center;
                           border: 2px solid white;
                           box-shadow: 0 2px 4px rgba(0,0,0,0.2); font-size: 14px;">
                  <i class="fas fa-${sitio.tipo === 'principal' ? 'building' : 'map-marker-alt'}"></i>
               </div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });
        
        // Crear el marcador
        const marcador = L.marker([coordenadas.lat, coordenadas.lng], {
            icon: iconoSitioTrabajo,
            title: `${contratista.nombre} - ${sitio.municipio} (${sitio.tipo})`
        }).addTo(marcadoresContratistas);
        
        // Agregar popup con información del sitio
        marcador.bindPopup(`
            <div class="popup-contratista" style="width: 300px;">
                <div class="popup-header p-3" style="background-color: ${colores[sitio.tipo] || '#6c757d'}; color: white;">
                    <h6 class="mb-0">${contratista.nombre}</h6>
                    <small class="opacity-75">
                        <i class="fas fa-${sitio.tipo === 'principal' ? 'star' : 'map-marker-alt'} me-1"></i>
                        Sitio de trabajo ${sitio.tipo}
                    </small>
                </div>
                <div class="popup-body p-3">
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="info-item">
                                <span class="info-label">Municipio:</span>
                                <span class="info-value">${sitio.municipio}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-item">
                                <span class="info-label">Dirección de trabajo:</span>
                                <span class="info-value long-text">${sitio.direccion}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Área:</span>
                                <span class="info-value">${contratista.area}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Contrato:</span>
                                <span class="info-value">${contratista.contrato}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value">${contratista.telefono}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Tipo Vinculación:</span>
                                <span class="info-value">${contratista.tipo_vinculacion || 'No especificado'}</span>
                            </div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="text-center small text-muted">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        ${coordenadas.lat.toFixed(6)}, ${coordenadas.lng.toFixed(6)}
                    </div>
                </div>
            </div>
        `);
        
        return marcador;
    }
    
    // ================= FUNCIÓN ORIGINAL (para compatibilidad) =================
    
    // Función para agregar un marcador al mapa (versión original - para compatibilidad)
    function agregarMarcadorContratista(contratista, coordenadas) {
        // Crear ícono personalizado profesional
        var iconoContratista = L.divIcon({
            className: 'marcador-contratista',
            html: '<i class="fas fa-user"></i>',
            iconSize: [28, 28],
            iconAnchor: [14, 28],
            popupAnchor: [0, -28]
        });
        
        // Crear el marcador
        var marcador = L.marker([coordenadas.lat, coordenadas.lng], {
            icon: iconoContratista,
            title: contratista.nombre
        }).addTo(marcadoresContratistas);
        
        // Determinar qué dirección mostrar
        const direccionMostrar = contratista.direccion_principal || contratista.direccion || 'No especificada';
        
        // Agregar popup con información profesional
        marcador.bindPopup(`
            <div class="popup-contratista" style="width: 300px;">
                <div class="popup-header bg-primary text-white p-3">
                    <h6 class="mb-0">${contratista.nombre}</h6>
                    <small class="opacity-75">Contratista</small>
                </div>
                <div class="popup-body p-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Cédula:</span>
                                <span class="info-value">${contratista.cedula}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value">${contratista.telefono}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-item">
                                <span class="info-label">Contrato:</span>
                                <span class="info-value">${contratista.contrato}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Área:</span>
                                <span class="info-value">${contratista.area}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Tipo Vinculación:</span>
                                <span class="info-value">${contratista.tipo_vinculacion || 'No especificado'}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <span class="info-label">Municipio principal:</span>
                                <span class="info-value">${contratista.municipio_principal}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="info-item">
                                <span class="info-label">Dirección de trabajo:</span>
                                <span class="info-value long-text">${direccionMostrar}</span>
                            </div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="text-center small text-muted">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        ${coordenadas.lat.toFixed(6)}, ${coordenadas.lng.toFixed(6)}
                    </div>
                </div>
            </div>
        `);
        
        return marcador;
    }
    
    // ================= FUNCIONES DE GEOCODIFICACIÓN =================
    
    // FUNCIÓN MEJORADA: Buscar dirección con múltiples intentos
    async function buscarDireccionMejorada(direccion, municipio) {
        // Lista de consultas a intentar
        const consultas = generarConsultas(direccion, municipio);
        
        for (let i = 0; i < consultas.length; i++) {
            // Verificar si el procesamiento sigue activo
            if (!procesamientoActivo) {
                return null;
            }
            
            const consulta = consultas[i];
            console.log(`   🔍 Intento ${i + 1}: "${consulta.substring(0, 50)}${consulta.length > 50 ? '...' : ''}"`);
            
            const resultado = await buscarEnNominatim(consulta);
            if (resultado) {
                console.log(`   ✅ Encontrado`);
                return resultado;
            }
            
            // Pequeña pausa entre intentos
            if (i < consultas.length - 1) {
                await esperar(100);
            }
        }
        
        console.log(`   ❌ No encontrado después de ${consultas.length} intentos`);
        return null;
    }
    
    // Generar múltiples variantes de búsqueda
    function generarConsultas(direccion, municipio) {
        const consultas = [];
        
        // 1. Dirección completa
        consultas.push(`${direccion}, ${municipio}, Meta, Colombia`);
        
        // 2. Dirección simplificada
        const direccionSimple = simplificarDireccion(direccion);
        if (direccionSimple !== direccion) {
            consultas.push(`${direccionSimple}, ${municipio}, Colombia`);
        }
        
        // 3. Solo elementos principales
        const elementos = extraerElementosDireccion(direccion);
        if (elementos.calle && elementos.numero) {
            consultas.push(`${elementos.calle} ${elementos.numero}, ${municipio}, Meta`);
        }
        
        // 4. Solo calle principal
        const callePrincipal = extraerCallePrincipal(direccion);
        if (callePrincipal) {
            consultas.push(`${callePrincipal}, ${municipio}, Colombia`);
        }
        
        // 5. Solo municipio (último recurso)
        consultas.push(`${municipio}, Meta, Colombia`);
        
        return consultas;
    }
    
    // Simplificar dirección para mejor búsqueda
    function simplificarDireccion(direccion) {
        if (!direccion) return '';
        
        // Quitar números específicos de casa/manzana/lote
        const patrones = [
            /^(.*?)(?:\s*[#\-]\s*\d+.*)$/i,
            /^(.*?\b(?:manzana|mz|lote|lt|torre|apartamento|apt)\s+[a-z0-9]+).*$/i,
            /^(.*?)(?:\s+(?:esquina|int|interior|local|oficina|ofc|piso)\s+.*)$/i
        ];
        
        for (const patron of patrones) {
            const match = direccion.match(patron);
            if (match && match[1]) {
                return match[1].trim();
            }
        }
        
        return direccion;
    }
    
    // Extraer calle principal
    function extraerCallePrincipal(direccion) {
        if (!direccion) return null;
        
        const patrones = [
            /(calle|carrera|avenida|diagonal|transversal|cll|cr|av)\s+(\d+[a-z]?(?:\s*[a-z])?)/i,
            /(cra|av|diag|trans)\s+(\d+[a-z]?)/i
        ];
        
        for (const patron of patrones) {
            const match = direccion.match(patron);
            if (match) {
                const tipo = match[1].toLowerCase();
                const numero = match[2];
                
                const tiposCompletos = {
                    'cll': 'calle', 'cr': 'carrera', 'cra': 'carrera',
                    'av': 'avenida', 'diag': 'diagonal', 'trans': 'transversal'
                };
                
                const tipoCompleto = tiposCompletos[tipo] || tipo;
                return `${tipoCompleto} ${numero}`;
            }
        }
        
        return null;
    }
    
    // Extraer elementos de dirección
    function extraerElementosDireccion(direccion) {
        const elementos = { calle: null, numero: null };
        
        if (!direccion) return elementos;
        
        // Patrones comunes
        const patrones = [
            /(calle|carrera|avenida|diagonal|transversal)\s+(\d+[a-z]?)\s*(?:#|no\.?)?\s*(\d+\s*[-–]\s*\d+)/i,
            /(calle|carrera|avenida)\s+(\d+[a-z]?)\s+(?:con|y)\s+(calle|carrera|avenida)\s+(\d+)/i
        ];
        
        for (const patron of patrones) {
            const match = direccion.match(patron);
            if (match) {
                elementos.calle = match[1] + ' ' + match[2];
                elementos.numero = match[3] || match[4] || null;
                break;
            }
        }
        
        return elementos;
    }
    
    // Función para buscar en Nominatim
    async function buscarEnNominatim(consulta) {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(consulta)}&limit=1&countrycodes=co`;
        
        try {
            const response = await fetch(url, {
                headers: { 'User-Agent': 'SistemaContratistasMeta/1.0' }
            });
            
            if (!response.ok) return null;
            
            const data = await response.json();
            
            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon)
                };
            }
            
            return null;
            
        } catch (error) {
            console.warn('Error en búsqueda OSM:', error);
            return null;
        }
    }
    
    // Función MEJORADA para obtener coordenadas de municipio
    async function obtenerCoordenadasMunicipio(municipioNombre) {
        // Coordenadas actualizadas de municipios del Meta
        const coordenadasMunicipios = {
            'Villavicencio': [4.1420, -73.6266],
            'Acacías': [3.9878, -73.7577],
            'Granada': [3.5431, -73.7075],
            'San Martín': [3.6959, -73.6942],
            'Puerto López': [4.0895, -72.9557],
            'Puerto Gaitán': [4.3133, -72.0825],
            'Restrepo': [4.2611, -73.5614],
            'Cumaral': [4.2695, -73.4862],
            'Castilla La Nueva': [3.8272, -73.6883],
            'San Carlos de Guaroa': [3.7111, -73.2422],
            'San Juan de Arama': [3.3464, -73.8897],
            'San Juanito': [4.4583, -73.6750],
            'San Luis de Cubarral': [3.7653, -73.6975],
            'Uribe': [3.2544, -74.3544],
            'Lejanías': [3.5278, -74.0239],
            'El Calvario': [4.3542, -73.7125],
            'El Castillo': [3.5653, -73.7944],
            'Fuente de Oro': [3.4625, -73.6208],
            'Guamal': [3.8803, -73.7656],
            'Mapiripán': [2.8911, -72.1328],
            'Mesetas': [3.3842, -74.0442],
            'La Macarena': [2.1797, -73.7847],
            'Vista Hermosa': [3.1242, -73.7514]
        };
        
        if (municipioNombre && coordenadasMunicipios[municipioNombre]) {
            return {
                lat: coordenadasMunicipios[municipioNombre][0],
                lng: coordenadasMunicipios[municipioNombre][1]
            };
        }
        
        // Si no tenemos el municipio, intentar buscarlo en OSM
        const resultado = await buscarEnNominatim(`${municipioNombre}, Meta, Colombia`);
        if (resultado) {
            return resultado;
        }
        
        // Último recurso: Villavicencio
        return null;
    }
    
    // ================= FUNCIONES DE UTILIDAD =================
    
    // Función para mostrar mensajes
    function mostrarMensaje(mensaje) {
        L.popup()
            .setLatLng(villavicencio)
            .setContent(`
                <div class="popup-mensaje">
                    <div class="text-center">
                        <i class="fas fa-info-circle fa-2x text-primary mb-2"></i>
                        <p class="mb-0">${mensaje}</p>
                    </div>
                </div>
            `)
            .openOn(mapa);
    }
    
    // Función de utilidad para esperar
    function esperar(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // Función para centrar en Villavicencio
    window.centrarVillavicencio = function() {
        mapa.setView(villavicencio, 13);
    };
    
    // Función para recargar
    window.recargarContratistas = function() {
        // Cancelar búsqueda actual si existe
        if (ultimaBusquedaAbortController) {
            ultimaBusquedaAbortController.abort();
        }
        
        // Detener procesamiento actual
        procesamientoActivo = false;
        
        marcadoresContratistas.clearLayers();
        cargarContratistas();
        mostrarMensaje('Recargando contratistas...');
    };
    
    // Evento Enter en el input de búsqueda
    document.addEventListener('keypress', function(e) {
        if (e.target.id === 'inputNombre' && e.key === 'Enter') {
            buscarContratistas();
        }
    });
    
    // ================= EVENTOS DE VENTANA =================
    
    // Redimensionar ventana
    window.addEventListener('resize', function() {
        configurarVisibilidadBuscador();
    });
    
    // Detectar cambios en el viewport cuando el teclado aparece/desaparece
    let viewportHeight = window.innerHeight;
    
    function handleViewportChange() {
        const newHeight = window.innerHeight;
        
        if (Math.abs(newHeight - viewportHeight) > 200) {
            if (newHeight < viewportHeight) {
                keyboardVisible = true;
            } else {
                keyboardVisible = false;
            }
            viewportHeight = newHeight;
        }
    }
    
    window.addEventListener('resize', handleViewportChange);
    window.addEventListener('orientationchange', handleViewportChange);
    
    // ================= EVENTOS PARA MANEJAR EL MODAL =================
    
    // Cerrar modal solo si se hace clic fuera del card Y no es un elemento interactivo
    document.addEventListener('touchstart', function(e) {
        if (isMobile() && isModalOpen && searchContainer) {
            const searchCard = document.getElementById('searchCard');
            const isInteractiveElement = e.target.closest('[data-prevent-close="true"]');
            const isCloseButton = e.target.closest('#closeSearchBtn');
            
            if (searchCard && !searchCard.contains(e.target) && !isInteractiveElement && !isCloseButton) {
                if (!keyboardVisible) {
                    cerrarBuscadorMovil();
                }
            }
        }
    });
    
    document.addEventListener('click', function(e) {
        if (isMobile() && isModalOpen && searchContainer) {
            const searchCard = document.getElementById('searchCard');
            const isInteractiveElement = e.target.closest('[data-prevent-close="true"]');
            const isCloseButton = e.target.closest('#closeSearchBtn');
            
            if (searchCard && !searchCard.contains(e.target) && !isInteractiveElement && !isCloseButton) {
                if (!keyboardVisible) {
                    cerrarBuscadorMovil();
                }
            }
        }
    });
    
    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMobile() && isModalOpen) {
            cerrarBuscadorMovil();
        }
    });
    
    // ================= FUNCIONALIDADES ADICIONALES =================
    
    // Función para mostrar información detallada en consola
    window.mostrarInfoContratistas = function() {
        console.log('=== INFORMACIÓN DE CONTRATISTAS PROCESADOS ===');
        contratistasProcesados.forEach((contratista, index) => {
            console.log(`${index + 1}. ${contratista.nombre}`);
            console.log(`   - Cédula: ${contratista.cedula}`);
            console.log(`   - Tiene sitios de trabajo: ${contratista.tiene_sitios_trabajo}`);
            console.log(`   - Número de marcadores: ${contratista.marcadores ? contratista.marcadores.length : 0}`);
            if (contratista.sitios_trabajo) {
                contratista.sitios_trabajo.forEach(sitio => {
                    console.log(`   - Sitio ${sitio.tipo}: ${sitio.municipio} - ${sitio.direccion}`);
                });
            }
        });
    };
    
    // Inicializar controles del botón volver
    const volverBtn = document.getElementById('volverBtn');
    if (volverBtn) {
        volverBtn.addEventListener('click', () => {
            window.location.href = 'menuContratistas.php';
        });
    }
});

// CSS adicional mejorado para el modal en móvil
const mobileStyles = `
/* ================= ESTILOS MÓVIL CORREGIDOS ================= */
@media (max-width: 768px) {
    .btn-open-search {
        display: flex !important;
        position: fixed;
        bottom: 140px;
        right: 20px;
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 14px 20px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        align-items: center;
        gap: 10px;
        z-index: 1001;
        white-space: nowrap;
        transition: all 0.3s ease;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        -webkit-tap-highlight-color: transparent;
        pointer-events: auto;
    }
    
    .btn-open-search:active {
        transform: scale(0.95);
    }
    
    .btn-close-search {
        display: flex !important;
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        z-index: 10;
        transition: background 0.2s;
        -webkit-tap-highlight-color: transparent;
        pointer-events: auto;
    }
    
    .btn-close-search:hover,
    .btn-close-search:active {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .search-container {
        display: none;
        pointer-events: none;
    }
    
    .search-container.modal-open {
        display: flex !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background: rgba(0, 0, 0, 0.7) !important;
        backdrop-filter: blur(3px) !important;
        justify-content: center !important;
        align-items: center !important;
        padding: 15px !important;
        box-sizing: border-box !important;
        z-index: 2000 !important;
        pointer-events: auto !important;
    }
    
    .search-container.modal-open ~ #mapa {
        pointer-events: none !important;
    }
    
    body.modal-open {
        overflow: hidden !important;
        position: fixed !important;
        width: 100% !important;
        height: 100% !important;
    }
    
    .search-container.modal-open .card {
        animation: modalFadeIn 0.3s ease-out;
        transform-origin: center center;
        pointer-events: auto !important;
        max-height: 80vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        margin: 0 !important;
        width: 95% !important;
        max-width: 400px !important;
    }
    
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    .search-input, .search-select {
        font-size: 16px !important;
        padding: 12px 15px !important;
        min-height: 48px !important;
        pointer-events: auto !important;
        -webkit-user-select: text !important;
        user-select: text !important;
        touch-action: manipulation !important;
    }
    
    @supports (-webkit-overflow-scrolling: touch) {
        .search-input, .search-select {
            font-size: 16px !important;
        }
    }
    
    .search-button {
        min-height: 48px !important;
        padding: 12px !important;
        font-size: 16px !important;
        pointer-events: auto !important;
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
    }
    
    .resultados-list {
        max-height: 40vh !important;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
}

@media (min-width: 769px) {
    .btn-open-search {
        display: none !important;
    }
    
    .btn-close-search {
        display: none !important;
    }
    
    .search-container.modal-open {
        display: block !important;
        position: relative !important;
        background: transparent !important;
        backdrop-filter: none !important;
        padding: 0 !important;
    }
    
    .search-container.modal-open ~ #mapa {
        pointer-events: auto !important;
    }
    
    body.modal-open {
        overflow: auto !important;
        position: static !important;
    }
}

.search-input:focus, .search-select:focus {
    outline: 2px solid #3498db !important;
    outline-offset: 2px !important;
}

.search-container.modal-open .card::-webkit-scrollbar {
    width: 6px;
}

.search-container.modal-open .card::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.search-container.modal-open .card::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.search-container.modal-open .card::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.search-input, .search-select, .search-button {
    min-height: 44px !important;
}

.search-input:focus, .search-select:focus {
    -webkit-user-select: text !important;
    user-select: text !important;
}
`;

// Inyectar estilos
const style = document.createElement('style');
style.textContent = mobileStyles;
document.head.appendChild(style);