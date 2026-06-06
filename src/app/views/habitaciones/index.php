<?php
if (!isset($checkins_pendientes) || !isset($checkouts_vencidos)) {
    
    // Cargar el modelo si no está cargado
    if (!class_exists('Reservacion')) {
        require_once __DIR__ . '/../../models/Reservacion.php';
    }
    
    try {
        // Crear instancia del modelo
        $reservacionTemp = new Reservacion();
        
        // Obtener check-ins pendientes
        $checkins_pendientes = $reservacionTemp->getCheckInsPendientes();
        
        // Obtener check-outs vencidos
        $checkouts_vencidos = $reservacionTemp->getCheckOutsPendientes();
        
        // Placeholder para llegadas tardías (implementación futura)
        $llegadas_tardias = [];
        
        // Log para debug (opcional - puedes comentar estas líneas)
        error_log("Reservaciones pendientes cargadas directamente en la vista");
        error_log("Check-ins pendientes: " . count($checkins_pendientes));
        error_log("Check-outs vencidos: " . count($checkouts_vencidos));
        
    } catch (Exception $e) {
        // Si hay error, inicializar como arrays vacíos
        error_log("Error al cargar reservaciones pendientes: " . $e->getMessage());
        $checkins_pendientes = [];
        $checkouts_vencidos = [];
        $llegadas_tardias = [];
    }
}
// Mapeo de colores de habitación (Área Confortable)
$colores_habitacion = [
    'MOKA'      => '#6F4E37',
    'PURPURA'   => '#800080',
    'ORO'       => '#DAA520',
    'AMARILLO'  => '#F0C420',
    'MARRON'    => '#8B4513',
    'CEREZA'    => '#DE3163',
    'VIOLETA'   => '#7C3AED',
    'LIMON'     => '#84CC16',
    'NARANJA'   => '#EA580C',
    'MARFIL'    => '#C8B88A',
    'AZUL'      => '#2563EB',
    'ROSA'      => '#EC4899',
    'VERDE'     => '#16A34A',
    'UVA'       => '#6B21A8',
    'MENTA'     => '#34D399',
    'AMBAR'     => '#D97706',
    'VINO'      => '#7F1D1D',
    'GRIS'      => '#6B7280',
    'CHOCOLATE' => '#7B3F00',
    'CORAL'     => '#F97316',
    'TURQUESA'  => '#0D9488',
    'MAGENTA'   => '#DB2777',
];
?>
<?php
// Obtener habitaciones en limpieza para el botón
$habitaciones_limpieza = [];
foreach ($habitaciones as $hab) {
    if ($hab['estado'] == 'limpieza') {
        $habitaciones_limpieza[] = [
            'id' => $hab['id'],
            'numero' => $hab['numero'],
            'tipo' => $tipos[$hab['tipo']] ?? $hab['tipo'],
            'piso' => $hab['piso']
        ];
    }
}
$tiene_limpieza = count($habitaciones_limpieza) > 0;
?>
<style>
/* ═══════════════════════════════════════════════════════════════
   VISUAL REDESIGN — Aesthetic Enhancement Layer
   Only CSS changes. Zero functionality modifications.
   ═══════════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Outfit:wght@300;400;500;600;700;800&display=swap');

:root {
    --primary: #4A6741;
    --primary-dark: #3A5233;
    --primary-light: #5C7D52;
    --accent: #C8956C;
    --accent-dark: #B07A52;
    --accent-light: #E0B896;
    --surface: #FAFBF9;
    --surface-card: #FFFFFF;
    --surface-hover: #F5F7F4;
    --text-primary: #1A2E1A;
    --text-secondary: #5A6B5A;
    --text-muted: #8A9B8A;
    --border: #E2E8E0;
    --border-light: #EEF2ED;
    --shadow-sm: 0 1px 3px rgba(74, 103, 65, 0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 12px rgba(74, 103, 65, 0.08), 0 2px 4px rgba(0,0,0,0.04);
    --shadow-lg: 0 8px 30px rgba(74, 103, 65, 0.10), 0 4px 8px rgba(0,0,0,0.04);
    --shadow-xl: 0 16px 48px rgba(74, 103, 65, 0.12), 0 8px 16px rgba(0,0,0,0.04);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;
    --hotel-green: #4A6741;
}

/* Global typography & base */
.habitaciones-view,
.habitaciones-view *:not(i):not([class*="fa-"]):not(.fas):not(.far):not(.fab):not(.fal):not(.fad) {
    font-family: 'DM Sans', system-ui, -apple-system, sans-serif !important;
}

.habitaciones-view h1,
.habitaciones-view h2,
.habitaciones-view h3,
.habitaciones-view h4,
.habitaciones-view .text-2xl,
.habitaciones-view .text-xl,
.habitaciones-view .text-lg {
    font-family: 'Outfit', 'DM Sans', system-ui, sans-serif !important;
    letter-spacing: -0.02em;
}

.habitaciones-view {
    background: linear-gradient(165deg, #F4F7F3 0%, #EDF1EC 40%, #F0F3EF 100%) !important;
    min-height: 100vh;
}

/* Subtle background texture */
.habitaciones-view::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: radial-gradient(circle at 25% 25%, rgba(74, 103, 65, 0.015) 0%, transparent 50%),
                      radial-gradient(circle at 75% 75%, rgba(200, 149, 108, 0.015) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.habitaciones-view > * {
    position: relative;
    z-index: 1;
}

/* ── Header redesign ── */
.modern-header {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(20px) saturate(1.3) !important;
    -webkit-backdrop-filter: blur(20px) saturate(1.3) !important;
    border-bottom: 1px solid rgba(74, 103, 65, 0.08) !important;
    box-shadow: 0 1px 8px rgba(74, 103, 65, 0.05), 0 0 1px rgba(0,0,0,0.05) !important;
    padding: 0.625rem 0 !important;
}

.modern-header h1 {
    color: var(--text-primary) !important;
    font-weight: 700 !important;
    font-size: 1.25rem !important;
}

.modern-header p {
    color: var(--text-muted) !important;
}

.modern-header .bg-gradient-to-br {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.25) !important;
}

/* ── Buttons redesign ── */
.btn-modern {
    border-radius: var(--radius-md) !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid transparent !important;
    font-size: 0.8125rem !important;
}

.btn-modern:hover {
    transform: translateY(-2px) !important;
    box-shadow: var(--shadow-md) !important;
}

.btn-modern.bg-gray-100 {
    background: var(--surface) !important;
    color: var(--text-secondary) !important;
    border-color: var(--border) !important;
}

.btn-modern.bg-gray-100:hover {
    background: white !important;
    border-color: var(--primary) !important;
    color: var(--primary) !important;
}

.btn-modern.bg-emerald-500 {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.3) !important;
}

.btn-modern.bg-emerald-500:hover {
    box-shadow: 0 4px 16px rgba(74, 103, 65, 0.35) !important;
}

.btn-modern.bg-blue-500 {
    background: linear-gradient(135deg, #3B7DD8 0%, #5B93E0 100%) !important;
    box-shadow: 0 2px 8px rgba(59, 125, 216, 0.3) !important;
}

.btn-modern.bg-gradient-to-r {
    background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 100%) !important;
    box-shadow: 0 2px 8px rgba(200, 149, 108, 0.3) !important;
}

.btn-modern.bg-gradient-to-r:hover {
    box-shadow: 0 4px 16px rgba(200, 149, 108, 0.4) !important;
}

/* ── Container & content area — FULL WIDTH ── */
.habitaciones-view .container {
    max-width: 100% !important;
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

.habitaciones-view .container.max-w-7xl {
    max-width: 100% !important;
}

@media (min-width: 1280px) {
    .habitaciones-view .container {
        padding-left: 2rem !important;
        padding-right: 2rem !important;
    }
}

/* ── Stat widgets redesign ── */
/* CRITICAL: Protect Font Awesome from font override */
.habitaciones-view i[class*="fa-"],
.habitaciones-view .fas,
.habitaciones-view .far,
.habitaciones-view .fab,
.habitaciones-view .fal,
.habitaciones-view .fad {
    font-family: "Font Awesome 5 Free", "Font Awesome 5 Brands", "FontAwesome" !important;
    font-style: normal !important;
}

.habitaciones-view .fas {
    font-weight: 900 !important;
}

.habitaciones-view .far {
    font-weight: 400 !important;
}

.stat-widget {
    border-radius: var(--radius-lg) !important;
    border: 1px solid var(--border-light) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
}

/* Decorative accent on stat widgets */
.stat-widget::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-widget:hover::before {
    opacity: 1;
}

.stat-widget.bg-blue-50::before { background: linear-gradient(90deg, #3B7DD8, #5B93E0); }
.stat-widget.bg-emerald-50::before { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.stat-widget.bg-purple-50::before { background: linear-gradient(90deg, #6A42B0, #9575CD); }
.stat-widget.bg-gray-50::before { background: linear-gradient(90deg, #5A6B5A, #8A9B8A); }

.stat-widget::after {
    content: '';
    position: absolute;
    top: -10px;
    right: -10px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    opacity: 0.04;
    pointer-events: none;
}

.stat-widget.bg-blue-50::after { background: #3B7DD8; }
.stat-widget.bg-emerald-50::after { background: var(--primary); }
.stat-widget.bg-purple-50::after { background: #7E57C2; }
.stat-widget.bg-gray-50::after { background: #5A6B5A; }

.stat-widget:hover {
    transform: translateY(-3px) !important;
    box-shadow: var(--shadow-lg) !important;
    border-color: transparent !important;
}

.stat-widget .text-2xl {
    font-weight: 800 !important;
    font-family: 'Outfit', sans-serif !important;
}

.stat-widget .bg-blue-500 {
    background: linear-gradient(135deg, #3B7DD8 0%, #5B93E0 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(59, 125, 216, 0.25) !important;
}

.stat-widget .bg-emerald-500 {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(74, 103, 65, 0.25) !important;
}

.stat-widget .bg-purple-500 {
    background: linear-gradient(135deg, #7E57C2 0%, #9575CD 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(126, 87, 194, 0.25) !important;
}

.stat-widget .bg-gray-600 {
    background: linear-gradient(135deg, #5A6B5A 0%, #7A8B7A 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(90, 107, 90, 0.25) !important;
}

/* Stat widget backgrounds */
.stat-widget.bg-blue-50 {
    background: linear-gradient(135deg, #F0F5FC 0%, #E8F0FA 100%) !important;
    border-color: rgba(59, 125, 216, 0.12) !important;
}

.stat-widget.bg-emerald-50 {
    background: linear-gradient(135deg, #F0F7EF 0%, #E5F0E4 100%) !important;
    border-color: rgba(74, 103, 65, 0.12) !important;
}

.stat-widget.bg-purple-50 {
    background: linear-gradient(135deg, #F5F0FC 0%, #EDE5FA 100%) !important;
    border-color: rgba(126, 87, 194, 0.12) !important;
}

.stat-widget.bg-gray-50 {
    background: linear-gradient(135deg, #F5F7F5 0%, #EDF0ED 100%) !important;
    border-color: rgba(90, 107, 90, 0.12) !important;
}

/* White wrapper for stats */
.bg-white.rounded-lg.shadow-sm.p-3.mb-4 {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-sm) !important;
    padding: 1rem !important;
}

/* ── Filter bar redesign ── */
.bg-white.rounded-xl.shadow-sm.p-2.mb-4,
.bg-white.rounded-xl.shadow-sm.p-3.mb-4,
div[class*="bg-white rounded-xl shadow-sm"][class*="mb-4"] {
    background: rgba(255, 255, 255, 0.75) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-sm) !important;
}

.filter-input,
.filter-select,
.filter-date {
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
    background: var(--surface) !important;
    color: var(--text-primary) !important;
    font-size: 0.8125rem !important;
    transition: all 0.2s ease !important;
}

.filter-input:focus,
.filter-select:focus,
.filter-date:focus {
    border-color: var(--primary) !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(74, 103, 65, 0.1) !important;
}

.filter-btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    color: white !important;
    border-radius: var(--radius-sm) !important;
    box-shadow: 0 2px 6px rgba(74, 103, 65, 0.2) !important;
}

.filter-btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%) !important;
    box-shadow: 0 3px 10px rgba(74, 103, 65, 0.3) !important;
    transform: translateY(-1px) !important;
}

.filter-btn-reset {
    background: var(--surface) !important;
    color: var(--text-muted) !important;
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
}

.filter-btn-today {
    background: var(--surface) !important;
    color: var(--text-secondary) !important;
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 0.75rem !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
    height: 32px !important;
    text-decoration: none !important;
    font-weight: 500 !important;
    transition: all 0.2s !important;
}

.filter-btn-today:hover {
    border-color: var(--primary) !important;
    color: var(--primary) !important;
    background: white !important;
}

/* ── Room cards - state colors refined ── */
.estado-disponible {
    background: linear-gradient(145deg, #E8F5E4 0%, #D4EDCF 50%, #C5E4BF 100%) !important;
    border-left: 4px solid var(--primary) !important;
    box-shadow: var(--shadow-sm) !important;
}

.estado-por_llegar {
    background: linear-gradient(145deg, #EDE5FA 0%, #DED3F5 50%, #D4C7F0 100%) !important;
    border-left: 4px solid #7E57C2 !important;
    box-shadow: 0 2px 4px rgba(126, 87, 194, 0.1) !important;
}

.estado-ocupada {
    background: linear-gradient(145deg, #FDE8E8 0%, #FACACA 50%, #F5B8B8 100%) !important;
    border-left: 4px solid #D45B5B !important;
    box-shadow: 0 2px 4px rgba(212, 91, 91, 0.1) !important;
}

.estado-mantenimiento {
    background: linear-gradient(145deg, #FEF3DC 0%, #FDE8B9 50%, #FBDDA0 100%) !important;
    border-left: 4px solid #C8956C !important;
    box-shadow: 0 2px 4px rgba(200, 149, 108, 0.1) !important;
}

.estado-limpieza {
    background: linear-gradient(145deg, #E3F0FC 0%, #D0E4F9 50%, #BFD9F5 100%) !important;
    border-left: 4px solid #3B7DD8 !important;
    box-shadow: 0 2px 4px rgba(59, 125, 216, 0.1) !important;
}

/* ── CREATIVE CARD ENHANCEMENTS ── */
/* Decorative corner accents on cards */
.flip-card-front::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 50px;
    height: 50px;
    border-radius: 0 var(--radius-lg) 0 100%;
    opacity: 0.06;
    pointer-events: none;
    z-index: 1;
    transition: all 0.3s ease;
}

.estado-disponible.flip-card-front::before { background: var(--primary); }
.estado-por_llegar.flip-card-front::before { background: #7E57C2; }
.estado-ocupada.flip-card-front::before { background: #D45B5B; }
.estado-mantenimiento.flip-card-front::before { background: #C8956C; }
.estado-limpieza.flip-card-front::before { background: #3B7DD8; }

/* Decorative bottom bar on cards */
.flip-card-front::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 12px;
    right: 12px;
    height: 3px;
    border-radius: 3px 3px 0 0;
    opacity: 0;
    transform: scaleX(0);
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    z-index: 2;
}

.estado-disponible.flip-card-front::after { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.estado-por_llegar.flip-card-front::after { background: linear-gradient(90deg, #6A42B0, #9575CD); }
.estado-ocupada.flip-card-front::after { background: linear-gradient(90deg, #C94444, #E88080); }
.estado-mantenimiento.flip-card-front::after { background: linear-gradient(90deg, #B07A52, #D4A070); }
.estado-limpieza.flip-card-front::after { background: linear-gradient(90deg, #2E6AB0, #5B93E0); }

.flip-card:hover .flip-card-front::after {
    opacity: 1;
    transform: scaleX(1);
}

.flip-card:hover .flip-card-front::before {
    opacity: 0.1;
    width: 65px;
    height: 65px;
}

/* Subtle diagonal pattern on disponible cards */
.estado-disponible {
    background-image: 
        linear-gradient(145deg, #E8F5E4 0%, #D4EDCF 50%, #C5E4BF 100%),
        repeating-linear-gradient(
            45deg,
            transparent,
            transparent 10px,
            rgba(74, 103, 65, 0.02) 10px,
            rgba(74, 103, 65, 0.02) 11px
        ) !important;
}

/* Subtle dots pattern on ocupada cards */
.estado-ocupada {
    background-image:
        linear-gradient(145deg, #FDE8E8 0%, #FACACA 50%, #F5B8B8 100%),
        radial-gradient(circle, rgba(212, 91, 91, 0.04) 1px, transparent 1px) !important;
    background-size: auto, 12px 12px !important;
}

/* Enhanced room number styling */
.flip-card-front h3 {
    position: relative;
    display: inline-block;
}

/* Status badge glow effect */
.estado-icon {
    position: relative;
}

.estado-disponible .estado-icon::after,
.estado-por_llegar .estado-icon::after,
.estado-ocupada .estado-icon::after,
.estado-limpieza .estado-icon::after,
.estado-mantenimiento .estado-icon::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: inherit;
    opacity: 0.25;
    z-index: -1;
}

.estado-disponible .estado-icon::after { box-shadow: 0 0 8px var(--primary); }
.estado-por_llegar .estado-icon::after { box-shadow: 0 0 8px #7E57C2; }
.estado-ocupada .estado-icon::after { box-shadow: 0 0 8px #D45B5B; }
.estado-limpieza .estado-icon::after { box-shadow: 0 0 8px #3B7DD8; }
.estado-mantenimiento .estado-icon::after { box-shadow: 0 0 8px #C8956C; }

/* Card inner shadow for depth */
.flip-card-front {
    box-shadow: inset 0 -1px 0 rgba(0,0,0,0.03), inset 0 1px 0 rgba(255,255,255,0.5) !important;
}

/* Price tag styling */
.flip-card-front .text-sm.font-bold {
    background: rgba(255,255,255,0.5);
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
    backdrop-filter: blur(4px);
}

/* ── Flip card refinement ── */
.flip-card {
    border-radius: var(--radius-lg) !important;
}

.flip-card-front,
.flip-card-back {
    border-radius: var(--radius-lg) !important;
}

.room-card-compact {
    border-radius: var(--radius-lg) !important;
}

.room-card-compact:hover {
    box-shadow: rgba(74, 103, 65, 0.18) 0px 60px 30px -40px !important;
}

.room-card-compact.flipped:hover {
    box-shadow: rgba(74, 103, 65, 0.22) 0px 60px 30px -40px !important;
}

/* Staggered card entrance animation */
@keyframes cardSlideUp {
    from { 
        opacity: 0; 
        transform: translateY(20px) scale(0.97); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

.room-card-compact {
    animation: cardSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards !important;
}

/* ── Back panel state colors — handled via inline style from PHP now ── */

/* Decorative circles on back panel */
.flip-card-back::before {
    content: '';
    position: absolute;
    top: -20px;
    right: -20px;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    pointer-events: none;
}

.flip-card-back::after {
    content: '';
    position: absolute;
    bottom: -12px;
    left: -12px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    pointer-events: none;
}

.flip-card-back .btn-action {
    border-radius: var(--radius-sm) !important;
    font-weight: 600 !important;
    backdrop-filter: blur(4px) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    background: rgba(255, 255, 255, 0.15) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.flip-card-back .btn-action:hover {
    background: rgba(255, 255, 255, 0.28) !important;
    transform: scale(1.04) !important;
}

.flip-card-back .btn-primary {
    background: white !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.18) !important;
    color: var(--room-accent-color, #4A6741) !important;
    font-weight: 700 !important;
    border-radius: 8px !important;
}

.flip-card-back .btn-primary:hover {
    background: rgba(255,255,255,0.95) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.22) !important;
    transform: translateY(-1px) !important;
}

/* ── Estado icons refined ── */
.estado-icon {
    border-radius: var(--radius-sm) !important;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1) !important;
}

.estado-disponible .estado-icon { background: var(--primary) !important; }
.estado-por_llegar .estado-icon { background: #7E57C2 !important; }
.estado-ocupada .estado-icon { background: #D45B5B !important; }
.estado-mantenimiento .estado-icon { background: var(--accent) !important; }
.estado-limpieza .estado-icon { background: #3B7DD8 !important; }

/* ── Check-in/out panels redesign ── */
.bg-purple-50.p-3.rounded-t-lg {
    background: linear-gradient(135deg, #F5F0FC 0%, #EDE5FA 100%) !important;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
}

.bg-yellow-50.p-3.rounded-t-lg {
    background: linear-gradient(135deg, #FEF7EC 0%, #FDF0DC 100%) !important;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
}

/* Panels container */
.grid.grid-cols-1.lg\:grid-cols-2.gap-3.mb-4 > .bg-white.rounded-lg.shadow-sm {
    border-radius: var(--radius-lg) !important;
    border: 1px solid var(--border-light) !important;
    overflow: hidden !important;
    box-shadow: var(--shadow-sm) !important;
}

/* ── Alert panel redesign ── */
.alert-panel {
    border-radius: var(--radius-xl) !important;
    border: 1px solid rgba(220, 80, 60, 0.12) !important;
    border-left: 5px solid #D45B5B !important;
    box-shadow: var(--shadow-md) !important;
    overflow: hidden !important;
    background: white !important;
}

.alert-header {
    background: linear-gradient(135deg, #FFF0EF 0%, #FFE6E4 100%) !important;
    border-bottom: 1px solid rgba(220, 80, 60, 0.1) !important;
    padding: 1rem 1.25rem !important;
}

.alert-badge {
    background: linear-gradient(135deg, #D45B5B 0%, #E07070 100%) !important;
    border-radius: 20px !important;
    box-shadow: 0 2px 6px rgba(212, 91, 91, 0.3) !important;
}

.alert-item {
    border-radius: var(--radius-md) !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.alert-item:hover {
    box-shadow: var(--shadow-md) !important;
    transform: translateX(3px) !important;
}

.btn-alert {
    border-radius: var(--radius-sm) !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}

.btn-alert-primary {
    background: linear-gradient(135deg, #D45B5B 0%, #E07070 100%) !important;
    box-shadow: 0 2px 6px rgba(212, 91, 91, 0.25) !important;
}

.btn-alert-warning {
    background: linear-gradient(135deg, #E89E40 0%, #F0B060 100%) !important;
    box-shadow: 0 2px 6px rgba(232, 158, 64, 0.25) !important;
}

.btn-alert-info {
    background: linear-gradient(135deg, #3B7DD8 0%, #5B93E0 100%) !important;
    box-shadow: 0 2px 6px rgba(59, 125, 216, 0.25) !important;
}

/* ── Quick view modal redesign ── */
#vistaRapidaModal .bg-white.rounded-xl {
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-xl) !important;
    border: 1px solid var(--border-light) !important;
}

#vistaRapidaModal .bg-gradient-to-r {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, var(--primary-light) 100%) !important;
    padding: 1rem 1.25rem !important;
}

/* Quick view room tiles */
.room-quick-view {
    border-radius: var(--radius-md) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.room-quick-view .font-bold,
.room-quick-view .text-xs {
    font-family: 'Outfit', sans-serif !important;
}

.room-quick-view:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
}

/* ── Cleaning modal redesign ── */
#modalLimpieza .bg-white.rounded-xl {
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-xl) !important;
}

#modalLimpieza .bg-gradient-to-r {
    background: linear-gradient(135deg, #2E6AB0 0%, #3B7DD8 60%, #5B93E0 100%) !important;
}

#modalLimpieza label {
    border-radius: var(--radius-md) !important;
    transition: all 0.2s ease !important;
}

#modalLimpieza label:hover {
    background: #E3F0FC !important;
    border-color: #3B7DD8 !important;
}

/* ── Tooltip redesign ── */
.room-tooltip {
    border-radius: var(--radius-md) !important;
    box-shadow: var(--shadow-xl) !important;
    border: 1px solid var(--border-light) !important;
    backdrop-filter: blur(8px) !important;
    background: rgba(255, 255, 255, 0.96) !important;
}

.room-tooltip .tooltip-header {
    font-family: 'Outfit', sans-serif !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
}

.room-tooltip .tooltip-guest {
    background: linear-gradient(135deg, #F5F7F4 0%, #EDF0ED 100%) !important;
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
}

/* ── Room color stripe refined ── */
.room-color-stripe {
    border-radius: 4px !important;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2), 0 0 0 1px rgba(255,255,255,0.5) !important;
}

/* ── SweetAlert custom styling ── */
.swal2-popup {
    border-radius: var(--radius-xl) !important;
}

.swal2-popup *:not(i):not([class*="fa-"]):not(.fas):not(.far):not(.fab) {
    font-family: 'DM Sans', system-ui, sans-serif !important;
}

.swal2-title {
    font-family: 'Outfit', sans-serif !important;
    color: var(--text-primary) !important;
}

/* ── Scrollbar styling ── */
.habitaciones-view ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.habitaciones-view ::-webkit-scrollbar-track {
    background: var(--surface);
    border-radius: 3px;
}

.habitaciones-view ::-webkit-scrollbar-thumb {
    background: var(--text-muted);
    border-radius: 3px;
}

.habitaciones-view ::-webkit-scrollbar-thumb:hover {
    background: var(--text-secondary);
}

/* ── No results message ── */
.habitaciones-view .bg-white.rounded-lg.shadow-sm.p-8 {
    background: rgba(255, 255, 255, 0.8) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-md) !important;
}

/* ── Date availability banner ── */
.bg-blue-50.border.border-blue-200.rounded-lg {
    background: linear-gradient(135deg, #EDF4FC 0%, #E3EEF9 100%) !important;
    border: 1px solid rgba(59, 125, 216, 0.15) !important;
    border-radius: var(--radius-md) !important;
}

/* ── Loading animation refined ── */
@keyframes slideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.room-card-compact {
    animation: slideUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards !important;
}

/* ── Checkout/Checkin indicators refined ── */
.checkout-today-indicator {
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4) !important;
    font-weight: 800 !important;
    letter-spacing: 0.02em !important;
}

.checkin-vencido-indicator,
.checkout-vencido-indicator {
    border-radius: 8px !important;
    letter-spacing: 0.02em !important;
}

.late-arrival-indicator {
    border-radius: 8px !important;
    letter-spacing: 0.02em !important;
}

/* ── Purple badge (check-ins count) ── */
.bg-purple-600.text-white.text-xs.px-2 {
    background: linear-gradient(135deg, #6A42B0 0%, #8E65D0 100%) !important;
    box-shadow: 0 1px 4px rgba(106, 66, 176, 0.3) !important;
}

.bg-yellow-600.text-white.text-xs.px-2 {
    background: linear-gradient(135deg, #C8956C 0%, #D4A070 100%) !important;
    box-shadow: 0 1px 4px rgba(200, 149, 108, 0.3) !important;
}

/* ── Fix subtle details ── */
.bg-purple-50.rounded.hover\:bg-purple-100 {
    border-radius: var(--radius-sm) !important;
}

.bg-yellow-50.rounded.hover\:bg-yellow-100 {
    border-radius: var(--radius-sm) !important;
}

/* Price text */
.flip-card-front .text-sm.font-bold {
    color: var(--primary-dark) !important;
    font-family: 'Outfit', sans-serif !important;
}

/* Room number in cards */
.flip-card-front h3 {
    font-family: 'Outfit', sans-serif !important;
    color: var(--text-primary) !important;
    font-weight: 800 !important;
}

/* Mobile flip content header */
.mobile-flip-content h3 {
    font-family: 'Outfit', sans-serif !important;
    font-weight: 800 !important;
}

/* ── Grid gap refinement ── */
#habitaciones-grid {
    gap: 1.25rem !important;
}

/* Add 5 columns for very wide screens */
@media (min-width: 1536px) {
    #habitaciones-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
    }
}

/* Add 6 columns for ultra-wide */
@media (min-width: 1800px) {
    #habitaciones-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 640px) {
    #habitaciones-grid {
        gap: 1rem !important;
    }
}

/* ── Btn opcion refinement ── */
.btn-opcion {
    border-radius: var(--radius-sm) !important;
    transition: all 0.2s ease !important;
}

.btn-opcion.activo {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.3) !important;
}

/* ── Responsive refinements ── */
@media (max-width: 640px) {
    .modern-header {
        padding: 0.5rem 0 !important;
    }
    
    .stat-widget {
        border-radius: var(--radius-md) !important;
    }
    
    .bg-white.rounded-lg.shadow-sm.p-3.mb-4 {
        border-radius: var(--radius-lg) !important;
        padding: 0.75rem !important;
    }
}

/* Texto verde oscuro para estado disponible */
.estado-disponible .text-center p,
.estado-disponible_fecha .text-center p {
    color: #059669 !important;
}

/* Texto oscuro para estado ocupada */
.estado-ocupada .bg-white\/70 p,
.estado-ocupada_fecha .bg-white\/70 p {
    color: #1f2937 !important; /* Gris muy oscuro */
}

/* Texto oscuro para estado por llegar */
.estado-por_llegar .bg-white\/70 p {
    color: #1f2937 !important; /* Gris muy oscuro */
}
/* Texto azul oscuro para limpieza */
.estado-limpieza .text-center p {
    color: #1e40af !important;
}
/* Mejoras adicionales para tooltips */
.room-tooltip {
    display: none;
    font-family: system-ui, -apple-system, sans-serif;
}

.room-tooltip.show {
    display: block;
}

.room-tooltip .tooltip-header {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #1f2937;
}

.room-tooltip .tooltip-info i {
    color: #6b7280;
    flex-shrink: 0;
}

.room-tooltip .tooltip-guest {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border: 1px solid #e5e7eb;
}

.room-tooltip .tooltip-guest i {
    color: #6b7280;
}

/* Animación de entrada mejorada */
@keyframes tooltipFadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.room-tooltip.show {
    animation: tooltipFadeIn 0.2s ease-out;
}

/* Sombra mejorada para el tooltip */
.room-tooltip {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.05);
}
/* Texto ámbar oscuro para mantenimiento */
.estado-mantenimiento .text-center p {
    color: #92400e !important;
}

/* Para móviles específicamente */
@media (max-width: 640px) {
    .mobile-flip-content .text-center p {
        font-weight: 700; /* Más negrita para mejor legibilidad */
    }
}

/* NUEVO: Texto oscuro para el estado combinado limpieza + por llegar */
.estado-limpieza-por-llegar .text-center p {
    color: #1f2937 !important;
}
/* CSS crítico */
.habitaciones-view { 
    opacity: 0; 
    transition: opacity 0.3s ease;
    min-height: 100vh;
    background: #f8f9fa;
}
.habitaciones-view.loaded { opacity: 1; }

/* Header flotante moderno y compacto */
.modern-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0,0,0,0.1);
    z-index: 40;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    padding: 0.75rem 0;
}

/* Header sticky solo en desktop */
@media (min-width: 768px) {
    .modern-header {
        position: sticky;
        top: 0;
    }
}

.modern-header .btn-modern {
    padding: 0.375rem 0.875rem;
    font-size: 0.8125rem;
}

.modern-header .btn-modern i {
    font-size: 0.75rem;
}

@media (max-width: 640px) {
    .modern-header .container {
        padding: 0.75rem;
    }
    
    .modern-header h1 {
        font-size: 1.125rem;
    }
    
    .modern-header .btn-modern {
        padding: 0.375rem 0.625rem;
        font-size: 0.75rem;
    }
}

/* Filtros compactos y modernos */
.filter-input,
.filter-select,
.filter-date {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
    transition: all 0.2s;
    height: 32px;
}

.filter-input:focus,
.filter-select:focus,
.filter-date:focus {
    outline: none;
    border-color: #4A6741;
    background: white;
    box-shadow: 0 0 0 3px rgba(74, 103, 65, 0.1);
}

.filter-select {
    padding-right: 1.5rem;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.25rem center;
    background-repeat: no-repeat;
    background-size: 1rem;
    appearance: none;
}

/* Estilos para modales con scroll */
.swal-popup-scrollable {
    max-height: 90vh !important;
    display: flex !important;
    flex-direction: column !important;
}

.swal-content-scrollable {
    overflow-y: auto !important;
    max-height: calc(90vh - 200px) !important;
}

/* Estilo específico para la lista de habitaciones */
.swal2-html-container .bg-white {
    scrollbar-width: thin;
    scrollbar-color: #FB923C #FED7AA;
}

.swal2-html-container .bg-white::-webkit-scrollbar {
    width: 8px;
}

.swal2-html-container .bg-white::-webkit-scrollbar-track {
    background: #FED7AA;
    border-radius: 4px;
}

.swal2-html-container .bg-white::-webkit-scrollbar-thumb {
    background: #FB923C;
    border-radius: 4px;
}

.swal2-html-container .bg-white::-webkit-scrollbar-thumb:hover {
    background: #F97316;
}

.filter-date {
    padding-right: 0.375rem;
}

.filter-btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    height: 32px;
    border: 1px solid transparent;
}

.filter-btn i {
    font-size: 0.625rem;
}

.filter-btn-primary {
    background: #4A6741;
    color: white;
}

.filter-btn-primary:hover {
    background: #3A5233;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(74, 103, 65, 0.2);
}

.filter-btn-reset {
    background: #f3f4f6;
    color: #6b7280;
    border-color: #e5e7eb;
}

.filter-btn-reset:hover {
    background: #e5e7eb;
    color: #4b5563;
}

/* Ajustes móviles para filtros */
@media (max-width: 640px) {
    .filter-input,
    .filter-select,
    .filter-date,
    .filter-btn {
        font-size: 0.8125rem;
        height: 36px;
    }
    
    .filter-input {
        padding-left: 1.75rem;
    }
    
    .filter-select,
    .filter-date {
        min-width: 0;
        flex: 1;
    }
}

/* Tarjetas compactas */
.room-card-compact { 
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
    height: 100%;
}
.room-card-compact:hover { 
    transform: scale(1.02); 
    box-shadow: 0 8px 16px rgba(0,0,0,0.12); 
}



/* Badge de color para la card */
.room-color-stripe {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 6px;
    height: 24px;
    border-radius: 3px;
    z-index: 5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
}

@media (max-width: 640px) {
    .room-color-stripe {
        width: 5px;
        height: 20px;
        top: 6px;
        left: 6px;
    }
}

/* Indicadores flotantes */
.checkout-today-indicator {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #fbbf24;
    color: #78350f;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 9999px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 2px;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 10;
}
/* Animación para el ícono de TV cuando hay controles pendientes */
@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        opacity: 1;
    }
}

.animate-pulse {
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.late-arrival-indicator {
    position: absolute;
    top: 4px;
    left: 4px;
    background: #581c87;
    color: #f3e8ff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 9999px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 2px;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Indicador de CHECK-IN VENCIDO - MUY LLAMATIVO */
.checkin-vencido-indicator {
    position: absolute;
    top: 4px;
    left: 4px;
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 9999px;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 3px;
    animation: alertPulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 20;
    box-shadow: 0 0 15px rgba(220, 38, 38, 0.6), 0 4px 8px rgba(0,0,0,0.3);
    border: 2px solid #fee2e2;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.checkin-vencido-indicator i {
    animation: shake 0.5s ease-in-out infinite;
}

/* Indicador de CHECK-OUT VENCIDO - MUY LLAMATIVO */
.checkout-vencido-indicator {
    position: absolute;
    top: 4px;
    right: 4px;
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
    color: white;
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 9999px;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 3px;
    animation: alertPulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 20;
    box-shadow: 0 0 15px rgba(234, 88, 12, 0.6), 0 4px 8px rgba(0,0,0,0.3);
    border: 2px solid #fed7aa;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.checkout-vencido-indicator i {
    animation: shake 0.5s ease-in-out infinite;
}

/* Animación de pulso más intensa para alertas críticas */
@keyframes alertPulse {
    0%, 100% { 
        opacity: 1;
        transform: scale(1);
    }
    50% { 
        opacity: 0.7;
        transform: scale(1.05);
    }
}

/* Animación de temblor para los íconos de alerta */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}

/* Estados con colores distintivos */
.estado-disponible { 
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    border-left: 4px solid #059669;
    box-shadow: 0 2px 4px rgba(5, 150, 105, 0.1);
}

/* Tarjetas con check-in vencido - borde rojo pulsante */
.flip-card.has-checkin-vencido .flip-card-front {
    border: 3px solid #dc2626 !important;
    box-shadow: 0 0 20px rgba(220, 38, 38, 0.4), 0 4px 8px rgba(0,0,0,0.1) !important;
    animation: borderPulse 2s ease-in-out infinite;
}

/* Tarjetas con check-out vencido - borde naranja pulsante */
.flip-card.has-checkout-vencido .flip-card-front {
    border: 3px solid #ea580c !important;
    box-shadow: 0 0 20px rgba(234, 88, 12, 0.4), 0 4px 8px rgba(0,0,0,0.1) !important;
    animation: borderPulse 2s ease-in-out infinite;
}

/* Animación de borde pulsante para alertas */
@keyframes borderPulse {
    0%, 100% { 
        box-shadow: 0 0 20px rgba(220, 38, 38, 0.4), 0 4px 8px rgba(0,0,0,0.1);
    }
    50% { 
        box-shadow: 0 0 30px rgba(220, 38, 38, 0.6), 0 6px 12px rgba(0,0,0,0.15);
    }
}

/* Ajustes para indicadores en móvil */
@media (max-width: 640px) {
    .checkin-vencido-indicator,
    .checkout-vencido-indicator,
    .checkout-today-indicator,
    .late-arrival-indicator {
        font-size: 8px;
        padding: 2px 5px;
        gap: 2px;
    }
    
    .checkin-vencido-indicator i,
    .checkout-vencido-indicator i {
        font-size: 8px;
    }
    
    /* Mantener bordes pulsantes visibles en móvil */
    .flip-card.has-checkin-vencido .flip-card-front,
    .flip-card.has-checkout-vencido .flip-card-front {
        border-width: 2px !important;
    }
}

.estado-por_llegar { 
    background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%) !important;
    border-left: 4px solid #9333ea;
    box-shadow: 0 2px 4px rgba(147, 51, 234, 0.15);
}

.estado-ocupada { 
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    border-left: 4px solid #dc2626;
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
}

.estado-mantenimiento { 
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border-left: 4px solid #d97706;
    box-shadow: 0 2px 4px rgba(217, 119, 6, 0.1);
}

.estado-limpieza { 
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
    border-left: 4px solid #2563eb;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
}
/* NUEVO: Estado combinado - Limpieza + Por Llegar (degradado azul a morado) */
.estado-limpieza-por-llegar { 
    background: linear-gradient(45deg, #dbeafe 0%, #dbeafe 45%, #e9d5ff 55%, #e9d5ff 100%) !important;
    border-left: 4px solid;
    border-image: linear-gradient(to bottom, #2563eb 0%, #9333ea 100%) 1;
    box-shadow: 0 2px 4px rgba(107, 114, 128, 0.15);
}

.estado-doble { 
    background: linear-gradient(45deg, #fee2e2 0%, #fee2e2 45%, #e9d5ff 55%, #e9d5ff 100%) !important;
    border-left: 4px solid;
    border-image: linear-gradient(to bottom, #dc2626 0%, #7c3aed 100%) 1;
    box-shadow: 0 2px 4px rgba(107, 114, 128, 0.15);
}

/* Estados para vista por fecha */
.estado-disponible_fecha {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    border-left: 4px solid #059669;
    box-shadow: 0 2px 4px rgba(5, 150, 105, 0.1);
}

.estado-ocupada_fecha {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    border-left: 4px solid #dc2626;
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
}

/* Iconos de estado */
.estado-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.estado-disponible .estado-icon { background: #059669; color: white; }
.estado-por_llegar .estado-icon { background: #9333ea; color: white; }
.estado-ocupada .estado-icon { background: #475569; color: white; }
.estado-mantenimiento .estado-icon { background: #d97706; color: white; }
.estado-limpieza .estado-icon { background: #2563eb; color: white; }
.estado-doble .estado-icon { 
    background: linear-gradient(45deg, #dc2626 50%, #9333ea 50%); 
    color: white; 
}

/* NUEVO: Icono para estado combinado limpieza + por llegar */
.estado-limpieza-por-llegar .estado-icon { 
    background: linear-gradient(45deg, #2563eb 50%, #9333ea 50%); 
    color: white; 
}

/* Widgets simplificados */
.stat-widget {
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.stat-widget:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}

/* Botones modernos */
.btn-modern {
    border: none;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Animación de carga */
@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.room-card-compact {
    animation: slideUp 0.3s ease forwards;
}

/* ═══════════════════════════════════════════
   UIVERSE CARD ANIMATION — Click to reveal
   ═══════════════════════════════════════════ */

.flip-card {
    height: 165px;
    background: white;
    border-radius: 22px;
    padding: 3px;
    position: relative;
    overflow: hidden;
    box-shadow: rgba(74, 103, 65, 0.22) 0px 50px 25px -40px;
    transition: height 0.4s ease-in-out, border-radius 0.3s ease;
    cursor: pointer;
}

.flip-card.flipped {
    height: 240px;
    border-top-left-radius: 42px;
}

@media (max-width: 1024px) {
    .flip-card { height: 172px; }
    .flip-card.flipped { height: 245px; }
}
@media (max-width: 768px) {
    .flip-card { height: 178px; }
    .flip-card.flipped { height: 250px; }
}
@media (max-width: 640px) {
    .flip-card { height: 190px; }
    .flip-card.flipped { height: 260px; }
    #habitaciones-grid { gap: 0.75rem; }
}

/* Ocultar cara frontal completamente cuando está abierta */
.flip-card.flipped .flip-card-front {
    opacity: 0;
    pointer-events: none;
}

/* Flat inner — no 3D transform */
.flip-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: left;
    transition: height 0.4s ease-in-out;
}
/* Tooltip para vista rápida */
.room-tooltip {
    position: absolute;
    z-index: 9999;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    padding: 0.75rem;
    min-width: 220px;
    max-width: 280px;
    pointer-events: none;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.2s ease;
}

.room-tooltip.show {
    opacity: 1;
    transform: translateY(0);
}

/* Agregar en tu sección de estilos */
.btn-success {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.3);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(74, 103, 65, 0.4);
}

.room-tooltip::before {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid white;
}

.room-tooltip .tooltip-header {
    font-weight: bold;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.room-tooltip .tooltip-info {
    font-size: 0.75rem;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.room-tooltip .tooltip-info i {
    width: 14px;
    text-align: center;
    color: #6b7280;
}

.room-tooltip .tooltip-guest {
    background: #f3f4f6;
    padding: 0.375rem 0.5rem;
    border-radius: 0.375rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
}

.room-tooltip .tooltip-price {
    font-weight: bold;
    color: #059669;
    margin-top: 0.5rem;
    text-align: right;
    font-size: 0.875rem;
}
/* ── UIverse card — click reveals back panel ── */
.flip-card.flipped {
    border-top-left-radius: 42px;
}

.flip-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
}

/* Front stays in place, just gets covered by the back panel sliding up */
.flip-card-front {
    position: absolute;
    width: calc(100% - 6px);
    height: calc(100% - 6px);
    top: 3px;
    left: 3px;
    z-index: 1;
    border-radius: 19px;
    overflow: hidden;
    padding: 12px;
}

/* ── BACK PANEL: slides up from bottom ── */
.flip-card-back {
    position: absolute;
    left: 3px;
    right: 3px;
    top: 100%;
    bottom: 3px;
    z-index: 2;
    border-radius: 19px;
    overflow: hidden;
    box-shadow: rgba(0,0,0,0.1) 0px 5px 10px inset;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: top 0.4s cubic-bezier(0.645, 0.045, 0.355, 1) 0s;
    padding: 10px 12px;
}

.flip-card.flipped .flip-card-back {
    top: 3px;
    transition: top 0.4s cubic-bezier(0.645, 0.045, 0.355, 1) 0.05s;
}

@media (max-width: 640px) {
    .flip-card-back {
        padding: 8px;
    }
    .flip-card-back h4 {
        font-size: 0.7rem;
        margin-bottom: 0.2rem;
    }
    .flip-card-back .info-item {
        font-size: 0.55rem;
        margin-bottom: 0.05rem;
    }
    .flip-card-back .action-buttons {
        gap: 0.2rem;
        margin-top: 0.2rem;
    }
    .flip-card-back .btn-action {
        font-size: 0.55rem;
        padding: 0.3rem 0.45rem;
        min-height: 28px;
    }
}



/* ── Front face circle view (shown when panel is open) ── */
/* Removed: no circle animation. Back panel fully covers the card. */

/* Subtle click indicator on front face — small dot at bottom center */
.flip-card-front::after {
    content: '';
    position: absolute;
    bottom: 7px;
    left: 50%;
    transform: translateX(-50%);
    width: 28px;
    height: 4px;
    background: var(--room-accent-color, #4A6741);
    opacity: 0.4;
    border-radius: 2px;
    pointer-events: none;
    transition: opacity 0.2s ease, width 0.2s ease;
}

.flip-card:hover .flip-card-front::after {
    opacity: 0.7;
    width: 40px;
}

.flip-card.flipped .flip-card-front::after {
    opacity: 0;
}

/* Back panel text colors */
.flip-card-back h4 {
    color: white !important;
}
.flip-card-back .info-item {
    color: rgba(255,255,255,0.92) !important;
}
.flip-card-back .info-item i {
    color: rgba(255,255,255,0.75) !important;
}

/* NUEVO: Estilos para reversos con alertas vencidas */
.flip-card-back.back-checkin-vencido {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    box-shadow: 0 0 20px rgba(220, 38, 38, 0.5);
}

.flip-card-back.back-checkout-vencido {
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
    color: white;
    box-shadow: 0 0 20px rgba(234, 88, 12, 0.5);
}

/* Mejorar levemente la legibilidad sin romper el diseño */
.flip-card-back.back-checkin-vencido h4,
.flip-card-back.back-checkout-vencido h4 {
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.flip-card-back.back-checkin-vencido .info-item,
.flip-card-back.back-checkout-vencido .info-item {
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
/* NUEVO: Color del reverso para estado combinado limpieza + por llegar */
.flip-card-back.back-limpieza-por-llegar {
    background: linear-gradient(135deg, #2563eb 0%, #9333ea 100%);
    color: white;
}

.flip-card-back.back-doble {
    background: linear-gradient(135deg, #dc2626 0%, #7c3aed 100%);
    color: white;
}

.flip-card-back h4 {
    font-size: 0.8125rem;
    font-weight: bold;
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flip-card-back .info-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6rem;
    margin-bottom: 0.15rem;
    opacity: 0.95;
}

.flip-card-back .info-item i {
    width: 12px;
    text-align: center;
    flex-shrink: 0;
}

.flip-card-back .action-buttons {
    display: flex;
    gap: 0.35rem;
    margin-top: auto;
    padding-top: 0.35rem;
    flex-shrink: 0;
}

.flip-card-back .btn-action {
    flex: 1;
    background: rgba(255, 255, 255, 0.18);
    color: white;
    padding: 0.3rem 0.4rem;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    font-size: 0.6rem;
    letter-spacing: 0.02em;
    transition: all 0.18s ease;
    cursor: pointer;
    border: 1px solid rgba(255, 255, 255, 0.28);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.18rem;
    white-space: nowrap;
    min-height: 26px;
    backdrop-filter: blur(6px);
}

.flip-card-back .btn-action:hover {
    background: rgba(255, 255, 255, 0.32);
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

/* Contenido responsivo para móvil/tablet */
@media (max-width: 1024px) {
    /* Ajustes para tablets */
    .flip-card-front {
        padding: 10px;
    }
    
    .flip-card-back {
        padding: 10px;
    }
    
    .estado-icon {
        width: 26px;
        height: 26px;
        font-size: 13px;
    }
    
    .flip-card-front h3 {
        font-size: 1.125rem;
    }
    
    .flip-card-back h4 {
        font-size: 0.8125rem;
    }
    
    .flip-card-back .info-item {
        font-size: 0.625rem;
    }
}

@media (max-width: 640px) {
    /* Espaciado general */
    .habitaciones-view .container {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    /* Widgets más compactos */
    .stat-widget {
        padding: 0.625rem;
    }
    
    .stat-widget .text-lg {
        font-size: 1rem;
    }
    
    /* Ocultar contenido desktop */
    .flip-card-front > .hidden.sm\:block {
        display: none !important;
    }
    
    /* Mostrar contenido móvil */
    .flip-card-front {
        padding: 8px;
        display: flex !important;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .mobile-flip-content {
        display: flex !important;
        flex-direction: column;
        height: 100%;
        justify-content: space-between;
    }
    
    /* Ajustes de tamaño para móvil */
    .mobile-flip-content h3 {
        font-size: 1.5rem;
        line-height: 1.2;
    }
    
    .mobile-flip-content .text-xs {
        font-size: 0.625rem;
    }
    
    .mobile-flip-content .text-sm {
        font-size: 0.75rem;
    }
    
    .mobile-estado-icon {
        width: 22px !important;
        height: 22px !important;
        font-size: 11px !important;
    }
    
    /* Indicador de flip más visible */
    .mobile-flip-indicator {
        background: rgba(0, 0, 0, 0.2);
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.625rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        color: rgba(0, 0, 0, 0.8);
    }
    
    .mobile-flip-indicator::after {
        content: "→";
        font-weight: bold;
    }
    
    /* Ajustar reverso para móvil */
    .flip-card-back {
        padding: 8px;
    }
    
    .flip-card-back h4 {
        font-size: 0.75rem;
        margin-bottom: 0.375rem;
    }
    
    .flip-card-back .info-item {
        font-size: 0.625rem;
        margin-bottom: 0.125rem;
    }
    
    .flip-card-back .info-item i {
        font-size: 0.625rem;
        width: 12px;
    }
    
    .flip-card-back .action-buttons {
        gap: 0.375rem;
        margin-top: 0.375rem;
    }
    
    .flip-card-back .btn-action {
        font-size: 0.625rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Forzar color morado en móvil */
    .estado-por_llegar,
    .flip-card-front.estado-por_llegar {
        background: #c084fc !important;
        background-color: #c084fc !important;
        border-left: 4px solid #9333ea !important;
    }
}

/* Ocultar contenido móvil en desktop */
@media (min-width: 641px) {
    .mobile-flip-content {
        display: none !important;
    }
}

/* Mejoras para la accesibilidad táctil */
@media (hover: none) and (pointer: coarse) {
    .btn-action {
        min-height: 44px;
    }
}

/* Botones de opción */
.btn-opcion {
    cursor: pointer;
    transition: all 0.2s;
}

.btn-opcion.activo {
    background-color: #4A6741 !important;
    border-color: #4A6741 !important;
    color: white !important;
}

.btn-opcion.activo i,
.btn-opcion.activo span,
.btn-opcion.activo p {
    color: white !important;
}

.btn-opcion:hover {
    background-color: #d1d5db;
}
</style>
<script>
// Script crítico para la animación de carga
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.habitaciones-view');
    if (view) {
        view.classList.add('loaded');
    }
    
    // Animación de entrada escalonada
    const cards = document.querySelectorAll('.room-card-compact');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 20}ms`;
    });
    
    // Prevenir submit si no hay fecha seleccionada
    const form = document.querySelector('form');
    const fechaInput = document.querySelector('input[name="fecha_consulta"]');
    
    if (form && fechaInput) {
        form.addEventListener('submit', function(e) {
            const mostrarDisp = document.querySelector('input[name="mostrar_disponibilidad"]');
            if (mostrarDisp && mostrarDisp.value === '1' && !fechaInput.value) {
                fechaInput.removeAttribute('name');
                mostrarDisp.removeAttribute('name');
            }
        });
    }
});
</script>
<style>
/* Fase habitaciones: acciones brand-aware y ocupacion separada de alertas criticas. */
.habitaciones-view{
    --hotel-brand-primary: var(--brand-primary,#2563EB);
    --hotel-brand-secondary: var(--brand-secondary,#0F172A);
    --state-occupied:#475569;
    --state-occupied-dark:#334155;
    --state-occupied-soft:#E2E8F0;
}
.btn-brand{background:var(--hotel-brand-primary)!important;color:#fff!important;border:1px solid transparent!important;box-shadow:0 10px 24px color-mix(in srgb,var(--hotel-brand-primary) 24%,transparent)!important;}
.btn-brand:hover{filter:brightness(0.94);transform:translateY(-1px)!important;}
.btn-brand-outline{background:#fff!important;color:var(--hotel-brand-primary)!important;border:1px solid color-mix(in srgb,var(--hotel-brand-primary) 35%,#fff)!important;}
.btn-brand-outline:hover{background:color-mix(in srgb,var(--hotel-brand-primary) 8%,#fff)!important;}
.brand-hover-card:hover{border-color:var(--hotel-brand-primary)!important;background:var(--hotel-brand-primary)!important;color:#fff!important;}
.brand-text{color:var(--hotel-brand-primary)!important;}
.brand-focus:focus,
.filter-input:focus,
.filter-select:focus,
.filter-date:focus{
    border-color:var(--hotel-brand-primary)!important;
    box-shadow:0 0 0 2px color-mix(in srgb,var(--hotel-brand-primary) 28%,transparent)!important;
    outline:none!important;
}
.filter-btn-primary{
    background:linear-gradient(135deg,var(--hotel-brand-primary),var(--hotel-brand-secondary))!important;
    color:#fff!important;
    box-shadow:0 6px 14px color-mix(in srgb,var(--hotel-brand-primary) 20%,transparent)!important;
}
.filter-btn-primary:hover{filter:brightness(0.96);box-shadow:0 8px 18px color-mix(in srgb,var(--hotel-brand-primary) 28%,transparent)!important;}
.filter-btn-today{color:var(--hotel-brand-primary)!important;border-color:color-mix(in srgb,var(--hotel-brand-primary) 28%,#fff)!important;}
.filter-btn-today:hover{background:color-mix(in srgb,var(--hotel-brand-primary) 8%,#fff)!important;}
.estado-ocupada,
.estado-ocupada_fecha{
    background-image:
        linear-gradient(145deg,#F1F5F9 0%,#E2E8F0 50%,#CBD5E1 100%),
        radial-gradient(circle, rgba(71,85,105,0.05) 1px, transparent 1px) !important;
    background-size: auto, 12px 12px !important;
    border-left-color:var(--state-occupied) !important;
    box-shadow:0 2px 4px rgba(71,85,105,0.12) !important;
}
.estado-ocupada.flip-card-front::before,
.estado-ocupada_fecha.flip-card-front::before{background:var(--state-occupied) !important;}
.estado-ocupada.flip-card-front::after,
.estado-ocupada_fecha.flip-card-front::after{background:linear-gradient(90deg,var(--state-occupied-dark),#64748B) !important;}
.estado-ocupada .estado-icon,
.estado-ocupada_fecha .estado-icon{background:var(--state-occupied) !important;color:#fff!important;}
.estado-ocupada .estado-icon::after,
.estado-ocupada_fecha .estado-icon::after{box-shadow:0 0 8px var(--state-occupied) !important;}
</style>
<div class="habitaciones-view">
    <!-- Header Moderno y Compacto -->
    <div class="modern-header" id="mainHeader">
        <div class="container mx-auto px-4 py-3">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-3">
                <div class="flex items-center gap-4">
                    <div class="p-2 rounded-lg shadow-sm" style="background: linear-gradient(135deg, var(--brand-primary, #2563EB), var(--brand-secondary, #0F172A));">
                        <i class="fas fa-bed text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Gestión de Habitaciones</h1>
                        <p class="text-xs text-gray-500 hidden sm:block">Control en tiempo real</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-2">
    <button onclick="mostrarVistaRapida()" 
            class="btn-modern bg-gray-100 text-gray-700 hover:bg-gray-200">
        <i class="fas fa-th text-sm"></i>
        <span class="hidden sm:inline">Vista Rápida</span>
    </button>
    
    <?php if ($tiene_limpieza): ?>
    <button onclick="mostrarModalLimpieza()" 
            class="btn-modern bg-blue-500 text-white hover:bg-blue-600 relative">
        <i class="fas fa-broom text-sm"></i>
        <span>Limpieza</span>
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
            <?= count($habitaciones_limpieza) ?>
        </span>
    </button>
    <?php endif; ?>
    
    <a href="<?= url('reservaciones/crear') ?>"
       class="btn-modern btn-brand">
        <i class="fas fa-plus-circle text-sm"></i>
        <span>Nueva Reserva</span>
    </a>
    <?php if (can('habitaciones.create')): ?>
    <a href="<?= url('habitaciones/create') ?>"
       class="btn-modern btn-brand-outline">
        <span class="hidden sm:inline">Nueva</span>
        <span>Habitación</span>
    </a>
    <?php endif; ?>
</div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-4 max-w-7xl">
        <!-- Widgets de estado (6, semánticos, estilo boutique) -->
        <div class="hb-stats" id="hbStats">
            <a class="hb-stat hb-stat--total" href="<?= url('habitaciones') ?>">
                <span class="hb-stat-ic"><i class="fas fa-door-closed"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['total'] ?? 0 ?></span>
                <span class="hb-stat-l">Total</span>
            </a>
            <a class="hb-stat hb-stat--available" href="<?= url('habitaciones') ?>?estado=disponible">
                <span class="hb-stat-ic"><i class="fas fa-check-circle"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['disponibles'] ?? 0 ?></span>
                <span class="hb-stat-l">Disponible</span>
            </a>
            <a class="hb-stat hb-stat--occupied" href="<?= url('habitaciones') ?>?estado=ocupada">
                <span class="hb-stat-ic"><i class="fas fa-bed"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['ocupadas'] ?? 0 ?></span>
                <span class="hb-stat-l">Ocupada</span>
            </a>
            <a class="hb-stat hb-stat--arriving" href="<?= url('habitaciones') ?>?estado=por_llegar">
                <span class="hb-stat-ic"><i class="fas fa-clock"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['por_llegar'] ?? 0 ?></span>
                <span class="hb-stat-l">Por llegar</span>
            </a>
            <a class="hb-stat hb-stat--cleaning" href="<?= url('habitaciones') ?>?estado=limpieza">
                <span class="hb-stat-ic"><i class="fas fa-broom"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['limpieza'] ?? 0 ?></span>
                <span class="hb-stat-l">Limpieza</span>
            </a>
            <a class="hb-stat hb-stat--maint" href="<?= url('habitaciones') ?>?estado=mantenimiento">
                <span class="hb-stat-ic"><i class="fas fa-wrench"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['mantenimiento'] ?? 0 ?></span>
                <span class="hb-stat-l">Mantenimiento</span>
            </a>
        </div>
           <?php
        $total_alertas = count($checkouts_vencidos ?? []) + count($checkins_pendientes ?? []) + count($llegadas_tardias ?? []);
        if ($total_alertas > 0):
        ?>
        
        <style>
        /* Estilos del panel de alertas */
        .alert-panel {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(212, 91, 91, 0.08), 0 2px 4px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border-left: 5px solid #D45B5B;
            border: 1px solid rgba(212, 91, 91, 0.1);
            border-left: 5px solid #D45B5B;
        }
        
        .alert-header {
            background: linear-gradient(135deg, #FFF5F4 0%, #FFECEB 100%);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(212, 91, 91, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert-badge {
            background: linear-gradient(135deg, #D45B5B 0%, #E07070 100%);
            color: white;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: bold;
            min-width: 24px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(212, 91, 91, 0.3);
        }
        
        .alert-content {
            padding: 1rem 1.25rem;
        }
        
        .alert-section {
            margin-bottom: 1.5rem;
        }
        
        .alert-section:last-child {
            margin-bottom: 0;
        }
        
        .alert-section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .alert-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.875rem;
            margin-bottom: 0.625rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid;
        }
        
        .alert-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateX(3px);
        }
        
        .alert-item:last-child {
            margin-bottom: 0;
        }
        
        .alert-critical {
            border-left-color: #f44336;
            background: #fff5f5;
        }
        
        .alert-warning {
            border-left-color: #ff9800;
            background: #fffbf5;
        }
        
        .alert-info {
            border-left-color: #2196f3;
            background: #f5f9ff;
        }
        
        .alert-info-text {
            flex: 1;
        }
        
        .alert-info-text strong {
            display: block;
            color: #1f2937;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }
        
        .alert-info-text small {
            color: #6b7280;
            font-size: 0.8rem;
            line-height: 1.4;
        }
        
        .alert-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        .btn-alert {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-alert-primary {
            background: linear-gradient(135deg, #D45B5B 0%, #E07070 100%);
            color: white;
            box-shadow: 0 2px 6px rgba(212, 91, 91, 0.25);
        }
        
        .btn-alert-primary:hover {
            box-shadow: 0 4px 12px rgba(212, 91, 91, 0.35);
            transform: translateY(-1px);
        }
        
        .btn-alert-warning {
            background: linear-gradient(135deg, #E89E40 0%, #F0B060 100%);
            color: white;
            box-shadow: 0 2px 6px rgba(232, 158, 64, 0.25);
        }
        
        .btn-alert-warning:hover {
            box-shadow: 0 4px 12px rgba(232, 158, 64, 0.35);
            transform: translateY(-1px);
        }
        
        .btn-alert-info {
            background: linear-gradient(135deg, #3B7DD8 0%, #5B93E0 100%);
            color: white;
            box-shadow: 0 2px 6px rgba(59, 125, 216, 0.25);
        }
        
        .btn-alert-info:hover {
            box-shadow: 0 4px 12px rgba(59, 125, 216, 0.35);
            transform: translateY(-1px);
        }
        
        .btn-alert-secondary {
            background: linear-gradient(135deg, #7A8B7A 0%, #8A9B8A 100%);
            color: white;
        }
        
        .btn-alert-secondary:hover {
            background: #757575;
        }
        
        .alert-collapse {
            cursor: pointer;
            user-select: none;
            background: none;
            border: none;
            padding: 0.5rem;
            color: #6b7280;
        }
        
        .alert-collapse:hover {
            color: #1f2937;
        }
        
        .alert-collapse i {
            transition: transform 0.2s;
        }
        
        .alert-collapse.collapsed i {
            transform: rotate(-90deg);
        }
        
        @media (max-width: 640px) {
            .alert-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .alert-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .btn-alert {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
        </style>
        
        <div class="alert-panel">
            <!-- Header -->
            <div class="alert-header">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                    <h3 class="text-base font-bold text-gray-800 m-0">Alertas Pendientes</h3>
                    <span class="alert-badge"><?= $total_alertas ?></span>
                </div>
                <button onclick="toggleAlertas()" class="alert-collapse" id="alertCollapseBtn">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            
            <!-- Content -->
            <div class="alert-content" id="alertContent">
                
                <!-- CHECK-OUTS VENCIDOS -->
                <?php if (!empty($checkouts_vencidos)): ?>
                <div class="alert-section">
                    <div class="alert-section-title" style="color: #f44336;">
                        <i class="fas fa-door-open"></i>
                        <span>Check-outs Vencidos (<?= count($checkouts_vencidos) ?>)</span>
                    </div>
                    
                    <?php foreach ($checkouts_vencidos as $checkout): ?>
                    <div class="alert-item alert-critical">
                        <div class="alert-info-text">
                            <strong><?= htmlspecialchars($checkout['nombre_completo']) ?></strong>
                            <small>
                                <i class="fas fa-bed"></i> Hab. <?= htmlspecialchars($checkout['habitaciones']) ?> •
                                <i class="fas fa-calendar"></i> Salida: <?= date('d/m/Y', strtotime($checkout['fecha_salida'])) ?> •
                                <i class="fas fa-clock"></i> <strong><?= $checkout['dias_retraso'] ?> día<?= $checkout['dias_retraso'] > 1 ? 's' : '' ?> de retraso</strong>
                            </small>
                        </div>
                        <div class="alert-actions">
                            <button onclick="confirmarCheckOut(<?= $checkout['id'] ?>)" 
                               class="btn-alert btn-alert-primary" style="border: none; cursor: pointer;">
                                <i class="fas fa-sign-out-alt"></i> <span>Check-out</span>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- CHECK-INS PENDIENTES -->
                <?php if (!empty($checkins_pendientes)): ?>
                <div class="alert-section">
                    <div class="alert-section-title" style="color: #ff9800;">
                        <i class="fas fa-user-clock"></i>
                        <span>Check-ins Pendientes (<?= count($checkins_pendientes) ?>)</span>
                    </div>
                    
                    <?php foreach ($checkins_pendientes as $checkin): ?>
                    <div class="alert-item alert-warning">
                        <div class="alert-info-text">
                            <strong><?= htmlspecialchars($checkin['nombre_completo']) ?></strong>
                            <small>
                                <i class="fas fa-bed"></i> Hab. <?= htmlspecialchars($checkin['habitaciones']) ?> •
                                <i class="fas fa-calendar"></i> Llegada: <?= date('d/m/Y', strtotime($checkin['fecha_entrada'])) ?> •
                                <i class="fas fa-hourglass-half"></i> <?= $checkin['dias_retraso'] ?> día<?= $checkin['dias_retraso'] > 1 ? 's' : '' ?> sin check-in
                                <?php if ($checkin['telefono']): ?>
                                    • <i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($checkin['telefono']) ?>" class="text-orange-600 hover:underline"><?= htmlspecialchars($checkin['telefono']) ?></a>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="alert-actions">
                            <a href="<?= url('reservaciones/ver/' . $checkin['id']) ?>" 
                               class="btn-alert btn-alert-warning">
                                <i class="fas fa-sign-in-alt"></i> <span>Check-in</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- LLEGADAS TARDÍAS HOY -->
                <?php if (!empty($llegadas_tardias)): ?>
                <div class="alert-section">
                    <div class="alert-section-title" style="color: #2196f3;">
                        <i class="fas fa-phone-alt"></i>
                        <span>Llegadas Tardías Hoy (<?= count($llegadas_tardias) ?>)</span>
                    </div>
                    
                    <?php foreach ($llegadas_tardias as $tardio): ?>
                    <div class="alert-item alert-info">
                        <div class="alert-info-text">
                            <strong><?= htmlspecialchars($tardio['nombre_completo']) ?></strong>
                            <small>
                                <i class="fas fa-bed"></i> Hab. <?= htmlspecialchars($tardio['habitaciones']) ?> •
                                <i class="fas fa-clock"></i> Hora estimada: <?= substr($tardio['hora_llegada_estimada'], 0, 5) ?>
                                <?php if ($tardio['telefono']): ?>
                                    • <i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($tardio['telefono']) ?>" class="text-blue-600 hover:underline"><?= htmlspecialchars($tardio['telefono']) ?></a>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="alert-actions">
                            <a href="<?= url('reservaciones/ver/' . $tardio['id']) ?>" 
                               class="btn-alert btn-alert-info">
                                <i class="fas fa-eye"></i> <span>Ver</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <script>
        function toggleAlertas() {
            const content = document.getElementById('alertContent');
            const btn = document.getElementById('alertCollapseBtn');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                btn.classList.remove('collapsed');
            } else {
                content.style.display = 'none';
                btn.classList.add('collapsed');
            }
        }
        
        // Auto-collapse en móvil si hay muchas alertas
        if (window.innerWidth < 768 && <?= $total_alertas ?> > 3) {
            document.getElementById('alertContent').style.display = 'none';
            document.getElementById('alertCollapseBtn').classList.add('collapsed');
        }
        </script>
        
        <?php endif; ?>
        <!-- Barra de Filtros Compacta -->
        <!-- Reemplazar toda la sección de "Barra de Filtros Compacta" con esto: -->
<div class="bg-white rounded-xl shadow-sm p-2 sm:p-3 mb-4">
    <?php if (!empty($filtros['fecha_consulta']) && !empty($filtros['mostrar_disponibilidad'])): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 mb-2 flex items-center justify-between">
        <div class="flex items-center flex-1">
            <i class="fas fa-info-circle text-blue-600 mr-2 text-xs"></i>
            <span class="text-xs text-blue-800 font-medium">
                Disponibilidad: <?= format_date($filtros['fecha_consulta']) ?>
            </span>
        </div>
        <a href="<?= url('habitaciones') ?>" class="text-blue-600 hover:text-blue-800 text-xs p-1">
            <i class="fas fa-times"></i>
        </a>
    </div>
    <?php endif; ?>
    
    <form method="GET" action="<?= url('habitaciones') ?>">
        <div class="flex flex-wrap gap-1.5 sm:gap-2 items-center">
            <!-- Campo de búsqueda -->
            <div class="flex-1 min-w-[140px] sm:min-w-[180px]">
                <div class="relative">
                    <input type="text" 
                           name="buscar" 
                           value="<?= $filtros['buscar'] ?? '' ?>"
                           placeholder="Buscar..."
                           class="filter-input w-full pl-7 pr-2">
                    <i class="fas fa-search absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
            </div>
            
            <!-- Filtros compactos -->
            <select name="estado" class="filter-select">
                <option value="">Estado</option>
                <?php foreach ($estados as $key => $estado): ?>
                    <?php if ($key !== 'disponible_fecha' && $key !== 'ocupada_fecha'): ?>
                        <option value="<?= $key ?>" <?= ($filtros['estado'] ?? '') == $key ? 'selected' : '' ?>>
                            <?= $estado['label'] ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            
            <select name="tipo" class="filter-select">
                <option value="">Tipo</option>
                <?php foreach ($tipos as $key => $tipo): ?>
                    <option value="<?= $key ?>" <?= ($filtros['tipo'] ?? '') == $key ? 'selected' : '' ?>>
                        <?= $tipo ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="piso" class="filter-select">
                <option value="">Piso</option>
                <?php foreach ($pisos as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($filtros['piso'] ?? '') == $value ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Contenedor con fecha y botón Hoy -->
            <div class="flex gap-1">
                <input type="date" 
                       name="fecha_consulta" 
                       id="fecha_consulta"
                       value="<?= $filtros['fecha_consulta'] ?? '' ?>"
                       class="filter-date">
                
                <a href="<?= url('habitaciones') ?>" 
                   class="filter-btn filter-btn-today"
                   title="Volver a hoy (limpiar filtros)">
                    <i class="fas fa-calendar-day"></i>
                    <span class="hidden sm:inline">Hoy</span>
                </a>
            </div>
            
            <input type="hidden" name="mostrar_disponibilidad" value="1">
            
            <!-- Botones de acción más pequeños -->
            <button type="submit" class="filter-btn filter-btn-primary">
                <i class="fas fa-filter"></i>
                <span class="hidden sm:inline">Filtrar</span>
            </button>
            
            <?php if (!empty($filtros['buscar']) || !empty($filtros['estado']) || !empty($filtros['tipo']) || !empty($filtros['piso']) || !empty($filtros['fecha_consulta'])): ?>
            <a href="<?= url('habitaciones') ?>" class="filter-btn filter-btn-reset" title="Limpiar filtros">
                <i class="fas fa-times"></i>
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

        <!-- Movimientos del día -->
        <?php 
        $fecha_consulta = !empty($filtros['fecha_consulta']) ? $filtros['fecha_consulta'] : date('Y-m-d');
        $mostrar_movimientos = !empty($filtros['mostrar_disponibilidad']) || !empty($filtros['fecha_consulta']) || $fecha_consulta == date('Y-m-d');
        $es_filtro_fecha = !empty($filtros['fecha_consulta']) && $filtros['fecha_consulta'] != date('Y-m-d');
        ?>
        <?php if ($mostrar_movimientos): ?>
            <?php
            $db = Database::getInstance();
            // Establecer zona horaria de MySQL a México
            $db->query("SET time_zone = '-06:00'");
            
            // Check-ins del día
            $sql_checkins = "SELECT r.*, h.nombre_completo, h.telefono,
                            GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                            GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids
                            FROM reservaciones r
                            INNER JOIN huespedes h ON r.huesped_id = h.id
                            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                            INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                            WHERE DATE(r.fecha_entrada) = ?
                            AND r.estado = 'confirmada'
                            GROUP BY r.id
                            ORDER BY r.hora_llegada_estimada";
            $stmt = $db->query($sql_checkins, [$fecha_consulta]);
            $checkins_dia = $stmt->fetchAll();            
            // Check-outs del día - Solo si NO es un filtro de fecha (solo para día actual)
            $checkouts_dia = [];
            if (!$es_filtro_fecha) {
                $sql_checkouts = "SELECT r.*, h.nombre_completo, h.telefono,
                                 GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                                 GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids
                                 FROM reservaciones r
                                 INNER JOIN huespedes h ON r.huesped_id = h.id
                                 INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                                 INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                                 WHERE DATE(r.fecha_salida) = ?
                                 AND r.estado IN ('checked_in', 'confirmada')
                                 GROUP BY r.id
                                 ORDER BY r.hora_entrada";
                $stmt = $db->query($sql_checkouts, [$fecha_consulta]);
                $checkouts_dia = $stmt->fetchAll();
            }
            
            // Crear mapa de habitaciones con doble movimiento
            $habitaciones_doble_movimiento = [];
            foreach ($checkouts_dia as $checkout) {
                $hab_ids = explode(',', $checkout['habitaciones_ids']);
                foreach ($hab_ids as $hab_id) {
                    $habitaciones_doble_movimiento[$hab_id]['checkout'] = $checkout;
                }
            }
            foreach ($checkins_dia as $checkin) {
                $hab_ids = explode(',', $checkin['habitaciones_ids']);
                foreach ($hab_ids as $hab_id) {
                    $habitaciones_doble_movimiento[$hab_id]['checkin'] = $checkin;
                }
            }
            ?>
            
            <?php if (count($checkins_dia) > 0 || count($checkouts_dia) > 0): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">
                <!-- Panel de Check-ins -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="bg-purple-50 p-3 rounded-t-lg border-b border-purple-100">
                        <h3 class="text-sm font-semibold text-purple-800 flex items-center justify-between">
                            <span><i class="fas fa-sign-in-alt mr-2"></i>Check-ins - <?= format_date($fecha_consulta) ?></span>
                            <span class="bg-purple-600 text-white text-xs px-2 py-0.5 rounded-full">
                                <?= count($checkins_dia) ?>
                            </span>
                        </h3>
                    </div>
                    <div class="p-2 max-h-48 overflow-y-auto">
                        <?php if (empty($checkins_dia)): ?>
                            <p class="text-gray-500 text-center py-3 text-sm">No hay check-ins programados</p>
                        <?php else: ?>
                            <div class="space-y-1">
                                <?php foreach ($checkins_dia as $checkin): ?>
                                    <div class="flex items-center justify-between p-2 bg-purple-50 rounded hover:bg-purple-100 transition-colors">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm text-gray-800 truncate">
                                                <?= htmlspecialchars($checkin['nombre_completo']) ?>
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                <i class="fas fa-bed mr-1"></i><?= htmlspecialchars($checkin['habitaciones_numeros']) ?>
                                                • <?= substr($checkin['hora_llegada_estimada'], 0, 5) ?>
                                            </p>
                                        </div>
                                        <a href="<?= url('reservaciones/ver/' . $checkin['id']) ?>" 
                                           class="text-purple-600 hover:text-purple-800 ml-2">
                                            <i class="fas fa-arrow-right text-sm"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Panel de Check-outs -->
                 <div class="bg-white rounded-lg shadow-sm">
                    <div class="bg-yellow-50 p-3 rounded-t-lg border-b border-yellow-100">
                        <h3 class="text-sm font-semibold text-yellow-800 flex items-center justify-between">
                            <span><i class="fas fa-sign-out-alt mr-2"></i>Check-outs - <?= format_date($fecha_consulta) ?></span>
                            <span class="bg-yellow-600 text-white text-xs px-2 py-0.5 rounded-full">
                                <?= count($checkouts_dia) ?>
                            </span>
                        </h3>
                    </div>
                    <div class="p-2 max-h-48 overflow-y-auto">
                        <?php if (empty($checkouts_dia)): ?>
                            <p class="text-gray-500 text-center py-3 text-sm">No hay check-outs programados</p>
                        <?php else: ?>
                            <div class="space-y-1">
                                <?php foreach ($checkouts_dia as $checkout): ?>
                                    <div class="flex items-center justify-between p-2 bg-yellow-50 rounded hover:bg-yellow-100 transition-colors">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm text-gray-800 truncate">
                                                <?= htmlspecialchars($checkout['nombre_completo']) ?>
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                <i class="fas fa-bed mr-1"></i><?= htmlspecialchars($checkout['habitaciones_numeros']) ?>
                                                • Hasta 12:00 PM
                                            </p>
                                        </div>
                                        <?php if ($checkout['estado'] == 'checked_in'): ?>
                                            <button onclick="confirmarCheckOut(<?= $checkout['id'] ?>)" 
                                                    class="bg-yellow-500 text-white px-2 py-1 rounded text-xs hover:bg-yellow-600 transition-colors ml-2">
                                                Check-out
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Grid de Habitaciones Compacto -->
        <?php
        // Obtener check-ins vencidos para alertas
        $db = Database::getInstance();
        $db->query("SET time_zone = '-06:00'");
        $sql_checkins_vencidos = "SELECT r.*, h.nombre_completo, h.telefono,
                    GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                    GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids,
                    DATEDIFF(CURDATE(), r.fecha_entrada) as dias_retraso
                    FROM reservaciones r
                    INNER JOIN huespedes h ON r.huesped_id = h.id
                    INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                    WHERE r.fecha_entrada < CURDATE()
                    AND r.estado = 'confirmada'
                    GROUP BY r.id
                    ORDER BY r.fecha_entrada";
        $stmt = $db->query($sql_checkins_vencidos);
        $checkins_vencidos_real = $stmt->fetchAll();
        
        // Crear array para mapeo rápido de check-ins vencidos
        $habitaciones_con_checkin_vencido = [];
        foreach ($checkins_vencidos_real as $cv) {
            $hab_ids = explode(',', $cv['habitaciones_ids']);
            foreach ($hab_ids as $hab_id) {
                $habitaciones_con_checkin_vencido[trim($hab_id)] = [
                    'nombre' => $cv['nombre_completo'],
                    'fecha_entrada' => $cv['fecha_entrada'],
                    'dias_retraso' => $cv['dias_retraso']
                ];
            }
        }
        
        // Obtener check-outs vencidos para alertas
        $sql_checkouts_vencidos = "SELECT r.*, h.nombre_completo, h.telefono,
                    GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                    GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids,
                    DATEDIFF(CURDATE(), r.fecha_salida) as dias_retraso
                    FROM reservaciones r
                    INNER JOIN huespedes h ON r.huesped_id = h.id
                    INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                    WHERE r.fecha_salida < CURDATE()
                    AND r.estado = 'checked_in'
                    GROUP BY r.id
                    ORDER BY r.fecha_salida";
        $stmt = $db->query($sql_checkouts_vencidos);
        $checkouts_vencidos_real = $stmt->fetchAll();
        
        // Crear array para mapeo rápido de check-outs vencidos
        $habitaciones_con_checkout_vencido = [];
        foreach ($checkouts_vencidos_real as $co) {
            $hab_ids = explode(',', $co['habitaciones_ids']);
            foreach ($hab_ids as $hab_id) {
                $habitaciones_con_checkout_vencido[trim($hab_id)] = [
                    'reservacion_id' => $co['id'],
                    'nombre' => $co['nombre_completo'],
                    'fecha_salida' => $co['fecha_salida'],
                    'dias_retraso' => $co['dias_retraso']
                ];
            }
        }
        ?>
        
        <div id="habitaciones-grid">
            <?php
            // Ordenar: habitaciones de color primero (se conserva dentro de cada piso)
            usort($habitaciones, function($a, $b) use ($colores_habitacion) {
                $a_es_color = isset($colores_habitacion[strtoupper($a['numero'])]);
                $b_es_color = isset($colores_habitacion[strtoupper($b['numero'])]);
                if ($a_es_color && !$b_es_color) return -1;
                if (!$a_es_color && $b_es_color) return 1;
                return 0;
            });
            // Agrupar por piso (conservando el orden anterior dentro de cada piso)
            $habitaciones_por_piso = [];
            foreach ($habitaciones as $__hab) { $habitaciones_por_piso[$__hab['piso']][] = $__hab; }
            ksort($habitaciones_por_piso, SORT_NUMERIC);
            ?>
            <?php foreach ($habitaciones_por_piso as $__piso => $__habs): ?>
                <section class="floor-section">
                    <div class="floor-label">
                        <span class="floor-t"><?= htmlspecialchars($pisos[$__piso] ?? ('Piso ' . $__piso)) ?></span>
                        <span class="floor-rule"></span>
                        <span class="floor-ct"><?= count($__habs) ?> <?= count($__habs) == 1 ? 'habitación' : 'habitaciones' ?></span>
                    </div>
                    <div class="rgrid">
                    <?php foreach ($__habs as $habitacion): ?>
                <?php 
                $estado_actual = $habitacion['estado_display'] ?? $habitacion['estado'];
                $estadoInfo = $estados[$estado_actual] ?? ['label' => 'Desconocido', 'color' => 'gray', 'icon' => 'question'];
                
                // Verificar si tiene doble movimiento
                $tiene_doble_movimiento = false;
                // NUEVO: Verificar si está en limpieza Y tiene reservación por llegar
$limpieza_con_por_llegar = false;
if ($habitacion['estado'] == 'limpieza' && isset($habitacion['reservacion_pendiente'])) {
    // La habitación está en limpieza y tiene una reservación pendiente
    $limpieza_con_por_llegar = true;
}
                $info_checkout = null;
                $info_checkin = null;
                
                if ($mostrar_movimientos && isset($habitaciones_doble_movimiento[$habitacion['id']])) {
                    if (isset($habitaciones_doble_movimiento[$habitacion['id']]['checkout']) && 
                        isset($habitaciones_doble_movimiento[$habitacion['id']]['checkin'])) {
                        $tiene_doble_movimiento = true;
                        $info_checkout = $habitaciones_doble_movimiento[$habitacion['id']]['checkout'];
                        $info_checkin = $habitaciones_doble_movimiento[$habitacion['id']]['checkin'];
                    }
                }
                
                // Verificar si tiene check-out hoy o vencido usando el array mapeado
                $tiene_checkout_hoy = false;
                $tiene_checkout_vencido = false;
                $info_checkout_vencido = null;
                
                // Primero verificar si está en el array de check-outs vencidos
                if (isset($habitaciones_con_checkout_vencido[$habitacion['id']])) {
                    $tiene_checkout_vencido = true;
                    $info_checkout_vencido = $habitaciones_con_checkout_vencido[$habitacion['id']];
                } elseif ($habitacion['estado'] == 'ocupada' && isset($habitacion['ocupacion_actual'])) {
                    // Si no está vencido, verificar si sale hoy
                    $fecha_salida = $habitacion['ocupacion_actual']['fecha_salida'];
                    if ($fecha_salida == date('Y-m-d')) {
                        $tiene_checkout_hoy = true;
                    }
                }
                
                // Verificar si es llegada tardía o check-in vencido usando el array mapeado
                $es_llegada_tardia = false;
                $es_checkin_vencido = false;
                $info_checkin_vencido = null;
                
                // Primero verificar si está en el array de check-ins vencidos
                if (isset($habitaciones_con_checkin_vencido[$habitacion['id']])) {
                    $es_checkin_vencido = true;
                    $info_checkin_vencido = $habitaciones_con_checkin_vencido[$habitacion['id']];
                } elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente'])) {
                    // Si no está vencido, verificar si es llegada tardía hoy
                    $fecha_entrada = $habitacion['reservacion_pendiente']['fecha_entrada'] ?? null;
                    if ($fecha_entrada == date('Y-m-d')) {
                        // Si es hoy pero con hora tardía
                        $hora_entrada = $habitacion['reservacion_pendiente']['hora_llegada_estimada'] ?? '14:00:00';
                        if (strtotime($hora_entrada) < strtotime(date('H:i:s'))) {
                            $es_llegada_tardia = true;
                        }
                    }
                }
                
                $nombrePiso = $pisos[$habitacion['piso']] ?? 'Piso ' . $habitacion['piso'];
                $pisoAbrev = $habitacion['piso'] == 1 ? 'PB' : 
                            ($habitacion['piso'] > 0 ? 'P' . $habitacion['piso'] : 
                            'S' . abs($habitacion['piso']));
                
                // Determinar clase de color para el reverso
                // Determinar clase de color para el reverso
if ($tiene_doble_movimiento) {
    $backColorClass = 'back-doble';
} elseif ($es_checkin_vencido) {
    $backColorClass = 'back-checkin-vencido';
} elseif ($tiene_checkout_vencido) {
    $backColorClass = 'back-checkout-vencido';
} elseif ($limpieza_con_por_llegar) {
    $backColorClass = 'back-limpieza-por-llegar';
} else {
    $backColorClass = 'back-' . $estado_actual;
}
                
                // Color de habitación (Área Confortable)
                $color_hab = $colores_habitacion[strtoupper($habitacion['numero'])] ?? null;
                ?>
                
                <!-- Tarjeta con flip para todas las habitaciones -->
                <?php
                // Determine the accent color for this card (room color or state color)
                $stateAccentColors = [
                    'disponible'       => '#4A6741',
                    'disponible_fecha' => '#4A6741',
                    'por_llegar'       => '#7C3AED',
                    'ocupada'          => '#C2603C',
                    'ocupada_fecha'    => '#C2603C',
                    'mantenimiento'    => '#B07A52',
                    'limpieza'         => '#3366B8',
                    'doble'            => '#7C3AED',
                    'limpieza-por-llegar' => '#3366B8',
                ];
                if ($es_checkin_vencido) {
                    $accentColor = '#dc2626';
                } elseif ($tiene_checkout_vencido) {
                    $accentColor = '#ea580c';
                } else {
                    $accentColor = $color_hab ?: ($stateAccentColors[$estado_actual] ?? '#4A6741');
                }
                // Darker shade inline (no function to avoid redeclaration in loop)
                $hexClean = ltrim($accentColor, '#');
                $darkerAccent = sprintf('#%02x%02x%02x',
                    max(0, hexdec(substr($hexClean,0,2)) - 40),
                    max(0, hexdec(substr($hexClean,2,2)) - 40),
                    max(0, hexdec(substr($hexClean,4,2)) - 40)
                );
                $backStyle = 'background: linear-gradient(145deg, ' . $darkerAccent . ' 0%, ' . $accentColor . ' 100%) !important; color: white !important;';
                ?>
                <div class="flip-card room-card-compact <?= $tiene_checkout_vencido ? 'has-checkout-vencido' : '' ?> <?= $es_checkin_vencido ? 'has-checkin-vencido' : '' ?>" 
                     onclick="toggleFlip(this, event)" 
                     data-habitacion-id="<?= $habitacion['id'] ?>"
                     style="--room-accent-color: <?= htmlspecialchars($accentColor) ?>">
                    <div class="flip-card-inner">
                        <!-- Parte frontal -->
                        <div class="flip-card-front <?= $tiene_doble_movimiento ? 'estado-doble' : ($limpieza_con_por_llegar ? 'estado-limpieza-por-llegar' : 'estado-' . $estado_actual) ?> rounded-lg shadow-sm relative">
                            
                            
                            
                            <?php if ($tiene_checkout_hoy): ?>
                            <div class="checkout-today-indicator">
                                <i class="fas fa-sign-out-alt" style="font-size: 9px;"></i>
                                <span>HOY</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($tiene_checkout_vencido): ?>
                            <div class="checkout-vencido-indicator">
                                <i class="fas fa-exclamation-circle" style="font-size: 10px;"></i>
                                <span class="font-bold">VENCIDO</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($es_checkin_vencido): ?>
                            <div class="checkin-vencido-indicator">
                                <i class="fas fa-exclamation-triangle" style="font-size: 10px;"></i>
                                <span class="font-bold">NO LLEGÓ</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($es_llegada_tardia && !$es_checkin_vencido): ?>
                            <div class="late-arrival-indicator">
                                <i class="fas fa-moon" style="font-size: 9px;"></i>
                                <span>TARDÍA</span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Cara frontal (rediseño boutique habitaciones.html) -->
                            <?php
                            $faceGuest = ''; $faceMeta = '';
                            if ($tiene_doble_movimiento) {
                                $faceGuest = trim(explode(' ', $info_checkout['nombre_completo'])[0] . ' → ' . explode(' ', $info_checkin['nombre_completo'])[0]);
                                $faceMeta = 'Rotación de huéspedes';
                            } elseif ($es_checkin_vencido && $info_checkin_vencido) {
                                $faceGuest = $info_checkin_vencido['nombre'];
                                $faceMeta = 'No llegó · ' . $info_checkin_vencido['dias_retraso'] . ' día' . ($info_checkin_vencido['dias_retraso'] > 1 ? 's' : '') . ' de retraso';
                            } elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente'])) {
                                $faceGuest = $habitacion['reservacion_pendiente']['nombre_completo'];
                                $faceMeta = $es_llegada_tardia ? 'Llegada tardía pendiente' : ('Llega ' . date('g:i A', strtotime($habitacion['reservacion_pendiente']['hora_llegada_estimada'])));
                            } elseif ($habitacion['estado'] == 'ocupada' && isset($habitacion['ocupacion_actual'])) {
                                $faceGuest = $habitacion['ocupacion_actual']['nombre_completo'];
                                if ($tiene_checkout_vencido) { $faceMeta = 'Check-out vencido'; }
                                elseif ($tiene_checkout_hoy) { $faceMeta = 'Sale hoy'; }
                                else { $faceMeta = 'Sale ' . format_date($habitacion['ocupacion_actual']['fecha_salida']); }
                            } elseif ($estado_actual == 'ocupada_fecha' && isset($habitacion['info_ocupacion'])) {
                                $faceGuest = $habitacion['info_ocupacion']['huesped'] ?? 'Ocupada';
                                $faceMeta = 'No disponible en la fecha';
                            } elseif ($habitacion['estado'] == 'limpieza') {
                                $faceMeta = 'Preparando habitación';
                            } elseif ($habitacion['estado'] == 'mantenimiento') {
                                $faceMeta = $habitacion['mantenimiento_actual']['tipo_mantenimiento'] ?? 'En mantenimiento';
                            }
                            ?>
                            <div class="rc-face">
                                <span class="rc-stripe" style="background: <?= htmlspecialchars($accentColor) ?>;"></span>
                                <div class="rc-top">
                                    <div class="rc-id">
                                        <div class="rc-num"><?= htmlspecialchars($habitacion['numero']) ?></div>
                                        <div class="rc-type"><?= $pisoAbrev ?> · <?= $tipos[$habitacion['tipo']] ?? $habitacion['tipo'] ?></div>
                                    </div>
                                    <span class="rc-badge"><i class="fas fa-<?= $estadoInfo['icon'] ?>"></i><span><?= $estadoInfo['label'] ?></span></span>
                                </div>
                                <div class="rc-mid">
                                    <?php if ($faceGuest !== ''): ?>
                                        <div class="rc-guest"><i class="fas fa-user"></i><span><?= htmlspecialchars($faceGuest) ?></span></div>
                                    <?php else: ?>
                                        <div class="rc-guest rc-guest--empty"><i class="fas fa-bed"></i><span>Sin huésped</span></div>
                                    <?php endif; ?>
                                    <?php if ($faceMeta !== ''): ?><div class="rc-meta"><?= htmlspecialchars($faceMeta) ?></div><?php endif; ?>
                                </div>
                                <div class="rc-foot">
                                    <span class="rc-price"><?= format_money($habitacion['precio_actual'] ?? $habitacion['precio_base']) ?><small>/noche</small></span>
                                    <span class="rc-hint"><i class="fas fa-hand-pointer"></i><span>Acciones</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Parte trasera con información adicional -->
                        <div class="flip-card-back <?= $backColorClass ?>"<?= $backStyle ? ' style="' . $backStyle . '"' : '' ?>>
                            <div>
                                <h4>Hab. <?= htmlspecialchars($habitacion['numero']) ?></h4>
                                
                                <?php if ($tiene_doble_movimiento): ?>
                                    <div class="info-item">
                                        <i class="fas fa-exchange-alt"></i>
                                        <span>Rotación de huéspedes</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Sale: <?= htmlspecialchars(explode(' ', $info_checkout['nombre_completo'])[0]) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <span>Entra: <?= htmlspecialchars(explode(' ', $info_checkin['nombre_completo'])[0]) ?></span>
                                    </div>
                                    
                                <?php elseif ($es_checkin_vencido && $info_checkin_vencido): ?>
                                    <div class="info-item">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>CHECK-IN VENCIDO</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($info_checkin_vencido['nombre']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Debió llegar: <?= date('d/m/Y', strtotime($info_checkin_vencido['fecha_entrada'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= $info_checkin_vencido['dias_retraso'] ?> día<?= $info_checkin_vencido['dias_retraso'] > 1 ? 's' : '' ?> de retraso</span>
                                    </div>
                                    
                                <?php elseif ($tiene_checkout_vencido && $info_checkout_vencido): ?>
                                    <div class="info-item">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>CHECK-OUT VENCIDO</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($info_checkout_vencido['nombre']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Debió salir: <?= date('d/m/Y', strtotime($info_checkout_vencido['fecha_salida'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= $info_checkout_vencido['dias_retraso'] ?> día<?= $info_checkout_vencido['dias_retraso'] > 1 ? 's' : '' ?> de retraso</span>
                                    </div>
                                    
                                <?php elseif ($estado_actual == 'disponible' || $estado_actual == 'disponible_fecha'): ?>
                                    <div class="info-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Lista para reservar</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-bed"></i>
                                        <span><?= $tipos[$habitacion['tipo']] ?? $habitacion['tipo'] ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?= format_money($habitacion['precio_actual'] ?? $habitacion['precio_base']) ?>/noche</span>
                                    </div>
                                    
                                <?php elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($habitacion['reservacion_pendiente']['nombre_completo']) ?></span>
                                    </div>
                                    <?php if ($es_llegada_tardia): ?>
                                        <div class="info-item">
                                            <i class="fas fa-moon"></i>
                                            <span>Llegada tardía pendiente</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Desde: <?= format_date($habitacion['reservacion_pendiente']['fecha_entrada']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span>Llegada: <?= date('g:i A', strtotime($habitacion['reservacion_pendiente']['hora_llegada_estimada'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-phone"></i>
                                            <span>Check-in pendiente</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php elseif ($habitacion['estado'] == 'ocupada' && isset($habitacion['ocupacion_actual'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($habitacion['ocupacion_actual']['nombre_completo']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>Entrada: <?= format_date($habitacion['ocupacion_actual']['fecha_entrada']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Salida: <?= format_date($habitacion['ocupacion_actual']['fecha_salida']) ?></span>
                                    </div>
                                    
                                <?php elseif ($habitacion['estado'] == 'limpieza'): ?>
                                    <div class="info-item">
                                        <i class="fas fa-broom"></i>
                                        <span>En proceso de limpieza</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Tiempo estimado: 30 min</span>
                                    </div>
                                    
                                <?php elseif ($habitacion['estado'] == 'mantenimiento'): ?>
                                    <div class="info-item">
                                        <i class="fas fa-tools"></i>
                                        <span><?= $habitacion['mantenimiento_actual']['tipo_mantenimiento'] ?? 'Mantenimiento general' ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-wrench"></i>
                                        <span>Trabajo en progreso</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span><?= $nombrePiso ?></span>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
    <?php if ($estado_actual == 'ocupada_fecha' && isset($habitacion['info_ocupacion']['reservacion_id'])): ?>
        <!-- Botón Ver Reservación cuando hay filtro de fecha -->
        <a href="<?= url('reservaciones/ver/' . $habitacion['info_ocupacion']['reservacion_id']) ?>" class="btn-action btn-primary" onclick="event.stopPropagation();">
            <i class="fas fa-eye mr-1"></i>Ver Reservación
        </a>
    <?php else: ?>
        <!-- Botón Detalles normal -->
        <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" class="btn-action" onclick="event.stopPropagation();">
            Detalles
        </a>
    <?php endif; ?>
                                
                                <?php if ($estado_actual == 'disponible' || $estado_actual == 'disponible_fecha'): ?>
                                    <?php if ($estado_actual == 'disponible_fecha'): ?>
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); crearReservacionConFecha(<?= $habitacion['id'] ?>, '<?= $filtros['fecha_consulta'] ?>')" class="btn-action btn-primary">
                                            <i class="fas fa-plus mr-1"></i>Reservar
                                        </a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); crearReservacionRapida(<?= $habitacion['id'] ?>)" class="btn-action btn-primary">
                                            <i class="fas fa-plus mr-1"></i>Reservar
                                        </a>
                                    <?php endif; ?>
                                    
                                <?php elseif ($tiene_checkout_vencido && $info_checkout_vencido): ?>
                                    <a href="javascript:void(0)" onclick="event.stopPropagation(); confirmarCheckOut(<?= $info_checkout_vencido['reservacion_id'] ?>)" class="btn-action btn-primary">
                                        <i class="fas fa-sign-out-alt mr-1"></i>Check-out
                                    </a>
                                    
                                <?php elseif ($es_checkin_vencido && $info_checkin_vencido): ?>
                                    <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" onclick="event.stopPropagation();" class="btn-action btn-primary">
                                        <i class="fas fa-eye mr-1"></i>Ver Detalle
                                    </a>
                                    
                                <?php elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente']) && can('reservaciones.checkin')): ?>
                                    <a href="javascript:void(0)" onclick="event.stopPropagation(); hacerCheckInRapido(<?= $habitacion['reservacion_pendiente']['reservacion_id'] ?? $habitacion['reservacion_pendiente']['id'] ?>)" class="btn-action btn-primary">
                                        <i class="fas fa-sign-in-alt mr-1"></i>Check-in
                                    </a>
                                    
                                <?php elseif ($habitacion['estado'] == 'limpieza'): ?>
                                    <a href="javascript:void(0)" onclick="event.stopPropagation(); liberarHabitacion(<?= $habitacion['id'] ?>)" class="btn-action btn-primary">
                                        <i class="fas fa-check mr-1"></i>Limpia
                                    </a>
                                    
                                <?php elseif ($habitacion['estado'] == 'mantenimiento'): ?>
                                    <a href="javascript:void(0)" onclick="event.stopPropagation(); finalizarMantenimiento(<?= $habitacion['id'] ?>)" class="btn-action btn-primary">
                                        <i class="fas fa-check mr-1"></i>Finalizar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>

        <!-- Mensaje si no hay habitaciones -->
        <?php if (empty($habitaciones)): ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="max-w-md mx-auto">
                    <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-bed text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700 mb-2">No se encontraron habitaciones</h3>
                    <p class="text-gray-500 mb-4 text-sm">Ajusta los filtros de búsqueda o verifica los criterios.</p>
                    <a href="<?= url('habitaciones') ?>" 
                       class="btn-modern btn-brand mx-auto">
                        <i class="fas fa-redo"></i>
                        Mostrar todas
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Resumen compacto de disponibilidad -->
       

<!-- Modal de Vista Rápida -->
<!-- Modal de Vista Rápida -->
<div id="vistaRapidaModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <div class="text-white p-3 flex justify-between items-center" style="background: linear-gradient(135deg, var(--brand-primary,#2563EB), var(--brand-secondary,#0F172A));">
            <h3 class="text-lg font-bold">Vista Rápida</h3>
            <button onclick="cerrarVistaRapida()" class="text-white hover:text-gray-200 transition-colors p-1">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-4 overflow-y-auto max-h-[calc(90vh-60px)]" id="vistaRapidaContainer">
            <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-2">
                <?php foreach ($habitaciones as $hab): ?>
                    <?php 
                    $estado_hab = $hab['estado_display'] ?? $hab['estado'];
                    $colorRapido = [
                        'disponible' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-emerald-300',
                        'disponible_fecha' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-emerald-300',
                        'por_llegar' => 'bg-purple-500 text-white shadow-purple-300',
                        'ocupada' => 'bg-gradient-to-br from-[#C2603C] to-[#9E4A2E] text-white shadow-orange-200',
                        'ocupada_fecha' => 'bg-gradient-to-br from-[#C2603C] to-[#9E4A2E] text-white shadow-orange-200',
                        'mantenimiento' => 'bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-amber-300',
                        'limpieza' => 'bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-blue-300'
                    ][$estado_hab] ?? 'bg-gradient-to-br from-gray-400 to-gray-600 text-white shadow-gray-300';
                    
                    $pisoCortado = $hab['piso'] == 1 ? 'PB' : 
                                  ($hab['piso'] > 0 ? 'P' . $hab['piso'] : 
                                  'S' . abs($hab['piso']));
                    
                    // Preparar datos para el tooltip
                    $tooltipData = [
                        'numero' => $hab['numero'],
                        'piso' => $pisos[$hab['piso']] ?? 'Piso ' . $hab['piso'],
                        'tipo' => $tipos[$hab['tipo']] ?? $hab['tipo'],
                        'estado' => $estados[$estado_hab]['label'] ?? 'Desconocido',
                        'precio' => format_money($hab['precio_actual'] ?? $hab['precio_base']),
                        'huesped' => null,
                        'fechas' => null,
                        'noches' => null,
                        'telefono' => null,
                        'hora_llegada' => null
                    ];
                    
                    if ($estado_hab == 'ocupada' && isset($hab['ocupacion_actual'])) {
                        $tooltipData['huesped'] = $hab['ocupacion_actual']['nombre_completo'] ?? 'Huésped';
                        $entrada = $hab['ocupacion_actual']['fecha_entrada'] ?? null;
                        $salida = $hab['ocupacion_actual']['fecha_salida'] ?? null;
                        
                        if ($entrada && $salida) {
                            $tooltipData['fechas'] = format_date($entrada) . ' - ' . format_date($salida);
                            try {
                                $fecha1 = new DateTime($entrada);
                                $fecha2 = new DateTime($salida);
                                $tooltipData['noches'] = $fecha1->diff($fecha2)->days;
                            } catch (Exception $e) {
                                $tooltipData['noches'] = null;
                            }
                        }
                        $tooltipData['telefono'] = $hab['ocupacion_actual']['telefono'] ?? null;
                        
                    } elseif ($estado_hab == 'por_llegar' && isset($hab['reservacion_pendiente'])) {
                        $tooltipData['huesped'] = $hab['reservacion_pendiente']['nombre_completo'] ?? 'Huésped';
                        $fecha_entrada = $hab['reservacion_pendiente']['fecha_entrada'] ?? null;
                        if ($fecha_entrada) {
                            $tooltipData['fechas'] = 'Llega: ' . format_date($fecha_entrada);
                        }
                        $tooltipData['hora_llegada'] = $hab['reservacion_pendiente']['hora_llegada_estimada'] ?? null;
                        $tooltipData['telefono'] = $hab['reservacion_pendiente']['telefono'] ?? null;
                    }
                    ?>
                    <div class="<?= $colorRapido ?> rounded-lg p-2 text-center cursor-pointer transition-all hover:scale-105 shadow-md room-quick-view"
                         onclick="<?= ($estado_hab == 'disponible' || $estado_hab == 'disponible_fecha') ? 'crearReservacionRapida(' . $hab['id'] . ')' : 'window.location.href=\'' . url('habitaciones/' . $hab['id']) . '\'' ?>"
                         data-tooltip='<?= htmlspecialchars(json_encode($tooltipData), ENT_QUOTES, 'UTF-8') ?>'>
                        <div class="font-bold text-sm"><?= $hab['numero'] ?></div>
                        <div class="text-xs opacity-90"><?= $pisoCortado ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Leyenda -->
            <div class="mt-4 pt-4 border-t flex flex-wrap gap-3 justify-center text-xs">
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded shadow-sm"></div>
                    <span>Disponible</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-gradient-to-br from-purple-400 to-purple-600 rounded shadow-sm"></div>
                    <span>Por llegar</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-gradient-to-br from-slate-500 to-slate-700 rounded shadow-sm"></div>
                    <span>Ocupada</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-gradient-to-br from-blue-400 to-blue-600 rounded shadow-sm"></div>
                    <span>Limpieza</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-gradient-to-br from-amber-400 to-amber-600 rounded shadow-sm"></div>
                    <span>Mantenimiento</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tooltip container -->
    <div id="roomTooltip" class="room-tooltip"></div>
</div>
<!-- Modal de Limpieza Múltiple -->
<div id="modalLimpieza" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header del modal -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-lg">
                    <i class="fas fa-broom text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold">Marcar Habitaciones Limpias</h3>
                    <p class="text-xs text-blue-100">Selecciona las habitaciones que ya están listas</p>
                </div>
            </div>
            <button onclick="cerrarModalLimpieza()" 
                    class="text-white hover:text-blue-100 transition-colors p-2 hover:bg-white/10 rounded-lg">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Contenido del modal -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-180px)]">
            <!-- Contador y botones de selección -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b">
                <div class="text-sm text-gray-600">
                    <span id="contadorSeleccionadas" class="font-bold text-lg text-blue-600">0</span>
                    <span> de </span>
                    <span class="font-bold"><?= count($habitaciones_limpieza) ?></span>
                    <span> habitaciones seleccionadas</span>
                </div>
                <div class="flex gap-2">
                    <button onclick="seleccionarTodasLimpieza(true)" 
                            class="text-xs px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors font-medium">
                        <i class="fas fa-check-square mr-1"></i>Todas
                    </button>
                    <button onclick="seleccionarTodasLimpieza(false)" 
                            class="text-xs px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                        <i class="fas fa-square mr-1"></i>Ninguna
                    </button>
                </div>
            </div>
            
            <!-- Lista de habitaciones -->
            <div class="space-y-2" id="listaHabitacionesLimpieza">
                <?php foreach ($habitaciones_limpieza as $hab): ?>
                <label class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg cursor-pointer border-2 border-transparent hover:border-blue-400 transition-all group">
                    <input 
                        type="checkbox" 
                        value="<?= $hab['id'] ?>"
                        class="checkbox-limpieza w-5 h-5 text-blue-600 rounded focus:ring-blue-500 mr-4"
                        onchange="actualizarContadorLimpieza()"
                    >
                    <div class="flex-1 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-600 text-white rounded-lg p-2 group-hover:bg-blue-700 transition-colors">
                                <i class="fas fa-door-open text-lg"></i>
                            </div>
                            <div>
                                <span class="font-bold text-gray-800 text-lg">Habitación <?= $hab['numero'] ?></span>
                                <div class="text-xs text-gray-600 mt-0.5">
                                    <span class="inline-flex items-center">
                                        <i class="fas fa-layer-group mr-1"></i>
                                        <?= $pisos[$hab['piso']] ?? 'Piso ' . $hab['piso'] ?>
                                    </span>
                                    <span class="mx-2">•</span>
                                    <span><?= $hab['tipo'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                                <i class="fas fa-broom"></i>
                                <span>En limpieza</span>
                            </div>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($habitaciones_limpieza)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-3 text-green-500"></i>
                <p class="font-medium">No hay habitaciones en limpieza</p>
                <p class="text-sm">Todas las habitaciones están disponibles</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer con botones -->
        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t">
            <button onclick="cerrarModalLimpieza()" 
                    class="px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors font-medium">
                <i class="fas fa-times mr-2"></i>Cancelar
            </button>
            <button onclick="marcarHabitacionesLimpias()" 
                    id="btnMarcarLimpias"
                    class="px-6 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all font-bold shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                <i class="fas fa-check-circle mr-2"></i>Marcar como Limpias
            </button>
        </div>
    </div>
</div>
<!-- Formulario oculto para check-out rápido -->
<form id="formCheckOut" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="hora_salida" value="<?= date('H:i:s') ?>">
</form>

<!-- ════════════════════════════════════════════════════════════════════
     BOUTIQUE REFINEMENT LAYER — adaptación visual de "habitaciones.html"
     Solo CSS. Brand-aware (--brand-*). Estados con color semántico fijo.
     No altera flip-cards, formularios, JS, rutas ni la lógica de estados.
     Capa scopeada a .habitaciones-view para ganar especificidad sin tocar markup.
     ════════════════════════════════════════════════════════════════════ -->
<style id="hb-boutique-refinement">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&display=swap');

.habitaciones-view{
  /* Identidad del hotel (fallback boutique navy/oro) */
  --hb-primary: var(--brand-primary, #1B2746);
  --hb-secondary: var(--brand-secondary, #0F172A);
  --hb-accent: var(--brand-accent, #BD9441);
  --hb-ivory:#F6F2EA; --hb-ivory-2:#FBF8F2;
  --hb-surface:#FFFFFF; --hb-surface-warm:#FCFAF5;
  --hb-line:#E7E1D4; --hb-line-soft:#F0EBE0;
  --hb-slate-700:#3E4A66; --hb-slate-500:#6C7689; --hb-slate-400:#9AA1B2;
  /* Estados (significado fijo) */
  --c-available:#1E9E63; --bg-available:#E8F3EC;
  --c-occupied:#C2603C;  --bg-occupied:#F8EAE1;   /* OCUPADA = terracota/rojo (override del diseño, "más visible que slate") */
  --c-arriving:#5A57D2;  --bg-arriving:#ECEBFB;
  --c-cleaning:#2F77E0;  --bg-cleaning:#E7EFFB;
  --c-maint:#C2841C;     --bg-maint:#FAF0DA;
  --c-critical:#D64539;  --bg-critical:#FBEAE8;
  --serif:'Cormorant Garamond', Georgia, 'Times New Roman', serif;
  --hb-radius:16px; --hb-radius-lg:20px;
  --hb-shadow-xs:0 1px 2px rgba(27,39,70,.05);
  --hb-shadow-sm:0 1px 2px rgba(27,39,70,.05),0 2px 6px rgba(27,39,70,.05);
  --hb-shadow:0 4px 14px rgba(27,39,70,.07),0 22px 40px -24px rgba(27,39,70,.30);
}

/* ── Lienzo ── */
.habitaciones-view{
  background:
    radial-gradient(1100px 460px at 85% -12%, color-mix(in srgb, var(--hb-accent) 9%, transparent), transparent 60%),
    linear-gradient(180deg, var(--hb-ivory-2), var(--hb-ivory)) !important;
}
.habitaciones-view::before{ display:none !important; }

/* ── Header ── */
.habitaciones-view .modern-header{
  background:color-mix(in srgb,#fff 86%,transparent)!important;
  border-bottom:1px solid var(--hb-line)!important;
  box-shadow:0 1px 0 rgba(255,255,255,.7) inset,0 10px 26px -22px rgba(27,39,70,.6)!important;
}
.habitaciones-view .modern-header h1{
  font-family:var(--serif)!important; font-size:2rem!important; font-weight:600!important;
  color:var(--hb-primary)!important; letter-spacing:0!important; line-height:1!important;
}
.habitaciones-view .modern-header p{ color:var(--hb-slate-500)!important; }
.habitaciones-view .modern-header .p-2.rounded-lg{
  background:linear-gradient(150deg,var(--hb-primary),var(--hb-secondary))!important; border-radius:12px!important;
}

/* ── Widgets (6 semánticos) ── */
.habitaciones-view .hb-stats{ display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:18px; }
.habitaciones-view .hb-stat{
  display:flex; flex-direction:column; text-decoration:none; background:var(--hb-surface);
  border:1px solid var(--hb-line); border-radius:var(--hb-radius); padding:14px 15px;
  box-shadow:var(--hb-shadow-sm); position:relative; overflow:hidden; transition:transform .16s, box-shadow .16s; --sc:var(--hb-primary);
}
.habitaciones-view .hb-stat::after{ content:''; position:absolute; left:0; right:0; bottom:0; height:3px; background:var(--sc); opacity:0; transition:opacity .2s; }
.habitaciones-view .hb-stat:hover{ transform:translateY(-2px); box-shadow:var(--hb-shadow); }
.habitaciones-view .hb-stat:hover::after{ opacity:1; }
.habitaciones-view .hb-stat-ic{ width:34px; height:34px; border-radius:10px; display:grid; place-items:center; background:color-mix(in srgb,var(--sc) 13%,#fff); color:var(--sc); margin-bottom:10px; }
.habitaciones-view .hb-stat-ic i{ font-size:.95rem; }
.habitaciones-view .hb-stat-n{ font-family:var(--serif); font-size:2.05rem; font-weight:700; line-height:1; color:var(--hb-primary); font-variant-numeric:tabular-nums; }
.habitaciones-view .hb-stat-l{ font-size:.7rem; font-weight:700; letter-spacing:.03em; color:var(--hb-slate-500); margin-top:6px; text-transform:uppercase; }
.habitaciones-view .hb-stat--total{ --sc:var(--hb-primary); }
.habitaciones-view .hb-stat--available{ --sc:var(--c-available); }
.habitaciones-view .hb-stat--occupied{ --sc:var(--c-occupied); }
.habitaciones-view .hb-stat--arriving{ --sc:var(--c-arriving); }
.habitaciones-view .hb-stat--cleaning{ --sc:var(--c-cleaning); }
.habitaciones-view .hb-stat--maint{ --sc:var(--c-maint); }

/* ── Filtros ── */
.habitaciones-view .filter-input,
.habitaciones-view .filter-select,
.habitaciones-view .filter-date{
  background:var(--hb-surface-warm)!important; border:1px solid var(--hb-line)!important;
  border-radius:11px!important; color:var(--hb-slate-700)!important; font-weight:600!important;
}
.habitaciones-view .filter-input:focus,
.habitaciones-view .filter-select:focus,
.habitaciones-view .filter-date:focus{
  outline:none!important; border-color:var(--hb-accent)!important;
  box-shadow:0 0 0 3px color-mix(in srgb, var(--hb-accent) 22%, transparent)!important;
}
.habitaciones-view .filter-btn{ border-radius:11px!important; font-weight:700!important; }
.habitaciones-view .filter-btn-primary{
  background:linear-gradient(150deg,var(--hb-primary),var(--hb-secondary))!important; color:#fff!important; border-color:transparent!important;
  box-shadow:0 8px 18px -10px color-mix(in srgb,var(--hb-primary) 75%, transparent)!important;
}
.habitaciones-view .filter-btn-primary:hover{ transform:translateY(-1px)!important; }
.habitaciones-view .filter-btn-today{ background:var(--hb-surface)!important; border:1px solid var(--hb-line)!important; color:var(--hb-slate-700)!important; }
.habitaciones-view .filter-btn-reset{ background:var(--bg-critical)!important; color:var(--c-critical)!important; border-color:transparent!important; }

/* ── Alertas ── */
.habitaciones-view .alert-panel{ background:var(--hb-surface)!important; border:1px solid var(--hb-line)!important; border-left:4px solid var(--c-critical)!important; border-radius:var(--hb-radius)!important; box-shadow:var(--hb-shadow-sm)!important; }
.habitaciones-view .alert-header{ background:linear-gradient(120deg, var(--bg-critical), color-mix(in srgb, var(--bg-critical) 35%, #fff))!important; border-bottom:1px solid var(--hb-line)!important; }
.habitaciones-view .alert-badge{ background:var(--c-critical)!important; box-shadow:0 6px 14px -6px var(--c-critical)!important; }
.habitaciones-view .alert-item{ border-radius:12px!important; border:1px solid var(--hb-line)!important; border-left:4px solid!important; }
.habitaciones-view .alert-critical{ border-left-color:var(--c-critical)!important; background:var(--bg-critical)!important; }
.habitaciones-view .alert-warning{ border-left-color:var(--c-maint)!important; background:var(--bg-maint)!important; }
.habitaciones-view .alert-info{ border-left-color:var(--c-cleaning)!important; background:var(--bg-cleaning)!important; }
.habitaciones-view .btn-alert{ border-radius:10px!important; font-weight:700!important; }
.habitaciones-view .btn-alert-primary{ background:var(--c-critical)!important; color:#fff!important; }
.habitaciones-view .btn-alert-warning{ background:var(--c-maint)!important; color:#fff!important; }
.habitaciones-view .btn-alert-info{ background:var(--c-cleaning)!important; color:#fff!important; }

/* ── TARJETAS DE HABITACIÓN (rediseño boutique) ── */
.habitaciones-view #habitaciones-grid{ gap:15px!important; }
.habitaciones-view .room-card-compact{ border-radius:var(--hb-radius)!important; }
.habitaciones-view .room-card-compact:hover{ box-shadow:var(--hb-shadow)!important; }
.habitaciones-view .flip-card-front{
  border:1px solid var(--hb-line)!important; border-radius:var(--hb-radius)!important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.55)!important; padding:0!important; overflow:hidden!important;
}
.habitaciones-view .flip-card-front::before,
.habitaciones-view .flip-card-front::after{ display:none!important; }
/* superficie semántica + barra de acento izquierda */
.habitaciones-view .estado-disponible,.habitaciones-view .estado-disponible_fecha{ background:var(--bg-available)!important; border-left:4px solid var(--c-available)!important; background-size:auto!important; }
.habitaciones-view .estado-ocupada,.habitaciones-view .estado-ocupada_fecha{ background:var(--bg-occupied)!important; border-left:4px solid var(--c-occupied)!important; background-size:auto!important; }
.habitaciones-view .estado-por_llegar,.habitaciones-view .estado-doble{ background:var(--bg-arriving)!important; border-left:4px solid var(--c-arriving)!important; }
.habitaciones-view .estado-limpieza,.habitaciones-view .estado-limpieza-por-llegar{ background:var(--bg-cleaning)!important; border-left:4px solid var(--c-cleaning)!important; }
.habitaciones-view .estado-mantenimiento{ background:var(--bg-maint)!important; border-left:4px solid var(--c-maint)!important; }

/* layout de la cara */
.habitaciones-view .rc-face{ position:relative; height:100%; display:flex; flex-direction:column; padding:13px 15px 13px 18px; }
.habitaciones-view .rc-stripe{ position:absolute; top:14px; left:0; width:5px; height:26px; border-radius:0 3px 3px 0; box-shadow:0 1px 3px rgba(0,0,0,.18); }
.habitaciones-view .rc-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.habitaciones-view .rc-num{ font-family:var(--serif)!important; font-size:1.95rem!important; font-weight:700!important; line-height:.92!important; color:var(--hb-primary)!important; letter-spacing:0!important; font-variant-numeric:tabular-nums; }
.habitaciones-view .rc-type{ font-size:.68rem; font-weight:600; color:var(--hb-slate-500); margin-top:3px; text-transform:uppercase; letter-spacing:.03em; }
.habitaciones-view .rc-badge{ display:inline-flex; align-items:center; gap:5px; font-size:.6rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase; padding:5px 9px; border-radius:999px; color:#fff; white-space:nowrap; background:var(--hb-primary); box-shadow:0 2px 6px -2px rgba(27,39,70,.4); }
.habitaciones-view .rc-badge i{ font-size:.58rem; }
.habitaciones-view .estado-disponible .rc-badge,.habitaciones-view .estado-disponible_fecha .rc-badge{ background:var(--c-available); }
.habitaciones-view .estado-ocupada .rc-badge,.habitaciones-view .estado-ocupada_fecha .rc-badge{ background:var(--c-occupied); }
.habitaciones-view .estado-por_llegar .rc-badge,.habitaciones-view .estado-doble .rc-badge{ background:var(--c-arriving); }
.habitaciones-view .estado-limpieza .rc-badge,.habitaciones-view .estado-limpieza-por-llegar .rc-badge{ background:var(--c-cleaning); }
.habitaciones-view .estado-mantenimiento .rc-badge{ background:var(--c-maint); }
.habitaciones-view .rc-mid{ margin-top:auto; min-width:0; }
.habitaciones-view .rc-guest{ display:flex; align-items:center; gap:6px; font-size:.8rem; font-weight:600; color:var(--hb-primary); min-width:0; }
.habitaciones-view .rc-guest i{ font-size:.68rem; color:var(--hb-slate-400); flex:none; }
.habitaciones-view .rc-guest span{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.habitaciones-view .rc-guest--empty{ color:var(--hb-slate-400); font-weight:500; }
.habitaciones-view .rc-meta{ font-size:.68rem; color:var(--hb-slate-500); margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.habitaciones-view .rc-foot{ display:flex; align-items:center; justify-content:space-between; margin-top:9px; gap:8px; }
.habitaciones-view .rc-price{ font-size:.82rem; font-weight:800; color:var(--hb-primary); white-space:nowrap; }
.habitaciones-view .rc-price small{ font-weight:600; color:var(--hb-slate-400); font-size:.6rem; }
.habitaciones-view .rc-hint{ display:inline-flex; align-items:center; gap:4px; font-size:.6rem; font-weight:700; color:var(--hb-slate-400); white-space:nowrap; }

/* indicadores (esquina) — conservados, refinados */
.habitaciones-view .checkout-today-indicator,
.habitaciones-view .checkout-vencido-indicator,
.habitaciones-view .checkin-vencido-indicator,
.habitaciones-view .late-arrival-indicator{ border-radius:999px!important; letter-spacing:.03em!important; box-shadow:0 3px 8px -2px rgba(0,0,0,.25)!important; z-index:5; }

/* ── Reverso / hoja de acciones ── */
.habitaciones-view .flip-card-back{ border-radius:var(--hb-radius)!important; }
.habitaciones-view .flip-card-back h4{ font-family:var(--serif)!important; font-weight:600!important; font-size:1.25rem!important; }
.habitaciones-view .flip-card-back .action-buttons{ gap:8px!important; }
.habitaciones-view .flip-card-back .btn-action{ border-radius:10px!important; font-weight:700!important; backdrop-filter:blur(4px)!important; border:1px solid rgba(255,255,255,.28)!important; background:rgba(255,255,255,.16)!important; }
.habitaciones-view .flip-card-back .btn-action:hover{ background:rgba(255,255,255,.3)!important; }
.habitaciones-view .flip-card-back .btn-primary{ background:#fff!important; color:var(--room-accent-color,var(--hb-primary))!important; border:none!important; box-shadow:0 4px 12px -4px rgba(0,0,0,.3)!important; }

/* ── Empty state ── */
.habitaciones-view .p-8.text-center{ background:var(--hb-ivory-2)!important; border:1px dashed var(--hb-line)!important; border-radius:var(--hb-radius)!important; }
.habitaciones-view .p-8.text-center .bg-gray-100{ background:color-mix(in srgb,var(--hb-accent) 16%, #fff)!important; color:var(--hb-accent)!important; }
.habitaciones-view .p-8.text-center .text-gray-400{ color:var(--hb-accent)!important; }

/* ── Modales ── */
#vistaRapidaModal .bg-white.rounded-xl, #modalLimpieza .bg-white.rounded-xl{ border-radius:18px!important; box-shadow:0 28px 70px -24px rgba(27,39,70,.45)!important; }

/* ── Responsive ── */
@media (max-width:1100px){ .habitaciones-view .hb-stats{ grid-template-columns:repeat(3,1fr); } }
@media (max-width:560px){
  .habitaciones-view .hb-stats{ grid-template-columns:repeat(2,1fr); gap:10px; }
  .habitaciones-view .modern-header h1{ font-size:1.6rem!important; }
  .habitaciones-view .rc-num{ font-size:1.8rem!important; }
}

/* serif (gana al universal DM Sans via ID specificity) */
#mainHeader h1{ font-family:var(--serif)!important; }
#hbStats .hb-stat-n{ font-family:var(--serif)!important; }
#habitaciones-grid .rc-num{ font-family:var(--serif)!important; }
#habitaciones-grid .flip-card-back h4{ font-family:var(--serif)!important; }
#habitaciones-grid .floor-t{ font-family:var(--serif)!important; }

/* ── Secciones por piso ── */
.habitaciones-view #habitaciones-grid{ display:block!important; }
.habitaciones-view .floor-section{ margin-bottom:4px; }
.habitaciones-view .floor-label{ display:flex; align-items:center; gap:14px; margin:24px 2px 14px; }
.habitaciones-view .floor-section:first-child .floor-label{ margin-top:4px; }
.habitaciones-view .floor-t{ font-size:1.35rem; font-weight:600; color:var(--hb-primary); white-space:nowrap; line-height:1; }
.habitaciones-view .floor-rule{ flex:1; height:1px; background:var(--hb-line); }
.habitaciones-view .floor-ct{ font-size:.7rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--hb-slate-400); white-space:nowrap; }
.habitaciones-view .rgrid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(216px, 1fr)); gap:15px; }
@media (max-width:560px){ .habitaciones-view .rgrid{ grid-template-columns:1fr; gap:12px; } }

/* ════ Animaciones (adaptadas de habitaciones.html) ════ */
.habitaciones-view .room-card-compact:not(.flipped):hover{ transform:translateY(-3px)!important; }
/* Hoja de acciones: el reverso revela info + botones de forma escalonada al voltear */
@keyframes hbReveal{ from{ opacity:0; transform:translateY(10px); } to{ opacity:1; transform:none; } }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item{ animation:hbReveal .34s cubic-bezier(.22,1,.36,1) backwards; }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item:nth-child(2){ animation-delay:.05s; }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item:nth-child(3){ animation-delay:.10s; }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item:nth-child(4){ animation-delay:.15s; }
.habitaciones-view .flip-card.flipped .flip-card-back .action-buttons{ animation:hbReveal .36s cubic-bezier(.22,1,.36,1) .18s backwards; }
/* Modales propios: pop al abrir (vista rápida y limpieza) */
@keyframes hbModalPop{ from{ opacity:0; transform:translateY(14px) scale(.985); } to{ opacity:1; transform:none; } }
#vistaRapidaModal:not(.hidden) > .bg-white, #modalLimpieza:not(.hidden) > .bg-white{ animation:hbModalPop .26s cubic-bezier(.22,1,.36,1); }
/* Tiles de vista rápida: micro-zoom ya existente; respetar reduce-motion */
@media (prefers-reduced-motion: reduce){
  .habitaciones-view .room-card-compact, .habitaciones-view .flip-card-back .info-item,
  .habitaciones-view .flip-card-back .action-buttons,
  #vistaRapidaModal > .bg-white, #modalLimpieza > .bg-white{ animation:none!important; transition:none!important; }
  .habitaciones-view .room-card-compact:not(.flipped):hover{ transform:none!important; }
}

/* ════ Modales — SweetAlert2 + propios (marca, NO --ms-*) ════ */
/* nivel body: SweetAlert vive fuera de .habitaciones-view → usar --brand-* directo */
.swal2-popup{ border-radius:20px!important; box-shadow:0 28px 70px -24px rgba(27,39,70,.45)!important; }
.swal2-title{ color:var(--brand-primary,#1B2746)!important; }
.swal2-styled.swal2-confirm{ border:0!important; border-radius:11px!important; font-weight:700!important; box-shadow:0 10px 22px -12px rgba(27,39,70,.45)!important; }
.swal2-styled.swal2-confirm:hover{ filter:brightness(1.06); }
/* Íconos decorativos de formularios DENTRO de SweetAlert → marca (el púrpura semántico de "por llegar" en leyendas vive fuera de swal y no se toca) */
.swal2-popup .text-purple-600{ color:var(--brand-primary,#1B2746)!important; }
.swal2-popup .text-purple-800{ color:var(--brand-secondary,#0F172A)!important; }
.swal2-popup .bg-purple-600{ background:var(--brand-primary,#1B2746)!important; }
/* Reverso / hoja de acciones: encabezado con separador (estilo sheet del diseño) */
.habitaciones-view .flip-card-back h4{ border-bottom:1px solid rgba(255,255,255,.22)!important; padding-bottom:7px!important; margin-bottom:5px!important; letter-spacing:.01em; }
.swal2-styled.swal2-confirm:focus{ box-shadow:0 0 0 3px color-mix(in srgb, var(--brand-primary,#1B2746) 30%, transparent)!important; }
.swal2-styled.swal2-cancel{ border-radius:11px!important; font-weight:700!important; }
/* Tarjetas selectoras "Cliente Nuevo / Existente" del flujo Reservar */
.brand-hover-card{ transition:all .18s ease!important; }
.brand-hover-card:hover{ background:var(--brand-primary,#1B2746)!important; border-color:var(--brand-primary,#1B2746)!important; color:#fff!important; transform:translateY(-2px); box-shadow:0 12px 24px -12px rgba(27,39,70,.55)!important; }
.brand-text{ color:var(--brand-primary,#1B2746)!important; }
/* Modal Limpieza: header + botón primario a marca (como Vista Rápida) */
#modalLimpieza .bg-gradient-to-r{ background:linear-gradient(135deg, var(--brand-primary,#1B2746), var(--brand-secondary,#0F172A))!important; }
#modalLimpieza .text-blue-600, #modalLimpieza .text-blue-700{ color:var(--brand-primary,#1B2746)!important; }
/* Acento dorado de marca para detalles/realces de los modales propios */
#vistaRapidaModal .vr-accent, #modalLimpieza .vr-accent{ color:var(--brand-accent,#BD9441)!important; }
</style>

<!-- JavaScript -->
<script>
// Función para hacer flip con clic
// Reemplazar la función toggleFlip con esta versión mejorada
function toggleFlip(card, event) {
    // Prevenir propagación si se hace clic en un enlace o botón
    if (event) {
        const target = event.target;
        if (target.closest('a') || target.closest('button') || target.closest('.btn-action')) {
            event.stopPropagation();
            return;
        }
    }
    
    // Hacer flip en todos los dispositivos
    card.classList.toggle('flipped');
}

function crearReservacionConFecha(habitacionId, fecha) {
    // Agregar T00:00:00 para evitar problemas de zona horaria
    const fechaEntrada = new Date(fecha + 'T00:00:00');
    const fechaSalida = new Date(fechaEntrada);
    fechaSalida.setDate(fechaSalida.getDate() + 1);
    
    // Función auxiliar para formatear fecha en formato local YYYY-MM-DD
    function formatearFechaLocal(fecha) {
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${año}-${mes}-${dia}`;
    }
    
    const fechaSalidaStr = formatearFechaLocal(fechaSalida);
    
    window.location.href = '<?= url('reservaciones/crear') ?>?' + 
        'habitacion_id=' + habitacionId + 
        '&fecha_entrada=' + fecha +
        '&fecha_salida=' + fechaSalidaStr;
}

function crearReservacionRapida(habitacionId) {
    // Obtener fechas actuales
    const hoy = new Date();
    const manana = new Date(hoy);
    manana.setDate(manana.getDate() + 1);
    
    // Función auxiliar para formatear fecha en formato local YYYY-MM-DD
    function formatearFechaLocal(fecha) {
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${año}-${mes}-${dia}`;
    }
    
    const fechaEntrada = formatearFechaLocal(hoy);
    const fechaSalida = formatearFechaLocal(manana);
    const horaActual = hoy.toTimeString().slice(0, 5);
    
    // PRIMER MODAL: Selección de tipo de cliente
    Swal.fire({
        title: 'Tipo de Cliente',
        html: `
            <div class="text-center">
                <p class="mb-6 text-gray-600">Seleccione el tipo de cliente para la reservación</p>
                
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="seleccionarTipoCliente('nuevo', ${habitacionId}, '${fechaEntrada}', '${fechaSalida}', '${horaActual}')" 
                            class="p-4 border-2 border-gray-300 rounded-lg brand-hover-card transition-all group">
                        <i class="fas fa-user-plus text-3xl mb-2 block brand-text group-hover:text-white"></i>
                        <span class="font-semibold block">Cliente Nuevo</span>
                        <p class="text-xs mt-1 text-gray-500 group-hover:text-white">Primer hospedaje</p>
                    </button>
                    <button onclick="seleccionarTipoCliente('existente', ${habitacionId}, '${fechaEntrada}', '${fechaSalida}', '${horaActual}')" 
                            class="p-4 border-2 border-gray-300 rounded-lg brand-hover-card transition-all group">
                        <i class="fas fa-user-check text-3xl mb-2 block brand-text group-hover:text-white"></i>
                        <span class="font-semibold block">Cliente Existente</span>
                        <p class="text-xs mt-1 text-gray-500 group-hover:text-white">Ya registrado</p>
                    </button>
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        cancelButtonColor: '#6B7280',
        width: '500px'
    });
}

// Función para entrega rápida de control remoto desde el índice de habitaciones
function entregarRemotoRapido(habitacionId, reservacionId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Entregar Control Remoto',
            html: `
                <div class="text-left space-y-4">
                    <!-- Tipo de identificación -->
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-id-card mr-1"></i>
                            Tipo de identificación:
                        </p>
                        <div class="space-y-2">
                            <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                                <input type="radio" name="tipo_id_rapido" value="ine" class="mr-2" checked>
                                <i class="fas fa-id-card mr-2 text-purple-600"></i>
                                INE
                            </label>
                            <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                                <input type="radio" name="tipo_id_rapido" value="licencia" class="mr-2">
                                <i class="fas fa-car mr-2 text-purple-600"></i>
                                Licencia de Conducir
                            </label>
                            <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                                <input type="radio" name="tipo_id_rapido" value="otro" class="mr-2">
                                <i class="fas fa-passport mr-2 text-purple-600"></i>
                                Otro
                            </label>
                        </div>
                    </div>

                    <!-- Nombre del propietario de la INE (NUEVO en v2.0) -->
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>
                            Nombre del propietario de la identificación:
                        </p>
                        <input type="text" 
                               id="nombre_propietario_ine_rapido" 
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                               placeholder="Ej: Juan Pérez García">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Ingrese el nombre completo tal como aparece en la identificación
                        </p>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--brand-primary, #1B2746)',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const tipoId = document.querySelector('input[name="tipo_id_rapido"]:checked');
                if (!tipoId) {
                    Swal.showValidationMessage('Debe seleccionar el tipo de identificación');
                    return false;
                }
                
                const nombrePropietario = document.getElementById('nombre_propietario_ine_rapido').value.trim();
                if (!nombrePropietario) {
                    Swal.showValidationMessage('Debe ingresar el nombre del propietario de la identificación');
                    return false;
                }
                
                return {
                    tipo_identificacion: tipoId.value,
                    nombre_propietario: nombrePropietario
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario temporal
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/entregar-remoto") ?>';
                
                // CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= csrf_token() ?>';
                form.appendChild(csrfInput);
                
                // Habitación ID
                const habInput = document.createElement('input');
                habInput.type = 'hidden';
                habInput.name = 'habitacion_id';
                habInput.value = habitacionId;
                form.appendChild(habInput);
                
                // Reservación ID
                const resInput = document.createElement('input');
                resInput.type = 'hidden';
                resInput.name = 'reservacion_id';
                resInput.value = reservacionId;
                form.appendChild(resInput);
                
                // Tipo entrega
                const tipoInput = document.createElement('input');
                tipoInput.type = 'hidden';
                tipoInput.name = 'tipo_entrega';
                tipoInput.value = 'usuario_actual';
                form.appendChild(tipoInput);
                
                // Tipo identificación
                const tipoIdInput = document.createElement('input');
                tipoIdInput.type = 'hidden';
                tipoIdInput.name = 'tipo_identificacion';
                tipoIdInput.value = result.value.tipo_identificacion;
                form.appendChild(tipoIdInput);
                
                // Nombre del propietario de la INE (NUEVO campo v2.0)
                const nombreInput = document.createElement('input');
                nombreInput.type = 'hidden';
                nombreInput.name = 'nombre_propietario_ine';
                nombreInput.value = result.value.nombre_propietario;
                form.appendChild(nombreInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    } else {
        // Fallback sin SweetAlert
        const tipoId = prompt('Ingrese tipo de identificación:\n1 = INE\n2 = Licencia\n3 = Otro');
        if (tipoId) {
            let tipoIdentificacion = '';
            switch(tipoId) {
                case '1': tipoIdentificacion = 'ine'; break;
                case '2': tipoIdentificacion = 'licencia'; break;
                case '3': tipoIdentificacion = 'otro'; break;
                default: 
                    alert('Opción inválida');
                    return;
            }
            
            const nombrePropietario = prompt('Ingrese el nombre del propietario de la identificación:');
            if (!nombrePropietario) {
                alert('Debe ingresar el nombre del propietario');
                return;
            }
            
            if (confirm('¿Entregar control remoto con ' + tipoIdentificacion + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/entregar-remoto") ?>';
                form.innerHTML = `
                    <?= csrf_field() ?>
                    <input type="hidden" name="habitacion_id" value="${habitacionId}">
                    <input type="hidden" name="reservacion_id" value="${reservacionId}">
                    <input type="hidden" name="tipo_entrega" value="usuario_actual">
                    <input type="hidden" name="tipo_identificacion" value="${tipoIdentificacion}">
                    <input type="hidden" name="nombre_propietario_ine" value="${nombrePropietario}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    }
}


 /**
 * NUEVA: Entregar controles remotos a múltiples habitaciones
 * Permite entregar remotos a varias habitaciones con una sola INE
 * 
 * @param {number} reservacionId - ID de la reservación
 * @param {array} habitaciones - Array de objetos con {id, numero} de las habitaciones de la reservación
 */
function entregarRemotosMultiples(reservacionId, habitaciones) {
    if (typeof Swal === 'undefined') {
        alert('Esta funcionalidad requiere SweetAlert2');
        return;
    }

    // Filtrar solo habitaciones que el hotel tiene el remoto (disponibles para entregar)
    // Esto debería venir del backend, pero lo dejamos como ejemplo
    const habitacionesDisponibles = habitaciones.filter(h => h.hotel_tiene_remoto);
    
    if (habitacionesDisponibles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin remotos disponibles',
            text: 'Todos los remotos de esta reservación ya fueron entregados',
            confirmButtonColor: 'var(--brand-primary, #1B2746)'
        });
        return;
    }

    // Crear HTML con checkboxes para seleccionar habitaciones
    let habitacionesHTML = '';
    habitacionesDisponibles.forEach(hab => {
        habitacionesHTML += `
            <label class="flex items-center p-3 border rounded-lg hover:bg-purple-50 cursor-pointer mb-2">
                <input type="checkbox" name="habitaciones_sel" value="${hab.id}" class="mr-3 w-4 h-4">
                <i class="fas fa-door-open mr-2 text-purple-600"></i>
                <span class="font-semibold">Habitación ${hab.numero}</span>
            </label>
        `;
    });

    Swal.fire({
        title: 'Entregar Controles Remotos',
        html: `
            <div class="text-left space-y-4">
                <!-- Selección de habitaciones -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-double mr-1"></i>
                        Seleccione las habitaciones:
                    </p>
                    <div class="max-h-40 overflow-y-auto border rounded-lg p-2">
                        ${habitacionesHTML}
                    </div>
                    <button type="button" onclick="document.querySelectorAll('input[name=habitaciones_sel]').forEach(cb => cb.checked = true)" 
                            class="mt-2 text-xs text-purple-600 hover:text-purple-800">
                        <i class="fas fa-check-square mr-1"></i>Seleccionar todas
                    </button>
                </div>

                <!-- Tipo de identificación -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-1"></i>
                        Tipo de identificación:
                    </p>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_id_multiple" value="ine" class="mr-2" checked>
                            <i class="fas fa-id-card mr-2 text-purple-600"></i>
                            INE
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_id_multiple" value="licencia" class="mr-2">
                            <i class="fas fa-car mr-2 text-purple-600"></i>
                            Licencia de Conducir
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_id_multiple" value="otro" class="mr-2">
                            <i class="fas fa-passport mr-2 text-purple-600"></i>
                            Otro
                        </label>
                    </div>
                </div>

                <!-- Nombre del propietario de la INE (NUEVO en v2.0) -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-1"></i>
                        Nombre del propietario de la identificación:
                    </p>
                    <input type="text" 
                           id="nombre_propietario_ine_multiple" 
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                           placeholder="Ej: Juan Pérez García">
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ingrese el nombre completo tal como aparece en la identificación
                    </p>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonColor: 'var(--brand-primary, #1B2746)',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-hand-holding mr-2"></i>Entregar Remotos',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            // Validar que se hayan seleccionado habitaciones
            const habitacionesSeleccionadas = Array.from(
                document.querySelectorAll('input[name="habitaciones_sel"]:checked')
            ).map(cb => cb.value);
            
            if (habitacionesSeleccionadas.length === 0) {
                Swal.showValidationMessage('Debe seleccionar al menos una habitación');
                return false;
            }

            // Validar tipo de identificación
            const tipoId = document.querySelector('input[name="tipo_id_multiple"]:checked');
            if (!tipoId) {
                Swal.showValidationMessage('Debe seleccionar el tipo de identificación');
                return false;
            }

            // Validar nombre del propietario
            const nombrePropietario = document.getElementById('nombre_propietario_ine_multiple').value.trim();
            if (!nombrePropietario) {
                Swal.showValidationMessage('Debe ingresar el nombre del propietario de la identificación');
                return false;
            }

            return {
                habitaciones: habitacionesSeleccionadas,
                tipo_identificacion: tipoId.value,
                nombre_propietario: nombrePropietario
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario temporal
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url("reservaciones/entregar-remotos-multiples") ?>';
            
            // CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= csrf_token() ?>';
            form.appendChild(csrfInput);
            
            // Reservación ID
            const resInput = document.createElement('input');
            resInput.type = 'hidden';
            resInput.name = 'reservacion_id';
            resInput.value = reservacionId;
            form.appendChild(resInput);
            
            // Habitaciones seleccionadas (como array)
            result.value.habitaciones.forEach(habId => {
                const habInput = document.createElement('input');
                habInput.type = 'hidden';
                habInput.name = 'habitaciones_ids[]';
                habInput.value = habId;
                form.appendChild(habInput);
            });
            
            // Tipo entrega
            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo_entrega';
            tipoInput.value = 'usuario_actual';
            form.appendChild(tipoInput);
            
            // Tipo identificación
            const tipoIdInput = document.createElement('input');
            tipoIdInput.type = 'hidden';
            tipoIdInput.name = 'tipo_identificacion';
            tipoIdInput.value = result.value.tipo_identificacion;
            form.appendChild(tipoIdInput);
            
            // Nombre del propietario de la INE (NUEVO campo v2.0)
            const nombreInput = document.createElement('input');
            nombreInput.type = 'hidden';
            nombreInput.name = 'nombre_propietario_ine';
            nombreInput.value = result.value.nombre_propietario;
            form.appendChild(nombreInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

/**
 * Función para recibir control remoto de MÚLTIPLES habitaciones de una reservación
 * Esta función muestra un modal para seleccionar habitaciones
 * 
 * @param {number} reservacionId - ID de la reservación
 * @param {array} habitaciones - Array de objetos con {id, numero} de las habitaciones de la reservación
 */
function recibirRemotosMultiples(reservacionId, habitaciones) {
    if (typeof Swal === 'undefined') {
        alert('Esta funcionalidad requiere SweetAlert2');
        return;
    }

    // Filtrar solo habitaciones que tienen el remoto con el huésped (disponibles para recibir)
    // Esto debería venir del backend, pero lo dejamos como ejemplo
    const habitacionesConRemoto = habitaciones.filter(h => !h.hotel_tiene_remoto);
    
    if (habitacionesConRemoto.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin remotos para recibir',
            text: 'Todos los remotos de esta reservación ya están en el hotel',
            confirmButtonColor: '#10B981'
        });
        return;
    }

    // Crear HTML con checkboxes para seleccionar habitaciones
    let habitacionesHTML = '';
    habitacionesConRemoto.forEach(hab => {
        habitacionesHTML += `
            <label class="flex items-center p-3 border rounded-lg hover:bg-green-50 cursor-pointer mb-2">
                <input type="checkbox" name="habitaciones_recibir" value="${hab.id}" class="mr-3 w-4 h-4">
                <i class="fas fa-door-open mr-2 text-green-600"></i>
                <span class="font-semibold">Habitación ${hab.numero}</span>
            </label>
        `;
    });

    Swal.fire({
        title: 'Recibir Controles Remotos',
        html: `
            <div class="text-left space-y-4">
                <!-- Selección de habitaciones -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-double mr-1"></i>
                        Seleccione las habitaciones:
                    </p>
                    <div class="max-h-40 overflow-y-auto border rounded-lg p-2">
                        ${habitacionesHTML}
                    </div>
                    <button type="button" onclick="document.querySelectorAll('input[name=habitaciones_recibir]').forEach(cb => cb.checked = true)" 
                            class="mt-2 text-xs text-green-600 hover:text-green-800">
                        <i class="fas fa-check-square mr-1"></i>Seleccionar todas
                    </button>
                </div>

                <!-- Notas opcionales -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sticky-note mr-1"></i>
                        Notas (opcional):
                    </p>
                    <textarea id="notas_recepcion_multiple" 
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                              rows="3"
                              placeholder="Ej: Remotos en buen estado"></textarea>
                </div>

                <div class="bg-green-50 p-3 rounded-lg">
                    <p class="text-xs text-green-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Se devolverán los controles remotos y las identificaciones correspondientes
                    </p>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-hand-holding mr-2"></i>Recibir Remotos',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            // Validar que se hayan seleccionado habitaciones
            const habitacionesSeleccionadas = Array.from(
                document.querySelectorAll('input[name="habitaciones_recibir"]:checked')
            ).map(cb => cb.value);
            
            if (habitacionesSeleccionadas.length === 0) {
                Swal.showValidationMessage('Debe seleccionar al menos una habitación');
                return false;
            }

            const notas = document.getElementById('notas_recepcion_multiple').value.trim();

            return {
                habitaciones: habitacionesSeleccionadas,
                notas: notas || 'Devolución múltiple desde índice'
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario temporal
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url("reservaciones/recibir-remotos-multiples") ?>';
            
            // CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= csrf_token() ?>';
            form.appendChild(csrfInput);
            
            // Reservación ID
            const resInput = document.createElement('input');
            resInput.type = 'hidden';
            resInput.name = 'reservacion_id';
            resInput.value = reservacionId;
            form.appendChild(resInput);
            
            // Habitaciones seleccionadas (como array)
            result.value.habitaciones.forEach(habId => {
                const habInput = document.createElement('input');
                habInput.type = 'hidden';
                habInput.name = 'habitaciones_ids[]';
                habInput.value = habId;
                form.appendChild(habInput);
            });
            
            // Tipo recepción
            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo_recepcion';
            tipoInput.value = 'usuario_actual';
            form.appendChild(tipoInput);
            
            // Notas
            const notasInput = document.createElement('input');
            notasInput.type = 'hidden';
            notasInput.name = 'notas';
            notasInput.value = result.value.notas;
            form.appendChild(notasInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}


function seleccionarTipoCliente(tipo, habitacionId, fechaEntrada, fechaSalida, horaActual) {
    Swal.fire({
        title: 'Datos de la Reservación',
        html: `
            <div class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center px-4 py-2 rounded-lg ${tipo === 'existente' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}">
                        <i class="fas ${tipo === 'existente' ? 'fa-user-check' : 'fa-user-plus'} mr-2"></i>
                        <span class="font-semibold">Cliente ${tipo === 'existente' ? 'Existente' : 'Nuevo'}</span>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 text-left text-sm">
                    <div class="space-y-2 mb-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Check-in:</span>
                            <span class="font-medium">${formatearFechaCorta(new Date(fechaEntrada + 'T00:00:00'))}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Check-out:</span>
                            <span class="font-medium">${formatearFechaCorta(new Date(fechaSalida + 'T00:00:00'))}</span>
                        </div>
                    </div>
                    
                    <div class="pt-3 border-t">
                        <label class="block text-gray-700 font-medium mb-2">
                            Hora llegada:
                        </label>
                        <div class="flex gap-2">
                            <input type="time" 
                                   id="horaLlegadaRapida" 
                                   value="" 
                                   placeholder="--:--"
                                   class="flex-1 px-3 py-2 border rounded-lg brand-focus">
                            <button onclick="document.getElementById('horaLlegadaRapida').value = '${horaActual}'" 
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition-colors"
                                    title="Usar hora actual">
                                <i class="fas fa-clock text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: `Continuar con Cliente ${tipo === 'existente' ? 'Existente' : 'Nuevo'}`,
        cancelButtonText: 'Volver',
        confirmButtonColor: 'var(--brand-primary, #2563EB)',
        preConfirm: () => {
            const horaSeleccionada = document.getElementById('horaLlegadaRapida').value;
            if (!horaSeleccionada) {
                Swal.showValidationMessage('Por favor ingrese la hora de llegada');
                return false;
            }
            return horaSeleccionada;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const horaSeleccionada = result.value;
            
            // Guardar datos en sessionStorage como respaldo
            const datosReservacion = {
                habitacionId: habitacionId,
                fechaEntrada: fechaEntrada,
                fechaSalida: fechaSalida,
                horaLlegada: horaSeleccionada
            };
            
            sessionStorage.setItem('reservacionRapida', JSON.stringify(datosReservacion));
            
            if (tipo === 'nuevo') {
                // MODIFICADO: Pasar los parámetros por URL para cliente nuevo
                const params = new URLSearchParams({
                    'return_to': 'reservacion_rapida',
                    'habitacion_id': habitacionId,
                    'fecha_entrada': fechaEntrada,
                    'fecha_salida': fechaSalida,
                    'hora_llegada': horaSeleccionada
                });
                
                window.location.href = '<?= url('huespedes/create') ?>?' + params.toString();
            } else {
                // Cliente existente - ir directo a crear reservación
                const params = new URLSearchParams({
                    habitacion_id: habitacionId,
                    fecha_entrada: fechaEntrada,
                    fecha_salida: fechaSalida,
                    hora_llegada: horaSeleccionada,
                    preseleccion: 'true'
                });
                
                window.location.href = '<?= url('reservaciones/crear') ?>?' + params.toString();
            }
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Volver al primer modal
            crearReservacionRapida(habitacionId);
        }
    });
}

// Función auxiliar para formatear fecha
function formatearFechaCorta(fecha) {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const dia = fecha.getDate();
    const mes = meses[fecha.getMonth()];
    const año = fecha.getFullYear();
    return `${dia} ${mes} ${año}`;
}

function hacerCheckInRapido(reservacionId) {
    Swal.fire({
        title: '¿Realizar Check-in?',
        text: 'Se procederá con el check-in del huésped',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--brand-primary, #1B2746)',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-sign-in-alt mr-2"></i>Hacer Check-in',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= url('reservaciones/ver/') ?>' + reservacionId + '#checkin';
        }
    });
}

// ============================================================================
// FUNCIÓN MEJORADA: Check-out con selección de habitaciones
// ============================================================================

function confirmarCheckOut(reservacionId) {
    // Mostrar loading mientras obtenemos los datos
    Swal.fire({
        title: 'Cargando información...',
        html: 'Obteniendo datos de la reservación',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // NUEVA FORMA: Obtener las habitaciones desde el servidor con sus IDs reales
    fetch(`<?= url('api/reservaciones/') ?>${reservacionId}/habitaciones`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (!data.success || !data.habitaciones || data.habitaciones.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron obtener las habitaciones'
            });
            return;
        }
        
        const habitaciones = data.habitaciones;
        
        // Si solo hay 1 habitación, hacer check-out directo
        if (habitaciones.length === 1) {
            checkOutDirectoIndex(reservacionId, habitaciones[0]);
        } else {
            // Si hay múltiples, mostrar modal de selección
            mostrarModalCheckOutIndex(reservacionId, habitaciones);
        }
    })
    .catch(error => {
        console.error('Error al obtener habitaciones:', error);
        Swal.close();
        
        // Si falla el API, usar método de respaldo
        confirmarCheckOutRespaldo(reservacionId);
    });
}

// ========================================
// MÉTODO DE RESPALDO (si falla el API)
// ========================================
function confirmarCheckOutRespaldo(reservacionId) {
    Swal.fire({
        title: '¿Realizar Check-out?',
        text: 'Se registrará la salida del huésped',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F97316',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Sí, hacer check-out',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarCheckOutIndex(reservacionId, null);
        }
    });
}

// ========================================
// CHECK-OUT DIRECTO (1 habitación)
// ========================================


// ========================================
// MODAL DE SELECCIÓN DE HABITACIONES
// ========================================
function mostrarModalCheckOutIndex(reservacionId, habitaciones) {
    // ✅ CORRECCIÓN: Manejar TODOS los posibles formatos de ID
    let habitacionesHTML = habitaciones.map(hab => {
        // Intentar obtener el ID en este orden de prioridad:
        const habId = hab.reservacion_habitacion_id || hab.habitacion_id || hab.id || hab.rel_id;
        
        // Si no hay ID, alertar
        if (!habId || habId === 'undefined') {
            console.error('⚠️ Habitación sin ID válido:', hab);
        }
        
        return `
        <label class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg cursor-pointer border-2 border-transparent hover:border-orange-400 transition-all">
            <input 
                type="checkbox" 
                value="${habId}"
                class="checkbox-habitacion-index mr-3 w-5 h-5 text-orange-600 rounded focus:ring-orange-500"
                checked
                data-hab-numero="${hab.numero || hab.habitacion_numero}"
            >
            <div class="flex-1">
                <span class="font-bold text-gray-800">Hab. ${hab.numero || hab.habitacion_numero || 'N/A'}</span>
                <span class="text-xs text-gray-500 ml-2">(${hab.tipo || hab.tipo_nombre || 'Standard'})</span>
            </div>
        </label>
        `;
    }).join('');
    
    Swal.fire({
        title: 'Seleccionar Habitaciones',
        html: `
            <div class="text-left">
                <p class="text-sm text-gray-600 mb-4">
                    Selecciona las habitaciones a liberar:
                </p>
                <div class="space-y-2 mb-4 max-h-96 overflow-y-auto">
                    ${habitacionesHTML}
                </div>
                <div class="flex gap-2 mb-4">
                    <button 
                        onclick="document.querySelectorAll('.checkbox-habitacion-index').forEach(cb => cb.checked = true)"
                        class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                        Todas
                    </button>
                    <button 
                        onclick="document.querySelectorAll('.checkbox-habitacion-index').forEach(cb => cb.checked = false)"
                        class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                        Ninguna
                    </button>
                </div>
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Si seleccionas todas, se completará el check-out. Si seleccionas algunas, la reservación permanecerá activa.
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#F97316',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-check mr-2"></i>Confirmar Check-out',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        width: '500px',
        customClass: {
            popup: 'swal-popup-scrollable',
            htmlContainer: 'swal-content-scrollable'
        },
        preConfirm: () => {
            // ✅ CORRECCIÓN: Asegurar que se convierten a números correctamente
            const checkboxes = document.querySelectorAll('.checkbox-habitacion-index:checked');
            const seleccionadas = Array.from(checkboxes).map(cb => {
                const valor = cb.value;
                const numero = parseInt(valor, 10);
                
                return numero;
            }).filter(id => !isNaN(id) && id > 0); // Filtrar NaN y valores inválidos
            
            if (seleccionadas.length === 0) {
                Swal.showValidationMessage('Debes seleccionar al menos una habitación');
                return false;
            }
            
            return seleccionadas;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            ejecutarCheckOutIndex(reservacionId, result.value);
        }
    });
}

// ========================================
// EJECUTAR CHECK-OUT CON HABITACIONES
// ========================================

// ========================================
// CHECK-OUT RÁPIDO (todas las habitaciones)
// ========================================
function ejecutarCheckOutRapido(reservacionId) {
    Swal.fire({
        title: 'Procesando Check-out...',
        html: 'Registrando salida del huésped',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('hora_salida', new Date().toTimeString().slice(0, 8));
    
    const endpoint = `<?= url('reservaciones/check-out-rapido/') ?>${reservacionId}`;
    
    fetch(endpoint, {
        method: 'POST',
        body: formData,
        redirect: 'follow'
    })
    .then(response => {
        // Siempre quedarse en la misma página (index)
        if (response.redirected || !response.ok) {
            // El servidor procesó el check-out y redirigió, recargar index
            window.location.reload();
            return null;
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        
        window.location.reload();
        return null;
    })
    .then(data => {
        if (data === null) return;
        
        Swal.close();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Check-out Completado!',
                html: data.message || 'La salida se ha registrado correctamente',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error al procesar check-out');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo completar el check-out'
        });
    });
}


// Obtener información de la reservación con sus habitaciones
function obtenerInfoReservacion(reservacionId) {
    Swal.fire({
        title: 'Cargando información...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Hacer petición para obtener las habitaciones de la reservación
    fetch(`<?= url('reservaciones/ver/') ?>${reservacionId}`)
        .then(response => response.text())
        .then(html => {
            // Parsear el HTML para extraer información de habitaciones
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Buscar información de habitaciones en el HTML
            const habitacionesInfo = extraerHabitacionesDelHTML(doc, reservacionId);
            
            if (habitacionesInfo.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron obtener las habitaciones'
                });
                return;
            }
            
            // Si solo hay 1 habitación, hacer check-out directo
            if (habitacionesInfo.length === 1) {
                checkOutDirectoIndex(reservacionId, habitacionesInfo[0]);
            } else {
                // Si hay múltiples, mostrar modal de selección
                mostrarModalCheckOutIndex(reservacionId, habitacionesInfo);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Si falla, hacer check-out tradicional
            checkOutDirectoIndex(reservacionId, null);
        });
}

// Extraer información de habitaciones del HTML
function extraerHabitacionesDelHTML(doc, reservacionId) {
    const habitaciones = [];
    
    // Buscar en la tabla o lista de habitaciones
    const habitacionesElements = doc.querySelectorAll('[data-habitacion-id], .habitacion-item, tr[data-habitacion]');
    
    if (habitacionesElements.length > 0) {
        habitacionesElements.forEach((el, index) => {
            const id = el.dataset.habitacionId || el.dataset.habitacion || (index + 1);
            const numero = el.textContent.match(/\d+/)?.[0] || (index + 1);
            const tipo = el.textContent.match(/(Sencilla|Doble|Triple|Suite|Cuádruple)/i)?.[0] || 'Standard';
            
            habitaciones.push({
                id: parseInt(id),
                numero: numero,
                tipo: tipo
            });
        });
    }
    
    // Si no encontramos habitaciones en el HTML, usar datos del checkout actual
    if (habitaciones.length === 0) {
        // Buscar en los datos de checkout del día
        const checkoutElement = document.querySelector(`[onclick*="confirmarCheckOut(${reservacionId})"]`);
        if (checkoutElement) {
            const parent = checkoutElement.closest('.p-2, .flex');
            const habitacionesText = parent?.querySelector('.text-xs')?.textContent || '';
            const numeros = habitacionesText.match(/\d+/g) || [];
            
            numeros.forEach((num, index) => {
                habitaciones.push({
                    id: index + 1, // ID temporal
                    numero: num,
                    tipo: 'Standard'
                });
            });
        }
    }
    
    return habitaciones;
}

function checkOutDirectoIndex(reservacionId, habitacionInfo) {
    const mensajeHabitacion = habitacionInfo 
        ? `<p class="mb-2">Habitación: <strong>${habitacionInfo.numero || habitacionInfo.habitacion_numero}</strong></p>`
        : '';
    
    Swal.fire({
        title: '¿Realizar Check-out?',
        html: `
            <div class="text-left">
                ${mensajeHabitacion}
                <p class="text-sm text-gray-600">Se registrará la salida y las habitaciones pasarán a limpieza</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F97316',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Sí, hacer check-out',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Obtener el ID correcto
            const habId = habitacionInfo.reservacion_habitacion_id || habitacionInfo.habitacion_id || habitacionInfo.id;
            ejecutarCheckOutIndex(reservacionId, habId ? [habId] : null);
        }
    });
}

// Modal de selección de habitaciones


// Ejecutar el check-out con las habitaciones seleccionadas
function ejecutarCheckOutIndex(reservacionId, habitacionesIds) {
    Swal.fire({
        title: 'Procesando Check-out...',
        html: 'Registrando salida del huésped',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('hora_salida', new Date().toTimeString().slice(0, 8));
    
    // ✅ CORRECCIÓN: Agregar habitaciones de forma más robusta
    if (habitacionesIds && Array.isArray(habitacionesIds) && habitacionesIds.length > 0) {
        habitacionesIds.forEach((id, index) => {
            formData.append('habitaciones[]', id);
        });
    }
    
    // Usar el endpoint de check-out parcial
    const endpoint = `<?= url('reservaciones/check-out-parcial/') ?>${reservacionId}`;

    fetch(endpoint, {
        method: 'POST',
        body: formData,
        redirect: 'follow'
    })
    .then(response => {
        // Siempre quedarse en la misma página (index)
        if (response.redirected) {
            window.location.reload();
            return null;
        }
        
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }
        
        // Intentar parsear como JSON
        const contentType = response.headers.get('content-type');

        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        
        // Si no es JSON, es un redirect exitoso o HTML
        window.location.reload();
        return null;
    })
    .then(data => {
        if (!data) return; // Ya manejado (redirect)

        if (data.success) {
            let htmlContent = `
                <div class="text-left mx-auto" style="max-width: 500px;">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <h4 class="font-semibold text-lg mb-2 flex items-center">
                            <i class="fas fa-check-circle mr-2 text-green-600"></i>
                            Check-out realizado
                        </h4>
                        <div class="space-y-2 text-sm">
                            ${data.huesped ? `<p><strong>Huésped:</strong> ${data.huesped}</p>` : ''}
                            ${data.habitaciones ? `<p><strong>Habitaciones liberadas:</strong> ${data.habitaciones}</p>` : ''}
                            <p><strong>Hora salida:</strong> ${data.hora_salida || new Date().toLocaleTimeString()}</p>
                            ${data.total ? `<p><strong>Total estancia:</strong> ${data.total}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            Swal.fire({
                icon: 'success',
                title: 'Check-out Exitoso',
                html: htmlContent,
                confirmButtonColor: '#F97316',
                confirmButtonText: '<i class="fas fa-check mr-2"></i>Aceptar'
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error desconocido en el check-out');
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error al procesar el check-out',
            confirmButtonColor: '#F97316'
        });
    });
}

function liberarHabitacion(id) {
    Swal.fire({
        title: '¿Marcar como disponible?',
        text: 'La habitación quedará lista para nuevas reservaciones',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-check mr-2"></i>Sí, está limpia',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loader
            Swal.fire({
                title: 'Procesando...',
                html: 'Actualizando estado de la habitación',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Hacer la petición AJAX
            fetch('<?= url('habitaciones/') ?>' + id + '/liberar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'csrf_token=<?= csrf_token() ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Habitación Disponible!',
                        html: `
                            <div class="text-center">
                                <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                                <p class="text-lg mb-2">Habitación <strong>${data.numero || id}</strong></p>
                                <p class="text-gray-600">Ha sido marcada como disponible</p>
                            </div>
                        `,
                        confirmButtonColor: '#059669',
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo actualizar el estado de la habitación',
                        confirmButtonColor: '#dc2626'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud',
                    confirmButtonColor: '#dc2626'
                });
            });
        }
    });
}

function finalizarMantenimiento(id) {
    Swal.fire({
        title: '¿Finalizar mantenimiento?',
        text: 'La habitación quedará disponible para reservaciones',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F59E0B',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-tools mr-2"></i>Sí, finalizar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('habitaciones/') ?>' + id + '/mantenimiento';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = 'csrf_token';
            csrfToken.value = '<?= csrf_token() ?>';
            form.appendChild(csrfToken);
            
            const accion = document.createElement('input');
            accion.type = 'hidden';
            accion.name = 'accion';
            accion.value = 'finalizar';
            form.appendChild(accion);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function mostrarVistaRapida() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('mainHeader').style.display = 'none';
    document.getElementById('vistaRapidaModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarVistaRapida() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('mainHeader').style.display = '';
    document.getElementById('vistaRapidaModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
// Función para recepción rápida de control remoto desde el índice de habitaciones
function recibirRemotoRapido(habitacionId, reservacionId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Recibir control remoto?',
            text: 'Se registrará la devolución del control remoto y la identificación',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, recibir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario temporal
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/recibir-remoto") ?>';
                
                // CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= csrf_token() ?>';
                form.appendChild(csrfInput);
                
                // Habitación ID
                const habInput = document.createElement('input');
                habInput.type = 'hidden';
                habInput.name = 'habitacion_id';
                habInput.value = habitacionId;
                form.appendChild(habInput);
                
                // Reservación ID
                const resInput = document.createElement('input');
                resInput.type = 'hidden';
                resInput.name = 'reservacion_id';
                resInput.value = reservacionId;
                form.appendChild(resInput);
                
                // Tipo recepción
                const tipoInput = document.createElement('input');
                tipoInput.type = 'hidden';
                tipoInput.name = 'tipo_recepcion';
                tipoInput.value = 'usuario_actual';
                form.appendChild(tipoInput);
                
                // Notas
                const notasInput = document.createElement('input');
                notasInput.type = 'hidden';
                notasInput.name = 'notas';
                notasInput.value = 'Devolución rápida desde índice';
                form.appendChild(notasInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    } else {
        if (confirm('¿Recibir control remoto del huésped?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url("reservaciones/recibir-remoto") ?>';
            form.innerHTML = `
                <?= csrf_field() ?>
                <input type="hidden" name="habitacion_id" value="${habitacionId}">
                <input type="hidden" name="reservacion_id" value="${reservacionId}">
                <input type="hidden" name="tipo_recepcion" value="usuario_actual">
                <input type="hidden" name="notas" value="Devolución rápida desde índice">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

function entregarLlaveRapida(habitacionId, reservacionId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Entregar llave al huésped?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3B82F6',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/entregar-llave") ?>';
                
                form.innerHTML = `
                    <?= csrf_field() ?>
                    <input type="hidden" name="habitacion_id" value="${habitacionId}">
                    <input type="hidden" name="reservacion_id" value="${reservacionId}">
                    <input type="hidden" name="tipo_entrega" value="usuario_actual">
                `;
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarVistaRapida();
    }
});

/**
 * ========================================
 * FUNCIONES PARA LIMPIEZA MÚLTIPLE
 * ========================================
 */

/**
 * Mostrar modal de limpieza
 */
function mostrarModalLimpieza() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('mainHeader').style.display = 'none';
    document.getElementById('modalLimpieza').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    actualizarContadorLimpieza();
}

/**
 * Cerrar modal de limpieza
 */
function cerrarModalLimpieza() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('mainHeader').style.display = '';
    document.getElementById('modalLimpieza').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

/**
 * Seleccionar/deseleccionar todas las habitaciones
 */
function seleccionarTodasLimpieza(seleccionar) {
    const checkboxes = document.querySelectorAll('.checkbox-limpieza');
    checkboxes.forEach(checkbox => {
        checkbox.checked = seleccionar;
    });
    actualizarContadorLimpieza();
}

/**
 * Actualizar contador de habitaciones seleccionadas
 */
/**
 * Actualizar contador de habitaciones seleccionadas
 */
function actualizarContadorLimpieza() {
    const checkboxes = document.querySelectorAll('.checkbox-limpieza:checked');
    const contador = checkboxes.length;
    
    // ✅ VALIDAR que el elemento existe antes de modificarlo
    const contadorElement = document.getElementById('contadorSeleccionadas');
    if (contadorElement) {
        contadorElement.textContent = contador;
    }
    
    // Habilitar/deshabilitar botón de confirmar
    const btnMarcar = document.getElementById('btnMarcarLimpias');
    if (btnMarcar) {
        if (contador > 0) {
            btnMarcar.disabled = false;
            btnMarcar.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            btnMarcar.disabled = true;
            btnMarcar.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
}

/**
 * Marcar habitaciones seleccionadas como limpias
 */
/**
 * Marcar habitaciones seleccionadas como limpias
 */
function marcarHabitacionesLimpias() {
    const checkboxes = document.querySelectorAll('.checkbox-limpieza:checked');
    const habitacionesIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    if (habitacionesIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Selecciona habitaciones',
            text: 'Debes seleccionar al menos una habitación',
            confirmButtonColor: '#3B82F6'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Procesando...',
        html: `Marcando ${habitacionesIds.length} habitación${habitacionesIds.length > 1 ? 'es' : ''} como limpia${habitacionesIds.length > 1 ? 's' : ''}`,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Enviar petición al servidor
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    habitacionesIds.forEach(id => {
        formData.append('habitaciones_ids[]', id);
    });
    
    const url = '<?= url('habitaciones/liberar-multiples') ?>';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Obtener el texto completo de la respuesta
        return response.text().then(text => {
            // Intentar parsear como JSON
            try {
                const data = JSON.parse(text);
                return { ok: response.ok, status: response.status, data: data };
            } catch (e) {
                console.error('ERROR: No se pudo parsear como JSON:', e);
                console.error('Text that failed to parse:', text);
                throw new Error('La respuesta del servidor no es JSON válido. Ver consola para detalles.');
            }
        });
    })
    .then(result => {
        if (result.data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Habitaciones Listas!',
                html: `
                    <div class="text-center">
                        <div class="bg-green-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-4xl text-green-600"></i>
                        </div>
                        <p class="text-lg mb-2">
                            <strong>${result.data.actualizadas}</strong> habitación${result.data.actualizadas > 1 ? 'es' : ''} 
                            marcada${result.data.actualizadas > 1 ? 's' : ''} como disponible${result.data.actualizadas > 1 ? 's' : ''}
                        </p>
                        ${result.data.habitaciones ? `
                            <p class="text-sm text-gray-600 mt-2">
                                Habitaciones: ${result.data.habitaciones}
                            </p>
                        ` : ''}
                    </div>
                `,
                confirmButtonColor: '#10B981',
                confirmButtonText: 'Entendido',
                timer: 3000
            }).then(() => {
                location.reload();
            });
            
            cerrarModalLimpieza();
        } else {
            throw new Error(result.data.message || 'Error desconocido en el servidor');
        }
    })
    .catch(error => {
        console.error('ERROR CAPTURADO:', error);
        console.error('Error stack:', error.stack);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudieron actualizar las habitaciones',
            confirmButtonColor: '#EF4444'
        });
    });
}
<?php 
$hayLimpieza = false;
foreach ($habitaciones as $h) {
    if ($h['estado'] == 'limpieza') {
        $hayLimpieza = true;
        break;
    }
}
?>
<?php if ($hayLimpieza): ?>
const updateIndicator = document.createElement('div');

document.body.appendChild(updateIndicator);

let seconds = 60;
const countdown = setInterval(() => {
    seconds--;
    document.getElementById('countdown').textContent = seconds;
    if (seconds <= 0) {
        clearInterval(countdown);
        location.reload();
    }
}, 1000);
<?php endif; ?>

// Sistema de tooltips mejorado para vista rápida
document.addEventListener('DOMContentLoaded', function() {
    let tooltipTimeout;
    const tooltip = document.getElementById('roomTooltip');
    
    // Solo inicializar si existe el tooltip
    if (!tooltip) return;
    
    // Añadir eventos a todas las habitaciones de vista rápida
    document.querySelectorAll('.room-quick-view').forEach(room => {
        room.addEventListener('mouseenter', function(e) {
            clearTimeout(tooltipTimeout);
            
            try {
                const data = JSON.parse(this.getAttribute('data-tooltip'));
                
                // Construir contenido del tooltip con más información
                let tooltipContent = `
                    <div class="tooltip-header">
                        <i class="fas fa-door-open mr-1"></i>
                        Habitación ${data.numero}
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-layer-group"></i>
                        <span>${data.piso}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-bed"></i>
                        <span>${data.tipo}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-circle text-xs"></i>
                        <span class="font-semibold">${data.estado}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-tag"></i>
                        <span class="text-green-600 font-bold">${data.precio}</span>
                    </div>
                `;
                
                // Información adicional según el estado
                if (data.huesped) {
                    tooltipContent += `
                        <div class="tooltip-guest">
                            <div class="flex items-center gap-1 mb-1">
                                <i class="fas fa-user text-xs"></i>
                                <div class="font-semibold">${data.huesped}</div>
                            </div>
                    `;
                    
                    if (data.telefono) {
                        tooltipContent += `
                            <div class="text-xs flex items-center gap-1 mt-1">
                                <i class="fas fa-phone text-xs"></i>
                                <span>${data.telefono}</span>
                            </div>
                        `;
                    }
                    
                    if (data.fechas) {
                        tooltipContent += `
                            <div class="text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-calendar-alt text-xs"></i>
                                <span>${data.fechas}</span>
                            </div>
                        `;
                    }
                    
                    if (data.hora_llegada) {
                        tooltipContent += `
                            <div class="text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-clock text-xs"></i>
                                <span>Hora estimada: ${data.hora_llegada.substring(0, 5)}</span>
                            </div>
                        `;
                    }
                    
                    if (data.noches) {
                        tooltipContent += `
                            <div class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-moon mr-1"></i>${data.noches} noche${data.noches > 1 ? 's' : ''}
                            </div>
                        `;
                    }
                    
                    tooltipContent += `</div>`;
                }
                
                // Indicadores especiales
                if (data.estado === 'Limpieza') {
                    tooltipContent += `
                        <div class="text-xs text-blue-600 mt-2 font-medium">
                            <i class="fas fa-broom mr-1"></i>Tiempo estimado: 30 min
                        </div>
                    `;
                }
                
                if (data.estado === 'Mantenimiento') {
                    tooltipContent += `
                        <div class="text-xs text-amber-600 mt-2 font-medium">
                            <i class="fas fa-tools mr-1"></i>En proceso
                        </div>
                    `;
                }
                
                tooltip.innerHTML = tooltipContent;
                
                // Posicionar tooltip
                const rect = this.getBoundingClientRect();
                
                // Calcular posición inicial
                let top = rect.top - 10;
                let left = rect.left + (rect.width / 2);
                
                // Mostrar temporalmente para obtener dimensiones
                tooltip.style.opacity = '0';
                tooltip.style.display = 'block';
                
                const tooltipRect = tooltip.getBoundingClientRect();
                
                // Ajustar posición final
                top = rect.top - tooltipRect.height - 10;
                left = left - (tooltipRect.width / 2);
                
                // Verificar límites de pantalla
                if (top < 10) {
                    top = rect.bottom + 10;
                }
                
                if (left < 10) {
                    left = 10;
                } else if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                
                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
                tooltip.style.display = '';
                tooltip.style.opacity = '';
                
                // Mostrar tooltip con animación
                tooltipTimeout = setTimeout(() => {
                    tooltip.classList.add('show');
                }, 50);
                
            } catch (error) {
                console.error('Error al procesar tooltip:', error);
            }
        });
        
        room.addEventListener('mouseleave', function() {
            clearTimeout(tooltipTimeout);
            if (tooltip) {
                tooltip.classList.remove('show');
            }
        });
    });
});
</script>



<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
