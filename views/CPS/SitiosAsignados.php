<?php
// SitiosAsignados.php - Mapa OSM centrado en Colombia
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitios Asignados - Colombia</title>
    
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
            padding: 5px 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            font-size: 12px;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Solo el mapa, sin títulos -->
    <div id="mapa"></div>
    
    <!-- Pequeña barra informativa -->
    <div class="barra-superior">
        🇨🇴 Colombia | OpenStreetMap
    </div>
    
    <script>
        // JavaScript para mapa centrado en Colombia
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') {
                console.error('Leaflet no está cargado');
                return;
            }
            
            // Coordenadas de Colombia (centro del país)
            const centroColombia = [4.5709, -74.2973]; // Cerca de Bogotá
            const zoomInicial = 6; // Zoom para ver todo el país
            
            // 1. Crear el mapa centrado en Colombia
            var mapa = L.map('mapa').setView(centroColombia, zoomInicial);
            
            // 2. Añadir capa de OpenStreetMap
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);
            
            console.log('Mapa de Colombia cargado');
            
            // 3. Añadir marcadores en ciudades principales
            var ciudades = [
                {
                    nombre: "Bogotá",
                    lat: 4.6097,
                    lng: -74.0817,
                    desc: "Capital de Colombia"
                },
                {
                    nombre: "Medellín",
                    lat: 6.2442,
                    lng: -75.5812,
                    desc: "Ciudad de la eterna primavera"
                },
                {
                    nombre: "Cali",
                    lat: 3.4516,
                    lng: -76.5320,
                    desc: "Capital de la salsa"
                },
                {
                    nombre: "Barranquilla",
                    lat: 10.9639,
                    lng: -74.7964,
                    desc: "Puerta de oro de Colombia"
                },
                {
                    nombre: "Cartagena",
                    lat: 10.3910,
                    lng: -75.4794,
                    desc: "Ciudad amurallada"
                }
            ];
            
            // Añadir marcadores
            ciudades.forEach(function(ciudad) {
                var marcador = L.marker([ciudad.lat, ciudad.lng]).addTo(mapa);
                marcador.bindPopup(
                    `<b>${ciudad.nombre}</b><br>${ciudad.desc}<br>
                     <small>Lat: ${ciudad.lat.toFixed(4)}<br>Lng: ${ciudad.lng.toFixed(4)}</small>`
                );
            });
            
            // 4. Mostrar coordenadas al hacer clic
            mapa.on('click', function(e) {
                L.popup()
                    .setLatLng(e.latlng)
                    .setContent(
                        `<b>Coordenadas</b><br>
                         Latitud: ${e.latlng.lat.toFixed(6)}<br>
                         Longitud: ${e.latlng.lng.toFixed(6)}<br>
                         <small>Haz doble clic para cerrar</small>`
                    )
                    .openOn(mapa);
            });
            
            // 5. Añadir controles básicos
            L.control.scale().addTo(mapa);
            
            // Opcional: Añadir botón para centrar en Colombia
            var botonCentrar = L.control({position: 'topright'});
            botonCentrar.onAdd = function() {
                var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                div.innerHTML = '<button style="padding:5px;background:white;border:1px solid #ccc;cursor:pointer">📍 Centrar Colombia</button>';
                div.onclick = function() {
                    mapa.setView(centroColombia, zoomInicial);
                };
                return div;
            };
            botonCentrar.addTo(mapa);
        });
    </script>
</body>
</html>