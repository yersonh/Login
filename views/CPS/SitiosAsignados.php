<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitios Asignados - Meta</title>
    
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="../styles/sitios_asignados.css">
</head>
<body>

    <div class="barra-superior">
        <i class="fas fa-map-marked-alt"></i> Departamento del Meta - Colombia
    </div>

    <div id="mapa" style="height: 100vh; width: 100%;"></div>

    <button class="volver-btn" id="volverBtn">
        <i class="fas fa-arrow-left"></i>
        <span>Volver al Menú</span>
    </button>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        document.getElementById('volverBtn').addEventListener('click', () => {
            window.location.href = 'OpcionesCPS.php';
        });
    </script>

    <script src="../../javascript/sitios_asignados.js"></script>

</body>
</html>