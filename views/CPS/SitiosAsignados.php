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
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../styles/sitios_asignados.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
</head>
<body>
    <!-- Solo el mapa -->
    <div id="mapa"></div>
    
    <!-- Barra informativa -->
    <div class="barra-superior">
        🗺️ Departamento del Meta - Colombia
    </div>
    
    <script src="../../javascript/sitios_asignados.js"></script>

</body>
</html>