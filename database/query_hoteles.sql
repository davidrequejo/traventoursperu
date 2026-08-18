USE traventours;

SET @user_id := 1;

SET @doc_ruc := (
  SELECT idsunat_c06_doc_identidad
  FROM sunat_c06_doc_identidad
  WHERE code_sunat = '6'
  LIMIT 1
);

SET @tipo_hotel := (
  SELECT idpersona_tipo
  FROM persona_tipo
  WHERE UPPER(TRIM(nombre)) = 'HOTEL'
  LIMIT 1
);

START TRANSACTION;

CREATE TABLE IF NOT EXISTS migracion_hotel_tipo_map (
  idhotel_tipo_antiguo INT PRIMARY KEY,
  idhotel_tipo_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_hotel_persona_map (
  idhotel_antiguo INT PRIMARY KEY,
  idpersona_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_hotel_map (
  idhotel_antiguo INT PRIMARY KEY,
  idhotel_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_hotel_habitacion_map (
  idhotel_habitacion_antiguo INT PRIMARY KEY,
  idhotel_habitacion_nuevo BIGINT NOT NULL
);

INSERT INTO hotel_tipo (
  nombre,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT DISTINCT
  TRIM(ht.nombre),
  '1',
  NOW(),
  NOW(),
  @user_id,
  @user_id
FROM gestion_turismo.hotel_tipo ht
WHERE IFNULL(ht.estado_delete, '1') = '1'
  AND TRIM(IFNULL(ht.nombre, '')) <> ''
  AND NOT EXISTS (
    SELECT 1
    FROM hotel_tipo nt
    WHERE UPPER(TRIM(nt.nombre)) = UPPER(TRIM(ht.nombre))
      AND nt.estado_trash = '1'
  );

INSERT INTO migracion_hotel_tipo_map (
  idhotel_tipo_antiguo,
  idhotel_tipo_nuevo
)
SELECT
  ht.idhotel_tipo,
  nt.idhotel_tipo
FROM gestion_turismo.hotel_tipo ht
INNER JOIN hotel_tipo nt
  ON UPPER(TRIM(nt.nombre)) = UPPER(TRIM(ht.nombre))
WHERE IFNULL(ht.estado_delete, '1') = '1'
ON DUPLICATE KEY UPDATE
  idhotel_tipo_nuevo = VALUES(idhotel_tipo_nuevo);

INSERT INTO persona (
  tipo_documento,
  codigo,
  tipo_persona_sunat,
  numero_documento,
  descripcion,
  nombre_comercial,
  celular,
  celular2,
  direccion,
  correo,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  @doc_ruc,
  CONCAT('MIG-HOTEL-', h.idhotel),
  'JURIDICA',
  NULLIF(TRIM(h.ruc), ''),
  COALESCE(NULLIF(TRIM(h.razon_social), ''), NULLIF(TRIM(h.nombre), ''), CONCAT('HOTEL ', h.idhotel)),
  NULLIF(TRIM(h.nombre), ''),
  NULLIF(TRIM(h.celular), ''),
  NULLIF(TRIM(h.telefono_fijo), ''),
  NULLIF(TRIM(h.direccion), ''),
  NULLIF(TRIM(h.correo), ''),
  '1',
  COALESCE(h.created_at, NOW()),
  COALESCE(h.updated_at, NOW()),
  COALESCE(NULLIF(h.user_created, 0), @user_id),
  COALESCE(NULLIF(h.user_updated, 0), @user_id)
FROM gestion_turismo.hotel h
WHERE IFNULL(h.estado_delete, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM persona p
    WHERE p.codigo = CONCAT('MIG-HOTEL-', h.idhotel)
  );

INSERT INTO migracion_hotel_persona_map (
  idhotel_antiguo,
  idpersona_nuevo
)
SELECT
  h.idhotel,
  p.idpersona
FROM gestion_turismo.hotel h
INNER JOIN persona p
  ON p.codigo = CONCAT('MIG-HOTEL-', h.idhotel)
WHERE IFNULL(h.estado_delete, '1') = '1'
ON DUPLICATE KEY UPDATE
  idpersona_nuevo = VALUES(idpersona_nuevo);

INSERT INTO persona_tipo_persona (
  idpersona,
  idpersona_tipo,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  m.idpersona_nuevo,
  @tipo_hotel,
  NOW(),
  NOW(),
  @user_id,
  @user_id
FROM migracion_hotel_persona_map m
WHERE @tipo_hotel IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM persona_tipo_persona ptp
    WHERE ptp.idpersona = m.idpersona_nuevo
      AND ptp.idpersona_tipo = @tipo_hotel
  );

INSERT INTO hotel (
  idpersona,
  idhotel_tipo,
  estrellas,
  check_out,
  check_in,
  tarifa_x_pers_paq,
  descripcion,
  gogle_maps,
  imagen_principal,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated,
  hotelcol
)
SELECT
  pm.idpersona_nuevo,
  COALESCE(tm.idhotel_tipo_nuevo, 1),
  NULLIF(TRIM(h.estrella), ''),
  CASE
    WHEN h.check_out REGEXP '^[0-9]{1,2}:[0-9]{2}' THEN TIME(STR_TO_DATE(h.check_out, '%H:%i'))
    ELSE NULL
  END,
  CASE
    WHEN h.check_in REGEXP '^[0-9]{1,2}:[0-9]{2}' THEN TIME(STR_TO_DATE(h.check_in, '%H:%i'))
    ELSE NULL
  END,
  IFNULL(h.tarifa_hotel, 0),
  NULLIF(TRIM(h.descripcion), ''),
  NULLIF(TRIM(h.gogle_maps), ''),
  NULLIF(TRIM(h.documento), ''),
  '1',
  COALESCE(h.created_at, NOW()),
  COALESCE(h.updated_at, NOW()),
  COALESCE(NULLIF(h.user_created, 0), @user_id),
  COALESCE(NULLIF(h.user_updated, 0), @user_id),
  CONCAT('MIG-HOTEL-', h.idhotel)
FROM gestion_turismo.hotel h
INNER JOIN migracion_hotel_persona_map pm
  ON pm.idhotel_antiguo = h.idhotel
LEFT JOIN migracion_hotel_tipo_map tm
  ON tm.idhotel_tipo_antiguo = h.idhotel_tipo
WHERE IFNULL(h.estado_delete, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM hotel nh
    WHERE nh.hotelcol = CONCAT('MIG-HOTEL-', h.idhotel)
  );

INSERT INTO migracion_hotel_map (
  idhotel_antiguo,
  idhotel_nuevo
)
SELECT
  h.idhotel,
  nh.idhotel
FROM gestion_turismo.hotel h
INNER JOIN hotel nh
  ON nh.hotelcol = CONCAT('MIG-HOTEL-', h.idhotel)
WHERE IFNULL(h.estado_delete, '1') = '1'
ON DUPLICATE KEY UPDATE
  idhotel_nuevo = VALUES(idhotel_nuevo);

INSERT INTO hotel_habitacion (
  idhotel,
  nombre,
  cant_huespeds,
  precio_coorporativo,
  precio_normal,
  precio_temp_alta,
  descripcion,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  hm.idhotel_nuevo,
  COALESCE(NULLIF(TRIM(hh.nombre), ''), CONCAT('Habitacion ', hh.idhotel_habitacion)),
  NULLIF(TRIM(hh.cant_huespeds), ''),
  IFNULL(hh.precio_coorporativo, 0),
  IFNULL(hh.precio_normal, 0),
  IFNULL(hh.precio_temp_alta, 0),
  NULLIF(TRIM(hh.descripcion), ''),
  '1',
  COALESCE(hh.created_at, NOW()),
  COALESCE(hh.updated_at, NOW()),
  COALESCE(NULLIF(hh.user_created, 0), @user_id),
  COALESCE(NULLIF(hh.user_updated, 0), @user_id)
FROM gestion_turismo.hotel_habitacion hh
INNER JOIN migracion_hotel_map hm
  ON hm.idhotel_antiguo = hh.idhotel
WHERE IFNULL(hh.estado_delete, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM migracion_hotel_habitacion_map mhm
    WHERE mhm.idhotel_habitacion_antiguo = hh.idhotel_habitacion
  );

INSERT INTO migracion_hotel_habitacion_map (
  idhotel_habitacion_antiguo,
  idhotel_habitacion_nuevo
)
SELECT
  hh.idhotel_habitacion,
  nhh.idhotel_habitacion
FROM gestion_turismo.hotel_habitacion hh
INNER JOIN migracion_hotel_map hm
  ON hm.idhotel_antiguo = hh.idhotel
INNER JOIN hotel_habitacion nhh
  ON nhh.idhotel = hm.idhotel_nuevo
 AND nhh.nombre = COALESCE(NULLIF(TRIM(hh.nombre), ''), CONCAT('Habitacion ', hh.idhotel_habitacion))
WHERE IFNULL(hh.estado_delete, '1') = '1'
ON DUPLICATE KEY UPDATE
  idhotel_habitacion_nuevo = VALUES(idhotel_habitacion_nuevo);

COMMIT;


----------------------------------------------


USE traventours;

SET @user_id := 1;

CREATE TABLE IF NOT EXISTS `traventours`.`migracion_hotel_map` (
  `idhotel_antiguo` INT NOT NULL,
  `idhotel_nuevo` BIGINT NOT NULL,
  PRIMARY KEY (`idhotel_antiguo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `traventours`.`migracion_hotel_habitacion_map` (
  `idhotel_habitacion_antiguo` INT NOT NULL,
  `idhotel_habitacion_nuevo` BIGINT NOT NULL,
  PRIMARY KEY (`idhotel_habitacion_antiguo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `traventours`.`migracion_hotel_map` (
  idhotel_antiguo,
  idhotel_nuevo
)
SELECT
  h.idhotel,
  nh.idhotel
FROM `gestion_turismo`.`hotel` h
INNER JOIN `traventours`.`hotel` nh
  ON nh.hotelcol = CONCAT('MIG-HOTEL-', h.idhotel)
WHERE IFNULL(h.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idhotel_nuevo = VALUES(idhotel_nuevo);

INSERT INTO `traventours`.`hotel_habitacion` (
  idhotel,
  nombre,
  cant_huespeds,
  precio_coorporativo,
  precio_normal,
  precio_temp_alta,
  descripcion,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  hm.idhotel_nuevo,
  COALESCE(NULLIF(TRIM(hh.nombre), ''), CONCAT('Habitacion ', hh.idhotel_habitacion)),
  NULLIF(TRIM(hh.cant_huespeds), ''),
  IFNULL(hh.precio_coorporativo, 0),
  IFNULL(hh.precio_normal, 0),
  IFNULL(hh.precio_temp_alta, 0),
  NULLIF(TRIM(hh.descripcion), ''),
  '1',
  COALESCE(hh.created_at, NOW()),
  COALESCE(hh.updated_at, NOW()),
  COALESCE(NULLIF(hh.user_created, 0), @user_id),
  COALESCE(NULLIF(hh.user_updated, 0), @user_id)
FROM `gestion_turismo`.`hotel_habitacion` hh
INNER JOIN `traventours`.`migracion_hotel_map` hm
  ON hm.idhotel_antiguo = hh.idhotel
WHERE IFNULL(hh.estado, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM `traventours`.`hotel_habitacion` nhh
    WHERE nhh.idhotel = hm.idhotel_nuevo
      AND UPPER(TRIM(nhh.nombre)) = UPPER(TRIM(
        COALESCE(NULLIF(hh.nombre, ''), CONCAT('Habitacion ', hh.idhotel_habitacion))
      ))
  );

INSERT INTO `traventours`.`migracion_hotel_habitacion_map` (
  idhotel_habitacion_antiguo,
  idhotel_habitacion_nuevo
)
SELECT
  hh.idhotel_habitacion,
  nhh.idhotel_habitacion
FROM `gestion_turismo`.`hotel_habitacion` hh
INNER JOIN `traventours`.`migracion_hotel_map` hm
  ON hm.idhotel_antiguo = hh.idhotel
INNER JOIN `traventours`.`hotel_habitacion` nhh
  ON nhh.idhotel = hm.idhotel_nuevo
 AND UPPER(TRIM(nhh.nombre)) = UPPER(TRIM(
    COALESCE(NULLIF(hh.nombre, ''), CONCAT('Habitacion ', hh.idhotel_habitacion))
 ))
WHERE IFNULL(hh.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idhotel_habitacion_nuevo = VALUES(idhotel_habitacion_nuevo);

INSERT INTO `traventours`.`hotel_habitacion` (
  idhotel,
  nombre,
  cant_huespeds,
  precio_coorporativo,
  precio_normal,
  precio_temp_alta,
  descripcion,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  h.idhotel,
  'Habitacion estandar',
  '2',
  IFNULL(h.tarifa_x_pers_paq, 0),
  IFNULL(h.tarifa_x_pers_paq, 0),
  IFNULL(h.tarifa_x_pers_paq, 0),
  'Habitacion creada por migracion para permitir reservas.',
  '1',
  NOW(),
  NOW(),
  @user_id,
  @user_id
FROM `traventours`.`hotel` h
WHERE h.estado_trash = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM `traventours`.`hotel_habitacion` hh
    WHERE hh.idhotel = h.idhotel
      AND hh.estado_trash = '1'
  );