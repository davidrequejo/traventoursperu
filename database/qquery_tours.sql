USE traventours;

SET @user_id := 1;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS migracion_tours_turno_map (
  idtours_turno_antiguo INT PRIMARY KEY,
  idtours_turno_nuevo BIGINT NOT NULL
);

CREATE TABLE IF NOT EXISTS migracion_tours_map (
  idtours_antiguo INT PRIMARY KEY,
  idtours_nuevo BIGINT NOT NULL
);

INSERT INTO tours_turno (
  descripcion,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT DISTINCT
  LEFT(
    COALESCE(
      NULLIF(TRIM(tt.nombre), ''),
      NULLIF(TRIM(tt.descripcion), ''),
      CONCAT('Turno ', tt.idtours_turno)
    ),
    255
  ),
  '1',
  COALESCE(tt.created_at, NOW()),
  COALESCE(tt.updated_at, NOW()),
  COALESCE(tt.user_created, @user_id),
  COALESCE(tt.user_updated, @user_id)
FROM gestion_turismo.tours_turno tt
WHERE IFNULL(tt.estado_delete, '1') = '1'
  AND NOT EXISTS (
    SELECT 1
    FROM tours_turno nt
    WHERE UPPER(TRIM(nt.descripcion)) = UPPER(TRIM(
      COALESCE(
        NULLIF(tt.nombre, ''),
        NULLIF(tt.descripcion, ''),
        CONCAT('Turno ', tt.idtours_turno)
      )
    ))
      AND nt.estado_trash = '1'
  );

INSERT INTO migracion_tours_turno_map (
  idtours_turno_antiguo,
  idtours_turno_nuevo
)
SELECT
  tt.idtours_turno,
  nt.idtours_turno
FROM gestion_turismo.tours_turno tt
INNER JOIN tours_turno nt
  ON UPPER(TRIM(nt.descripcion)) = UPPER(TRIM(
    COALESCE(
      NULLIF(tt.nombre, ''),
      NULLIF(tt.descripcion, ''),
      CONCAT('Turno ', tt.idtours_turno)
    )
  ))
WHERE IFNULL(tt.estado_delete, '1') = '1'
ON DUPLICATE KEY UPDATE
  idtours_turno_nuevo = VALUES(idtours_turno_nuevo);

INSERT INTO tours (
  idtours_turno,
  idubigeo_distrito,
  codigo,
  nombre,
  precio_publico,
  precio_corporativo,
  precio_tours,
  precio_web,
  descripcion_inicial,
  duracion,
  hora_recojo,
  hora_retorno,
  descripcion,
  descripcion_momento_destacados,
  descripcion_incluye_noincluye,
  ubicacion_maps,
  brochure,
  estado_trash,
  created_at,
  updated_at,
  user_created,
  user_updated
)
SELECT
  COALESCE(tm.idtours_turno_nuevo, 1),
  t.idubigeo_distrito,
  LEFT(COALESCE(NULLIF(TRIM(t.codigo), ''), CONCAT('MIG', t.idtours)), 10),
  COALESCE(NULLIF(TRIM(t.nombre), ''), CONCAT('Tour ', t.idtours)),
  IFNULL(t.precio_publico, 0),
  IFNULL(t.precio_corporativo, 0),
  IFNULL(t.precio_tours, 0),
  IFNULL(t.precio_web, 0),
  NULLIF(TRIM(t.detalle_programa_turistico), ''),
  NULLIF(TRIM(t.detalle_duracion), ''),
  NULL,
  NULL,
  NULLIF(TRIM(t.detalle_programa_turistico), ''),
  NULL,
  NULLIF(TRIM(t.detalle_incluye), ''),
  NULL,
  NULLIF(TRIM(t.brochure), ''),
  '1',
  COALESCE(t.created_at, NOW()),
  COALESCE(t.updated_at, NOW()),
  COALESCE(t.user_created, @user_id),
  COALESCE(t.user_updated, @user_id)
FROM gestion_turismo.tours t
LEFT JOIN migracion_tours_turno_map tm
  ON tm.idtours_turno_antiguo = t.idtours_turno
WHERE IFNULL(t.estado_delete, '1') = '1'
  AND EXISTS (
    SELECT 1
    FROM ubigeo_distrito ud
    WHERE ud.idubigeo_distrito = t.idubigeo_distrito
  )
  AND NOT EXISTS (
    SELECT 1
    FROM tours nt
    WHERE nt.codigo = LEFT(COALESCE(NULLIF(TRIM(t.codigo), ''), CONCAT('MIG', t.idtours)), 10)
  );

INSERT INTO migracion_tours_map (
  idtours_antiguo,
  idtours_nuevo
)
SELECT
  t.idtours,
  nt.idtours
FROM gestion_turismo.tours t
INNER JOIN tours nt
  ON nt.codigo = LEFT(COALESCE(NULLIF(TRIM(t.codigo), ''), CONCAT('MIG', t.idtours)), 10)
WHERE IFNULL(t.estado_delete, '1') = '1'
ON DUPLICATE KEY UPDATE
  idtours_nuevo = VALUES(idtours_nuevo);

COMMIT;