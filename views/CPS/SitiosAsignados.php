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
    
    <!-- Botón para centrar en Villavicencio -->
    <button class="btn-control" onclick="centrarVillavicencio()">
        📍 Villavicencio
    </button>
    
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
            
            // 3. Añadir marcadores principales del Meta
            var municipios = [
                {
                    nombre: "Villavicencio",
                    lat: 4.1420,
                    lng: -73.6266,
                    desc: "Capital del departamento del Meta",
                    tipo: "capital"
                },
                {
                    nombre: "Acacías",
                    lat: 3.9880,
                    lng: -73.7582,
                    desc: "Municipio del Meta",
                    tipo: "municipio"
                },
                {
                    nombre: "Granada",
                    lat: 3.5431,
                    lng: -73.7064,
                    desc: "Municipio del Meta",
                    tipo: "municipio"
                },
                {
                    nombre: "Puerto López",
                    lat: 4.0833,
                    lng: -72.9667,
                    desc: "Municipio del Meta - Puerto fluvial",
                    tipo: "municipio"
                },
                {
                    nombre: "Puerto Gaitán",
                    lat: 4.3133,
                    lng: -72.0825,
                    desc: "Municipio del Meta",
                    tipo: "municipio"
                }
            ];
            
            // Crear marcadores
            municipios.forEach(function(municipio) {
                // Color diferente para la capital
                var icono = L.divIcon({
                    className: 'custom-marker',
                    html: municipio.tipo === "capital" ? 
                        '<div style="background:#e74c3c;color:white;border-radius:50%;width:25px;height:25px;display:flex;align-items:center;justify-content:center;font-weight:bold">V</div>' :
                        '<div style="background:#3498db;color:white;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:12px">●</div>',
                    iconSize: [25, 25],
                    iconAnchor: [12, 25]
                });
                
                var marcador = L.marker([municipio.lat, municipio.lng], {icon: icono}).addTo(mapa);
                marcador.bindPopup(
                    `<b>${municipio.nombre}</b><br>
                     ${municipio.desc}<br>
                     <small>📌 Lat: ${municipio.lat.toFixed(4)}<br>
                     📌 Lng: ${municipio.lng.toFixed(4)}</small>`
                );
            });
            
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
            
            // 7. Añadir polígono aproximado del Meta (opcional)
            var polygon = L.polygon([
                [4.5, -74.0],   // Noroeste
                [4.5, -71.5],   // Noreste
                [2.0, -71.5],   // Sureste
                [2.0, -74.0],   // Suroeste
                [4.5, -74.0]    // Cerrar polígono
            ], {
                color: 'blue',
                weight: 2,
                opacity: 0.5,
                fillOpacity: 0.1,
                fillColor: 'blue'
            }).addTo(mapa);
            
            polygon.bindPopup("Área aproximada del Departamento del Meta");
        });
    </script>
</body>
</html>