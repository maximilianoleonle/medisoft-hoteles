<?php
/**
 * Contrato de adaptadores por giro (Fases 8/9 nomina core).
 *
 * El motor de nomina es AGNOSTICO del giro: no lee tablas operativas de
 * hoteles, restaurantes ni academias. Cada giro implementa este contrato y
 * PROPONE incidencias normalizadas (origen 'adaptador', estado 'pendiente');
 * un humano las aprueba antes de que cuenten para la nomina.
 *
 * Regla heredada del sistema: la operacion (tareas, asistencias) NUNCA
 * genera nomina de forma automatica; el adaptador solo propone.
 */

interface NominaAdaptadorGiro {

    /** Clave del giro que atiende (hotel, restaurante, academia...). */
    public function giro(): string;

    /** Descripcion corta para UI/diagnostico. */
    public function descripcion(): string;

    /**
     * Propone incidencias del rango como filas 'pendiente' en
     * nomina_incidencias (idempotente por referencia_origen).
     * Devuelve ['propuestas' => int, 'omitidas' => int, 'avisos' => string[]].
     */
    public function proponerIncidencias(int $hotelId, string $fechaInicio, string $fechaFin, ?int $usuarioId = null): array;
}
