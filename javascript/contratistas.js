// javascript/aom.js
document.addEventListener('DOMContentLoaded', function() {
    
    initButtons();
    
    console.log('AOM Contratistas - Usuario:', USER_CORREO);
    console.log('AOM Contratistas - Rol:', USER_TIPO);

    function initButtons() {

        const opciones = [
            'agregar-contratista',
            'municipios-card',
            'modificar-contratista',
            'parametrizar-obligaciones',
            'dashboard-estadistico',
            'visor-registrados'
        ];
        
        opciones.forEach(opcionId => {
            const elemento = document.getElementById(opcionId);
            if (elemento) {
                elemento.addEventListener('click', function() {
                    handleOptionClick(opcionId);
                });
            }
        });
    }
    
    function handleOptionClick(opcionId) {
        console.log('Opción seleccionada:', opcionId);
        
        // Aquí puedes agregar la lógica para cada opción
        switch(opcionId) {
            case 'agregar-contratista':
                window.location.href = 'agregar_contratista.php';
                break;
            case 'municipios-card':
                window.location.href = 'SitiosAsignados.php';
            case 'modificar-contratista':
                window.location.href = 'modificar_contratista.php';
                break;
            case 'parametrizar-obligaciones':
                // Mostrar modal de clave para esta opción
                document.getElementById('modalClave').classList.add('active');
                break;
            case 'dashboard-estadistico':
                window.location.href = 'dashboard.php';
                break;
            case 'visor-registrados':
                window.location.href = 'visor_registrados.php';
                break;
        }
    }
    
});