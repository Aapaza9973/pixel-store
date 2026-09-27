-- =====================================================
-- TIPOS ENUMERADOS
-- =====================================================
CREATE TYPE enum_categoria_tipo        AS ENUM ('Componente','Periférico','Accesorio','Equipo','Software');
CREATE TYPE enum_atributo_tipo_dato    AS ENUM ('string','integer','decimal','boolean','enum');
CREATE TYPE enum_numero_serie_estado   AS ENUM ('disponible','vendido','reservado','devuelto','defectuoso','baja');
CREATE TYPE enum_ubicacion_tipo        AS ENUM ('tienda','deposito');
CREATE TYPE enum_cotizacion_estado     AS ENUM ('Vigente','Vencida','Convertida','Anulada');
CREATE TYPE enum_proveedor_tipo        AS ENUM ('Mayorista','Importador','Distribuidor','Local');
CREATE TYPE enum_orden_compra_estado   AS ENUM ('Borrador','Enviada','Recibida','Cancelada');
CREATE TYPE enum_venta_estado          AS ENUM ('Pendiente','Pagado','Cancelada');
CREATE TYPE enum_pago_metodo           AS ENUM ('Efectivo','Tarjeta','Transferencia','QR','Otro');
CREATE TYPE enum_pago_estado           AS ENUM ('Completado','Pendiente','Cancelado');
CREATE TYPE enum_movimiento_tipo       AS ENUM ('entrada','salida','ajuste','venta','devolucion','baja');
CREATE TYPE enum_alerta_tipo           AS ENUM ('bajo_stock','sin_stock');
CREATE TYPE enum_devolucion_estado     AS ENUM ('Pendiente','Aprobada','Rechazada');
CREATE TYPE enum_pedido_estado         AS ENUM ('Pendiente','Confirmado','Cancelado');
CREATE TYPE enum_pedido_estado_pago    AS ENUM ('Pendiente','Pagado','Fallido');
CREATE TYPE enum_puntos_tipo           AS ENUM ('acumulado','canjeado','ajuste');
CREATE TYPE enum_factura_estado        AS ENUM ('Pendiente','Emitida','Anulada','Error');
CREATE TYPE enum_regla_tipo            AS ENUM ('igual','rango','enum');
CREATE TYPE enum_respaldo_estado       AS ENUM ('exitoso','fallido');
CREATE TYPE enum_papel_comprobante     AS ENUM ('termico','carta');

-- =====================================================
-- USUARIOS Y ROLES
-- =====================================================
CREATE TABLE users (
    id                     BIGSERIAL PRIMARY KEY,
    name                   VARCHAR(255) NOT NULL,
    email                  VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at      TIMESTAMP NULL,
    password               VARCHAR(255) NOT NULL,
    telefono               VARCHAR(30) NULL,
    nit_ci                 VARCHAR(20) NULL,
    pref_papel_comprobante enum_papel_comprobante NOT NULL DEFAULT 'termico',
    pref_imprimir_pos      BOOLEAN NOT NULL DEFAULT FALSE,
    activo                 BOOLEAN NOT NULL DEFAULT TRUE,
    remember_token         VARCHAR(100) NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT now(),
    updated_at             TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_users_activo ON users (activo);

CREATE TABLE roles (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    guard_name  VARCHAR(255) NOT NULL DEFAULT 'web',
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP NOT NULL DEFAULT now(),
    UNIQUE (name, guard_name)
);

CREATE TABLE permissions (
    id          BIGSERIAL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    guard_name  VARCHAR(255) NOT NULL DEFAULT 'web',
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP NOT NULL DEFAULT now(),
    UNIQUE (name, guard_name)
);

CREATE TABLE model_has_roles (
    role_id     BIGINT NOT NULL,
    model_type  VARCHAR(255) NOT NULL,
    model_id    BIGINT NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type)
);

CREATE TABLE model_has_permissions (
    permission_id BIGINT NOT NULL,
    model_type    VARCHAR(255) NOT NULL,
    model_id      BIGINT NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type)
);

CREATE TABLE role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id       BIGINT NOT NULL,
    PRIMARY KEY (permission_id, role_id)
);

-- =====================================================
-- CLIENTES
-- =====================================================
CREATE TABLE clientes (
    id            BIGSERIAL PRIMARY KEY,
    user_id       BIGINT NULL,
    nombre        VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NULL,
    telefono      VARCHAR(30) NULL,
    nit_ci        VARCHAR(20) NULL,
    direccion     VARCHAR(255) NULL,
    es_frecuente  BOOLEAN NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- CATÁLOGO: CATEGORÍAS, MARCAS, PRODUCTOS, ATRIBUTOS
-- =====================================================
CREATE TABLE categorias (
    id                    BIGSERIAL PRIMARY KEY,
    nombre                VARCHAR(255) NOT NULL UNIQUE,
    tipo                  enum_categoria_tipo NOT NULL,
    atributos_aplicables  JSONB NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT now(),
    updated_at            TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE marcas (
    id          BIGSERIAL PRIMARY KEY,
    nombre      VARCHAR(255) NOT NULL UNIQUE,
    logo_url    VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE productos (
    id                    BIGSERIAL PRIMARY KEY,
    categoria_id          BIGINT NOT NULL,
    marca_id              BIGINT NULL,
    nombre                VARCHAR(255) NOT NULL UNIQUE,
    descripcion           TEXT NULL,
    precio_unitario       DECIMAL(10,2) NOT NULL,
    costo                 DECIMAL(10,2) NULL,
    stock                 INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
    umbral_alerta         INTEGER NOT NULL DEFAULT 5 CHECK (umbral_alerta >= 0),
    maneja_numero_serie   BOOLEAN NOT NULL DEFAULT FALSE,
    sku                   VARCHAR(50) NULL UNIQUE,
    codigo_barras         VARCHAR(50) NULL,
    visible_catalogo      BOOLEAN NOT NULL DEFAULT TRUE,
    imagen_principal      VARCHAR(255) NULL,
    imagenes              JSONB NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT now(),
    updated_at            TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE atributos_tecnicos (
    id              BIGSERIAL PRIMARY KEY,
    nombre          VARCHAR(255) NOT NULL,
    tipo_dato       enum_atributo_tipo_dato NOT NULL,
    unidad          VARCHAR(20) NULL,
    categoria_id    BIGINT NULL,
    es_filtrable    BOOLEAN NOT NULL DEFAULT TRUE,
    es_comparable   BOOLEAN NOT NULL DEFAULT TRUE,
    orden           INTEGER NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE valores_atributo (
    id            BIGSERIAL PRIMARY KEY,
    atributo_id   BIGINT NOT NULL,
    valor         VARCHAR(255) NOT NULL,
    orden         INTEGER NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE producto_atributos (
    id              BIGSERIAL PRIMARY KEY,
    producto_id     BIGINT NOT NULL,
    atributo_id     BIGINT NOT NULL,
    valor_string    VARCHAR(255) NULL,
    valor_integer   INTEGER NULL,
    valor_decimal   DECIMAL(12,4) NULL,
    valor_boolean   BOOLEAN NULL,
    valor_enum_id   BIGINT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT now(),
    updated_at      TIMESTAMP NOT NULL DEFAULT now(),
    UNIQUE (producto_id, atributo_id)
);

-- =====================================================
-- UBICACIONES Y STOCK
-- =====================================================
CREATE TABLE ubicaciones (
    id          BIGSERIAL PRIMARY KEY,
    nombre      VARCHAR(255) NOT NULL,
    tipo        enum_ubicacion_tipo NOT NULL,
    direccion   VARCHAR(255) NULL,
    pasillo     VARCHAR(50) NULL,
    estante     VARCHAR(50) NULL,
    anaquel     VARCHAR(50) NULL,
    activa      BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE stock_ubicacion (
    id            BIGSERIAL PRIMARY KEY,
    producto_id   BIGINT NOT NULL,
    ubicacion_id  BIGINT NOT NULL,
    cantidad      INTEGER NOT NULL DEFAULT 0 CHECK (cantidad >= 0),
    created_at    TIMESTAMP NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP NOT NULL DEFAULT now(),
    UNIQUE (producto_id, ubicacion_id)
);

-- =====================================================
-- VENTAS, COTIZACIONES Y ÓRDENES DE COMPRA
-- =====================================================
CREATE TABLE cotizaciones (
    id                  BIGSERIAL PRIMARY KEY,
    numero              VARCHAR(20) NOT NULL UNIQUE,
    cliente_id          BIGINT NULL,
    user_id             BIGINT NOT NULL,
    nombre_cliente      VARCHAR(255) NOT NULL,
    nit_ci              VARCHAR(20) NULL,
    telefono            VARCHAR(30) NULL,
    email               VARCHAR(255) NULL,
    subtotal            DECIMAL(12,2) NOT NULL,
    descuento           DECIMAL(12,2) NOT NULL DEFAULT 0,
    total               DECIMAL(12,2) NOT NULL,
    validez_dias        INTEGER NOT NULL DEFAULT 4,
    fecha_emision       TIMESTAMP NOT NULL DEFAULT now(),
    fecha_vencimiento   TIMESTAMP NOT NULL,
    estado              enum_cotizacion_estado NOT NULL DEFAULT 'Vigente',
    pdf_path            VARCHAR(255) NULL,
    notas               TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE cotizacion_items (
    id                BIGSERIAL PRIMARY KEY,
    cotizacion_id     BIGINT NOT NULL,
    producto_id       BIGINT NULL,
    nombre            VARCHAR(255) NOT NULL,
    precio_unitario   DECIMAL(12,2) NOT NULL,
    cantidad          INTEGER NOT NULL CHECK (cantidad > 0),
    created_at        TIMESTAMP NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE proveedores (
    id            BIGSERIAL PRIMARY KEY,
    nombre        VARCHAR(255) NOT NULL,
    nit           VARCHAR(20) NULL,
    contacto      VARCHAR(100) NULL,
    telefono      VARCHAR(30) NULL,
    email         VARCHAR(255) NULL,
    direccion     VARCHAR(255) NULL,
    tipo          enum_proveedor_tipo NOT NULL,
    condiciones   TEXT NULL,
    activo        BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE ordenes_compra (
    id            BIGSERIAL PRIMARY KEY,
    numero        VARCHAR(20) NOT NULL UNIQUE,
    proveedor_id  BIGINT NOT NULL,
    user_id       BIGINT NOT NULL,
    fecha         TIMESTAMP NOT NULL DEFAULT now(),
    estado        enum_orden_compra_estado NOT NULL DEFAULT 'Borrador',
    subtotal      DECIMAL(12,2) NOT NULL DEFAULT 0,
    impuestos     DECIMAL(12,2) NOT NULL DEFAULT 0,
    total         DECIMAL(12,2) NOT NULL DEFAULT 0,
    pdf_path      VARCHAR(255) NULL,
    notas         TEXT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE orden_compra_items (
    id                BIGSERIAL PRIMARY KEY,
    orden_compra_id   BIGINT NOT NULL,
    producto_id       BIGINT NOT NULL,
    cantidad          INTEGER NOT NULL CHECK (cantidad > 0),
    precio_unitario   DECIMAL(12,2) NOT NULL,
    subtotal          DECIMAL(12,2) NOT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE ventas (
    id                     BIGSERIAL PRIMARY KEY,
    fecha                  TIMESTAMP NOT NULL DEFAULT now(),
    user_id                BIGINT NOT NULL,
    cliente_id             BIGINT NULL,
    total                  DECIMAL(12,2) NOT NULL,
    descuento              DECIMAL(12,2) NOT NULL DEFAULT 0,
    descuento_porcentaje   DECIMAL(5,2) NOT NULL DEFAULT 0,
    autorizado_por         BIGINT NULL,
    puntos_canjeados       INTEGER NOT NULL DEFAULT 0,
    estado                 enum_venta_estado NOT NULL DEFAULT 'Pendiente',
    cotizacion_id          BIGINT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT now(),
    updated_at             TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE numeros_serie (
    id                BIGSERIAL PRIMARY KEY,
    producto_id       BIGINT NOT NULL,
    numero_serie      VARCHAR(100) NOT NULL UNIQUE,
    estado            enum_numero_serie_estado NOT NULL DEFAULT 'disponible',
    venta_id          BIGINT NULL,
    cliente_id        BIGINT NULL,
    costo_unitario    DECIMAL(10,2) NULL,
    fecha_ingreso     TIMESTAMP NOT NULL DEFAULT now(),
    fecha_venta       TIMESTAMP NULL,
    garantia_hasta    DATE NULL,
    ubicacion_id      BIGINT NULL,
    notas             TEXT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE detalle_ventas (
    id                BIGSERIAL PRIMARY KEY,
    venta_id          BIGINT NOT NULL,
    producto_id       BIGINT NOT NULL,
    numero_serie_id   BIGINT NULL,
    cantidad          INTEGER NOT NULL CHECK (cantidad > 0),
    precio_unitario   DECIMAL(10,2) NOT NULL,
    descuento_item    DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at        TIMESTAMP NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE pagos (
    id          BIGSERIAL PRIMARY KEY,
    venta_id    BIGINT NOT NULL,
    monto       DECIMAL(12,2) NOT NULL,
    metodo      enum_pago_metodo NOT NULL,
    estado      enum_pago_estado NOT NULL DEFAULT 'Completado',
    referencia  VARCHAR(100) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- INVENTARIO: MOVIMIENTOS Y ALERTAS
-- =====================================================
CREATE TABLE movimientos_stock (
    id                 BIGSERIAL PRIMARY KEY,
    producto_id        BIGINT NOT NULL,
    ubicacion_id       BIGINT NULL,
    tipo               enum_movimiento_tipo NOT NULL,
    cantidad           INTEGER NOT NULL,
    stock_resultante   INTEGER NOT NULL,
    motivo             VARCHAR(255) NULL,
    user_id            BIGINT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT now(),
    updated_at         TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE alertas_stock (
    id            BIGSERIAL PRIMARY KEY,
    producto_id   BIGINT NOT NULL,
    tipo          enum_alerta_tipo NOT NULL,
    mensaje       VARCHAR(255) NOT NULL,
    leida         BOOLEAN NOT NULL DEFAULT FALSE,
    created_at    TIMESTAMP NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE devoluciones (
    id                BIGSERIAL PRIMARY KEY,
    venta_id          BIGINT NOT NULL,
    producto_id       BIGINT NOT NULL,
    numero_serie_id   BIGINT NULL,
    cantidad          INTEGER NOT NULL CHECK (cantidad > 0),
    motivo            TEXT NOT NULL,
    estado            enum_devolucion_estado NOT NULL DEFAULT 'Pendiente',
    monto_reembolso   DECIMAL(12,2) NULL,
    user_id           BIGINT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE cierres_caja (
    id                    BIGSERIAL PRIMARY KEY,
    user_id               BIGINT NOT NULL,
    fecha_cierre          DATE NOT NULL,
    cantidad_ventas       INTEGER NOT NULL DEFAULT 0,
    total_ventas          DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_efectivo        DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_tarjeta         DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_transferencia   DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_qr              DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_otros           DECIMAL(12,2) NOT NULL DEFAULT 0,
    observacion           TEXT NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT now(),
    updated_at            TIMESTAMP NOT NULL DEFAULT now(),
    UNIQUE (user_id, fecha_cierre)
);

-- =====================================================
-- CATÁLOGO PÚBLICO: PEDIDOS
-- =====================================================
CREATE TABLE pedidos (
    id                       BIGSERIAL PRIMARY KEY,
    nombre_cliente           VARCHAR(255) NOT NULL,
    telefono                 VARCHAR(30) NOT NULL,
    email                    VARCHAR(255) NULL,
    direccion                VARCHAR(255) NULL,
    nota                     TEXT NULL,
    total                    DECIMAL(12,2) NOT NULL,
    estado                   enum_pedido_estado NOT NULL DEFAULT 'Pendiente',
    user_id                  BIGINT NULL,
    venta_id                 BIGINT NULL,
    metodo_pago              VARCHAR(50) NULL,
    estado_pago              enum_pedido_estado_pago NOT NULL DEFAULT 'Pendiente',
    referencia_pago          VARCHAR(255) NULL,
    token                    VARCHAR(48) NULL UNIQUE,
    cliente_confirmado_en    TIMESTAMP NULL,
    created_at               TIMESTAMP NOT NULL DEFAULT now(),
    updated_at               TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE pedido_items (
    id                BIGSERIAL PRIMARY KEY,
    pedido_id         BIGINT NOT NULL,
    producto_id       BIGINT NULL,
    nombre            VARCHAR(255) NOT NULL,
    precio_unitario   DECIMAL(12,2) NOT NULL,
    cantidad          INTEGER NOT NULL CHECK (cantidad > 0),
    created_at        TIMESTAMP NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- FIDELIZACIÓN
-- =====================================================
CREATE TABLE puntos (
    id          BIGSERIAL PRIMARY KEY,
    cliente_id  BIGINT NOT NULL,
    venta_id    BIGINT NULL,
    user_id     BIGINT NULL,
    tipo        enum_puntos_tipo NOT NULL,
    puntos      INTEGER NOT NULL,
    concepto    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- IMPORTACIONES Y RESPALDOS
-- =====================================================
CREATE TABLE importaciones (
    id                BIGSERIAL PRIMARY KEY,
    lote              VARCHAR(50) NOT NULL UNIQUE,
    creados           INTEGER NOT NULL DEFAULT 0,
    actualizados      INTEGER NOT NULL DEFAULT 0,
    errores           INTEGER NOT NULL DEFAULT 0,
    total_filas       INTEGER NOT NULL DEFAULT 0,
    user_id           BIGINT NULL,
    detalle_errores   JSONB NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT now(),
    updated_at        TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE respaldos (
    id             BIGSERIAL PRIMARY KEY,
    archivo        VARCHAR(255) NOT NULL,
    tamano_bytes   BIGINT NULL,
    estado         enum_respaldo_estado NOT NULL,
    mensaje        TEXT NULL,
    ejecutado_en   TIMESTAMP NOT NULL DEFAULT now(),
    created_at     TIMESTAMP NOT NULL DEFAULT now(),
    updated_at     TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- FACTURACIÓN ELECTRÓNICA
-- =====================================================
CREATE TABLE facturas_electronicas (
    id                  BIGSERIAL PRIMARY KEY,
    venta_id            BIGINT NOT NULL,
    numero_factura      VARCHAR(50) NOT NULL,
    cuf                 VARCHAR(100) NOT NULL UNIQUE,
    cufd                VARCHAR(100) NOT NULL,
    estado              enum_factura_estado NOT NULL DEFAULT 'Pendiente',
    xml_path            VARCHAR(255) NULL,
    pdf_path            VARCHAR(255) NULL,
    codigo_recepcion    VARCHAR(255) NULL,
    fecha_emision       TIMESTAMP NOT NULL DEFAULT now(),
    fecha_anulacion     TIMESTAMP NULL,
    motivo_anulacion    TEXT NULL,
    respuesta_sin       JSONB NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- AUDITORÍA
-- =====================================================
CREATE TABLE logs_auditoria (
    id                  BIGSERIAL PRIMARY KEY,
    user_id             BIGINT NULL,
    accion              VARCHAR(100) NOT NULL,
    modelo              VARCHAR(100) NULL,
    modelo_id           BIGINT NULL,
    datos_anteriores    JSONB NULL,
    datos_nuevos        JSONB NULL,
    ip                  VARCHAR(45) NULL,
    user_agent          TEXT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- MOTOR DE COMPATIBILIDAD
-- =====================================================
CREATE TABLE compatibilidad_reglas (
    id                  BIGSERIAL PRIMARY KEY,
    nombre              VARCHAR(255) NOT NULL,
    atributo_origen     VARCHAR(255) NOT NULL,
    atributo_destino    VARCHAR(255) NOT NULL,
    tipo_regla          enum_regla_tipo NOT NULL,
    mensaje_error       TEXT NOT NULL,
    activa              BOOLEAN NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP NOT NULL DEFAULT now(),
    updated_at          TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- CLAVES FORÁNEAS
-- =====================================================
ALTER TABLE model_has_roles       ADD CONSTRAINT fk_mhr_role       FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;
ALTER TABLE model_has_permissions ADD CONSTRAINT fk_mhp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;
ALTER TABLE role_has_permissions  ADD CONSTRAINT fk_rhp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;
ALTER TABLE role_has_permissions  ADD CONSTRAINT fk_rhp_role       FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;

ALTER TABLE clientes ADD CONSTRAINT fk_clientes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE productos ADD CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT;
ALTER TABLE productos ADD CONSTRAINT fk_productos_marca     FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE SET NULL;

ALTER TABLE atributos_tecnicos ADD CONSTRAINT fk_atributos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL;

ALTER TABLE valores_atributo ADD CONSTRAINT fk_valores_atributo_atributo FOREIGN KEY (atributo_id) REFERENCES atributos_tecnicos(id) ON DELETE CASCADE;

ALTER TABLE producto_atributos ADD CONSTRAINT fk_pa_producto   FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;
ALTER TABLE producto_atributos ADD CONSTRAINT fk_pa_atributo   FOREIGN KEY (atributo_id) REFERENCES atributos_tecnicos(id) ON DELETE CASCADE;
ALTER TABLE producto_atributos ADD CONSTRAINT fk_pa_valor_enum FOREIGN KEY (valor_enum_id) REFERENCES valores_atributo(id) ON DELETE SET NULL;

ALTER TABLE stock_ubicacion ADD CONSTRAINT fk_su_producto  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;
ALTER TABLE stock_ubicacion ADD CONSTRAINT fk_su_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id) ON DELETE CASCADE;

ALTER TABLE cotizaciones ADD CONSTRAINT fk_cot_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;
ALTER TABLE cotizaciones ADD CONSTRAINT fk_cot_user    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;

ALTER TABLE cotizacion_items ADD CONSTRAINT fk_ci_cotizacion FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE;
ALTER TABLE cotizacion_items ADD CONSTRAINT fk_ci_producto   FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL;

ALTER TABLE ordenes_compra ADD CONSTRAINT fk_oc_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE RESTRICT;
ALTER TABLE ordenes_compra ADD CONSTRAINT fk_oc_user      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;

ALTER TABLE orden_compra_items ADD CONSTRAINT fk_oci_orden    FOREIGN KEY (orden_compra_id) REFERENCES ordenes_compra(id) ON DELETE CASCADE;
ALTER TABLE orden_compra_items ADD CONSTRAINT fk_oci_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT;

ALTER TABLE ventas ADD CONSTRAINT fk_ventas_user         FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;
ALTER TABLE ventas ADD CONSTRAINT fk_ventas_cliente      FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;
ALTER TABLE ventas ADD CONSTRAINT fk_ventas_autorizador  FOREIGN KEY (autorizado_por) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE ventas ADD CONSTRAINT fk_ventas_cotizacion   FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE SET NULL;

ALTER TABLE numeros_serie ADD CONSTRAINT fk_ns_producto  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT;
ALTER TABLE numeros_serie ADD CONSTRAINT fk_ns_venta     FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL;
ALTER TABLE numeros_serie ADD CONSTRAINT fk_ns_cliente   FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;
ALTER TABLE numeros_serie ADD CONSTRAINT fk_ns_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id) ON DELETE SET NULL;

ALTER TABLE detalle_ventas ADD CONSTRAINT fk_dv_venta         FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE;
ALTER TABLE detalle_ventas ADD CONSTRAINT fk_dv_producto      FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT;
ALTER TABLE detalle_ventas ADD CONSTRAINT fk_dv_numero_serie  FOREIGN KEY (numero_serie_id) REFERENCES numeros_serie(id) ON DELETE SET NULL;

ALTER TABLE pagos ADD CONSTRAINT fk_pagos_venta FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE;

ALTER TABLE movimientos_stock ADD CONSTRAINT fk_ms_producto  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;
ALTER TABLE movimientos_stock ADD CONSTRAINT fk_ms_ubicacion FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id) ON DELETE SET NULL;
ALTER TABLE movimientos_stock ADD CONSTRAINT fk_ms_user      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE alertas_stock ADD CONSTRAINT fk_as_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE;

ALTER TABLE devoluciones ADD CONSTRAINT fk_dev_venta         FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE;
ALTER TABLE devoluciones ADD CONSTRAINT fk_dev_producto      FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT;
ALTER TABLE devoluciones ADD CONSTRAINT fk_dev_numero_serie  FOREIGN KEY (numero_serie_id) REFERENCES numeros_serie(id) ON DELETE SET NULL;
ALTER TABLE devoluciones ADD CONSTRAINT fk_dev_user          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE cierres_caja ADD CONSTRAINT fk_cc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE pedidos ADD CONSTRAINT fk_ped_user  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE pedidos ADD CONSTRAINT fk_ped_venta FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL;

ALTER TABLE pedido_items ADD CONSTRAINT fk_pi_pedido   FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE;
ALTER TABLE pedido_items ADD CONSTRAINT fk_pi_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE SET NULL;

ALTER TABLE puntos ADD CONSTRAINT fk_puntos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE;
ALTER TABLE puntos ADD CONSTRAINT fk_puntos_venta   FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL;
ALTER TABLE puntos ADD CONSTRAINT fk_puntos_user    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE importaciones ADD CONSTRAINT fk_imp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE facturas_electronicas ADD CONSTRAINT fk_fe_venta FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE RESTRICT;

ALTER TABLE logs_auditoria ADD CONSTRAINT fk_la_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- ÍNDICES ADICIONALES
-- =====================================================
CREATE INDEX idx_clientes_email        ON clientes (email);
CREATE INDEX idx_clientes_nit_ci       ON clientes (nit_ci);
CREATE INDEX idx_ventas_fecha          ON ventas (fecha);
CREATE INDEX idx_productos_categoria   ON productos (categoria_id);
CREATE INDEX idx_productos_marca       ON productos (marca_id);
CREATE INDEX idx_producto_atributos_producto ON producto_atributos (producto_id);
CREATE INDEX idx_movimientos_producto  ON movimientos_stock (producto_id);
CREATE INDEX idx_numeros_serie_producto ON numeros_serie (producto_id);
CREATE INDEX idx_logs_auditoria_user   ON logs_auditoria (user_id);
CREATE INDEX idx_pedidos_estado        ON pedidos (estado);

-- =====================================================
-- TABLAS INTERNAS DE LARAVEL
-- =====================================================
CREATE TABLE password_reset_tokens (
    email       VARCHAR(255) PRIMARY KEY,
    token       VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP NULL
);

CREATE TABLE sessions (
    id             VARCHAR(255) PRIMARY KEY,
    user_id        BIGINT NULL,
    ip_address     VARCHAR(45) NULL,
    user_agent     TEXT NULL,
    payload        TEXT NOT NULL,
    last_activity  INTEGER NOT NULL
);
CREATE INDEX sessions_user_id_index       ON sessions (user_id);
CREATE INDEX sessions_last_activity_index ON sessions (last_activity);

CREATE TABLE cache (
    key         VARCHAR(255) PRIMARY KEY,
    value       TEXT NOT NULL,
    expiration  INTEGER NOT NULL
);

CREATE TABLE cache_locks (
    key         VARCHAR(255) PRIMARY KEY,
    owner       VARCHAR(255) NOT NULL,
    expiration  INTEGER NOT NULL
);

CREATE TABLE jobs (
    id             BIGSERIAL PRIMARY KEY,
    queue          VARCHAR(255) NOT NULL,
    payload        TEXT NOT NULL,
    attempts       SMALLINT NOT NULL,
    reserved_at    INTEGER NULL,
    available_at   INTEGER NOT NULL,
    created_at     INTEGER NOT NULL
);
CREATE INDEX jobs_queue_index ON jobs (queue);

CREATE TABLE job_batches (
    id              VARCHAR(255) PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    total_jobs      INTEGER NOT NULL,
    pending_jobs    INTEGER NOT NULL,
    failed_jobs     INTEGER NOT NULL,
    failed_job_ids  TEXT NOT NULL,
    options         TEXT NULL,
    cancelled_at    INTEGER NULL,
    created_at      INTEGER NOT NULL,
    finished_at     INTEGER NULL
);

CREATE TABLE failed_jobs (
    id          BIGSERIAL PRIMARY KEY,
    uuid        VARCHAR(255) NOT NULL UNIQUE,
    connection  TEXT NOT NULL,
    queue       TEXT NOT NULL,
    payload     TEXT NOT NULL,
    exception   TEXT NOT NULL,
    failed_at   TIMESTAMP NOT NULL DEFAULT now()
);

-- =====================================================
-- BÚSQUEDA FUZZY (pg_trgm)
-- =====================================================
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS unaccent;

CREATE INDEX productos_nombre_trgm_idx ON productos USING GIN (nombre gin_trgm_ops);
CREATE INDEX productos_sku_trgm_idx    ON productos USING GIN (sku gin_trgm_ops);
CREATE INDEX productos_desc_trgm_idx   ON productos USING GIN (descripcion gin_trgm_ops);

-- =====================================================
-- TABLA INTERNA DE MIGRACIONES DE LARAVEL
-- =====================================================
CREATE TABLE migrations (
    id        SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch     INTEGER NOT NULL
);
