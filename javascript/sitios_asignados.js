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
    
    console.log('Mapa del Meta cargado');
    
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
    
    // Función para cargar contratistas
    function cargarContratistas() {
        fetch('../api/contratistas_mapa.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(contratistas => {
                console.log('Contratistas cargados:', contratistas.length);
                
                if (contratistas.length === 0) {
                    mostrarMensaje('No hay contratistas con direcciones registradas');
                    return;
                }
                
                // Procesar cada contratista
                contratistas.forEach(contratista => {
                    // Intentar geocodificar la dirección
                    geocodificarDireccion(contratista).then(coordenadas => {
                        if (coordenadas) {
                            agregarMarcadorContratista(contratista, coordenadas);
                        }
                    }).catch(error => {
                        console.error('Error geocodificando:', error);
                        // Si no se puede geocodificar, usar coordenadas del municipio
                        usarCoordenadasMunicipio(contratista);
                    });
                });
                
            })
            .catch(error => {
                console.error('Error cargando contratistas:', error);
                mostrarMensaje('Error al cargar los contratistas');
            });
    }
    
    // Función para geocodificar una dirección (usando Nominatim de OSM)
    async function geocodificarDireccion(contratista) {
        const direccionCompleta = `${contratista.direccion}, ${contratista.municipio}, Meta, Colombia`;
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccionCompleta)}&limit=1`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon)
                };
            }
        } catch (error) {
            console.error('Error en geocodificación:', error);
        }
        
        return null;
    }
    
    // Función para usar coordenadas aproximadas del municipio
    function usarCoordenadasMunicipio(contratista) {
        // Coordenadas aproximadas de municipios del Meta
        const coordenadasMunicipios = {
            'Villavicencio': [4.1420, -73.6266],
            'Acacías': [3.9878, -73.7577],
            'Granada': [3.5431, -73.7075],
            'San Martín': [3.6959, -73.6942],
            'Puerto López': [4.0895, -72.9557],
            'Puerto Gaitán': [4.3133, -72.0825],
            'Restrepo': [4.2611, -73.5614],
            'Cumaral': [4.2695, -73.4862],
            // Agrega más municipios según necesites
        };
        
        const municipio = contratista.municipio;
        if (coordenadasMunicipios[municipio]) {
            agregarMarcadorContratista(contratista, {
                lat: coordenadasMunicipios[municipio][0],
                lng: coordenadasMunicipios[municipio][1]
            });
        } else {
            // Si no encontramos el municipio, usar Villavicencio como fallback
            agregarMarcadorContratista(contratista, {
                lat: villavicencio[0],
                lng: villavicencio[1]
            });
        }
    }
    
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
                <p style="margin: 5px 0;"><strong>📞 Teléfono:</strong> ${contratista.telefono || 'No registrado'}</p>
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
    
    // Función para centrar en Villavicencio
    window.centrarVillavicencio = function() {
        mapa.setView(villavicencio, 13);
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