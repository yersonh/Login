<?php
// SitiosAsignados.php - Mapa OSM centrado en el Meta
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitios Asignados - Meta</title>
    
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        /* Estilo minimalista */
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: Arial, sans-serif;
        }
        
        #mapa {
            width: 100vw;
            height: 100vh;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        /* Barra superior simple */
        .barra-superior {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 8px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            font-size: 14px;
            color: #333;
            font-weight: bold;
        }
        
        /* Botón personalizado */
        .btn-control {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 8px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-control:hover {
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <!-- Solo el mapa -->
    <div id="mapa"></div>
    
    <!-- Barra informativa -->
    <div class="barra-superior">
        🗺️ Departamento del Meta - Colombia
    </div>
    
    <script>
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
            
            // 1. Crear el mapa centrado en el Meta
            var mapa = L.map('mapa').setView(villavicencio, zoomInicial);
            
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
            
            // 5. Añadir controles básicos
            L.control.scale().addTo(mapa);
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
    </script>
</body>
</html>