-- Feedback del usuario sobre las respuestas del copiloto (👍/👎 en el
-- widget). util: 1 = le sirvio, 0 = no le sirvio, NULL = sin calificar.
-- Alimenta el panel /copiloto/valor y el observatorio (que enseñar).
ALTER TABLE copiloto_mensajes
  ADD COLUMN util TINYINT(1) NULL DEFAULT NULL AFTER tokens_salida,
  ADD COLUMN feedback_at TIMESTAMP NULL DEFAULT NULL AFTER util;
