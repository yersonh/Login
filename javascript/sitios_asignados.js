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
    
    // 1. Cargar contratistas desde la API
    cargarContratistas();
    
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
    
    // ================= FUNCIONES MEJORADAS (INTERNAS) =================
    
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
            
            // Procesar cada contratista con búsqueda mejorada
            for (const contratista of contratistas) {
                await procesarContratista(contratista);
                // Pequeña pausa para no saturar OSM
                await esperar(200);
            }
            
            console.log('✅ Procesamiento completado');
            
        } catch (error) {
            console.error('❌ Error cargando contratistas:', error);
            mostrarMensaje('Error al cargar los contratistas');
        }
    }
    
    // Función principal para procesar un contratista
    async function procesarContratista(contratista) {
        let coordenadas = null;
        
        // PRIMERO: Intentar búsqueda mejorada
        if (contratista.direccion && contratista.municipio) {
            coordenadas = await buscarDireccionMejorada(contratista.direccion, contratista.municipio);
        }
        
        // SEGUNDO: Si no funciona, usar municipio
        if (!coordenadas && contratista.municipio) {
            coordenadas = await obtenerCoordenadasMunicipio(contratista.municipio);
        }
        
        // TERCERO: Fallback a Villavicencio
        if (!coordenadas) {
            coordenadas = {
                lat: villavicencio[0],
                lng: villavicencio[1]
            };
        }
        
        // Agregar marcador (mismo diseño visual)
        agregarMarcadorContratista(contratista, coordenadas);
    }
    
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
    
    // Función para agregar un marcador al mapa (MISMO DISEÑO)
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
        
        // Agregar popup con información (MISMO DISEÑO)
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
    
    // Función para recargar (opcional)
    window.recargarContratistas = function() {
        marcadoresContratistas.clearLayers();
        cargarContratistas();
        mostrarMensaje('Recargando contratistas...');
    };
    
    // Agregar estilos CSS para los marcadores
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
    `;
    document.head.appendChild(estilo);
});