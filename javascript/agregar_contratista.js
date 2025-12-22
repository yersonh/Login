document.addEventListener('DOMContentLoaded', function() {
    flatpickr.localize(flatpickr.l10ns.es);
    
    const dateOptions = {
        dateFormat: "d/m/Y",
        locale: "es",
        allowInput: true
    };
    
    document.querySelectorAll('input[placeholder*="dd/mm/aaaa"]').forEach(input => {
        flatpickr(input, dateOptions);
    });
    
    // === CALCULAR DURACIÓN DEL CONTRATO ===
    document.getElementById('fecha_inicio').addEventListener('change', calcularDuracionContrato);
    document.getElementById('fecha_final').addEventListener('change', calcularDuracionContrato);
    
    // Hacer el campo de duración de solo lectura
    const duracionContratoInput = document.getElementById('duracion_contrato');
    if (duracionContratoInput) {
        duracionContratoInput.readOnly = true;
        duracionContratoInput.style.backgroundColor = '#f8f9fa';
        duracionContratoInput.style.cursor = 'not-allowed';
    }
    
    // === MANEJO DE CAMPOS DE DIRECCIÓN CONDICIONALES ===
    const municipioSecundario = document.getElementById('id_municipio_secundario');
    const grupoDireccionSecundario = document.getElementById('grupo_direccion_secundario');
    const direccionSecundario = document.getElementById('direccion_municipio_secundario');
    
    const municipioTerciario = document.getElementById('id_municipio_terciario');
    const grupoDireccionTerciario = document.getElementById('grupo_direccion_terciario');
    const direccionTerciario = document.getElementById('direccion_municipio_terciario');

    function toggleDireccionOpcional(selectElement, grupoElement, inputElement) {
        if (selectElement.value && selectElement.value !== '0') {
            grupoElement.style.display = 'block';
            inputElement.required = true;
        } else {
            grupoElement.style.display = 'none';
            inputElement.required = false;
            inputElement.value = '';
        }
    }
    
    // Event listeners para municipio secundario
    if (municipioSecundario) {
        municipioSecundario.addEventListener('change', function() {
            toggleDireccionOpcional(this, grupoDireccionSecundario, direccionSecundario);
        });
    }
    
    // Event listeners para municipio terciario
    if (municipioTerciario) {
        municipioTerciario.addEventListener('change', function() {
            toggleDireccionOpcional(this, grupoDireccionTerciario, direccionTerciario);
        });
    }
    
    // Inicializar estado de los campos de dirección
    if (municipioSecundario && grupoDireccionSecundario && direccionSecundario) {
        toggleDireccionOpcional(municipioSecundario, grupoDireccionSecundario, direccionSecundario);
    }
    
    if (municipioTerciario && grupoDireccionTerciario && direccionTerciario) {
        toggleDireccionOpcional(municipioTerciario, grupoDireccionTerciario, direccionTerciario);
    }
    
    setupFileInput('adjuntar_contrato', 'contratoPreview', 'contratoFilename');
    setupFileInput('adjuntar_acta_inicio', 'actaPreview', 'actaFilename');
    setupFileInput('adjuntar_rp', 'rpPreview', 'rpFilename');
    
    function setupFileInput(inputId, previewId, filenameId) {
        const fileInput = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const filenameSpan = document.getElementById(filenameId);
        
        if (fileInput && preview && filenameSpan) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    const maxSize = 5 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert('El archivo excede el tamaño máximo de 5MB');
                        this.value = '';
                        preview.style.display = 'none';
                        return;
                    }

                    const allowedExtensions = ['.pdf'];
                    if (inputId === 'adjuntar_cv') {
                        allowedExtensions.push('.doc', '.docx');
                    }
                    
                    const fileName = file.name.toLowerCase();
                    const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                    
                    if (!isValidExtension) {
                        const formatos = inputId === 'adjuntar_cv' 
                            ? 'PDF, DOC y DOCX' 
                            : 'PDF';
                        alert(`Solo se permiten archivos ${formatos}`);
                        this.value = '';
                        preview.style.display = 'none';
                        return;
                    }
                    
                    filenameSpan.textContent = file.name;
                    preview.style.display = 'block';
                } else {
                    preview.style.display = 'none';
                }
            });
        }
    }
    
    window.removeContrato = function() {
        const input = document.getElementById('adjuntar_contrato');
        const preview = document.getElementById('contratoPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
    window.removeActaInicio = function() {
        const input = document.getElementById('adjuntar_acta_inicio');
        const preview = document.getElementById('actaPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
    window.removeRP = function() {
        const input = document.getElementById('adjuntar_rp');
        const preview = document.getElementById('rpPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
    const cvInput = document.getElementById('adjuntar_cv');
    const cvPreview = document.getElementById('cvPreview');
    const cvFilename = document.getElementById('cvFilename');
    
    if (cvInput && cvPreview && cvFilename) {
        cvInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('El archivo excede el tamaño máximo de 5MB');
                    this.value = '';
                    cvPreview.style.display = 'none';
                    return;
                }

                const allowedExtensions = ['.pdf', '.doc', '.docx'];
                const fileName = file.name.toLowerCase();
                const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                
                if (!isValidExtension) {
                    alert('Solo se permiten archivos PDF, DOC y DOCX');
                    this.value = '';
                    cvPreview.style.display = 'none';
                    return;
                }

                cvFilename.textContent = file.name;
                cvPreview.style.display = 'block';
            } else {
                cvPreview.style.display = 'none';
            }
        });
    }
    
    window.removeCV = function() {
        if (cvInput) cvInput.value = '';
        if (cvPreview) cvPreview.style.display = 'none';
    };
    
    // === FUNCIÓN: CALCULAR DURACIÓN DEL CONTRATO ===
    function calcularDuracionContrato() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFinal = document.getElementById('fecha_final').value;
        const duracionInput = document.getElementById('duracion_contrato');
        
        if (fechaInicio && fechaFinal) {
            try {
                const [diaInicio, mesInicio, anioInicio] = fechaInicio.split('/');
                const [diaFinal, mesFinal, anioFinal] = fechaFinal.split('/');
                
                const fechaInicioDate = new Date(anioInicio, mesInicio - 1, diaInicio);
                const fechaFinalDate = new Date(anioFinal, mesFinal - 1, diaFinal);
                
                if (fechaFinalDate <= fechaInicioDate) {
                    alert('La fecha final debe ser mayor a la fecha de inicio');
                    duracionInput.value = '';
                    return;
                }
                
                const diferenciaMs = fechaFinalDate - fechaInicioDate;
                const diferenciaDias = Math.floor(diferenciaMs / (1000 * 60 * 60 * 24));
                
                const meses = Math.floor(diferenciaDias / 30);
                const diasRestantes = diferenciaDias % 30;
                
                const años = Math.floor(meses / 12);
                const mesesRestantes = meses % 12;
                
                let duracionTexto = '';
                
                if (años > 0) {
                    duracionTexto += `${años} ${años === 1 ? 'año' : 'años'}`;
                    if (mesesRestantes > 0) {
                        duracionTexto += ` ${mesesRestantes} ${mesesRestantes === 1 ? 'mes' : 'meses'}`;
                    }
                } else if (meses > 0) {
                    duracionTexto += `${meses} ${meses === 1 ? 'mes' : 'meses'}`;
                    if (diasRestantes > 0 && meses < 3) {
                        duracionTexto += ` ${diasRestantes} ${diasRestantes === 1 ? 'día' : 'días'}`;
                    }
                } else {
                    duracionTexto += `${diferenciaDias} ${diferenciaDias === 1 ? 'día' : 'días'}`;
                }
                
                if (duracionInput) {
                    duracionInput.value = duracionTexto;
                }
                
            } catch (error) {
                console.error('Error calculando duración del contrato:', error);
                if (duracionInput) {
                    duracionInput.value = '';
                }
            }
        } else {
            if (duracionInput) {
                duracionInput.value = '';
            }
        }
    }
    
    // === VALIDACIONES EN TIEMPO REAL ===
    function setupValidacionesEnTiempoReal() {
        const camposRequeridos = document.querySelectorAll('[required]');
        
        camposRequeridos.forEach(campo => {
            const errorElement = document.createElement('div');
            errorElement.className = 'validation-message';
            errorElement.style.color = '#dc3545';
            errorElement.style.fontSize = '12px';
            errorElement.style.marginTop = '5px';
            errorElement.style.display = 'none';
            
            campo.parentNode.appendChild(errorElement);
            
            campo.addEventListener('blur', function() {
                validarCampo(this, errorElement);
            });
            
            campo.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e0e0e0';
                    errorElement.style.display = 'none';
                }
            });
            
            if (campo.tagName === 'SELECT') {
                campo.addEventListener('change', function() {
                    validarCampo(this, errorElement);
                });
            }
        });
        
        if (direccionSecundario) {
            direccionSecundario.addEventListener('blur', function() {
                if (grupoDireccionSecundario.style.display === 'block' && !this.value.trim()) {
                    this.style.borderColor = '#dc3545';
                    mostrarError(this, 'Este campo es requerido cuando se selecciona un municipio secundario');
                } else {
                    this.style.borderColor = '#e0e0e0';
                    ocultarError(this);
                }
            });
            
            direccionSecundario.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e0e0e0';
                    ocultarError(this);
                }
            });
        }
        
        if (direccionTerciario) {
            direccionTerciario.addEventListener('blur', function() {
                if (grupoDireccionTerciario.style.display === 'block' && !this.value.trim()) {
                    this.style.borderColor = '#dc3545';
                    mostrarError(this, 'Este campo es requerido cuando se selecciona un municipio terciario');
                } else {
                    this.style.borderColor = '#e0e0e0';
                    ocultarError(this);
                }
            });
            
            direccionTerciario.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#e0e0e0';
                    ocultarError(this);
                }
            });
        }
        
        const camposEspecificos = {
            'cedula': {
                validar: function(valor) {
                    if (valor.length < 5) return 'La cédula debe tener al menos 5 dígitos';
                    if (!/^\d+$/.test(valor)) return 'La cédula solo debe contener números';
                    return null;
                }
            },
            'correo': {
                validar: function(valor) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(valor)) return 'Ingrese un correo electrónico válido';
                    return null;
                }
            },
            'celular': {
                validar: function(valor) {
                    if (valor.length < 10) return 'El celular debe tener al menos 10 dígitos';
                    if (!/^\d+$/.test(valor)) return 'El celular solo debe contener números';
                    return null;
                }
            },
            'nombre_completo': {
                validar: function(valor) {
                    if (valor.split(' ').length < 2) return 'Ingrese nombre y apellido completos';
                    return null;
                }
            }
        };
        
        Object.keys(camposEspecificos).forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.addEventListener('blur', function() {
                    const error = camposEspecificos[id].validar(this.value.trim());
                    if (error) {
                        this.style.borderColor = '#dc3545';
                        mostrarError(this, error);
                    }
                });
                
                campo.addEventListener('input', function() {
                    const error = camposEspecificos[id].validar(this.value.trim());
                    if (!error && this.value.trim()) {
                        this.style.borderColor = '#e0e0e0';
                        ocultarError(this);
                    }
                });
            }
        });
    }
    
    function validarCampo(campo, errorElement) {
        if (!campo.value.trim()) {
            campo.style.borderColor = '#dc3545';
            errorElement.textContent = 'Este campo es requerido';
            errorElement.style.display = 'block';
            return false;
        } else {
            campo.style.borderColor = '#e0e0e0';
            errorElement.style.display = 'none';
            return true;
        }
    }
    
    function mostrarError(campo, mensaje) {
        let errorElement = campo.parentNode.querySelector('.validation-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'validation-message';
            errorElement.style.color = '#dc3545';
            errorElement.style.fontSize = '12px';
            errorElement.style.marginTop = '5px';
            campo.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = mensaje;
        errorElement.style.display = 'block';
    }
    
    function ocultarError(campo) {
        const errorElement = campo.parentNode.querySelector('.validation-message');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }
    
    // Inicializar validaciones en tiempo real
    setupValidacionesEnTiempoReal();
    
    // === CREAR MODAL DE CONFIRMACIÓN ===
    function crearModalConfirmacion() {
        const modalHTML = `
            <div id="confirmModal" class="modal-overlay" style="display: none;">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3><i class="fas fa-clipboard-check"></i> Confirmar Registro</h3>
                        <button class="modal-close" id="closeModal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="confirmation-message">
                            <p><strong>¿Está seguro de registrar al siguiente contratista?</strong></p>
                            <p class="modal-subtitle">Revise los datos antes de continuar:</p>
                        </div>
                        
                        <div class="data-summary">
                            <div class="summary-section">
                                <h4><i class="fas fa-user"></i> Datos Personales</h4>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span class="summary-label">Nombre completo:</span>
                                        <span class="summary-value" id="summaryNombre"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Cédula:</span>
                                        <span class="summary-value" id="summaryCedula"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Correo:</span>
                                        <span class="summary-value" id="summaryCorreo"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Celular:</span>
                                        <span class="summary-value" id="summaryCelular"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Tipo de vinculación:</span>
                                        <span class="summary-value" id="summaryTipoVinculacion"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="summary-section">
                                <h4><i class="fas fa-map-marker-alt"></i> Información Geográfica</h4>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span class="summary-label">Municipio principal:</span>
                                        <span class="summary-value" id="summaryMunicipioPrincipal"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Dirección principal:</span>
                                        <span class="summary-value" id="summaryDireccionPrincipal"></span>
                                    </div>
                                    <div class="summary-item" id="summaryMunicipioSecundarioItem" style="display: none;">
                                        <span class="summary-label">Municipio secundario:</span>
                                        <span class="summary-value" id="summaryMunicipioSecundario"></span>
                                    </div>
                                    <div class="summary-item" id="summaryDireccionSecundariaItem" style="display: none;">
                                        <span class="summary-label">Dirección secundaria:</span>
                                        <span class="summary-value" id="summaryDireccionSecundaria"></span>
                                    </div>
                                    <div class="summary-item" id="summaryMunicipioTerciarioItem" style="display: none;">
                                        <span class="summary-label">Municipio terciario:</span>
                                        <span class="summary-value" id="summaryMunicipioTerciario"></span>
                                    </div>
                                    <div class="summary-item" id="summaryDireccionTerciariaItem" style="display: none;">
                                        <span class="summary-label">Dirección terciaria:</span>
                                        <span class="summary-value" id="summaryDireccionTerciaria"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Área:</span>
                                        <span class="summary-value" id="summaryArea"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="summary-section">
                                <h4><i class="fas fa-file-contract"></i> Información del Contrato</h4>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span class="summary-label">Número de contrato:</span>
                                        <span class="summary-value" id="summaryNumeroContrato"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Fecha contrato:</span>
                                        <span class="summary-value" id="summaryFechaContrato"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Fecha inicio:</span>
                                        <span class="summary-value" id="summaryFechaInicio"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Fecha final:</span>
                                        <span class="summary-value" id="summaryFechaFinal"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Duración:</span>
                                        <span class="summary-value" id="summaryDuracion"></span>
                                    </div>
                                    <div class="summary-item" id="summaryRPItem" style="display: none;">
                                        <span class="summary-label">Número RP:</span>
                                        <span class="summary-value" id="summaryRP"></span>
                                    </div>
                                    <div class="summary-item" id="summaryFechaRPItem" style="display: none;">
                                        <span class="summary-label">Fecha RP:</span>
                                        <span class="summary-value" id="summaryFechaRP"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="summary-section">
                                <h4><i class="fas fa-paperclip"></i> Documentos Adjuntos</h4>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span class="summary-label">CV:</span>
                                        <span class="summary-value" id="summaryCV"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Contrato PDF:</span>
                                        <span class="summary-value" id="summaryContrato"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Acta de inicio:</span>
                                        <span class="summary-value" id="summaryActaInicio"></span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Registro presupuestal:</span>
                                        <span class="summary-value" id="summaryRPArchivo"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-warning">
                            <p><i class="fas fa-exclamation-triangle"></i> <strong>Nota:</strong> Una vez confirmado, los datos no podrán ser modificados desde este formulario.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" id="cancelModalBtn">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button class="btn btn-primary" id="confirmSaveBtn">
                            <i class="fas fa-check"></i> Confirmar y Guardar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Obtener elementos del modal
        const modal = document.getElementById('confirmModal');
        const closeModalBtn = document.getElementById('closeModal');
        const cancelModalBtn = document.getElementById('cancelModalBtn');
        const confirmSaveBtn = document.getElementById('confirmSaveBtn');
        
        // Función para mostrar el modal
        function mostrarModal() {
            // Recopilar datos del formulario
            const datos = recopilarDatosFormulario();
            
            // Llenar el modal con los datos
            llenarModalConDatos(datos);
            
            // Mostrar modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        // Función para ocultar el modal
        function ocultarModal() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Event listeners para el modal
        closeModalBtn.addEventListener('click', ocultarModal);
        cancelModalBtn.addEventListener('click', ocultarModal);
        
        // Cerrar modal al hacer clic fuera del contenido
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                ocultarModal();
            }
        });
        
        // Confirmar y guardar
        confirmSaveBtn.addEventListener('click', function() {
            ocultarModal();
            guardarContratista();
        });
        
        return {
            mostrar: mostrarModal,
            ocultar: ocultarModal
        };
    }
    
    // Función para recopilar datos del formulario
    function recopilarDatosFormulario() {
        const datos = {};
        
        // Obtener valores de los campos
        const campos = [
            'nombre_completo', 'cedula', 'correo', 'celular', 'direccion',
            'numero_contrato', 'fecha_contrato', 'fecha_inicio', 'fecha_final',
            'duracion_contrato', 'numero_registro_presupuestal', 'fecha_rp',
            'direccion_municipio_principal', 'direccion_municipio_secundario',
            'direccion_municipio_terciario'
        ];
        
        campos.forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                datos[id] = elemento.value.trim();
            }
        });
        
        // Obtener textos de selects
        const selects = {
            'id_tipo_vinculacion': 'summaryTipoVinculacion',
            'id_area': 'summaryArea',
            'id_municipio_principal': 'summaryMunicipioPrincipal',
            'id_municipio_secundario': 'summaryMunicipioSecundario',
            'id_municipio_terciario': 'summaryMunicipioTerciario'
        };
        
        Object.keys(selects).forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                const texto = select.options[select.selectedIndex].text;
                datos[selects[id]] = texto === 'Seleccione' || texto === 'Ninguno' ? '' : texto;
            }
        });
        
        // Obtener nombres de archivos
        const archivos = ['adjuntar_cv', 'adjuntar_contrato', 'adjuntar_acta_inicio', 'adjuntar_rp'];
        archivos.forEach(id => {
            const fileInput = document.getElementById(id);
            datos[id] = fileInput && fileInput.files[0] ? fileInput.files[0].name : 'No seleccionado';
        });
        
        return datos;
    }
    
    // Función para llenar el modal con datos
    function llenarModalConDatos(datos) {
        // Datos personales
        document.getElementById('summaryNombre').textContent = datos.nombre_completo || 'No especificado';
        document.getElementById('summaryCedula').textContent = datos.cedula || 'No especificado';
        document.getElementById('summaryCorreo').textContent = datos.correo || 'No especificado';
        document.getElementById('summaryCelular').textContent = datos.celular || 'No especificado';
        document.getElementById('summaryTipoVinculacion').textContent = datos.summaryTipoVinculacion || 'No especificado';
        
        // Información geográfica
        document.getElementById('summaryMunicipioPrincipal').textContent = datos.summaryMunicipioPrincipal || 'No especificado';
        document.getElementById('summaryDireccionPrincipal').textContent = datos.direccion_municipio_principal || 'No especificado';
        
        // Municipios secundarios/terciarios (condicionales)
        const municipioSecundario = datos.summaryMunicipioSecundario;
        const direccionSecundaria = datos.direccion_municipio_secundario;
        
        if (municipioSecundario && municipioSecundario !== '') {
            document.getElementById('summaryMunicipioSecundarioItem').style.display = 'flex';
            document.getElementById('summaryDireccionSecundariaItem').style.display = 'flex';
            document.getElementById('summaryMunicipioSecundario').textContent = municipioSecundario;
            document.getElementById('summaryDireccionSecundaria').textContent = direccionSecundaria || 'No especificado';
        } else {
            document.getElementById('summaryMunicipioSecundarioItem').style.display = 'none';
            document.getElementById('summaryDireccionSecundariaItem').style.display = 'none';
        }
        
        const municipioTerciario = datos.summaryMunicipioTerciario;
        const direccionTerciaria = datos.direccion_municipio_terciario;
        
        if (municipioTerciario && municipioTerciario !== '') {
            document.getElementById('summaryMunicipioTerciarioItem').style.display = 'flex';
            document.getElementById('summaryDireccionTerciariaItem').style.display = 'flex';
            document.getElementById('summaryMunicipioTerciario').textContent = municipioTerciario;
            document.getElementById('summaryDireccionTerciaria').textContent = direccionTerciaria || 'No especificado';
        } else {
            document.getElementById('summaryMunicipioTerciarioItem').style.display = 'none';
            document.getElementById('summaryDireccionTerciariaItem').style.display = 'none';
        }
        
        document.getElementById('summaryArea').textContent = datos.summaryArea || 'No especificado';
        
        // Información del contrato
        document.getElementById('summaryNumeroContrato').textContent = datos.numero_contrato || 'No especificado';
        document.getElementById('summaryFechaContrato').textContent = datos.fecha_contrato || 'No especificado';
        document.getElementById('summaryFechaInicio').textContent = datos.fecha_inicio || 'No especificado';
        document.getElementById('summaryFechaFinal').textContent = datos.fecha_final || 'No especificado';
        document.getElementById('summaryDuracion').textContent = datos.duracion_contrato || 'No especificado';
        
        // RP (condicional)
        if (datos.numero_registro_presupuestal) {
            document.getElementById('summaryRPItem').style.display = 'flex';
            document.getElementById('summaryRP').textContent = datos.numero_registro_presupuestal;
        } else {
            document.getElementById('summaryRPItem').style.display = 'none';
        }
        
        if (datos.fecha_rp) {
            document.getElementById('summaryFechaRPItem').style.display = 'flex';
            document.getElementById('summaryFechaRP').textContent = datos.fecha_rp;
        } else {
            document.getElementById('summaryFechaRPItem').style.display = 'none';
        }
        
        // Documentos adjuntos
        document.getElementById('summaryCV').textContent = datos.adjuntar_cv || 'No seleccionado';
        document.getElementById('summaryContrato').textContent = datos.adjuntar_contrato || 'No seleccionado';
        document.getElementById('summaryActaInicio').textContent = datos.adjuntar_acta_inicio || 'No seleccionado';
        document.getElementById('summaryRPArchivo').textContent = datos.adjuntar_rp || 'No seleccionado';
    }
    
    // Crear el modal al cargar la página
    const modal = crearModalConfirmacion();
    
    // === FUNCIÓN PARA GUARDAR CONTRATISTA (separada) ===
    async function guardarContratista() {
        const btnOriginalHTML = guardarBtn.innerHTML;
        guardarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        guardarBtn.disabled = true;
        
        const formData = new FormData();
        
        const formElements = document.querySelectorAll('input:not([type="file"]), select, textarea');
        formElements.forEach(element => {
            if (element.name && element.value !== undefined) {
                if (!element.name.includes('adjuntar_')) {
                    if ((element.name === 'id_municipio_secundario' || element.name === 'id_municipio_terciario') 
                        && element.value === '0') {
                        formData.append(element.name, '');
                    } else {
                        formData.append(element.name, element.value);
                    }
                }
            }
        });

        const archivos = [
            'adjuntar_cv', 
            'adjuntar_contrato', 
            'adjuntar_acta_inicio', 
            'adjuntar_rp'
        ];
        
        archivos.forEach(id => {
            const fileInput = document.getElementById(id);
            if (fileInput && fileInput.files[0]) {
                formData.append(id, fileInput.files[0]);
            }
        });
        
        try {
            const response = await fetch('../../api/procesar_contratista.php', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await response.json();
            
            if (resultado.success) {
                alert(`¡Contratista registrado exitosamente!\n\nContratista N°: ${resultado.id_detalle}`);

                if (resultado.proximo_consecutivo) {
                    const consecutivoElement = document.querySelector('.consecutivo-number');
                    if (consecutivoElement) {
                        consecutivoElement.textContent = resultado.proximo_consecutivo;
                    }
                }

                limpiarFormulario();

                guardarBtn.innerHTML = btnOriginalHTML;
                guardarBtn.disabled = false;
                const primerCampo = document.getElementById('nombre_completo');
                if (primerCampo) primerCampo.focus();

                // Limpiar todos los mensajes de error
                document.querySelectorAll('.validation-message').forEach(el => {
                    el.style.display = 'none';
                });
                
            } else {
                alert('Error: ' + resultado.error);
                
                guardarBtn.innerHTML = btnOriginalHTML;
                guardarBtn.disabled = false;
            }
            
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión con el servidor. Por favor intente nuevamente.');
            
            guardarBtn.innerHTML = btnOriginalHTML;
            guardarBtn.disabled = false;
        }
    }
    
    // === BOTONES DE NAVEGACIÓN ===
    const volverBtn = document.getElementById('volverBtn');
    if (volverBtn) {
        volverBtn.addEventListener('click', function() {
            window.location.href = 'menuContratistas.php';
        });
    }
    
    const cancelarBtn = document.getElementById('cancelarBtn');
    if (cancelarBtn) {
        cancelarBtn.addEventListener('click', function() {
            if (confirm('¿Está seguro de cancelar? Los datos no guardados se perderán.')) {
                window.location.href = 'menuContratistas.php';
            }
        });
    }
    
    const guardarBtn = document.getElementById('guardarBtn');
    if (guardarBtn) {
        guardarBtn.addEventListener('click', async function() {
            let valid = true;
            let primerCampoInvalido = null;
            
            // Validar campos requeridos
            const requiredFields = document.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    mostrarError(field, 'Este campo es requerido');
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = field;
                    }
                } else {
                    field.style.borderColor = '#e0e0e0';
                    ocultarError(field);
                }
            });
            
            // Validar campos de dirección opcionales
            if (grupoDireccionSecundario && grupoDireccionSecundario.style.display === 'block') {
                if (!direccionSecundario.value.trim()) {
                    direccionSecundario.style.borderColor = '#dc3545';
                    mostrarError(direccionSecundario, 'Este campo es requerido cuando se selecciona un municipio secundario');
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = direccionSecundario;
                    }
                }
            }
            
            if (grupoDireccionTerciario && grupoDireccionTerciario.style.display === 'block') {
                if (!direccionTerciario.value.trim()) {
                    direccionTerciario.style.borderColor = '#dc3545';
                    mostrarError(direccionTerciario, 'Este campo es requerido cuando se selecciona un municipio terciario');
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = direccionTerciario;
                    }
                }
            }
            
            // Validar que la duración del contrato esté calculada
            const duracionContrato = document.getElementById('duracion_contrato');
            if (duracionContrato && !duracionContrato.value.trim()) {
                mostrarError(duracionContrato, 'Complete las fechas de inicio y final para calcular la duración');
                duracionContrato.style.borderColor = '#dc3545';
                valid = false;
                if (!primerCampoInvalido) {
                    primerCampoInvalido = duracionContrato;
                }
            }
            
            // Validaciones específicas adicionales
            const cedula = document.getElementById('cedula');
            if (cedula && cedula.value.trim()) {
                if (cedula.value.trim().length < 5) {
                    mostrarError(cedula, 'La cédula debe tener al menos 5 dígitos');
                    cedula.style.borderColor = '#dc3545';
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = cedula;
                    }
                }
                if (!/^\d+$/.test(cedula.value.trim())) {
                    mostrarError(cedula, 'La cédula solo debe contener números');
                    cedula.style.borderColor = '#dc3545';
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = cedula;
                    }
                }
            }
            
            const correo = document.getElementById('correo');
            if (correo && correo.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(correo.value.trim())) {
                    mostrarError(correo, 'Ingrese un correo electrónico válido');
                    correo.style.borderColor = '#dc3545';
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = correo;
                    }
                }
            }
            
            const celular = document.getElementById('celular');
            if (celular && celular.value.trim()) {
                if (celular.value.trim().length < 10) {
                    mostrarError(celular, 'El celular debe tener al menos 10 dígitos');
                    celular.style.borderColor = '#dc3545';
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = celular;
                    }
                }
                if (!/^\d+$/.test(celular.value.trim())) {
                    mostrarError(celular, 'El celular solo debe contener números');
                    celular.style.borderColor = '#dc3545';
                    valid = false;
                    if (!primerCampoInvalido) {
                        primerCampoInvalido = celular;
                    }
                }
            }
            
            if (!valid) {
                alert('Por favor complete todos los campos obligatorios (*) correctamente');
                if (primerCampoInvalido) {
                    primerCampoInvalido.focus();
                }
                return;
            }
            
            // Validar fechas
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFinal = document.getElementById('fecha_final').value;
            
            if (fechaInicio && fechaFinal) {
                const inicio = new Date(fechaInicio.split('/').reverse().join('-'));
                const final = new Date(fechaFinal.split('/').reverse().join('-'));
                
                if (inicio > final) {
                    alert('La fecha de inicio no puede ser mayor a la fecha final');
                    document.getElementById('fecha_inicio').style.borderColor = '#dc3545';
                    document.getElementById('fecha_final').style.borderColor = '#dc3545';
                    mostrarError(document.getElementById('fecha_inicio'), 'La fecha de inicio debe ser menor a la fecha final');
                    mostrarError(document.getElementById('fecha_final'), 'La fecha final debe ser mayor a la fecha de inicio');
                    return;
                }
            }
            
            // Mostrar modal de confirmación en lugar de guardar directamente
            modal.mostrar();
        });
    }
    
    function limpiarFormulario() {
        // 1. Campos de texto principales
        const camposTextoLimpios = [
            'nombre_completo', 'cedula', 'correo', 'celular', 'direccion',
            'numero_contrato', 'duracion_contrato', 'numero_registro_presupuestal',
            'direccion_municipio_principal', 'direccion_municipio_secundario', 
            'direccion_municipio_terciario'
        ];
        
        camposTextoLimpios.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.value = '';
        });

        const fechaCampos = ['fecha_contrato', 'fecha_inicio', 'fecha_final', 'fecha_rp'];
        fechaCampos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                campo.value = '';
                if (campo._flatpickr) {
                    campo._flatpickr.clear();
                }
            }
        });
        
        const selectsLimpiar = [
            'id_tipo_vinculacion', 
            'id_area', 
            'id_municipio_secundario', 
            'id_municipio_terciario'
        ];
        
        selectsLimpiar.forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                select.selectedIndex = 0;
            }
        });

        const municipioPrincipal = document.getElementById('id_municipio_principal');
        if (municipioPrincipal) {
            municipioPrincipal.selectedIndex = 0;
            Array.from(municipioPrincipal.options).forEach(option => {
                if (option.text.includes('Villavicencio')) {
                    option.selected = true;
                }
            });
        }

        const archivos = [
            'adjuntar_cv', 
            'adjuntar_contrato', 
            'adjuntar_acta_inicio', 
            'adjuntar_rp'
        ];
        
        archivos.forEach(id => {
            const fileInput = document.getElementById(id);
            const previewId = id === 'adjuntar_cv' ? 'cvPreview' : 
                             id === 'adjuntar_contrato' ? 'contratoPreview' :
                             id === 'adjuntar_acta_inicio' ? 'actaPreview' : 'rpPreview';
            const preview = document.getElementById(previewId);
            
            if (fileInput) fileInput.value = '';
            if (preview) preview.style.display = 'none';
        });
        
        if (grupoDireccionSecundario) grupoDireccionSecundario.style.display = 'none';
        if (grupoDireccionTerciario) grupoDireccionTerciario.style.display = 'none';
        
        // Restablecer estilo del campo de duración
        const duracionContratoInput = document.getElementById('duracion_contrato');
        if (duracionContratoInput) {
            duracionContratoInput.style.borderColor = '#e0e0e0';
        }
        
        // Limpiar todos los mensajes de error
        document.querySelectorAll('.validation-message').forEach(el => {
            el.style.display = 'none';
        });
    }

    const municipioPrincipal = document.getElementById('id_municipio_principal');
    if (municipioPrincipal) {
        municipioPrincipal.addEventListener('change', function() {
            const principalId = this.value;
            const principalNombre = this.options[this.selectedIndex].text;
            const secundario = document.getElementById('id_municipio_secundario');
            const terciario = document.getElementById('id_municipio_terciario');
            
            if (principalId && secundario && !secundario.value) {
                if (confirm(`¿Desea asignar "${principalNombre}" como municipio secundario?`)) {
                    secundario.value = principalId;
                    toggleDireccionOpcional(secundario, grupoDireccionSecundario, direccionSecundario);
                }
            }
            
            if (principalId && terciario && !terciario.value) {
                if (confirm(`¿Desea asignar "${principalNombre}" como municipio terciario?`)) {
                    terciario.value = principalId;
                    toggleDireccionOpcional(terciario, grupoDireccionTerciario, direccionTerciario);
                }
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
    
    function actualizarHora() {
        const now = new Date();
        const options = { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true 
        };
        const fechaHora = now.toLocaleDateString('es-ES', options).replace(',', '');
        const datetimeElement = document.querySelector('.datetime-display');
        if (datetimeElement) {
            datetimeElement.innerHTML = `<i class="fas fa-clock"></i> Ahora: ${fechaHora}`;
        }
    }
    
    setInterval(actualizarHora, 1000);
    actualizarHora();
});