-- Centro documental: retencion antes de purga fisica. "Dar de baja" solo
-- cambiaba el estado a 'eliminado' en BD; el archivo se quedaba en el
-- servidor para siempre (jamas habia un unlink real). eliminado_en marca
-- cuando se dio de baja (arranca la ventana de retencion); purgado_en marca
-- cuando el cron de purga (tools/cron_purga_documentos.php) borro el
-- archivo fisico de verdad.
ALTER TABLE documentos
  ADD COLUMN eliminado_en TIMESTAMP NULL DEFAULT NULL
  COMMENT 'Cuando paso a estado eliminado; arranca la ventana de retencion'
  AFTER estado,
  ADD COLUMN purgado_en TIMESTAMP NULL DEFAULT NULL
  COMMENT 'Cuando el cron de purga borro el archivo fisico (NULL = aun en servidor)'
  AFTER eliminado_en;

ALTER TABLE documentos
  ADD KEY idx_documentos_purga (estado, eliminado_en, purgado_en);
