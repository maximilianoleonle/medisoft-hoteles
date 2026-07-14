/**
 * Tailwind PRECOMPILADO — Medisoft Hoteles
 * Antes el CSS lo generaba el Play CDN (vendor/tailwind/tailwindcdn.js) EN EL
 * NAVEGADOR en cada carga de página (cientos de ms de CPU, peor en móvil, y
 * unsafe-eval en la CSP). Ahora se compila una vez: `npm run build:css` →
 * src/public_html/css/tailwind.css (lo incluye header.php y errores 404/500).
 *
 * theme.extend = UNIÓN de las 3 configs inline que existían (layout/header.php,
 * errors/404.php, errors/500.php). Los colores se resuelven contra las
 * variables de marca white-label (--brand-*) con sus fallbacks de siempre;
 * errors/500 pierde sus hex legacy y adopta la paleta de marca (deseable).
 *
 * Si agregas clases Tailwind NUEVAS en vistas PHP o JS: correr build:css.
 * Clases construidas por concatenación (bg-<?= $x ?>) NO las ve el escáner:
 * escribir siempre el nombre completo o agregarlas a `safelist`.
 */
module.exports = {
  content: [
    './src/app/views/**/*.php',
    // OJO: helpers SOLO nivel superior (17 archivos). NO usar `**`:
    // src/app/helpers/tcpdf/fonts/ trae fuentes CID de TCPDF (cid0*.php,
    // 1.5MB con líneas de 1.1M caracteres) que ahogan el extractor de
    // Tailwind — el build pasa de ~4s a 5+ MINUTOS.
    './src/app/helpers/*.php',
    './src/app/controllers/**/*.php',
    './src/public_html/js/*.js',
    './src/public_html/js/*/*.js',
  ],
  theme: {
    extend: {
      colors: {
        'hotel-brown': 'var(--brand-secondary, #0F172A)',
        'hotel-brown-light': 'color-mix(in srgb, var(--brand-secondary, #0F172A) 82%, #FFFFFF)',
        'hotel-brown-dark': 'color-mix(in srgb, var(--brand-secondary, #0F172A) 92%, #000000)',
        'hotel-gold': 'var(--brand-accent, #BD9441)',
        'hotel-cream': 'color-mix(in srgb, var(--brand-accent, #BD9441) 9%, #F8F5ED)',
        'hotel-beige': 'color-mix(in srgb, var(--brand-accent, #BD9441) 18%, #F8F5ED)',
        'hotel-olive': 'var(--brand-primary, #1B2746)',
        'hotel-olive-light': 'color-mix(in srgb, var(--brand-primary, #1B2746) 76%, #FFFFFF)',
        'hotel-olive-dark': 'var(--brand-secondary, #0F172A)',
        'hotel-ink': 'var(--brand-text, #172033)',
        'hotel-muted': 'var(--brand-muted, #6B7280)',
      },
      fontFamily: {
        'playfair': ['Playfair Display', 'serif'],
        'inter': ['Inter', 'sans-serif'],
      },
    },
  },
};
