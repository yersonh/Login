// JavaScript para mapa centrado en el Meta con buscador/filtro
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
        zoom: zoomInicial
    });
    
    // Añadir capa de OpenStreetMap
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);
    
    console.log('✅ Mapa del Meta cargado');
    
    // Variables globales para el buscador
    var marcadoresContratistas = L.layerGroup().addTo(mapa);
    var municipiosCargados = [];
    var todosContratistas = []; // Almacenar todos los contratistas para la lista
    var contratistasProcesados = []; // Contratistas con marcadores
    
    // Inicializar buscador
    inicializarBuscador();
    
    // 1. Primero cargar municipios
    cargarMunicipios().then(() => {
        // 2. Luego cargar contratistas
        cargarContratistas();
    });
    
    // 2. Mostrar coordenadas al hacer clic
    mapa.on('click', function(e) {
        L.popup()
            .setLatLng(e.latlng)
            .setContent(
                `<b>📍 Ubicación seleccionada</b><br>
                 Latitud: ${e.latlng.lat.toFixed(6)}<br>
                 Longitud: ${e.latlng.lng.toFixed(6)}<br>
                 <small>Departamento del Meta, Colombia</small>`
            )
            .openOn(mapa);
    });
    
    // 3. Añadir controles básicos
    L.control.scale().addTo(mapa);
    L.control.zoom({ position: 'bottomright' }).addTo(mapa);
    
    // ================= BUSCADOR Y FILTROS =================
    
    function inicializarBuscador() {
        // Crear contenedor para el buscador
        const searchContainer = L.control({ position: 'topright' });
        
        searchContainer.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'search-container');
            div.innerHTML = `
                <div class="card shadow-sm" style="width: 350px; max-width: 90vw;">
                    <div class="card-body p-3">
                        <h5 class="card-title mb-3">🔍 Buscar Contratistas</h5>
                        
                        <!-- Búsqueda por nombre -->
                        <div class="mb-3">
                            <label class="form-label small">Nombre del contratista</label>
                            <input type="text" 
                                   id="inputNombre" 
                                   class="form-control form-control-sm" 
                                   placeholder="Ej: Juan Pérez">
                        </div>
                        
                        <!-- Filtro por municipio -->
                        <div class="mb-3">
                            <label class="form-label small">Filtrar por municipio</label>
                            <select id="selectMunicipio" class="form-select form-select-sm">
                                <option value="">Todos los municipios</option>
                            </select>
                        </div>
                        
                        <!-- Área -->
                        <div class="mb-3">
                            <label class="form-label small">Área</label>
                            <select id="selectArea" class="form-select form-select-sm">
                                <option value="">Todas las áreas</option>
                            </select>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="d-flex gap-2">
                            <button onclick="aplicarFiltros()" 
                                    class="btn btn-primary btn-sm flex-grow-1">
                                🔍 Aplicar filtros
                            </button>
                            <button onclick="limpiarFiltros()" 
                                    class="btn btn-outline-secondary btn-sm">
                                🗑️ Limpiar
                            </button>
                        </div>
                        
                        <!-- Resultados de búsqueda -->
                        <div id="resultadosBusqueda" class="mt-3" style="display: none;">
                            <h6 class="border-bottom pb-2">Resultados</h6>
                            <div id="listaResultados" style="max-height: 300px; overflow-y: auto;"></div>
                            <div class="mt-2 text-end small">
                                <span id="contadorResultados">0</span> contratistas encontrados
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Prevenir eventos del mapa en el buscador
            L.DomEvent.disableClickPropagation(div);
            L.DomEvent.disableScrollPropagation(div);
            
            return div;
        };
        
        searchContainer.addTo(mapa);
    }
    
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
        }
    }
    
    // Llenar select de municipios
    function llenarSelectMunicipios() {
        const select = document.getElementById('selectMunicipio');
        
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
    
    // ================= FUNCIONES PRINCIPALES =================
    
    // Función para cargar contratistas (modificada para aceptar filtros)
    async function cargarContratistas(filtros = {}) {
        console.log('🔄 Cargando contratistas...', filtros);
        
        try {
            // Construir URL con parámetros de filtro
            let url = '../../api/contratistas_mapa.php';
            const params = new URLSearchParams();
            
            if (filtros.nombre) params.append('nombre', filtros.nombre);
            if (filtros.municipio) params.append('municipio', filtros.municipio);
            if (filtros.area) params.append('area', filtros.area);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Error desconocido');
            }
            
            const contratistas = result.data;
            console.log(`📊 ${contratistas.length} contratistas cargados`);
            
            // Guardar todos los contratistas
            todosContratistas = contratistas;
            
            // Actualizar lista de áreas dinámicamente
            actualizarListaAreas(contratistas);
            
            // Limpiar marcadores anteriores
            marcadoresContratistas.clearLayers();
            contratistasProcesados = [];
            
            if (contratistas.length === 0) {
                mostrarMensaje('No hay contratistas que coincidan con los filtros');
                ocultarResultadosBusqueda();
                return;
            }
            
            // Procesar cada contratista
            for (const contratista of contratistas) {
                const contratistaProcesado = await procesarContratista(contratista);
                contratistasProcesados.push(contratistaProcesado);
                await esperar(150); // Pausa para no saturar OSM
            }
            
            // Actualizar lista de resultados
            actualizarListaResultados(contratistasProcesados);
            
            console.log('✅ Procesamiento completado');
            
        } catch (error) {
            console.error('❌ Error cargando contratistas:', error);
            mostrarMensaje('Error al cargar los contratistas: ' + error.message);
        }
    }
    
    // Función principal para procesar un contratista
    async function procesarContratista(contratista) {
        let coordenadas = null;
        
        // PRIMERO: Intentar búsqueda mejorada
        if (contratista.direccion && contratista.municipio_principal) {
            coordenadas = await buscarDireccionMejorada(contratista.direccion, contratista.municipio_principal);
        }
        
        // SEGUNDO: Si no funciona, usar municipio
        if (!coordenadas && contratista.municipio_principal) {
            coordenadas = await obtenerCoordenadasMunicipio(contratista.municipio_principal);
        }
        
        // TERCERO: Fallback a Villavicencio
        if (!coordenadas) {
            coordenadas = {
                lat: villavicencio[0],
                lng: villavicencio[1]
            };
        }
        
        // Agregar marcador
        const marcador = agregarMarcadorContratista(contratista, coordenadas);
        
        return {
            ...contratista,
            coordenadas,
            marcador
        };
    }
    
    // ================= FUNCIONES DE FILTRADO =================
    
    // Aplicar filtros desde el buscador
    window.aplicarFiltros = function() {
        const filtros = {
            nombre: document.getElementById('inputNombre').value.trim(),
            municipio: document.getElementById('selectMunicipio').value,
            area: document.getElementById('selectArea').value
        };
        
        cargarContratistas(filtros);
    };
    
    // Limpiar filtros
    window.limpiarFiltros = function() {
        document.getElementById('inputNombre').value = '';
        document.getElementById('selectMunicipio').selectedIndex = 0;
        document.getElementById('selectArea').selectedIndex = 0;
        
        // Cargar todos los contratistas sin filtros
        cargarContratistas();
    };
    
    // Actualizar lista de áreas dinámicamente
    function actualizarListaAreas(contratistas) {
        const areas = new Set();
        
        contratistas.forEach(c => {
            if (c.area) areas.add(c.area);
        });
        
        const select = document.getElementById('selectArea');
        
        // Limpiar opciones (excepto "Todas las áreas")
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        // Ordenar áreas alfabéticamente
        const areasOrdenadas = Array.from(areas).sort();
        
        // Agregar opciones
        areasOrdenadas.forEach(area => {
            const option = document.createElement('option');
            option.value = area;
            option.textContent = area;
            select.appendChild(option);
        });
    }
    
    // ================= LISTA DE RESULTADOS =================
    
    function actualizarListaResultados(contratistas) {
        const container = document.getElementById('listaResultados');
        const contador = document.getElementById('contadorResultados');
        const resultadosDiv = document.getElementById('resultadosBusqueda');
        
        // Mostrar contenedor de resultados
        resultadosDiv.style.display = 'block';
        contador.textContent = contratistas.length;
        
        // Limpiar lista anterior
        container.innerHTML = '';
        
        if (contratistas.length === 0) {
            container.innerHTML = `
                <div class="alert alert-warning py-2 my-2">
                    No se encontraron contratistas
                </div>
            `;
            return;
        }
        
        // Crear elementos de lista
        contratistas.forEach((contratista, index) => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action';
            item.style.cursor = 'pointer';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${contratista.nombre}</h6>
                        <small class="text-muted">
                            📍 ${contratista.municipio_principal || 'Sin municipio'}
                            | 📋 ${contratista.cedula}
                            ${contratista.area ? `| 🏢 ${contratista.area}` : ''}
                        </small>
                    </div>
                    <button onclick="event.stopPropagation(); irAContratista(${index})" 
                            class="btn btn-sm btn-outline-primary">
                        👁️
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
        document.getElementById('resultadosBusqueda').style.display = 'none';
    }
    
    // Ir a un contratista específico
    window.irAContratista = function(index) {
        if (contratistasProcesados[index] && contratistasProcesados[index].marcador) {
            const marcador = contratistasProcesados[index].marcador;
            
            // Centrar mapa en el marcador
            mapa.setView(marcador.getLatLng(), 15);
            
            // Abrir popup
            marcador.openPopup();
            
            // Resaltar marcador
            resaltarMarcador(marcador);
        }
    };
    
    // Resaltar marcador temporalmente
    function resaltarMarcador(marcador) {
        const originalIcon = marcador.options.icon;
        
        // Cambiar a ícono resaltado
        const iconoResaltado = L.divIcon({
            className: 'marcador-contratista-resaltado',
            html: '⭐',
            iconSize: [35, 35],
            iconAnchor: [17, 35]
        });
        
        marcador.setIcon(iconoResaltado);
        
        // Restaurar después de 3 segundos
        setTimeout(() => {
            if (marcador && marcador.setIcon) {
                marcador.setIcon(originalIcon);
            }
        }, 3000);
    }
    
    // ================= FUNCIONES DE GEOCODIFICACIÓN (TUS FUNCIONES ORIGINALES) =================
    
    // FUNCIÓN MEJORADA: Buscar dirección con múltiples intentos
    async function buscarDireccionMejorada(direccion, municipio) {
        // Lista de consultas a intentar
        const consultas = generarConsultas(direccion, municipio);
        
        for (let i = 0; i < consultas.length; i++) {
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
    
    // ================= FUNCIONES DE MARCADORES =================
    
    // Función para agregar un marcador al mapa
    function agregarMarcadorContratista(contratista, coordenadas) {
        // Crear ícono personalizado para contratistas
        var iconoContratista = L.divIcon({
            className: 'marcador-contratista',
            html: '👤',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        });
        
        // Crear el marcador
        var marcador = L.marker([coordenadas.lat, coordenadas.lng], {
            icon: iconoContratista,
            title: contratista.nombre
        }).addTo(marcadoresContratistas);
        
        // Agregar popup con información
        marcador.bindPopup(`
            <div class="popup-contratista" style="max-width: 300px;">
                <h4 style="margin: 0 0 10px 0; color: #2c3e50;">
                    <strong>${contratista.nombre}</strong>
                </h4>
                <p style="margin: 5px 0;"><strong>📋 Cédula:</strong> ${contratista.cedula}</p>
                <p style="margin: 5px 0;"><strong>📞 Teléfono:</strong> ${contratista.telefono}</p>
                <p style="margin: 5px 0;"><strong>🏢 Área:</strong> ${contratista.area}</p>
                <p style="margin: 5px 0;"><strong>📄 Contrato:</strong> ${contratista.contrato}</p>
                <p style="margin: 5px 0;"><strong>📍 Municipio:</strong> ${contratista.municipio_principal}</p>
                <p style="margin: 5px 0;"><strong>🏠 Dirección:</strong> ${contratista.direccion}</p>
                <hr style="margin: 10px 0;">
                <small style="color: #7f8c8d;">Coordenadas: ${coordenadas.lat.toFixed(6)}, ${coordenadas.lng.toFixed(6)}</small>
            </div>
        `);
        
        return marcador;
    }
    
    // ================= FUNCIONES DE UTILIDAD =================
    
    // Función para mostrar mensajes
    function mostrarMensaje(mensaje) {
        L.popup()
            .setLatLng(villavicencio)
            .setContent(`<div style="padding: 10px; text-align: center;">${mensaje}</div>`)
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
        marcadoresContratistas.clearLayers();
        cargarContratistas();
        mostrarMensaje('Recargando contratistas...');
    };
    
    // ================= ESTILOS CSS =================
    
    // Agregar estilos CSS
    const estilo = document.createElement('style');
    estilo.textContent = `
        .search-container {
            background: none;
            border: none;
        }
        
        .search-container .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(0, 0, 0, 0.125);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .search-container .card-title {
            color: #2c3e50;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .marcador-contratista {
            background: none;
            border: none;
            font-size: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            cursor: pointer;
        }
        
        .marcador-contratista-resaltado {
            background: none;
            border: none;
            font-size: 24px;
            text-shadow: 0 0 10px gold;
            cursor: pointer;
            z-index: 1000 !important;
        }
        
        .leaflet-popup-content {
            font-family: Arial, sans-serif;
        }
        
        .list-group-item {
            border-left: none;
            border-right: none;
            border-radius: 0;
            padding: 10px 15px;
        }
        
        .list-group-item:first-child {
            border-top: none;
        }
        
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
        
        #listaResultados::-webkit-scrollbar {
            width: 6px;
        }
        
        #listaResultados::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        #listaResultados::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .form-control-sm, .form-select-sm {
            font-size: 0.875rem;
        }
    `;
    document.head.appendChild(estilo);
    
    // Evento Enter en el input de búsqueda
    document.addEventListener('keypress', function(e) {
        if (e.target.id === 'inputNombre' && e.key === 'Enter') {
            aplicarFiltros();
        }
    });
});