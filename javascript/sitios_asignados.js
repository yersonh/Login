// JavaScript para mapa centrado en el Meta
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
    
    // Variable para almacenar los marcadores
    var marcadoresContratistas = L.layerGroup().addTo(mapa);
    
    // Variables globales para filtros
    let filtroMunicipioActual = 'todos';
    let filtroBusquedaActual = '';
    
    // ================= SISTEMA DE FILTRADO =================
    
    // Función para crear controles de filtrado
    function crearControlesFiltrado() {
        const controles = L.control({ position: 'topright' });
        
        controles.onAdd = function() {
            const div = L.DomUtil.create('div', 'controles-filtrado');
            div.innerHTML = `
                <div style="
                    background: white;
                    padding: 15px;
                    border-radius: 8px;
                    box-shadow: 0 3px 15px rgba(0,0,0,0.2);
                    font-family: Arial, sans-serif;
                    width: 280px;
                    max-height: 80vh;
                    overflow-y: auto;
                ">
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;">
                        🔍 Filtros de Contratistas
                    </h3>
                    
                    <!-- Filtro por Municipio -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">
                            🗺️ Filtrar por Municipio
                        </label>
                        <select id="filtro-municipio" 
                                style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="todos">Todos los municipios</option>
                            <!-- Se llenará con JavaScript -->
                        </select>
                    </div>
                    
                    <!-- Búsqueda por Nombre/Contrato -->
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px;">
                            👤 Buscar por Nombre o Contrato
                        </label>
                        <input type="text" 
                               id="busqueda-contratista"
                               placeholder="Nombre, cédula o número de contrato"
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <!-- Contador de resultados -->
                    <div id="contador-resultados" 
                         style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 13px; text-align: center; margin-bottom: 15px;">
                        Cargando...
                    </div>
                    
                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 10px;">
                        <button id="btn-aplicar-filtros" 
                                style="flex: 1; padding: 10px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                            Aplicar Filtros
                        </button>
                        <button id="btn-limpiar-filtros" 
                                style="flex: 1; padding: 10px; background: #95a5a6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
                            Limpiar
                        </button>
                    </div>
                    
                    <!-- Indicador de carga -->
                    <div id="indicador-carga" 
                         style="display: none; text-align: center; margin-top: 10px; color: #f39c12;">
                        ⏳ Aplicando filtros...
                    </div>
                </div>
            `;
            return div;
        };
        
        controles.addTo(mapa);
        
        // Cargar municipios después de crear controles
        setTimeout(() => {
            cargarMunicipios();
            configurarEventosFiltrado();
        }, 500);
    }
    
    // Función para cargar municipios desde la API
    async function cargarMunicipios() {
        try {
            const response = await fetch('../../api/contratistas_mapa.php?action=municipios');
            const municipios = await response.json();
            
            const select = document.getElementById('filtro-municipio');
            
            // Agregar municipios al select
            municipios.forEach(municipio => {
                const option = document.createElement('option');
                option.value = municipio;
                option.textContent = municipio;
                select.appendChild(option);
            });
            
            console.log(`✅ ${municipios.length} municipios cargados`);
            
        } catch (error) {
            console.error('❌ Error cargando municipios:', error);
        }
    }
    
    // Configurar eventos de los controles
    function configurarEventosFiltrado() {
        // Aplicar filtros
        document.getElementById('btn-aplicar-filtros').addEventListener('click', aplicarFiltros);
        
        // Limpiar filtros
        document.getElementById('btn-limpiar-filtros').addEventListener('click', limpiarFiltros);
        
        // Buscar al presionar Enter
        document.getElementById('busqueda-contratista').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') aplicarFiltros();
        });
    }
    
    async function aplicarFiltros() {
        mostrarIndicadorCarga(true);
        
        const municipio = document.getElementById('filtro-municipio').value;
        const busqueda = document.getElementById('busqueda-contratista').value.trim();
        
        filtroMunicipioActual = municipio;
        filtroBusquedaActual = busqueda;
        
        marcadoresContratistas.clearLayers();
        
        let url = '../../api/contratistas_mapa.php';
        const params = [];
        
        if (municipio && municipio !== 'todos') {
            params.push(`municipio=${encodeURIComponent(municipio)}`);
        }
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        try {
            const response = await fetch(url);
            const resultado = await response.json();
            
            if (resultado.success && resultado.data) {
                let contratistas = resultado.data;

                if (busqueda) {
                    const busquedaLower = busqueda.toLowerCase();
                    contratistas = contratistas.filter(c => 
                        c.nombre.toLowerCase().includes(busquedaLower) ||
                        c.cedula.includes(busqueda) ||
                        c.contrato.toLowerCase().includes(busquedaLower)
                    );
                }

                for (const contratista of contratistas) {
                    await procesarContratista(contratista);
                    await esperar(200);
                }

                actualizarContadorResultados(contratistas.length, resultado.total || contratistas.length);

                if (contratistas.length > 0) {
                    ajustarVistaAFiltros();
                } else {
                    mostrarMensaje('No hay contratistas que coincidan con los filtros');
                }
                
            } else {
                mostrarMensaje(resultado.message || 'Error aplicando filtros');
            }
            
        } catch (error) {
            console.error('❌ Error aplicando filtros:', error);
            mostrarMensaje('Error al aplicar filtros');
        } finally {
            mostrarIndicadorCarga(false);
        }
    }

    function limpiarFiltros() {
        document.getElementById('filtro-municipio').value = 'todos';
        document.getElementById('busqueda-contratista').value = '';
        
        filtroMunicipioActual = 'todos';
        filtroBusquedaActual = '';

        recargarContratistasCompletos();
    }

    async function recargarContratistasCompletos() {
        marcadoresContratistas.clearLayers();
        await cargarContratistas();
    }

    function actualizarContadorResultados(mostrados, total) {
        const contador = document.getElementById('contador-resultados');
        
        let mensaje = '';
        if (filtroMunicipioActual !== 'todos' || filtroBusquedaActual) {
            mensaje = `<strong>${mostrados} contratistas</strong>`;
            if (filtroMunicipioActual !== 'todos') {
                mensaje += ` en <strong>${filtroMunicipioActual}</strong>`;
            }
            if (filtroBusquedaActual) {
                mensaje += ` que coinciden con "<strong>${filtroBusquedaActual}</strong>"`;
            }
        } else {
            mensaje = `<strong>${mostrados} contratistas</strong> en total`;
        }
        
        contador.innerHTML = mensaje;
    }

    function ajustarVistaAFiltros() {
        const marcadores = marcadoresContratistas.getLayers();
        
        if (marcadores.length === 0) {
            mapa.setView(villavicencio, zoomInicial);
            return;
        }
        
        if (marcadores.length === 1) {
            const marcador = marcadores[0];
            const zoom = filtroMunicipioActual !== 'todos' ? 12 : 14;
            mapa.setView(marcador.getLatLng(), zoom);
        } else {
            const grupo = L.featureGroup(marcadores);
            mapa.fitBounds(grupo.getBounds().pad(0.1));
        }
    }

    function mostrarIndicadorCarga(mostrar) {
        const indicador = document.getElementById('indicador-carga');
        indicador.style.display = mostrar ? 'block' : 'none';
    }

    async function cargarContratistas() {
        console.log('🔄 Cargando contratistas...');
        
        try {
            mostrarIndicadorCarga(true);

            let url = '../../api/contratistas_mapa.php';
            if (filtroMunicipioActual && filtroMunicipioActual !== 'todos') {
                url += `?municipio=${encodeURIComponent(filtroMunicipioActual)}`;
            }
            
            const response = await fetch(url);
            const resultado = await response.json();
            
            if (resultado.success && resultado.data) {
                let contratistas = resultado.data;

                if (filtroBusquedaActual) {
                    const busquedaLower = filtroBusquedaActual.toLowerCase();
                    contratistas = contratistas.filter(c => 
                        c.nombre.toLowerCase().includes(busquedaLower) ||
                        c.cedula.includes(filtroBusquedaActual) ||
                        c.contrato.toLowerCase().includes(busquedaLower)
                    );
                }
                
                console.log(`📊 ${contratistas.length} contratistas cargados`);
                
                if (contratistas.length === 0) {
                    mostrarMensaje(resultado.message || 'No hay contratistas que coincidan');
                    actualizarContadorResultados(0, 0);
                    return;
                }

                for (const contratista of contratistas) {
                    await procesarContratista(contratista);
                    await esperar(200);
                }
                
                actualizarContadorResultados(contratistas.length, resultado.total || contratistas.length);
                
            } else {
                mostrarMensaje(resultado.error || 'Error cargando datos');
            }
            
        } catch (error) {
            console.error('❌ Error cargando contratistas:', error);
            mostrarMensaje('Error al cargar los contratistas');
        } finally {
            mostrarIndicadorCarga(false);
        }
    }

    async function procesarContratista(contratista) {
        let coordenadas = null;
        
        if (contratista.direccion && contratista.municipio) {
            coordenadas = await buscarDireccionMejorada(contratista.direccion, contratista.municipio);
        }
        
        // SEGUNDO: Si no funciona, usar municipio
        if (!coordenadas && contratista.municipio) {
            coordenadas = await obtenerCoordenadasMunicipio(contratista.municipio);
        }

        if (!coordenadas) {
            coordenadas = {
                lat: villavicencio[0],
                lng: villavicencio[1]
            };
        }

        agregarMarcadorContratista(contratista, coordenadas);
    }

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

    function generarConsultas(direccion, municipio) {
        const consultas = [];

        consultas.push(`${direccion}, ${municipio}, Meta, Colombia`);

        const direccionSimple = simplificarDireccion(direccion);
        if (direccionSimple !== direccion) {
            consultas.push(`${direccionSimple}, ${municipio}, Colombia`);
        }

        const elementos = extraerElementosDireccion(direccion);
        if (elementos.calle && elementos.numero) {
            consultas.push(`${elementos.calle} ${elementos.numero}, ${municipio}, Meta`);
        }

        const callePrincipal = extraerCallePrincipal(direccion);
        if (callePrincipal) {
            consultas.push(`${callePrincipal}, ${municipio}, Colombia`);
        }

        consultas.push(`${municipio}, Meta, Colombia`);
        
        return consultas;
    }
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
        const resultado = await buscarEnNominatim(`${municipioNombre}, Meta, Colombia`);
        if (resultado) {
            return resultado;
        }
        
        // Último recurso: Villavicencio
        return null;
    }

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

        marcador.bindPopup(`
            <div class="popup-contratista" style="max-width: 300px;">
                <h4 style="margin: 0 0 10px 0; color: #2c3e50;">
                    <strong>${contratista.nombre}</strong>
                </h4>
                <p style="margin: 5px 0;"><strong>📋 Cédula:</strong> ${contratista.cedula}</p>
                <p style="margin: 5px 0;"><strong>📞 Teléfono:</strong> ${contratista.telefono}</p>
                <p style="margin: 5px 0;"><strong>🏢 Área:</strong> ${contratista.area}</p>
                <p style="margin: 5px 0;"><strong>📄 Contrato:</strong> ${contratista.contrato}</p>
                <p style="margin: 5px 0;"><strong>📍 Municipio:</strong> ${contratista.municipio}</p>
                <p style="margin: 5px 0;"><strong>🏠 Dirección:</strong> ${contratista.direccion}</p>
                <hr style="margin: 10px 0;">
                <small style="color: #7f8c8d;">Coordenadas: ${coordenadas.lat.toFixed(6)}, ${coordenadas.lng.toFixed(6)}</small>
            </div>
        `);
    }

    function mostrarMensaje(mensaje) {
        L.popup()
            .setLatLng(villavicencio)
            .setContent(`<div style="padding: 10px; text-align: center;">${mensaje}</div>`)
            .openOn(mapa);
    }

    function esperar(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    window.centrarVillavicencio = function() {
        mapa.setView(villavicencio, 13);
    };

    window.recargarContratistas = function() {
        marcadoresContratistas.clearLayers();
        cargarContratistas();
        mostrarMensaje('Recargando contratistas...');
    };

    window.aplicarFiltros = aplicarFiltros;
    window.limpiarFiltros = limpiarFiltros;
    
    // Agregar estilos CSS
    const estilo = document.createElement('style');
    estilo.textContent = `
        .marcador-contratista {
            background: none;
            border: none;
            font-size: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            cursor: pointer;
        }
        .leaflet-popup-content {
            font-family: Arial, sans-serif;
        }
        .controles-filtrado {
            max-height: 80vh;
            overflow-y: auto;
        }
        .controles-filtrado select:focus,
        .controles-filtrado input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        .controles-filtrado button {
            transition: all 0.2s ease;
        }
        .controles-filtrado button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .controles-filtrado button:active {
            transform: translateY(0);
        }
        #btn-aplicar-filtros:hover {
            background: #2980b9 !important;
        }
        #btn-limpiar-filtros:hover {
            background: #7f8c8d !important;
        }
    `;
    document.head.appendChild(estilo);

    cargarContratistas();
    setTimeout(() => {
        crearControlesFiltrado();
    }, 1000);
});