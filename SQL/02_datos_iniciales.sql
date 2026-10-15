BEGIN;

INSERT INTO bodegas (id, nombre) VALUES (1, 'Bodega 1'), (2, 'Bodega 2');
INSERT INTO sucursales (id, nombre, bodega_id) VALUES
    (1, 'Sucursal 1', 1), (2, 'Sucursal 2', 1),
    (3, 'Sucursal 3', 2), (4, 'Sucursal 4', 2);
INSERT INTO monedas (id, nombre) VALUES
    (1, 'PESO CHILENO'), (2, 'DÓLAR'), (3, 'EURO');
INSERT INTO materiales (id, nombre) VALUES
    (1, 'Plástico'), (2, 'Metal'), (3, 'Madera'), (4, 'Vidrio'), (5, 'Textil');

COMMIT;
