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
    
    // Agregar leyenda de colores
    agregarLeyenda();
    
    // 1. Cargar contratistas desde la API
    cargarContratistas();
    
    // ================= FUNCIONES MEJORADAS =================
    
    // Función para cargar contratistas
    async function cargarContratistas() {
        console.log('🔄 Cargando contratistas...');
        
        try {
            const response = await fetch('../../api/contratistas_mapa.php');
            
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            
            const contratistas = await response.json();
            console.log(`📊 ${contratistas.length} contratistas cargados`);
            
            if (contratistas.length === 0) {
                mostrarMensaje('No hay contratistas con direcciones registradas');
                return;
            }
            
            // Actualizar barra superior
            actualizarBarraSuperior(contratistas.length);
            
            // Procesar cada contratista con mejor manejo
            for (const contratista of contratistas) {
                await procesarContratista(contratista);
                await esperar(300); // Pequeña pausa para no saturar OSM
            }
            
            console.log('✅ Todos los contratistas procesados');
            
        } catch (error) {
            console.error('❌ Error cargando contratistas:', error);
            mostrarMensaje('Error al cargar los contratistas');
        }
    }
    
    // Función mejorada para procesar un contratista
    async function procesarContratista(contratista) {
        console.log(`📍 Procesando: ${contratista.nombre}`);
        console.log(`   Dirección: "${contratista.direccion}"`);
        console.log(`   Municipio: ${contratista.municipio}`);
        
        let coordenadas = null;
        let precision = 'municipio'; // Por defecto
        
        // PRIMERO: Intentar buscar con múltiples estrategias
        if (contratista.direccion && contratista.municipio) {
            coordenadas = await geocodificarDireccionMejorado(contratista);
            
            if (coordenadas) {
                // Determinar precisión basada en el tipo de resultado
                if (coordenadas.tipo === 'street' || coordenadas.tipo === 'road') {
                    precision = 'calle';
                } else if (coordenadas.tipo === 'suburb' || coordenadas.tipo === 'neighbourhood') {
                    precision = 'barrio';
                } else if (coordenadas.tipo === 'town' || coordenadas.tipo === 'city') {
                    precision = 'municipio';
                } else if (coordenadas.tipo === 'administrative') {
                    precision = 'area';
                } else {
                    precision = 'aproximado';
                }
            }
        }
        
        // SEGUNDO: Si no encontramos nada, usar municipio
        if (!coordenadas && contratista.municipio) {
            coordenadas = await obtenerCoordenadasMunicipioMejorado(contratista.municipio);
            precision = 'municipio';
            console.log(`   ⚠️ Usando ubicación del municipio`);
        }
        
        // TERCERO: Si todo falla, Villavicencio
        if (!coordenadas) {
            coordenadas = {
                lat: villavicencio[0],
                lng: villavicencio[1],
                nombre: 'Villavicencio',
                tipo: 'fallback'
            };
            precision = 'fallback';
            console.log(`   ⚠️ Usando Villavicencio como fallback`);
        }
        
        // Agregar marcador con información de precisión
        agregarMarcadorContratistaMejorado(contratista, coordenadas, precision);
    }
    
    // Función MEJORADA para geocodificar (con múltiples intentos)
    async function geocodificarDireccionMejorado(contratista) {
        // Lista de consultas a intentar (de más específica a menos)
        const consultas = [];
        
        // 1. Dirección completa con municipio
        consultas.push(`${contratista.direccion}, ${contratista.municipio}, Meta, Colombia`);
        
        // 2. Dirección simplificada (quitando números específicos)
        const direccionSimple = simplificarDireccion(contratista.direccion);
        if (direccionSimple !== contratista.direccion) {
            consultas.push(`${direccionSimple}, ${contratista.municipio}, Colombia`);
        }
        
        // 3. Solo calle principal si se puede extraer
        const callePrincipal = extraerCallePrincipal(contratista.direccion);
        if (callePrincipal) {
            consultas.push(`${callePrincipal}, ${contratista.municipio}, Meta`);
        }
        
        // 4. Solo municipio (último intento)
        consultas.push(`${contratista.municipio}, Meta, Colombia`);
        
        console.log(`   🔍 Intentando ${consultas.length} búsquedas...`);
        
        // Intentar cada consulta
        for (const consulta of consultas) {
            console.log(`     Probando: "${consulta.substring(0, 50)}..."`);
            
            const resultado = await buscarEnNominatim(consulta);
            if (resultado) {
                console.log(`     ✅ Encontrado: ${resultado.tipo}`);
                return resultado;
            }
            
            // Pequeña pausa entre intentos
            await esperar(200);
        }
        
        console.log(`     ❌ Ninguna búsqueda funcionó`);
        return null;
    }
    
    // Función para simplificar direcciones complejas
    function simplificarDireccion(direccion) {
        if (!direccion) return '';
        
        // Quitar números específicos de casa/manzana/lote
        // Ej: "Calle 13A #14-60" → "Calle 13A"
        // Ej: "Manzana B Lote 5" → "Manzana B"
        
        const patrones = [
            // Quitar complementos después de #, -, etc.
            /^(.*?)(?:\s*[#\-]\s*\d+.*)$/i,
            /^(.*?\b(?:manzana|mz|lote|lt|torre|apartamento|apt)\s+[a-z0-9]+).*$/i,
            // Quitar palabras como "esquina", "interior", etc.
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
    
    // Función para extraer calle principal
    function extraerCallePrincipal(direccion) {
        if (!direccion) return null;
        
        // Patrones comunes en Colombia
        const patrones = [
            /(calle|carrera|avenida|diagonal|transversal|cll|cr|av)\s+(\d+[a-z]?(?:\s*[a-z])?)/i,
            /(cra|av|diag|trans)\s+(\d+[a-z]?)/i
        ];
        
        for (const patron of patrones) {
            const match = direccion.match(patron);
            if (match) {
                const tipo = match[1].toLowerCase();
                const numero = match[2];
                
                // Normalizar abreviaturas
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
                    lng: parseFloat(data[0].lon),
                    nombre: data[0].display_name,
                    tipo: data[0].type || 'unknown'
                };
            }
            
            return null;
            
        } catch (error) {
            console.warn('Error Nominatim:', error);
            return null;
        }
    }
    
    // Función MEJORADA para obtener coordenadas de municipio
    async function obtenerCoordenadasMunicipioMejorado(municipioNombre) {
        // Coordenadas más completas de municipios del Meta
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
            const coords = coordenadasMunicipios[municipioNombre];
            return {
                lat: coords[0],
                lng: coords[1],
                nombre: `Centro de ${municipioNombre}`,
                tipo: 'municipio'
            };
        }
        
        // Si no encontramos el municipio, buscar en Nominatim
        const resultado = await buscarEnNominatim(`${municipioNombre}, Meta, Colombia`);
        if (resultado) {
            return resultado;
        }
        
        // Último recurso: Villavicencio
        return {
            lat: villavicencio[0],
            lng: villavicencio[1],
            nombre: 'Villavicencio',
            tipo: 'fallback'
        };
    }
    
    // Función MEJORADA para agregar marcador
    function agregarMarcadorContratistaMejorado(contratista, coordenadas, precision) {
        // Colores según precisión
        const colores = {
            'calle': '#27ae60',     // Verde - Encontramos la calle
            'barrio': '#2ecc71',    // Verde claro - Encontramos el barrio
            'area': '#f39c12',      // Naranja - Encontramos el área
            'municipio': '#e74c3c', // Rojo - Solo municipio
            'aproximado': '#95a5a6', // Gris - Aproximado
            'fallback': '#7f8c8d'   // Gris oscuro - Fallback
        };
        
        const color = colores[precision] || colores.fallback;
        
        // Mensajes según precisión
        const mensajes = {
            'calle': '🚶 <strong>En esta calle</strong> (ubicación aproximada)',
            'barrio': '🏡 <strong>En este barrio/vecindario</strong>',
            'area': '🗺️ <strong>En esta área</strong>',
            'municipio': '🏙️ <strong>En este municipio</strong> (ubicación central)',
            'aproximado': '🔍 <strong>Ubicación aproximada</strong>',
            'fallback': '📍 <strong>Ubicación general</strong>'
        };
        
        const mensajePrecision = mensajes[precision] || mensajes.fallback;
        
        // Crear ícono personalizado con color
        var iconoContratista = L.divIcon({
            className: 'marcador-contratista',
            html: `
                <div style="
                    background: ${color};
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    border: 2px solid white;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: bold;
                    font-size: 14px;
                ">👤</div>
            `,
            iconSize: [28, 28],
            iconAnchor: [14, 28],
            popupAnchor: [0, -28]
        });
        
        // Crear el marcador
        var marcador = L.marker([coordenadas.lat, coordenadas.lng], {
            icon: iconoContratista,
            title: contratista.nombre
        }).addTo(marcadoresContratistas);
        
        // Agregar popup con información MEJORADA
        marcador.bindPopup(`
            <div class="popup-contratista" style="max-width: 300px; font-family: Arial, sans-serif;">
                <div style="background: ${color}; color: white; padding: 10px 15px; margin: -12px -12px 15px -12px; border-radius: 5px 5px 0 0;">
                    <h4 style="margin: 0; font-size: 15px;">👤 ${contratista.nombre}</h4>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <p style="margin: 6px 0;"><strong>📋 Cédula:</strong> ${contratista.cedula}</p>
                    <p style="margin: 6px 0;"><strong>📞 Teléfono:</strong> ${contratista.telefono || 'No registrado'}</p>
                    <p style="margin: 6px 0;"><strong>🏢 Área:</strong> ${contratista.area}</p>
                    <p style="margin: 6px 0;"><strong>📄 Contrato:</strong> ${contratista.contrato}</p>
                    <p style="margin: 6px 0;"><strong>📍 Municipio:</strong> ${contratista.municipio}</p>
                    <p style="margin: 6px 0;"><strong>🏠 Dirección:</strong><br>
                        <span style="color: #7f8c8d; font-size: 12px;">${contratista.direccion || 'No especificada'}</span>
                    </p>
                </div>
                
                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid ${color}; margin-bottom: 10px;">
                    ${mensajePrecision}
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #7f8c8d;">
                        ${coordenadas.nombre ? `"${coordenadas.nombre}"` : 'Ubicación en el mapa'}
                    </p>
                </div>
                
                <div style="font-size: 11px; color: #95a5a6; text-align: center; border-top: 1px solid #eee; padding-top: 8px;">
                    Coordenadas: ${coordenadas.lat.toFixed(5)}, ${coordenadas.lng.toFixed(5)}
                </div>
            </div>
        `);
        
        // Agregar tooltip
        marcador.bindTooltip(contratista.nombre, {
            direction: 'top',
            offset: [0, -10],
            opacity: 0.9
        });
    }
    
    // Función para agregar leyenda al mapa
    function agregarLeyenda() {
        const leyenda = L.control({ position: 'bottomleft' });
        
        leyenda.onAdd = function() {
            const div = L.DomUtil.create('div', 'leyenda-mapa');
            div.innerHTML = `
                <div style="
                    background: white;
                    padding: 12px;
                    border-radius: 6px;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    max-width: 200px;
                ">
                    <h4 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 13px;">
                        🎯 Precisión de ubicación
                    </h4>
                    
                    <div style="margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; margin: 4px 0;">
                            <div style="width: 12px; height: 12px; background: #27ae60; border-radius: 50%; margin-right: 8px;"></div>
                            <span>En la calle exacta</span>
                        </div>
                        <div style="display: flex; align-items: center; margin: 4px 0;">
                            <div style="width: 12px; height: 12px; background: #2ecc71; border-radius: 50%; margin-right: 8px;"></div>
                            <span>En el barrio/área</span>
                        </div>
                        <div style="display: flex; align-items: center; margin: 4px 0;">
                            <div style="width: 12px; height: 12px; background: #f39c12; border-radius: 50%; margin-right: 8px;"></div>
                            <span>En la zona general</span>
                        </div>
                        <div style="display: flex; align-items: center; margin: 4px 0;">
                            <div style="width: 12px; height: 12px; background: #e74c3c; border-radius: 50%; margin-right: 8px;"></div>
                            <span>En el municipio</span>
                        </div>
                    </div>
                    
                    <div style="font-size: 11px; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 8px;">
                        <strong>💡 Nota:</strong> Las ubicaciones son aproximadas basadas en OpenStreetMap.
                    </div>
                </div>
            `;
            return div;
        };
        
        leyenda.addTo(mapa);
    }
    
    // Función para actualizar barra superior
    function actualizarBarraSuperior(cantidad) {
        const barra = document.querySelector('.barra-superior');
        if (barra) {
            barra.innerHTML = `🗺️ Departamento del Meta - ${cantidad} contratistas`;
        }
    }
    
    // Función para mostrar mensajes
    function mostrarMensaje(mensaje) {
        L.popup()
            .setLatLng(villavicencio)
            .setContent(`<div style="padding: 15px; text-align: center; min-width: 250px;">${mensaje}</div>`)
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
    
    // Función para recargar contratistas
    window.recargarContratistas = function() {
        // Limpiar marcadores existentes
        marcadoresContratistas.clearLayers();
        // Volver a cargar
        cargarContratistas();
        mostrarMensaje('Recargando contratistas...');
    };
    
    // Agregar estilos CSS para los marcadores
    const estilo = document.createElement('style');
    estilo.textContent = `
        .marcador-contratista {
            background: none;
            border: none;
        }
        .leaflet-popup-content {
            font-family: Arial, sans-serif;
        }
        .leaflet-tooltip {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .leyenda-mapa {
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    `;
    document.head.appendChild(estilo);
});