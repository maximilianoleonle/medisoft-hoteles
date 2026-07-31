-- Personal deja de ser "Personal y Nomina" (jul-2026, decision del owner).
--
-- El modulo 'personal' queda en lo que de verdad hace desde ahora: registro de la
-- gente que trabaja en el hotel + las tareas que se le asignan. TODA la nomina
-- (sueldos, incidencias, calculo por periodos, recibos y pagos) es el modulo
-- aparte 'nomina_avanzada', que ya existe y se cobra por separado.
--
-- Esto es SOLO el texto del catalogo que ve el hotelero al contratar modulos (la
-- casilla del Panel SaaS y el buscador). El apagado de la superficie vieja vive en
-- codigo: personal_nomina_legacy_visible() en src/app/helpers/modulos.php.
--
-- NO toca precios, NO toca hotel_modulos (nadie gana ni pierde el modulo) y NO
-- borra datos: los movimientos laborales ya registrados siguen en sus tablas.
--
-- Idempotente: re-ejecutarla no cambia nada una vez aplicada.

UPDATE modulos
   SET nombre = 'Personal',
       descripcion = 'Registro de trabajadores: datos, puesto, contacto, altas y bajas. Cada ficha muestra las tareas que tiene asignadas. La nomina va en el modulo Nomina.'
 WHERE clave = 'personal';
