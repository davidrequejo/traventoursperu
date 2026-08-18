USE traventours;

SET @user_id := 1;

SET @doc_sin_identidad := (
  SELECT idsunat_c06_doc_identidad
  FROM sunat_c06_doc_identidad
  WHERE code_sunat = '0'
  LIMIT 1
);

SET @tipo_cliente := (
  SELECT idpersona_tipo
  FROM persona_tipo
  WHERE UPPER(TRIM(nombre)) = 'CLIENTE'
  LIMIT 1
);

START TRANSACTION;

CREATE TABLE IF NOT EXISTS migracion_cliente_map (
  idpersona_cliente_antiguo INT PRIMARY KEY,
  idpersona_antiguo INT NOT NULL,
  idpersona_nuevo BIGINT NOT NULL
);

INSERT INTO persona (
  tipo_documento,
  codigo,
  tipo_persona_sunat,
  numero_documento,
  descripcion,
  nombre_comercial,
  nombre_persona_natural,
  apellido_paterno_persona_natural,
  fecha_nacimiento,
  celular,
  direccion,
  iddistrito,
  cod_ubigeo,
  correo,
  foto_perfil,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  COALESCE(doc.idsunat_c06_doc_identidad, @doc_sin_identidad),
  CONCAT('MIG-CLI-', p.idpersona),
  UPPER(COALESCE(NULLIF(TRIM(p.tipo_persona_sunat), ''), 'NATURAL')),
  NULLIF(TRIM(p.numero_documento), ''),
  LEFT(
    COALESCE(
      NULLIF(TRIM(CONCAT_WS(' ', NULLIF(TRIM(p.nombre_razonsocial), ''), NULLIF(TRIM(p.apellidos_nombrecomercial), ''))), ''),
      CONCAT('CLIENTE ', p.idpersona)
    ),
    255
  ),
  NULLIF(TRIM(p.apellidos_nombrecomercial), ''),
  CASE
    WHEN UPPER(COALESCE(NULLIF(TRIM(p.tipo_persona_sunat), ''), 'NATURAL')) = 'NATURAL'
      THEN NULLIF(TRIM(p.nombre_razonsocial), '')
    ELSE NULL
  END,
  CASE
    WHEN UPPER(COALESCE(NULLIF(TRIM(p.tipo_persona_sunat), ''), 'NATURAL')) = 'NATURAL'
      THEN NULLIF(TRIM(p.apellidos_nombrecomercial), '')
    ELSE NULL
  END,
  p.fecha_nacimiento,
  NULLIF(TRIM(p.celular), ''),
  NULLIF(TRIM(p.direccion), ''),
  ub.iddistrito_nuevo,
  NULLIF(TRIM(p.cod_ubigeo), ''),
  NULLIF(TRIM(p.correo), ''),
  NULLIF(TRIM(p.foto_perfil), ''),
  '1',
  COALESCE(p.created_at, NOW()),
  COALESCE(p.updated_at, NOW()),
  COALESCE(NULLIF(p.user_created, 0), @user_id),
  COALESCE(NULLIF(p.user_updated, 0), @user_id)
FROM gestion_turismo.persona_cliente pc
INNER JOIN gestion_turismo.persona p
  ON p.idpersona = pc.idpersona
LEFT JOIN sunat_c06_doc_identidad doc
  ON doc.code_sunat = p.tipo_documento
LEFT JOIN (
  SELECT ubigeo_codigo, MIN(idubigeo_distrito) AS iddistrito_nuevo
  FROM (
    SELECT ubigeo_inei AS ubigeo_codigo, idubigeo_distrito
    FROM ubigeo_distrito
    WHERE ubigeo_inei IS NOT NULL

    UNION ALL

    SELECT ubigeo_reniec AS ubigeo_codigo, idubigeo_distrito
    FROM ubigeo_distrito
    WHERE ubigeo_reniec IS NOT NULL
  ) u
  GROUP BY ubigeo_codigo
) ub
  ON ub.ubigeo_codigo = p.cod_ubigeo
WHERE IFNULL(pc.estado, '1') = '1'
  AND IFNULL(p.estado, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM persona np
    WHERE np.codigo = CONCAT('MIG-CLI-', p.idpersona)
  );

INSERT INTO migracion_cliente_map (
  idpersona_cliente_antiguo,
  idpersona_antiguo,
  idpersona_nuevo
)
SELECT
  pc.idpersona_cliente,
  p.idpersona,
  np.idpersona
FROM gestion_turismo.persona_cliente pc
INNER JOIN gestion_turismo.persona p
  ON p.idpersona = pc.idpersona
INNER JOIN persona np
  ON np.codigo = CONCAT('MIG-CLI-', p.idpersona)
WHERE IFNULL(pc.estado, '1') = '1'
  AND IFNULL(p.estado, '1') = '1'
ON DUPLICATE KEY UPDATE
  idpersona_antiguo = VALUES(idpersona_antiguo),
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
  @tipo_cliente,
  NOW(),
  NOW(),
  @user_id,
  @user_id
FROM migracion_cliente_map m
WHERE @tipo_cliente IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM persona_tipo_persona ptp
    WHERE ptp.idpersona = m.idpersona_nuevo
      AND ptp.idpersona_tipo = @tipo_cliente
  );

COMMIT;