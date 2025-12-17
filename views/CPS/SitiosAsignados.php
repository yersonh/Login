<?php
// SitiosAsignados.php - Mapa OSM básico
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Básico OSM</title>
    
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
        // JavaScript básico
        // 1. Crear el mapa
        var mapa = L.map('mapa');
        
        // 2. Centrar en una ubicación (coordenadas de Madrid)
        mapa.setView([40.4168, -3.7038], 13);
        
        // 3. Añadir capa de OpenStreetMap
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(mapa);
        
        // 4. Añadir un marcador básico
        var marcador = L.marker([40.4168, -3.7038]).addTo(mapa);
        marcador.bindPopup("¡Hola! Este es Madrid");
        
        console.log("Mapa cargado correctamente");
    </script>
    
    <!-- Incluir Leaflet desde CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>