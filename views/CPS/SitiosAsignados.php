<?php
// SitiosAsignados.php - Mapa OSM básico
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Básico OSM</title>
    
    <!-- Leaflet DEBE ir en el HEAD -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        /* CSS básico */
        #mapa {
            width: 100%;
            height: 500px;
            border: 2px solid #ccc;
            border-radius: 8px;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        h1 {
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Mapa Básico OpenStreetMap</h1>
    
    <div id="mapa"></div>
    
    <script>
        // JavaScript básico - Esperar a que Leaflet se cargue
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar que Leaflet esté cargado
            if (typeof L === 'undefined') {
                console.error('Leaflet no está cargado');
                return;
            }
            
            console.log('Leaflet cargado, inicializando mapa...');
            
            // 1. Crear el mapa
            var mapa = L.map('mapa');
            
            // 2. Centrar en una ubicación (coordenadas de Madrid)
            mapa.setView([40.4168, -3.7038], 13);
            
            // 3. Añadir capa de OpenStreetMap
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapa);
            
            // 4. Añadir un marcador básico
            var marcador = L.marker([40.4168, -3.7038]).addTo(mapa);
            marcador.bindPopup("<b>¡Hola!</b><br>Este es Madrid");
            
            console.log("Mapa cargado correctamente");
            
            // Opcional: Mostrar coordenadas al hacer clic
            mapa.on('click', function(e) {
                console.log('Coordenadas:', e.latlng.lat, e.latlng.lng);
                var popup = L.popup()
                    .setLatLng(e.latlng)
                    .setContent("Latitud: " + e.latlng.lat.toFixed(6) + "<br>Longitud: " + e.latlng.lng.toFixed(6))
                    .openOn(mapa);
            });
        });
    </script>
</body>
</html>