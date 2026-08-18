USE traventours;

SET @user_id := 1;
SET @cuenta_bancaria_default := 1;

SET @tipo_ticket := (
  SELECT idsunat_c01_tipo_comprobante
  FROM sunat_c01_tipo_comprobante
  WHERE codigo = '12'
  LIMIT 1
);

START TRANSACTION;

CREATE TABLE IF NOT EXISTS migracion_reserva_pago_map (
  idreserva_pago_antiguo INT PRIMARY KEY,
  idventa_antiguo INT NOT NULL,
  idreserva_antiguo INT NOT NULL,
  idrdocumento_nuevo BIGINT NOT NULL
);

INSERT INTO rdocumento (
  idpersona_cliente,
  idsunat_c01,
  origen,
  crear_enviar_sunat,
  tipo_comprobante,
  serie_comprobante,
  numero_comprobante,
  fecha_emision,
  impuesto,
  venta_subtotal,
  venta_descuento,
  venta_igv,
  venta_total,
  venta_cuotas,
  vc_cantidad_total,
  vc_cantidad_pagada,
  vc_estado,
  total_recibido,
  total_vuelto,
  sunat_estado,
  sunat_observacion,
  sunat_code,
  sunat_mensaje,
  sunat_hash,
  sunat_error,
  observacion_documento,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  cm.idpersona_nuevo,
  COALESCE(tc.idsunat_c01_tipo_comprobante, @tipo_ticket),
  'reserva',
  COALESCE(NULLIF(TRIM(v.crear_enviar_sunat), ''), 'NO'),
  COALESCE(NULLIF(TRIM(v.tipo_comprobante), ''), '12'),
  COALESCE(NULLIF(TRIM(v.serie_comprobante), ''), 'T001'),
  COALESCE(NULLIF(TRIM(v.numero_comprobante), ''), CAST(v.idventa AS CHAR)),
  COALESCE(v.fecha_emision, v.created_at, NOW()),
  IFNULL(v.impuesto, 0),
  IFNULL(v.venta_subtotal, IFNULL(v.venta_total, 0)),
  IFNULL(v.venta_descuento, 0),
  IFNULL(v.venta_igv, 0),
  IFNULL(v.venta_total, 0),
  'contado',
  1,
  1,
  'pagado',
  IFNULL(v.total_recibido, IFNULL(v.venta_total, 0)),
  IFNULL(v.total_vuelto, 0),
  COALESCE(NULLIF(TRIM(v.sunat_estado), ''), 'ACEPTADA'),
  NULLIF(TRIM(v.sunat_observacion), ''),
  NULLIF(TRIM(v.sunat_code), ''),
  NULLIF(TRIM(v.sunat_mensaje), ''),
  NULLIF(TRIM(v.sunat_hash), ''),
  NULLIF(TRIM(v.sunat_error), ''),
  LEFT(
    TRIM(
      CONCAT(
        'Reserva #',
        rm.idreserva_nuevo,
        ' - Migracion venta antigua #',
        v.idventa,
        CASE
          WHEN TRIM(IFNULL(v.observacion_documento, '')) <> ''
            THEN CONCAT(' - ', TRIM(v.observacion_documento))
          ELSE ''
        END
      )
    ),
    500
  ),
  '1',
  COALESCE(v.created_at, NOW()),
  COALESCE(v.updated_at, NOW()),
  COALESCE(NULLIF(v.user_created, 0), @user_id),
  COALESCE(NULLIF(v.user_updated, 0), @user_id)
FROM gestion_turismo.reserva_pago rp
INNER JOIN gestion_turismo.venta v
  ON v.idventa = rp.idventa
INNER JOIN migracion_reserva_map rm
  ON rm.idreserva_antiguo = rp.idreserva
INNER JOIN gestion_turismo.reserva r
  ON r.idreserva = rp.idreserva
INNER JOIN migracion_cliente_map cm
  ON cm.idpersona_cliente_antiguo = r.idpersona_cliente
LEFT JOIN sunat_c01_tipo_comprobante tc
  ON tc.codigo = COALESCE(NULLIF(TRIM(v.tipo_comprobante), ''), '12')
WHERE IFNULL(v.estado, '1') = '1'
  AND IFNULL(rp.idventa, 0) > 0
  AND NOT EXISTS (
    SELECT 1
    FROM migracion_reserva_pago_map rpm
    WHERE rpm.idreserva_pago_antiguo = rp.idreserva_pago
  )
  AND NOT EXISTS (
    SELECT 1
    FROM rdocumento rd
    WHERE rd.origen = 'reserva'
      AND rd.observacion_documento LIKE CONCAT('Reserva #', rm.idreserva_nuevo, ' - Migracion venta antigua #', v.idventa, '%')
  );

INSERT INTO migracion_reserva_pago_map (
  idreserva_pago_antiguo,
  idventa_antiguo,
  idreserva_antiguo,
  idrdocumento_nuevo
)
SELECT
  rp.idreserva_pago,
  rp.idventa,
  rp.idreserva,
  rd.idrdocumento
FROM gestion_turismo.reserva_pago rp
INNER JOIN gestion_turismo.venta v
  ON v.idventa = rp.idventa
INNER JOIN migracion_reserva_map rm
  ON rm.idreserva_antiguo = rp.idreserva
INNER JOIN rdocumento rd
  ON rd.origen = 'reserva'
 AND rd.observacion_documento LIKE CONCAT('Reserva #', rm.idreserva_nuevo, ' - Migracion venta antigua #', v.idventa, '%')
WHERE IFNULL(v.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idventa_antiguo = VALUES(idventa_antiguo),
  idreserva_antiguo = VALUES(idreserva_antiguo),
  idrdocumento_nuevo = VALUES(idrdocumento_nuevo);

INSERT INTO rdocumento_detalle (
  idrdocumento,
  descripcion,
  v_tipo_comprobante,
  v_fecha_emision,
  cantidad,
  precio_compra,
  precio_venta,
  precio_venta_descuento,
  descuento,
  descuento_porcentaje,
  subtotal,
  subtotal_no_descuento
)
SELECT
  rpm.idrdocumento_nuevo,
  LEFT(
    COALESCE(
      NULLIF(TRIM(vd.pr_nombre), ''),
      CONCAT('Pago de reserva ', nr.serie_numero)
    ),
    250
  ),
  COALESCE(NULLIF(TRIM(vd.v_tipo_comprobante), ''), v.tipo_comprobante, '12'),
  COALESCE(vd.v_fecha_emision, v.fecha_emision, NOW()),
  IFNULL(vd.cantidad, 1),
  IFNULL(vd.precio_compra, 0),
  IFNULL(vd.precio_venta, IFNULL(v.venta_total, 0)),
  IFNULL(vd.precio_venta_descuento, IFNULL(v.venta_total, 0)),
  IFNULL(vd.descuento, 0),
  IFNULL(vd.descuento_porcentaje, 0),
  IFNULL(vd.subtotal, IFNULL(v.venta_total, 0)),
  IFNULL(vd.subtotal_no_descuento, IFNULL(vd.subtotal, IFNULL(v.venta_total, 0)))
FROM migracion_reserva_pago_map rpm
INNER JOIN gestion_turismo.venta v
  ON v.idventa = rpm.idventa_antiguo
INNER JOIN migracion_reserva_map rm
  ON rm.idreserva_antiguo = rpm.idreserva_antiguo
INNER JOIN reserva nr
  ON nr.idreserva = rm.idreserva_nuevo
INNER JOIN gestion_turismo.venta_detalle vd
  ON vd.idventa = v.idventa
WHERE NOT EXISTS (
  SELECT 1
  FROM rdocumento_detalle nvd
  WHERE nvd.idrdocumento = rpm.idrdocumento_nuevo
);

INSERT INTO rdocumento_metodo_pago (
  idrdocumento,
  idcuenta_bancaria,
  monto,
  codigo_voucher,
  comprobante,
  comprobante_nombre_visible,
  comprobante_nombre_original,
  comprobante_size_bytes,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  rpm.idrdocumento_nuevo,
  @cuenta_bancaria_default,
  IFNULL(vmp.monto, IFNULL(v.venta_total, 0)),
  NULLIF(TRIM(vmp.codigo_voucher), ''),
  NULLIF(TRIM(vmp.comprobante), ''),
  NULLIF(TRIM(vmp.comprobante_nombre_original), ''),
  NULLIF(TRIM(vmp.comprobante_nombre_original), ''),
  vmp.comprobante_size_bytes,
  '1',
  COALESCE(vmp.created_at, v.created_at, NOW()),
  COALESCE(vmp.updated_at, v.updated_at, NOW()),
  COALESCE(NULLIF(vmp.user_created, 0), @user_id),
  COALESCE(NULLIF(vmp.user_updated, 0), @user_id)
FROM migracion_reserva_pago_map rpm
INNER JOIN gestion_turismo.venta v
  ON v.idventa = rpm.idventa_antiguo
INNER JOIN gestion_turismo.venta_metodo_pago vmp
  ON vmp.idventa = v.idventa
WHERE IFNULL(vmp.estado, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM rdocumento_metodo_pago mp
    WHERE mp.idrdocumento = rpm.idrdocumento_nuevo
  );

COMMIT;