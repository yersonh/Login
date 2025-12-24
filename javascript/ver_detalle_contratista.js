 function descargarCV(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_cv.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarContrato(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_contrato.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarActa(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_acta.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarRP(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_rp.php?id=${idDetalle}`, '_blank');
        }