// JavaScript para mapa centrado en el Meta con buscador/filtro profesional
// VERSIÓN RESPONSIVE CORREGIDA - Problema del teclado móvil solucionado
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
    var originalMobileBtnBottom = 120;
    
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
    
    // ================= BUSCADOR RESPONSIVE CORREGIDO =================
    
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
                        <!-- Botón cerrar (solo móvil) -->
                        <button type="button" id="closeSearchBtn" class="btn-close-search" style="display: none;" aria-label="Cerrar buscador">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="card-body p-3" id="searchBody" style="pointer-events: auto;">
                        <!-- Búsqueda por nombre -->
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
                        
                        <!-- Filtro por municipio -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-map-marker-alt me-1"></i>Municipio
                            </label>
                            <select id="selectMunicipio" class="form-select search-select" data-prevent-close="true">
                                <option value="">Todos los municipios</option>
                            </select>
                        </div>
                        
                        <!-- Área -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-building me-1"></i>Área
                            </label>
                            <select id="selectArea" class="form-select search-select" data-prevent-close="true">
                                <option value="">Todas las áreas</option>
                            </select>
                        </div>
                        
                        <!-- Tipo de Vinculación -->
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="fas fa-handshake me-1"></i>Tipo de Vinculación
                            </label>
                            <select id="selectTipoVinculacion" class="form-select search-select" data-prevent-close="true">
                                <option value="">Todos los tipos</option>
                            </select>
                        </div>
                        
                        <!-- Botones de acción -->
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
                        
                        <!-- Indicador de búsqueda -->
                        <div id="indicadorBusqueda" class="mt-2" style="display: none;">
                            <div class="d-flex align-items-center text-primary">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Buscando...</span>
                                </div>
                                <small class="fw-medium">Buscando contratistas...</small>
                            </div>
                        </div>
                        
                        <!-- Resultados de búsqueda -->
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
            
            // SOLUCIÓN MEJORADA: Permitir eventos en el buscador
            L.DomEvent.disableClickPropagation(searchContainer);
            L.DomEvent.disableScrollPropagation(searchContainer);
            
            return searchContainer;
        };
        
        searchControl.addTo(mapa);
        
        // Crear botón flotante para móvil
        crearBotonMovil();
        
        // Configurar eventos para inputs (después de que se creen)
        setTimeout(configurarEventosBuscador, 100);
    }
    
    function configurarEventosBuscador() {
        // Configurar eventos para prevenir cierre del modal
        const inputs = document.querySelectorAll('[data-prevent-close="true"]');
        const closeBtn = document.getElementById('closeSearchBtn');
        
        // Detectar cuando el teclado aparece/desaparece
        const inputNombre = document.getElementById('inputNombre');
        if (inputNombre) {
            inputNombre.addEventListener('focus', function() {
                keyboardVisible = true;
                console.log('📱 Teclado visible');
                // Mover el botón móvil más arriba cuando el teclado está visible
                if (mobileSearchBtn) {
                    mobileSearchBtn.style.bottom = '250px';
                }
            });
            
            inputNombre.addEventListener('blur', function() {
                setTimeout(() => {
                    keyboardVisible = false;
                    console.log('📱 Teclado oculto');
                    // Restaurar posición original
                    if (mobileSearchBtn) {
                        mobileSearchBtn.style.bottom = originalMobileBtnBottom + 'px';
                    }
                }, 300);
            });
        }
        
        // Prevenir cierre del modal cuando se interactúa con inputs/botones
        inputs.forEach(element => {
            element.addEventListener('touchstart', function(e) {
                e.stopPropagation();
            });
            
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            element.addEventListener('focus', function(e) {
                // Cuando un input recibe foco, no cerrar el modal
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
        // Si ya existe, no crear otro
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
        
        // Posición mejorada - más arriba para mejor acceso
        const bottomPosition = 140; // Más arriba que el botón volver
        
        // Estilos inline mejorados
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
        
        // Guardar posición original
        originalMobileBtnBottom = bottomPosition;
        
        // Eventos del botón móvil - SOLUCIÓN MEJORADA
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
            // En móvil: ocultar buscador Leaflet, mostrar botón flotante
            if (searchContainer) {
                searchContainer.style.display = 'none';
            }
            if (mobileSearchBtn) {
                mobileSearchBtn.style.display = 'flex';
            }
            
            // Mostrar botón cerrar
            const closeBtn = document.getElementById('closeSearchBtn');
            if (closeBtn) {
                closeBtn.style.display = 'flex';
            }
            
        } else {
            // En PC: mostrar buscador normal, ocultar botón flotante
            if (searchContainer) {
                searchContainer.style.display = 'block';
                searchContainer.className = 'search-container';
                searchContainer.style.position = '';
                searchContainer.style.background = '';
            }
            
            if (mobileSearchBtn) {
                mobileSearchBtn.style.display = 'none';
            }
            
            // Ocultar botón cerrar
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
            
            // Ocultar botón móvil
            if (mobileSearchBtn) {
                mobileSearchBtn.style.display = 'none';
            }
            
            // Configurar como modal overlay
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
            
            // Deshabilitar interacción con el mapa cuando el modal está abierto
            if (mapa) {
                mapa.getContainer().style.pointerEvents = 'none';
            }
            
            // Asegurar que el cuerpo no se desplace
            document.body.classList.add('modal-open');
            
            console.log('✅ Buscador móvil abierto');
            
            // Enfocar el primer input después de un pequeño delay
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
            
            // Ocultar modal
            searchContainer.style.display = 'none';
            searchContainer.className = 'search-container';
            
            // Restaurar interacción con el mapa
            if (mapa) {
                mapa.getContainer().style.pointerEvents = 'auto';
            }
            
            // Remover clase del cuerpo
            document.body.classList.remove('modal-open');
            
            // Mostrar botón móvil nuevamente
            if (mobileSearchBtn && isMobile()) {
                mobileSearchBtn.style.display = 'flex';
                // Restaurar posición original
                mobileSearchBtn.style.bottom = originalMobileBtnBottom + 'px';
            }
            
            console.log('✅ Buscador móvil cerrado');
        }
    }
    
    // Añadir controles básicos
    L.control.scale().addTo(mapa);
    L.control.zoom({ position: 'bottomright' }).addTo(mapa);
    
    // Inicializar sistema
    inicializarSistema();
    
    // ================= EVENTOS PARA MANEJAR EL TECLADO MÓVIL =================
    
    // Detectar cambios en el viewport cuando el teclado aparece/desaparece
    let viewportHeight = window.innerHeight;
    
    function handleViewportChange() {
        const newHeight = window.innerHeight;
        
        // Si la altura cambió significativamente, probablemente el teclado apareció/desapareció
        if (Math.abs(newHeight - viewportHeight) > 200) {
            if (newHeight < viewportHeight) {
                console.log('📱 Teclado probablemente visible');
                keyboardVisible = true;
            } else {
                console.log('📱 Teclado probablemente oculto');
                keyboardVisible = false;
            }
            viewportHeight = newHeight;
        }
    }
    
    window.addEventListener('resize', handleViewportChange);
    window.addEventListener('orientationchange', handleViewportChange);
    
    // ================= EVENTO DE CLICK FUERA DEL MODAL (SOLUCIÓN MEJORADA) =================
    
    // Cerrar modal solo si se hace clic fuera del card Y no es un elemento interactivo
    document.addEventListener('touchstart', function(e) {
        if (isMobile() && isModalOpen && searchContainer) {
            const searchCard = document.getElementById('searchCard');
            const isInteractiveElement = e.target.closest('[data-prevent-close="true"]');
            const isCloseButton = e.target.closest('#closeSearchBtn');
            
            // Solo cerrar si se hace clic fuera del card Y no es un elemento interactivo
            if (searchCard && !searchCard.contains(e.target) && !isInteractiveElement && !isCloseButton) {
                // Verificar si el teclado está visible
                if (!keyboardVisible) {
                    cerrarBuscadorMovil();
                } else {
                    console.log('⚠️ No cerrar modal mientras el teclado está visible');
                }
            }
        }
    });
    
    // También para click normal (mouse)
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
    
    // Prevenir scroll cuando el modal está abierto
    document.addEventListener('touchmove', function(e) {
        if (isMobile() && isModalOpen) {
            // Permitir scroll solo dentro del card del buscador
            const searchCard = document.getElementById('searchCard');
            if (!searchCard || !searchCard.contains(e.target)) {
                e.preventDefault();
            }
        }
    }, { passive: false });
    
    // ================= EL RESTO DEL CÓDIGO (FUNCIONES DE CARGA, FILTRADO, ETC.) =================
    
    // [Aquí va todo el resto del código que ya tenías...]
    // Función para cargar municipios
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
    
    // Función para cargar áreas
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
    
    // Llenar select de municipios
    function llenarSelectMunicipios() {
        const select = document.getElementById('selectMunicipio');
        if (!select) return;
        
        // Ordenar municipios alfabéticamente
        municipiosCargados.sort((a, b) => a.nombre.localeCompare(b.nombre));
        
        // Agregar opciones
        municipiosCargados.forEach(municipio => {
            const option = document.createElement('option');
            option.value = municipio.nombre;
            option.textContent = municipio.nombre;
            select.appendChild(option);
        });
    }
    
    // Llenar select de áreas
    function llenarSelectAreas() {
        const select = document.getElementById('selectArea');
        if (!select) return;
        
        // Ordenar áreas alfabéticamente
        areasCargadas.sort((a, b) => a.nombre.localeCompare(b.nombre));
        
        // Agregar opciones
        areasCargadas.forEach(area => {
            const option = document.createElement('option');
            option.value = area.nombre;
            option.textContent = area.nombre;
            select.appendChild(option);
        });
    }
    
    // Función para cargar contratistas
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
    
    // ... [Todas las demás funciones se mantienen igual] ...
    
    // Función de utilidad para esperar
    function esperar(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
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
    
    // ================= EVENTOS ADICIONALES =================
    
    // Redimensionar ventana
    window.addEventListener('resize', function() {
        configurarVisibilidadBuscador();
    });
    
    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMobile() && isModalOpen) {
            cerrarBuscadorMovil();
        }
    });
    
    // Evento Enter en el input de búsqueda
    document.addEventListener('keypress', function(e) {
        if (e.target.id === 'inputNombre' && e.key === 'Enter') {
            buscarContratistas();
        }
    });
});

// CSS adicional mejorado para el modal en móvil
const mobileStyles = `
/* ================= ESTILOS MÓVIL CORREGIDOS ================= */
@media (max-width: 768px) {
    /* Botón abrir buscador - POSICIÓN MEJORADA */
    .btn-open-search {
        display: flex !important;
        position: fixed;
        bottom: 140px; /* Más arriba para mejor acceso */
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
    
    /* Botón cerrar buscador */
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
    
    /* Contenedor del buscador cuando es modal */
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
    
    /* El mapa debe estar detrás cuando el modal está abierto */
    .search-container.modal-open ~ #mapa {
        pointer-events: none !important;
    }
    
    /* Prevenir scroll del body cuando el modal está abierto */
    body.modal-open {
        overflow: hidden !important;
        position: fixed !important;
        width: 100% !important;
        height: 100% !important;
    }
    
    /* Card del buscador en móvil */
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
    
    /* Inputs más grandes para táctil - EVITAR ZOOM AUTOMÁTICO */
    .search-input, .search-select {
        font-size: 16px !important;
        padding: 12px 15px !important;
        min-height: 48px !important;
        pointer-events: auto !important;
        -webkit-user-select: text !important;
        user-select: text !important;
        touch-action: manipulation !important;
    }
    
    /* Evitar zoom automático en iOS al hacer focus */
    @supports (-webkit-overflow-scrolling: touch) {
        .search-input, .search-select {
            font-size: 16px !important;
        }
    }
    
    /* Botones más grandes */
    .search-button {
        min-height: 48px !important;
        padding: 12px !important;
        font-size: 16px !important;
        pointer-events: auto !important;
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
    }
    
    /* Resultados de búsqueda en móvil */
    .resultados-list {
        max-height: 40vh !important;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Ajustar altura del modal cuando el teclado está visible */
    .keyboard-visible .search-container.modal-open .card {
        max-height: 60vh !important;
        margin-bottom: 20vh !important;
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

/* Mejorar experiencia táctil */
.search-input:focus, .search-select:focus {
    outline: 2px solid #3498db !important;
    outline-offset: 2px !important;
}

/* Scrollbar en móvil */
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

/* Asegurar que los inputs sean claramente táctiles */
.search-input, .search-select, .search-button {
    min-height: 44px !important; /* Tamaño mínimo para toques en iOS */
}

/* Prevenir que los inputs pierdan foco muy rápido en iOS */
.search-input:focus, .search-select:focus {
    -webkit-user-select: text !important;
    user-select: text !important;
}
`;

// Inyectar estilos
const style = document.createElement('style');
style.textContent = mobileStyles;
document.head.appendChild(style);