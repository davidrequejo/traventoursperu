SET NAMES utf8mb4 COLLATE utf8mb4_spanish_ci;

INSERT INTO users (
  idpersona,
  name,
  email,
  email_verified_at,
  password,
  remember_token,
  estado_trash,
  created_at,
  updated_at,
  user_trash,
  user_created,
  user_updated,
  user_update_sistema
)
SELECT
  p.idpersona,
  COALESCE(NULLIF(p.descripcion, ''), CONCAT('Usuario ', LPAD(p.idpersona, 6, '0'))) AS name,
  CONCAT('usuario', LPAD(p.idpersona, 6, '0'), '@raices.test') AS email,
  NOW() AS email_verified_at,
  '$2y$12$7f.J8E.68DUPUhLUZ6tVO.rrEPIEqxpl0sRZeNohde/EAnf1WfHW.' AS password,
  NULL AS remember_token,
  '1' AS estado_trash,
  NOW() AS created_at,
  NOW() AS updated_at,
  NULL AS user_trash,
  1 AS user_created,
  1 AS user_updated,
  '1' AS user_update_sistema
FROM persona p
WHERE p.idpersona BETWEEN 1 AND 999999
  AND NOT EXISTS (
    SELECT 1
    FROM users u
    WHERE u.idpersona = p.idpersona
  );
