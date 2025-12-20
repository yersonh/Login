     // JavaScript para mapa centrado en el Meta
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') {
                console.error('Leaflet no está cargado');
                return;
            }
            
            // Coordenadas del Meta
            const centroMeta = [3.9026, -73.0769]; // Centro aproximado del departamento
            const villavicencio = [4.1420, -73.6266]; // Capital del Meta
            const zoomInicial = 10; // Zoom para ver el departamento
            
            // 1. Crear el mapa DESHABILITANDO los controles por defecto
            var mapa = L.map('mapa', {
                zoomControl: false,  // ¡IMPORTANTE! Esto elimina los controles de la esquina superior izquierda
                center: villavicencio,
                zoom: zoomInicial
            });
            
            // 2. Añadir capa de OpenStreetMap
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);
            
            console.log('Mapa del Meta cargado');
            
            // 4. Mostrar coordenadas al hacer clic
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
            
            // 5. Añadir controles básicos - SOLO la escala y el zoom en posición específica
            L.control.scale().addTo(mapa);
            
            // Añadir control de zoom personalizado en la esquina inferior derecha
            L.control.zoom({
                position: 'bottomright'
            }).addTo(mapa);
            
            // 6. Función para centrar en Villavicencio
            window.centrarVillavicencio = function() {
                mapa.setView(villavicencio, 13);
                // Abrir popup de Villavicencio
                setTimeout(function() {
                    var layers = mapa._layers;
                    for (var id in layers) {
                        if (layers[id].getLatLng && 
                            layers[id].getLatLng().lat === villavicencio[0] && 
                            layers[id].getLatLng().lng === villavicencio[1]) {
                            layers[id].openPopup();
                            break;
                        }
                    }
                }, 500);
            };
        });