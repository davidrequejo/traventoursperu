------------------------------------------------

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

-----------------------------------------------
USE traventours;

CREATE TABLE IF NOT EXISTS migracion_hotel_map (
  idhotel_antiguo INT PRIMARY KEY,
  idhotel_nuevo BIGINT NOT NULL
);

INSERT INTO migracion_hotel_map (
  idhotel_antiguo,
  idhotel_nuevo
)
SELECT
  h.idhotel,
  nh.idhotel
FROM gestion_turismo.hotel h
INNER JOIN traventours.hotel nh
  ON nh.hotelcol = CONCAT('MIG-HOTEL-', h.idhotel)
WHERE IFNULL(h.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idhotel_nuevo = VALUES(idhotel_nuevo);
-----------------------------------------------
USE traventours;

CREATE TABLE IF NOT EXISTS migracion_tours_turno_map (
  idtours_turno_antiguo INT PRIMARY KEY,
  idtours_turno_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_tours_map (
  idtours_antiguo INT PRIMARY KEY,
  idtours_nuevo BIGINT NOT NULL
);

INSERT INTO migracion_tours_turno_map (
  idtours_turno_antiguo,
  idtours_turno_nuevo
)
SELECT
  tt.idtours_turno,
  nt.idtours_turno
FROM gestion_turismo.tours_turno tt
INNER JOIN traventours.tours_turno nt
  ON UPPER(TRIM(nt.descripcion)) = UPPER(TRIM(
    COALESCE(
      NULLIF(tt.nombre, ''),
      NULLIF(tt.descripcion, ''),
      CONCAT('Turno ', tt.idtours_turno)
    )
  ))
WHERE IFNULL(tt.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idtours_turno_nuevo = VALUES(idtours_turno_nuevo);

INSERT INTO migracion_tours_map (
  idtours_antiguo,
  idtours_nuevo
)
SELECT
  t.idtours,
  nt.idtours
FROM gestion_turismo.tours t
INNER JOIN traventours.tours nt
  ON nt.codigo = LEFT(COALESCE(NULLIF(TRIM(t.codigo), ''), CONCAT('MIG', t.idtours)), 10)
WHERE IFNULL(t.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idtours_nuevo = VALUES(idtours_nuevo);
--------------------------------------------------

USE traventours;

CREATE TABLE IF NOT EXISTS migracion_tours_turno_map (
  idtours_turno_antiguo INT PRIMARY KEY,
  idtours_turno_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_tours_map (
  idtours_antiguo INT PRIMARY KEY,
  idtours_nuevo BIGINT NOT NULL
);

INSERT INTO migracion_tours_turno_map (
  idtours_turno_antiguo,
  idtours_turno_nuevo
)
SELECT
  tt.idtours_turno,
  nt.idtours_turno
FROM gestion_turismo.tours_turno tt
INNER JOIN traventours.tours_turno nt
  ON UPPER(TRIM(nt.descripcion)) = UPPER(TRIM(
    COALESCE(
      NULLIF(tt.nombre, ''),
      NULLIF(tt.descripcion, ''),
      CONCAT('Turno ', tt.idtours_turno)
    )
  ))
WHERE IFNULL(tt.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idtours_turno_nuevo = VALUES(idtours_turno_nuevo);

INSERT INTO migracion_tours_map (
  idtours_antiguo,
  idtours_nuevo
)
SELECT
  t.idtours,
  nt.idtours
FROM gestion_turismo.tours t
INNER JOIN traventours.tours nt
  ON nt.codigo = LEFT(COALESCE(NULLIF(TRIM(t.codigo), ''), CONCAT('MIG', t.idtours)), 10)
WHERE IFNULL(t.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idtours_nuevo = VALUES(idtours_nuevo);

--------------------------------------------------
USE traventours;

CREATE TABLE IF NOT EXISTS migracion_origen_reserva_map (
  idorigen_reserva_antiguo INT PRIMARY KEY,
  idorigen_reserva_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_llegada_empresa_map (
  idllegada_por_empresa_antiguo INT PRIMARY KEY,
  idllegada_por_empresa_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_reserva_map (
  idreserva_antiguo INT PRIMARY KEY,
  idreserva_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_reserva_detalle_map (
  idreserva_detalle_antiguo INT PRIMARY KEY,
  idreserva_detalle_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_reserva_hotel_map (
  idreserva_hotel_antiguo INT PRIMARY KEY,
  idreserva_hotel_nuevo BIGINT NOT NULL
);
------------------------------------------------
USE traventours;

CREATE TABLE IF NOT EXISTS migracion_cliente_map (
  idpersona_cliente_antiguo INT PRIMARY KEY,
  idpersona_antiguo INT NOT NULL,
  idpersona_nuevo BIGINT NOT NULL
);

INSERT INTO migracion_cliente_map (
  idpersona_cliente_antiguo,
  idpersona_antiguo,
  idpersona_nuevo
)
SELECT
  pc.idpersona_cliente,
  p.idpersona AS idpersona_antiguo,
  np.idpersona AS idpersona_nuevo
FROM gestion_turismo.persona_cliente pc
INNER JOIN gestion_turismo.persona p
  ON p.idpersona = pc.idpersona
INNER JOIN traventours.persona np
  ON np.codigo = CONCAT('MIG-CLI-', p.idpersona)
WHERE IFNULL(pc.estado, '1') = '1'
  AND IFNULL(p.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idpersona_antiguo = VALUES(idpersona_antiguo),
  idpersona_nuevo = VALUES(idpersona_nuevo);












-----------------------------------------
USE traventours;

SET @user_id := 1;
SET @trabajador_reserva := 133;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS migracion_origen_reserva_map (
  idorigen_reserva_antiguo INT PRIMARY KEY,
  idorigen_reserva_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_llegada_empresa_map (
  idllegada_por_empresa_antiguo INT PRIMARY KEY,
  idllegada_por_empresa_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_reserva_map (
  idreserva_antiguo INT PRIMARY KEY,
  idreserva_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_reserva_detalle_map (
  idreserva_detalle_antiguo INT PRIMARY KEY,
  idreserva_detalle_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_reserva_hotel_map (
  idreserva_hotel_antiguo INT PRIMARY KEY,
  idreserva_hotel_nuevo BIGINT NOT NULL
);

INSERT INTO migracion_origen_reserva_map (
  idorigen_reserva_antiguo,
  idorigen_reserva_nuevo
)
SELECT
  o.idorigen_reserva,
  no.idorigen_reserva
FROM gestion_turismo.origen_reserva o
INNER JOIN origen_reserva no
  ON UPPER(REPLACE(TRIM(no.descripcion), ' ', '')) = UPPER(REPLACE(TRIM(o.nombre), ' ', ''))
WHERE IFNULL(o.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idorigen_reserva_nuevo = VALUES(idorigen_reserva_nuevo);

INSERT INTO migracion_llegada_empresa_map (
  idllegada_por_empresa_antiguo,
  idllegada_por_empresa_nuevo
)
SELECT
  le.idllegada_por_empresa,
  nle.idllegada_por_empresa
FROM gestion_turismo.llegada_por_empresa le
INNER JOIN llegada_por_empresa nle
  ON UPPER(REPLACE(TRIM(nle.descripcion), ' ', '')) = UPPER(REPLACE(TRIM(le.nombre_empresa_llegada), ' ', ''))
WHERE IFNULL(le.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idllegada_por_empresa_nuevo = VALUES(idllegada_por_empresa_nuevo);

INSERT INTO reserva (
  idtrabajador,
  idcliente,
  llegada_ref_asesor,
  idorigen_reserva,
  idllegada_por_empresa,
  tours_paquete,
  serie_reserva,
  nro_reserva,
  serie_numero,
  nro_pasajeros,
  fecha_llegada,
  hora_llegada,
  fecha_salida,
  llegada_por,
  reserva_hotel,
  observacion_recojo,
  itinerario_general,
  vuelo_ticket,
  vuelo_costo,
  vuelo_observacion,
  total_reserva,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  @trabajador_reserva,
  cm.idpersona_nuevo,
  @trabajador_reserva,
  COALESCE(om.idorigen_reserva_nuevo, 1),
  COALESCE(lm.idllegada_por_empresa_nuevo, 1),
  COALESCE(NULLIF(TRIM(r.tours_reserva), ''), 'NO'),
  NULLIF(TRIM(r.serie_reserva), ''),
  NULLIF(TRIM(r.numero_reserva), ''),
  COALESCE(NULLIF(TRIM(r.numero_serie), ''), CONCAT('MIG-RE-', r.idreserva)),
  IFNULL(r.numero_pasajero, 0),
  r.llegada_fecha,
  r.llegadahora,
  r.salida_fecha,
  NULLIF(TRIM(r.llegada_por), ''),
  COALESCE(NULLIF(TRIM(r.reserva_hotel), ''), 'NO'),
  NULLIF(TRIM(r.observacion_recojo), ''),
  NULLIF(TRIM(r.itinerario_general), ''),
  NULLIF(TRIM(r.vuelo_ticket), ''),
  IFNULL(r.vuelo_costo, 0),
  NULLIF(TRIM(r.vuelo_observacion), ''),
  IFNULL(r.total_reserva, 0),
  '1',
  COALESCE(r.created_at, NOW()),
  COALESCE(r.updated_at, NOW()),
  COALESCE(NULLIF(r.user_created, 0), @user_id),
  COALESCE(NULLIF(r.user_updated, 0), @user_id)
FROM gestion_turismo.reserva r
INNER JOIN migracion_cliente_map cm
  ON cm.idpersona_cliente_antiguo = r.idpersona_cliente
LEFT JOIN migracion_origen_reserva_map om
  ON om.idorigen_reserva_antiguo = r.idorigen_reserva
LEFT JOIN migracion_llegada_empresa_map lm
  ON lm.idllegada_por_empresa_antiguo = r.idllegada_por_empresa
WHERE IFNULL(r.estado, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM migracion_reserva_map rm
    WHERE rm.idreserva_antiguo = r.idreserva
  )
  AND NOT EXISTS (
    SELECT 1
    FROM reserva nr
    WHERE nr.serie_numero = COALESCE(NULLIF(TRIM(r.numero_serie), ''), CONCAT('MIG-RE-', r.idreserva))
  );

INSERT INTO migracion_reserva_map (
  idreserva_antiguo,
  idreserva_nuevo
)
SELECT
  r.idreserva,
  nr.idreserva
FROM gestion_turismo.reserva r
INNER JOIN reserva nr
  ON nr.serie_numero = COALESCE(NULLIF(TRIM(r.numero_serie), ''), CONCAT('MIG-RE-', r.idreserva))
WHERE IFNULL(r.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idreserva_nuevo = VALUES(idreserva_nuevo);

INSERT INTO reserva_detalle (
  idreserva,
  idtours,
  idtours_turno,
  nombre_tours,
  vehiculo,
  nro_pax,
  fecha_tours,
  observacion,
  precio,
  descuento,
  descuento_porcentaje,
  subtotal,
  subtotal_no_descuento,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  rm.idreserva_nuevo,
  tm.idtours_nuevo,
  COALESCE(ttm.idtours_turno_nuevo, nt.idtours_turno),
  LEFT(COALESCE(NULLIF(TRIM(rd.nombre_tours), ''), nt.nombre), 255),
  NULLIF(TRIM(rd.vehiculo), ''),
  IFNULL(rd.nro_pax, 0),
  rd.fecha_tours,
  NULLIF(TRIM(rd.observacion), ''),
  IFNULL(rd.precio, 0),
  IFNULL(rd.descuento, 0),
  IFNULL(rd.descuento_porcentaje, 0),
  IFNULL(rd.subtotal, 0),
  IFNULL(rd.subtotal_no_descuento, IFNULL(rd.subtotal, 0)),
  '1',
  COALESCE(rd.created_at, NOW()),
  COALESCE(rd.updated_at, NOW()),
  COALESCE(NULLIF(rd.user_created, 0), @user_id),
  COALESCE(NULLIF(rd.user_updated, 0), @user_id)
FROM gestion_turismo.reserva_detalle rd
INNER JOIN migracion_reserva_map rm
  ON rm.idreserva_antiguo = rd.idreserva
INNER JOIN migracion_tours_map tm
  ON tm.idtours_antiguo = rd.idtours
INNER JOIN tours nt
  ON nt.idtours = tm.idtours_nuevo
LEFT JOIN migracion_tours_turno_map ttm
  ON ttm.idtours_turno_antiguo = rd.idtours_turno
WHERE IFNULL(rd.estado, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM migracion_reserva_detalle_map rdm
    WHERE rdm.idreserva_detalle_antiguo = rd.idreserva_detalle
  );

INSERT INTO migracion_reserva_detalle_map (
  idreserva_detalle_antiguo,
  idreserva_detalle_nuevo
)
SELECT
  rd.idreserva_detalle,
  MIN(nrd.idreserva_detalle)
FROM gestion_turismo.reserva_detalle rd
INNER JOIN migracion_reserva_map rm
  ON rm.idreserva_antiguo = rd.idreserva
INNER JOIN migracion_tours_map tm
  ON tm.idtours_antiguo = rd.idtours
INNER JOIN reserva_detalle nrd
  ON nrd.idreserva = rm.idreserva_nuevo
 AND nrd.idtours = tm.idtours_nuevo
 AND IFNULL(nrd.fecha_tours, '1000-01-01') = IFNULL(rd.fecha_tours, '1000-01-01')
WHERE IFNULL(rd.estado, '1') = '1'
GROUP BY rd.idreserva_detalle
ON DUPLICATE KEY UPDATE
  idreserva_detalle_nuevo = VALUES(idreserva_detalle_nuevo);

INSERT INTO reserva_hotel_detalle (
  idreserva,
  hotel_habitacion_idhotel_habitacion,
  nombre_habitacion,
  nro_pax,
  cantidad_habitacion,
  fecha_check_in,
  fecha_check_out,
  nro_noches,
  precio,
  descuento,
  adicional,
  `observación`,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  rm.idreserva_nuevo,
  hm.idhotel_habitacion_nuevo,
  COALESCE(NULLIF(TRIM(rh.nombre_habitacion), ''), nhh.nombre),
  CAST(IFNULL(NULLIF(TRIM(rh.nro_pax), ''), '0') AS DECIMAL(20,2)),
  NULLIF(TRIM(rh.cantidad_habitacion), ''),
  rh.fecha_check_in,
  rh.fecha_check_out,
  NULLIF(TRIM(rh.nro_noches), ''),
  CAST(IFNULL(NULLIF(TRIM(rh.precio), ''), '0') AS DECIMAL(11,2)),
  CAST(IFNULL(NULLIF(TRIM(rh.descuento), ''), '0') AS DECIMAL(11,2)),
  CAST(IFNULL(NULLIF(TRIM(rh.adicional), ''), '0') AS DECIMAL(11,2)),
  NULLIF(TRIM(rh.observacion), ''),
  '1',
  COALESCE(rh.created_at, NOW()),
  COALESCE(rh.updated_at, NOW()),
  COALESCE(NULLIF(rh.user_created, 0), @user_id),
  COALESCE(NULLIF(rh.user_updated, 0), @user_id)
FROM gestion_turismo.reserva_hotel_detalle rh
INNER JOIN migracion_reserva_map rm
  ON rm.idreserva_antiguo = rh.idreserva
INNER JOIN migracion_hotel_habitacion_map hm
  ON hm.idhotel_habitacion_antiguo = rh.idhotel_habitacion
INNER JOIN hotel_habitacion nhh
  ON nhh.idhotel_habitacion = hm.idhotel_habitacion_nuevo
WHERE IFNULL(rh.estado, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM migracion_reserva_hotel_map rhm
    WHERE rhm.idreserva_hotel_antiguo = rh.idreserva_hotel
  );

INSERT INTO migracion_reserva_hotel_map (
  idreserva_hotel_antiguo,
  idreserva_hotel_nuevo
)
SELECT
  rh.idreserva_hotel,
  MIN(nrh.idreserva_hotel_detalle)
FROM gestion_turismo.reserva_hotel_detalle rh
INNER JOIN migracion_reserva_map rm
  ON rm.idreserva_antiguo = rh.idreserva
INNER JOIN migracion_hotel_habitacion_map hm
  ON hm.idhotel_habitacion_antiguo = rh.idhotel_habitacion
INNER JOIN reserva_hotel_detalle nrh
  ON nrh.idreserva = rm.idreserva_nuevo
 AND nrh.hotel_habitacion_idhotel_habitacion = hm.idhotel_habitacion_nuevo
 AND IFNULL(nrh.fecha_check_in, '1000-01-01') = IFNULL(rh.fecha_check_in, '1000-01-01')
WHERE IFNULL(rh.estado, '1') = '1'
GROUP BY rh.idreserva_hotel
ON DUPLICATE KEY UPDATE
  idreserva_hotel_nuevo = VALUES(idreserva_hotel_nuevo);

COMMIT;