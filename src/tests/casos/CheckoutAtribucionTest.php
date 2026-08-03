<?php
/**
 * Atribucion de notas historicas de check-out sin tocar datos persistidos.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CheckoutAtribucionTest\n";

$notaGenerica = "Observacion previa\n\n"
    . "[CHECK-OUT COMPLETO] 2026-08-01 23:36:44 - Por: Usuario\n"
    . "Habitaciones procesadas: 112";

$resuelta = ReservacionController::aplicarResponsablesCheckoutHistoricos(
    $notaGenerica,
    ['2026-08-01 23:36:44' => 'Dafne']
);

t_ok(strpos($resuelta, 'Por: Dafne') !== false, 'reemplaza Usuario con el responsable comprobado');
t_ok(strpos($resuelta, 'Habitaciones procesadas: 112') !== false, 'conserva el resto de la nota');

$notaConNombre = '[CHECK-OUT PARCIAL] 2026-08-01 20:00:00 - Por: Daniela';
t_eq(
    $notaConNombre,
    ReservacionController::aplicarResponsablesCheckoutHistoricos(
        $notaConNombre,
        ['2026-08-01 20:00:00' => 'Dafne']
    ),
    'no sobrescribe un nombre ya registrado'
);

$sinEvidencia = '[CHECK-OUT COMPLETO] 2026-08-01 21:00:00 - Por: Usuario';
t_eq(
    $sinEvidencia,
    ReservacionController::aplicarResponsablesCheckoutHistoricos(
        $sinEvidencia,
        ['2026-08-01 23:36:44' => 'Dafne']
    ),
    'sin coincidencia temporal conserva el valor original'
);

$varias = "[CHECK-OUT PARCIAL] 2026-08-01 18:00:00 - Por: Sistema\n"
    . "[CHECK-OUT COMPLETO] 2026-08-01 23:36:44 - Por: Usuario";
$variasResueltas = ReservacionController::aplicarResponsablesCheckoutHistoricos(
    $varias,
    [
        '2026-08-01 18:00:00' => 'Ana Perez',
        '2026-08-01 23:36:44' => 'Dafne',
    ]
);
t_ok(strpos($variasResueltas, 'Por: Ana Perez') !== false, 'resuelve una nota parcial generica');
t_ok(strpos($variasResueltas, 'Por: Dafne') !== false, 'resuelve una nota completa generica');

t_fin();
