CREATE TABLE public.persona (
    id_persona SERIAL PRIMARY KEY,
    nombres VARCHAR(50) NOT NULL,
    apellidos VARCHAR(50) NOT NULL,
    telefono VARCHAR(15),
    cedula VARCHAR(20),
    fecha_registro TIMESTAMP,
    correo_personal VARCHAR(100),
    profesion VARCHAR(100)
);

ALTER TABLE public.persona
ADD CONSTRAINT uq_persona_cedula UNIQUE (cedula);


CREATE TABLE public.usuario (
    id_usuario SERIAL PRIMARY KEY,
    id_persona INT4 NOT NULL,
    correo VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP,
    tipo_usuario VARCHAR(20),
    activo BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP,
    aprobado_por INT4,
    fecha_aprobacion TIMESTAMP,
    notificado_aprobacion BOOLEAN DEFAULT false
);

ALTER TABLE public.usuario
ADD CONSTRAINT fk_usuario_persona
FOREIGN KEY (id_persona)
REFERENCES public.persona (id_persona)
ON DELETE CASCADE;

ALTER TABLE public.usuario
ADD CONSTRAINT uq_usuario_correo UNIQUE (correo);

ALTER TABLE public.usuario
ADD CONSTRAINT chk_usuario_tipo
CHECK (tipo_usuario IN ('contratista', 'asistente', 'administrador', 'superAdmin'));


CREATE TABLE public.recovery_tokens (
    id_recovery SERIAL PRIMARY KEY,
    id_usuario INT4 NOT NULL,
    token VARCHAR(64) NOT NULL,
    expiracion TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT false,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE public.recovery_tokens
ADD CONSTRAINT fk_recovery_tokens_usuario
FOREIGN KEY (id_usuario)
REFERENCES public.usuario (id_usuario)
ON DELETE CASCADE;
CREATE UNIQUE INDEX idx_recovery_token
ON public.recovery_tokens (token);

CREATE TABLE public.remember_tokens (
    id_token SERIAL PRIMARY KEY,
    id_usuario INT4 NOT NULL,
    token VARCHAR(64) NOT NULL,
    expiracion TIMESTAMP NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE public.remember_tokens
ADD CONSTRAINT fk_remember_tokens_usuario
FOREIGN KEY (id_usuario)
REFERENCES public.usuario (id_usuario)
ON DELETE CASCADE;
CREATE UNIQUE INDEX idx_remember_token
ON public.remember_tokens (token);

CREATE TABLE parametrizar (
    id_parametrizacion SERIAL PRIMARY KEY,
    version_sistema VARCHAR(50) NOT NULL,
    tipo_licencia VARCHAR(50) NOT NULL,
    valida_hasta DATE,
    desarrollado_por VARCHAR(100),
    direccion TEXT,
    correo_contacto VARCHAR(100),
    telefono VARCHAR(50),
    ruta_logo VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enlace_web VARCHAR(255),
    cantidad VARCHAR(255),
    dias_restantes INT,
    nit VARCHAR(100)
);

CREATE TABLE fotos_perfil (
    id_foto SERIAL PRIMARY KEY,
    id_persona INTEGER NOT NULL,
    A2_nombre_archivo VARCHAR(255) NOT NULL,
    A2_tipo_mime VARCHAR(50) NOT NULL,
    contenido BYTEA NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índice para búsquedas por persona
CREATE INDEX idx_fotos_perfil_id_persona ON fotos_perfil(id_persona);

CREATE TABLE municipio (
    id_municipio SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    codigo_dane VARCHAR(50) UNIQUE NOT NULL
);

-- Índice para búsquedas rápidas por estado activo
CREATE INDEX idx_municipio_activo ON municipio(activo);

CREATE TABLE area (
    id_area SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    codigo_area VARCHAR(20) UNIQUE NOT NULL,
    descripcion TEXT
);
-- Índice para búsquedas por nombre
CREATE INDEX idx_area_nombre ON area(nombre);

-- Índice para búsquedas por estado activo
CREATE INDEX idx_area_activo ON area(activo);

CREATE TABLE tipo_vinculacion (
    id_tipo SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo BOOLEAN DEFAULT TRUE,
    descripcion TEXT,
    codigo VARCHAR(50) UNIQUE NOT NULL
);

-- Índice para búsquedas por estado activo
CREATE INDEX idx_tipo_vinculacion_activo ON tipo_vinculacion(activo);

CREATE TABLE detalle_contrato (
    id_detalle SERIAL PRIMARY KEY,
    id_persona INTEGER NOT NULL,
    id_area INTEGER NOT NULL,
    id_tipo_vinculacion INTEGER NOT NULL,
    id_municipio_principal INTEGER,
    id_municipio_secundario INTEGER,
    id_municipio_terciario INTEGER,
    numero_contrato VARCHAR(50),
    fecha_contrato DATE,
    fecha_inicio DATE,
    fecha_final DATE,
    duracion_contrato VARCHAR(50),
    numero_registro_presupuestal VARCHAR(50),
    fecha_rp DATE,
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cv_archivo BYTEA,
    cv_nombre_original VARCHAR(255),
    cv_tipo_mime VARCHAR(100),
    cv_tamano INTEGER,
    direccion_municipio_principal TEXT,
    direccion_municipio_secundario TEXT,
    direccion_municipio_terciario TEXT,
    contrato_archivo BYTEA,
    contrato_nombre_original VARCHAR(255),
    contrato_tipo_mime VARCHAR(100),
    contrato_tamano INTEGER,
    acta_inicio_archivo BYTEA,
    acta_inicio_nombre_original VARCHAR(255),
    acta_inicio_tipo_mime VARCHAR(100),
    acta_inicio_tamano INTEGER,
    rp_archivo BYTEA,
    rp_nombre_original VARCHAR(255),
    rp_tipo_mime VARCHAR(100),
    rp_tamano INTEGER
);

-- Índices para búsquedas frecuentes
CREATE INDEX idx_detalle_contrato_id_persona ON detalle_contrato(id_persona);
CREATE INDEX idx_detalle_contrato_id_area ON detalle_contrato(id_area);
CREATE INDEX idx_detalle_contrato_id_tipo_vinculacion ON detalle_contrato(id_tipo_vinculacion);
CREATE INDEX idx_detalle_contrato_fecha_inicio ON detalle_contrato(fecha_inicio);
CREATE INDEX idx_detalle_contrato_fecha_final ON detalle_contrato(fecha_final);
CREATE INDEX idx_detalle_contrato_numero_contrato ON detalle_contrato(numero_contrato);

-- Índices para municipios
CREATE INDEX idx_detalle_contrato_municipio_principal ON detalle_contrato(id_municipio_principal);
CREATE INDEX idx_detalle_contrato_municipio_secundario ON detalle_contrato(id_municipio_secundario);
CREATE INDEX idx_detalle_contrato_municipio_terciario ON detalle_contrato(id_municipio_terciario);

-- Restricciones de clave foránea completas
ALTER TABLE detalle_contrato 
ADD CONSTRAINT fk_detalle_contrato_persona 
FOREIGN KEY (id_persona) REFERENCES persona(id_persona);

ALTER TABLE detalle_contrato 
ADD CONSTRAINT fk_detalle_contrato_area 
FOREIGN KEY (id_area) REFERENCES area(id_area);

ALTER TABLE detalle_contrato 
ADD CONSTRAINT fk_detalle_contrato_tipo_vinculacion 
FOREIGN KEY (id_tipo_vinculacion) REFERENCES tipo_vinculacion(id_tipo);

ALTER TABLE detalle_contrato 
ADD CONSTRAINT fk_detalle_contrato_municipio_principal 
FOREIGN KEY (id_municipio_principal) REFERENCES municipio(id_municipio);

ALTER TABLE detalle_contrato 
ADD CONSTRAINT fk_detalle_contrato_municipio_secundario 
FOREIGN KEY (id_municipio_secundario) REFERENCES municipio(id_municipio);

ALTER TABLE detalle_contrato 
ADD CONSTRAINT fk_detalle_contrato_municipio_terciario 
FOREIGN KEY (id_municipio_terciario) REFERENCES municipio(id_municipio);