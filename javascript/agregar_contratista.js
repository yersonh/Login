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
    
    // === CONFIGURACIÓN DE ARCHIVOS - NUEVO: FOTO DE PERFIL ===
    setupFotoPerfil();
    setupFileInput('adjuntar_cv', 'cvPreview', 'cvFilename', ['pdf', 'doc', 'docx']);
    setupFileInput('adjuntar_contrato', 'contratoPreview', 'contratoFilename', ['pdf']);
    setupFileInput('adjuntar_acta_inicio', 'actaPreview', 'actaFilename', ['pdf']);
    setupFileInput('adjuntar_rp', 'rpPreview', 'rpFilename', ['pdf']);
    
    // === FUNCIÓN PARA MANEJAR FOTO DE PERFIL ===
    function setupFotoPerfil() {
        const fotoInput = document.getElementById('foto_perfil');
        const fotoPreview = document.getElementById('fotoPreviewImg');
        const fotoPlaceholder = document.querySelector('.foto-placeholder');
        const fotoError = document.getElementById('fotoError');
        const fotoErrorMessage = document.getElementById('fotoErrorMessage');
        
        if (fotoInput) {
            fotoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Validar tamaño máximo (10MB para fotos)
                    const maxSize = 10 * 1024 * 1024;
                    if (file.size > maxSize) {
                        mostrarErrorFoto('La imagen excede el tamaño máximo de 10MB');
                        this.value = '';
                        resetearFotoPreview();
                        return;
                    }
                    
                    // Validar tipo de imagen
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        mostrarErrorFoto('Solo se permiten imágenes JPG, JPEG, PNG o GIF');
                        this.value = '';
                        resetearFotoPreview();
                        return;
                    }
                    
                    // Leer y mostrar la imagen
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fotoPreview.src = e.target.result;
                        fotoPreview.style.display = 'block';
                        if (fotoPlaceholder) fotoPlaceholder.style.display = 'none';
                        ocultarErrorFoto();
                    };
                    reader.onerror = function() {
                        mostrarErrorFoto('Error al cargar la imagen');
                        resetearFotoPreview();
                    };
                    reader.readAsDataURL(file);
                } else {
                    resetearFotoPreview();
                }
            });
            
            // Efecto de arrastrar y soltar
            const fotoContainer = document.querySelector('.foto-container');
            if (fotoContainer) {
                fotoContainer.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('highlight');
                });
                
                fotoContainer.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('highlight');
                });
                
                fotoContainer.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('highlight');
                    
                    if (e.dataTransfer.files.length) {
                        const file = e.dataTransfer.files[0];
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fotoInput.files = dataTransfer.files;
                        
                        // Disparar evento change
                        const event = new Event('change', { bubbles: true });
                        fotoInput.dispatchEvent(event);
                    }
                });
            }
        }
        
        function mostrarErrorFoto(mensaje) {
            if (fotoError && fotoErrorMessage) {
                fotoErrorMessage.textContent = mensaje;
                fotoError.style.display = 'flex';
            }
        }
        
        function ocultarErrorFoto() {
            if (fotoError) {
                fotoError.style.display = 'none';
            }
        }
        
        function resetearFotoPreview() {
            if (fotoPreview) {
                fotoPreview.src = '';
                fotoPreview.style.display = 'none';
            }
            if (fotoPlaceholder) {
                fotoPlaceholder.style.display = 'flex';
            }
            ocultarErrorFoto();
        }
        
        // Botón para eliminar foto
        window.removeFotoPerfil = function() {
            if (fotoInput) fotoInput.value = '';
            resetearFotoPreview();
        };
    }
    
    function setupFileInput(inputId, previewId, filenameId, allowedExtensions) {
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

                    const fileName = file.name.toLowerCase();
                    const isValidExtension = allowedExtensions.some(ext => fileName.endsWith('.' + ext));
                    
                    if (!isValidExtension) {
                        const formatos = allowedExtensions.map(ext => ext.toUpperCase()).join(', ');
                        alert(`Solo se permiten archivos: ${formatos}`);
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
    
    // Funciones para eliminar archivos
    window.removeCV = function() {
        const input = document.getElementById('adjuntar_cv');
        const preview = document.getElementById('cvPreview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    };
    
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
            },
            'profesion': {
                validar: function(valor) {
                    if (valor.length > 100) return 'La profesión no puede exceder los 100 caracteres';
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
    
    // === CREAR MODAL DE CONFIRMACIÓN (ACTUALIZADO) ===
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
                        <!-- SECCIÓN DE FOTO Y DATOS BÁSICOS -->
                        <div class="summary-section" style="display: flex; flex-direction: column;">
                            <h4><i class="fas fa-user"></i> Información del Contratista</h4>
                            <div style="display: flex; gap: 30px; margin-top: 15px;">
                                <!-- Columna izquierda: Foto -->
                                <div style="flex: 0 0 200px;">
                                    <div style="margin-bottom: 10px; font-weight: 600; color: var(--dark-color);">
                                        Foto de perfil:
                                    </div>
                                    <div id="modalFotoPreview" style="width: 200px; height: 200px; border-radius: 10px; 
                                         border: 2px solid #e9ecef; overflow: hidden; display: flex; 
                                         align-items: center; justify-content: center; background: #f8f9fa;">
                                        <div id="modalFotoPlaceholder" style="text-align: center; color: #6c757d;">
                                            <i class="fas fa-user-circle" style="font-size: 60px; color: #adb5bd; margin-bottom: 10px; display: block;"></i>
                                            <span style="display: block; font-size: 14px; font-weight: 500;">Sin foto</span>
                                        </div>
                                        <img id="modalFotoImg" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div id="modalFotoNombre" style="margin-top: 8px; font-size: 12px; color: #6c757d; text-align: center;">
                                        No seleccionada
                                    </div>
                                </div>
                                
                                <!-- Columna derecha: Datos básicos -->
                                <div style="flex: 1;">
                                    <div class="summary-grid" style="grid-template-columns: repeat(2, 1fr);">
                                        <div class="summary-item">
                                            <span class="summary-label">Nombre completo:</span>
                                            <span class="summary-value" id="summaryNombre"></span>
                                        </div>
                                        <div class="summary-item">
                                            <span class="summary-label">Profesión:</span>
                                            <span class="summary-value" id="summaryProfesion"></span>
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
    
    // Elementos de la foto en el modal
    const modalFotoImg = document.getElementById('modalFotoImg');
    const modalFotoPlaceholder = document.getElementById('modalFotoPlaceholder');
    const modalFotoNombre = document.getElementById('modalFotoNombre');
        
    function mostrarFotoEnModal(file) {
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                modalFotoImg.src = e.target.result;
                modalFotoImg.style.display = 'block';
                modalFotoPlaceholder.style.display = 'none';
                modalFotoNombre.textContent = file.name;
            };
            reader.readAsDataURL(file);
        } else {
            modalFotoImg.style.display = 'none';
            modalFotoPlaceholder.style.display = 'block';
            modalFotoNombre.textContent = 'No seleccionada';
        }
    }
        // Función para mostrar el modal
        function mostrarModal() {
        // Recopilar datos del formulario
        const datos = recopilarDatosFormulario();
        
        // Llenar el modal con los datos
        llenarModalConDatos(datos);
        
        // Mostrar foto en el modal si existe
        const fotoInput = document.getElementById('foto_perfil');
        if (fotoInput && fotoInput.files[0]) {
            mostrarFotoEnModal(fotoInput.files[0]);
        } else {
            mostrarFotoEnModal(null);
        }
        
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
    
    // Función para recopilar datos del formulario (ACTUALIZADA)
    function recopilarDatosFormulario() {
        const datos = {};
        
        // Obtener valores de los campos (incluye profesion)
        const campos = [
            'nombre_completo', 'cedula', 'correo', 'celular', 'direccion', 'profesion',
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
        
        // Obtener nombres de archivos (incluye foto_perfil)
        const archivos = [
            'foto_perfil', 'adjuntar_cv', 'adjuntar_contrato', 
            'adjuntar_acta_inicio', 'adjuntar_rp'
        ];
        
        archivos.forEach(id => {
            const fileInput = document.getElementById(id);
            if (fileInput && fileInput.files[0]) {
                datos[id] = fileInput.files[0].name;
            } else {
                datos[id] = id === 'foto_perfil' ? 'No seleccionada' : 'No seleccionado';
            }
        });
        
        return datos;
    }
    
    // Función para llenar el modal con datos (ACTUALIZADA)
    function llenarModalConDatos(datos) {
    // Datos personales (con profesion)
    document.getElementById('summaryNombre').textContent = datos.nombre_completo || 'No especificado';
    document.getElementById('summaryProfesion').textContent = datos.profesion || 'No especificado';
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
    
    // === FUNCIÓN PARA GUARDAR CONTRATISTA (ACTUALIZADA) ===
    async function guardarContratista() {
        const btnOriginalHTML = guardarBtn.innerHTML;
        guardarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        guardarBtn.disabled = true;
        
        const formData = new FormData();
        
        // Agregar todos los campos del formulario (excepto archivos que se manejan aparte)
        const formElements = document.querySelectorAll('input:not([type="file"]), select, textarea');
        formElements.forEach(element => {
            if (element.name && element.value !== undefined) {
                if (!element.name.includes('adjuntar_') && element.name !== 'foto_perfil') {
                    if ((element.name === 'id_municipio_secundario' || element.name === 'id_municipio_terciario') 
                        && element.value === '0') {
                        formData.append(element.name, '');
                    } else {
                        formData.append(element.name, element.value);
                    }
                }
            }
        });

        // Agregar archivos (incluye foto_perfil)
        const archivos = [
            'foto_perfil', 'adjuntar_cv', 'adjuntar_contrato', 
            'adjuntar_acta_inicio', 'adjuntar_rp'
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

            // Limpiar formulario COMPLETAMENTE (incluye errores)
            limpiarFormulario();

            // Enfocar el primer campo para nuevo registro
            const primerCampo = document.getElementById('nombre_completo');
            if (primerCampo) {
                primerCampo.focus();
                // Asegurar que no tenga estilo de error
                primerCampo.style.borderColor = '#e0e0e0';
            }

            // Resetear estado del botón
            guardarBtn.innerHTML = btnOriginalHTML;
            guardarBtn.disabled = false;
            
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
            const camposValidar = {
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
                },
                'profesion': {
                    validar: function(valor) {
                        if (valor.length > 100) return 'La profesión no puede exceder los 100 caracteres';
                        return null;
                    }
                }
            };
            
            Object.keys(camposValidar).forEach(id => {
                const campo = document.getElementById(id);
                if (campo && campo.value.trim()) {
                    const error = camposValidar[id].validar(campo.value.trim());
                    if (error) {
                        mostrarError(campo, error);
                        campo.style.borderColor = '#dc3545';
                        valid = false;
                        if (!primerCampoInvalido) {
                            primerCampoInvalido = campo;
                        }
                    }
                }
            });
            
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
            
            // Validar foto de perfil (tamaño y tipo)
            const fotoInput = document.getElementById('foto_perfil');
            if (fotoInput && fotoInput.files[0]) {
                const file = fotoInput.files[0];
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('La foto de perfil excede el tamaño máximo de 10MB');
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Solo se permiten imágenes JPG, JPEG, PNG o GIF para la foto de perfil');
                    return;
                }
            }
            
            // Mostrar modal de confirmación en lugar de guardar directamente
            modal.mostrar();
        });
    }
    
    function limpiarFormulario() {
    // 1. Campos de texto principales (incluye profesion)
    const camposTextoLimpios = [
        'nombre_completo', 'cedula', 'correo', 'celular', 'direccion', 'profesion',
        'numero_contrato', 'duracion_contrato', 'numero_registro_presupuestal',
        'direccion_municipio_principal', 'direccion_municipio_secundario', 
        'direccion_municipio_terciario'
    ];
    
    camposTextoLimpios.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.value = '';
            // RESET ESTILO DE VALIDACIÓN
            campo.style.borderColor = '#e0e0e0';
        }
    });

    // 2. Campos de fecha
    const fechaCampos = ['fecha_contrato', 'fecha_inicio', 'fecha_final', 'fecha_rp'];
    fechaCampos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.value = '';
            // RESET ESTILO DE VALIDACIÓN
            campo.style.borderColor = '#e0e0e0';
            if (campo._flatpickr) {
                campo._flatpickr.clear();
            }
        }
    });
    
    // 3. Selects
    const selectsLimpiar = [
        'id_tipo_vinculacion', 'id_area', 
        'id_municipio_secundario', 'id_municipio_terciario'
    ];
    
    selectsLimpiar.forEach(id => {
        const select = document.getElementById(id);
        if (select) {
            select.selectedIndex = 0;
            // RESET ESTILO DE VALIDACIÓN
            select.style.borderColor = '#e0e0e0';
        }
    });

    // 4. Municipio principal (restaurar a Villavicencio)
    const municipioPrincipal = document.getElementById('id_municipio_principal');
    if (municipioPrincipal) {
        municipioPrincipal.selectedIndex = 0;
        Array.from(municipioPrincipal.options).forEach(option => {
            if (option.text.includes('Villavicencio')) {
                option.selected = true;
            }
        });
        // RESET ESTILO DE VALIDACIÓN
        municipioPrincipal.style.borderColor = '#e0e0e0';
    }

    // 5. Archivos (incluye foto_perfil)
    const archivos = [
        'foto_perfil', 'adjuntar_cv', 'adjuntar_contrato', 
        'adjuntar_acta_inicio', 'adjuntar_rp'
    ];
    
    archivos.forEach(id => {
        const fileInput = document.getElementById(id);
        if (fileInput) fileInput.value = '';
    });
    
    // 6. Resetear vista previa de foto
    const fotoPreview = document.getElementById('fotoPreviewImg');
    const fotoPlaceholder = document.querySelector('.foto-placeholder');
    if (fotoPreview) {
        fotoPreview.src = '';
        fotoPreview.style.display = 'none';
    }
    if (fotoPlaceholder) {
        fotoPlaceholder.style.display = 'flex';
    }
    
    // 7. Ocultar vistas previas de otros archivos
    const previews = ['cvPreview', 'contratoPreview', 'actaPreview', 'rpPreview'];
    previews.forEach(id => {
        const preview = document.getElementById(id);
        if (preview) preview.style.display = 'none';
    });
    
    // 8. Ocultar grupos de dirección
    if (grupoDireccionSecundario) {
        grupoDireccionSecundario.style.display = 'none';
        const direccionSec = document.getElementById('direccion_municipio_secundario');
        if (direccionSec) {
            direccionSec.value = '';
            direccionSec.style.borderColor = '#e0e0e0';
        }
    }
    
    if (grupoDireccionTerciario) {
        grupoDireccionTerciario.style.display = 'none';
        const direccionTer = document.getElementById('direccion_municipio_terciario');
        if (direccionTer) {
            direccionTer.value = '';
            direccionTer.style.borderColor = '#e0e0e0';
        }
    }
    
    // 9. Restablecer estilo del campo de duración
    const duracionContratoInput = document.getElementById('duracion_contrato');
    if (duracionContratoInput) {
        duracionContratoInput.style.borderColor = '#e0e0e0';
        duracionContratoInput.style.backgroundColor = '#f8f9fa';
    }
    
    // 10. Limpiar todos los mensajes de error (ESTO ES LO MÁS IMPORTANTE)
    document.querySelectorAll('.validation-message').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });
    
    // 11. También limpiar errores específicos que puedas haber creado
    document.querySelectorAll('[style*="border-color: #dc3545"]').forEach(el => {
        el.style.borderColor = '#e0e0e0';
    });
    
    // 12. Resetear el mensaje de error de la foto
    const fotoError = document.getElementById('fotoError');
    if (fotoError) {
        fotoError.style.display = 'none';
    }
}

    // Funcionalidad adicional para municipios
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

    // Prevenir submit con Enter
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
    
    // Actualizar hora en tiempo real
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