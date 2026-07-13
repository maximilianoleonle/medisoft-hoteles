<?php
/**
 * Filtros boutique - sistema compartido de barras de filtro (Deleite Sereno, white-label).
 *
 * Reemplaza las 6 convenciones fragmentadas de nomina/reportes (wk-*, snap-*, payroll-*,
 * audit-*, exp-*, inc-*) por una sola familia .msf-* derivada de --brand-*.
 *
 * Uso en una vista:
 *   <?php include APP_PATH . '/views/partials/filtros.php'; ?>
 *   <form class="msf-bar" method="GET" ...>
 *     <label class="msf-field">
 *       <span class="msf-label">Trabajador</span>
 *       <select class="msf-control" name="trabajador_id"><?= msf_worker_options($trabajadores, $id) ?></select>
 *     </label>
 *     ...
 *     <div class="msf-ranges" data-msf-from="fecha_inicio" data-msf-to="fecha_fin"> chips </div>
 *     <div class="msf-actions"> botones </div>
 *   </form>
 *
 * Los assets (CSS + JS) se emiten una sola vez por request.
 */

if (!function_exists('msf_worker_options')) {
    /**
     * Genera los <option> de un selector de trabajador. Tolerante a listas que solo
     * traen id + nombre_completo (incidencias) o filas completas (listarPorHotel).
     */
    function msf_worker_options(array $trabajadores, $selectedId = 0, string $labelTodos = 'Todos los trabajadores'): string
    {
        $selectedId = (int) $selectedId;
        $html = '<option value="0">' . htmlspecialchars($labelTodos) . '</option>';
        foreach ($trabajadores as $t) {
            $id = (int) ($t['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $nombre = trim((string) ($t['nombre_completo'] ?? ('#' . $id)));
            if ($nombre === '') {
                $nombre = '#' . $id;
            }
            $estado = (string) ($t['estado'] ?? 'activo');
            if ($estado === 'inactivo') {
                $nombre .= ' · inactivo';
            } elseif ($estado === 'baja') {
                $nombre .= ' · baja';
            }
            $sel = $id === $selectedId ? ' selected' : '';
            $html .= '<option value="' . $id . '"' . $sel . '>' . htmlspecialchars($nombre) . '</option>';
        }
        return $html;
    }
}

if (!function_exists('msf_options')) {
    /** Genera <option> a partir de un mapa valor => etiqueta. */
    function msf_options(array $mapa, $selected = null): string
    {
        $html = '';
        foreach ($mapa as $valor => $etiqueta) {
            $sel = ((string) $valor === (string) $selected) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars((string) $valor) . '"' . $sel . '>'
                . htmlspecialchars((string) $etiqueta) . '</option>';
        }
        return $html;
    }
}

if (defined('MSF_FILTROS_ASSETS')) {
    return;
}
define('MSF_FILTROS_ASSETS', 1);
?>
<style>
.msf-bar {
    --msf-brand: var(--brand-primary, #1B2746);
    --msf-accent: var(--brand-accent, #BD9441);
    --msf-surface: var(--brand-surface, #F6F2EA);
    --msf-text: var(--brand-text, #232323);
    --msf-muted: var(--brand-muted, #6d675e);
    --msf-border: var(--brand-border, #e3dccd);
    --msf-field-bg: var(--brand-surface-raised, #ffffff);
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px 14px;
    padding: 14px 16px;
    margin-bottom: 16px;
    background: color-mix(in srgb, var(--msf-surface) 45%, #ffffff);
    border: 1px solid var(--msf-border);
    border-radius: 16px;
}
.msf-bar.is-plain { padding: 0; margin-bottom: 14px; background: none; border: 0; border-radius: 0; }

.msf-field { display: flex; flex-direction: column; gap: 5px; min-width: 150px; flex: 1 1 170px; }
.msf-field--sm { flex: 0 1 130px; min-width: 118px; }
.msf-field--grow { flex: 2 1 230px; }
.msf-field--full { flex: 1 1 100%; }
.msf-label { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--msf-muted); }

.msf-control {
    width: 100%;
    border: 1px solid var(--msf-border);
    border-radius: 12px;
    padding: 10px 12px;
    font-size: 15px;
    line-height: 1.3;
    background: var(--msf-field-bg);
    color: var(--msf-text);
    transition: border-color .16s ease, box-shadow .16s ease;
}
select.msf-control {
    appearance: none;
    -webkit-appearance: none;
    padding-right: 34px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236d675e' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    cursor: pointer;
}
.msf-control::placeholder { color: color-mix(in srgb, var(--msf-muted) 75%, transparent); }
.msf-control:hover { border-color: color-mix(in srgb, var(--msf-accent) 45%, var(--msf-border)); }
.msf-control:focus {
    outline: none;
    border-color: var(--msf-accent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--msf-accent) 22%, transparent);
}

.msf-ranges { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; flex: 1 1 100%; }
.msf-ranges-label { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--msf-muted); margin-right: 2px; }
.msf-chip {
    display: inline-flex; align-items: center; gap: 5px;
    border: 1px solid var(--msf-border);
    background: color-mix(in srgb, var(--msf-surface) 60%, #ffffff);
    color: var(--msf-muted);
    border-radius: 999px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .14s ease, color .14s ease, background .14s ease;
}
.msf-chip:hover { border-color: color-mix(in srgb, var(--msf-accent) 55%, var(--msf-border)); color: var(--msf-brand); }
.msf-chip.is-active { background: var(--msf-brand); border-color: var(--msf-brand); color: #ffffff; }

.msf-check { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; color: var(--msf-text); padding-bottom: 9px; cursor: pointer; }
.msf-check input { width: 16px; height: 16px; accent-color: var(--msf-accent); }

.msf-actions { display: flex; align-items: flex-end; gap: 8px; margin-left: auto; }
.msf-btn {
    display: inline-flex; align-items: center; gap: 7px;
    border: 1px solid var(--msf-border);
    border-radius: 12px;
    padding: 10px 15px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.1;
    cursor: pointer;
    background: var(--msf-field-bg);
    color: var(--msf-text);
    text-decoration: none;
    white-space: nowrap;
    transition: filter .14s ease, border-color .14s ease;
}
.msf-btn:hover { border-color: color-mix(in srgb, var(--msf-accent) 55%, var(--msf-border)); }
.msf-btn--primary { background: var(--msf-brand); border-color: var(--msf-brand); color: #ffffff; }
.msf-btn--primary:hover { filter: brightness(1.08); border-color: var(--msf-brand); }

@media (max-width: 640px) {
    .msf-bar { padding: 12px; gap: 10px; }
    .msf-field, .msf-field--sm, .msf-field--grow { flex: 1 1 100%; min-width: 0; }
    .msf-control { font-size: 16px; } /* evita el zoom automatico de iOS */
    .msf-actions { width: 100%; margin-left: 0; }
    .msf-actions .msf-btn { flex: 1 1 auto; justify-content: center; }
}
</style>
<script>
(function () {
    if (window.__msfFiltrosReady) { return; }
    window.__msfFiltrosReady = true;

    function fmt(d) {
        var m = ('0' + (d.getMonth() + 1)).slice(-2);
        var day = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + m + '-' + day;
    }

    function rango(key) {
        var now = new Date();
        var y = now.getFullYear();
        var mo = now.getMonth();
        switch (key) {
            case 'hoy': return { from: fmt(now), to: fmt(now) };
            case '7d': {
                var s = new Date(now);
                s.setDate(now.getDate() - 6);
                return { from: fmt(s), to: fmt(now) };
            }
            case 'mes': return { from: fmt(new Date(y, mo, 1)), to: fmt(new Date(y, mo + 1, 0)) };
            case 'mes-pasado': return { from: fmt(new Date(y, mo - 1, 1)), to: fmt(new Date(y, mo, 0)) };
            case 'todo': return { from: '', to: '' };
            default: return null;
        }
    }

    document.addEventListener('click', function (e) {
        var chip = e.target.closest('[data-msf-range]');
        if (!chip) { return; }
        var form = chip.closest('form');
        if (!form) { return; }

        var host = chip.closest('[data-msf-from], [data-msf-to]') || form;
        var fromName = host.getAttribute('data-msf-from');
        var toName = host.getAttribute('data-msf-to');
        var r = rango(chip.getAttribute('data-msf-range'));
        if (!r) { return; }

        var fromEl = fromName ? form.querySelector('[name="' + fromName + '"]') : null;
        var toEl = toName ? form.querySelector('[name="' + toName + '"]') : null;
        if (fromEl) { fromEl.value = r.from; }
        if (toEl) { toEl.value = r.to; }

        var chips = host.querySelectorAll('[data-msf-range]');
        for (var i = 0; i < chips.length; i++) {
            chips[i].classList.toggle('is-active', chips[i] === chip);
        }

        if (form.matches('[data-auto-filter-form]')) {
            var trigger = fromEl || toEl;
            if (trigger) { trigger.dispatchEvent(new Event('change', { bubbles: true })); }
        } else if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
})();
</script>
