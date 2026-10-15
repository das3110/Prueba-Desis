BEGIN;

CREATE TABLE bodegas (
    id INTEGER PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE sucursales (
    id INTEGER PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    bodega_id INTEGER NOT NULL REFERENCES bodegas(id),
    UNIQUE (id, bodega_id)
);

CREATE INDEX sucursales_bodega_idx ON sucursales(bodega_id);

CREATE TABLE monedas (
    id INTEGER PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

CREATE TABLE materiales (
    id SMALLINT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE productos (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo VARCHAR(15) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL CHECK (char_length(nombre) BETWEEN 2 AND 50),
    bodega_id INTEGER NOT NULL REFERENCES bodegas(id),
    sucursal_id INTEGER NOT NULL,
    moneda_id INTEGER NOT NULL REFERENCES monedas(id),
    precio NUMERIC NOT NULL CHECK (precio > 0 AND scale(precio) <= 2),
    descripcion TEXT NOT NULL CHECK (char_length(descripcion) BETWEEN 10 AND 1000),
    CHECK (char_length(codigo) BETWEEN 5 AND 15
        AND codigo ~ '^[A-Za-z0-9]+$' AND codigo ~ '[A-Za-z]' AND codigo ~ '[0-9]'),
    FOREIGN KEY (sucursal_id, bodega_id) REFERENCES sucursales(id, bodega_id)
);

CREATE TABLE producto_material (
    producto_id BIGINT NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
    material_id SMALLINT NOT NULL REFERENCES materiales(id),
    PRIMARY KEY (producto_id, material_id)
);

COMMIT;
