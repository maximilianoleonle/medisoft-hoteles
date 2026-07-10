/* ── Sidebar PC de dos niveles (rail + flyout) ─────────────────────────────
   Construye el nivel 1 (áreas) a partir de las secciones ya renderizadas por
   sidebar.php — misma fuente de verdad: rutas, permisos y burbujas intactos.
   Solo escritorio ≥1025px y solo shell hotelero. Sin JS (o si algo falla),
   la sidebar clásica sigue funcionando tal cual.
   Revertir: borrar este archivo + css/sidebar-rail.css + las 2 líneas en
   views/layout/sidebar.php que los cargan. */
(function () {
    'use strict';

    var MQ = '(min-width: 1025px)';
    var built = false;

    /* Icono representativo por área; si no hay mapeo, hereda el del primer ítem. */
    var ICONOS = {
        'dashboard': 'fas fa-th-large',
        'gestion': 'fas fa-bed',
        'finanzas': 'fas fa-wallet',
        'operacion-interna': 'fas fa-clipboard-list',
        'ventas': 'fas fa-globe',
        'admin': 'fas fa-chart-line',
        'config': 'fas fa-cog'
    };

    function sentenceCase(s) {
        s = (s || '').trim();
        if (!s) { return s; }
        var lower = s.toLocaleLowerCase('es');
        return lower.charAt(0).toLocaleUpperCase('es') + lower.slice(1);
    }

    function claveSeccion(sec) {
        var claves = [];
        Array.prototype.forEach.call(sec.classList, function (c) {
            var m = c.match(/^hotel-(.+)-section$/);
            if (m && m[1] !== 'nav') { claves.push(m[1]); }
        });
        for (var i = 0; i < claves.length; i++) {
            if (ICONOS[claves[i]]) { return claves[i]; }
        }
        return claves[0] || '';
    }

    function leerItem(a) {
        var icono = a.querySelector('.nav-icon i');
        var texto = a.querySelector('.nav-text');
        var info = {
            href: a.getAttribute('href') || '#',
            icono: icono ? icono.className : 'fas fa-circle',
            etiqueta: texto ? texto.textContent.trim() : a.textContent.trim(),
            activo: a.classList.contains('active'),
            count: 0,
            green: false,
            nov: false
        };
        Array.prototype.forEach.call(a.querySelectorAll('.nav-badge'), function (b) {
            if (b.classList.contains('pulse-green')) { info.green = true; return; }
            if (b.classList.contains('nav-badge-novedad')) { info.nov = true; return; }
            var n = parseInt(b.textContent, 10);
            if (!isNaN(n) && n > 0) { info.count += n; }
        });
        return info;
    }

    function crearBadge(count, green, nov) {
        if (count > 0) {
            var pill = document.createElement('span');
            pill.className = 'ms-rail-badge';
            pill.textContent = count > 99 ? '99+' : String(count);
            return pill;
        }
        if (green || nov) {
            var dot = document.createElement('span');
            dot.className = 'ms-rail-dot ' + (green ? 'ms-green' : 'ms-nov');
            return dot;
        }
        return null;
    }

    function crearIcono(cls) {
        var wrap = document.createElement('span');
        wrap.className = 'ms-rail-ic';
        var i = document.createElement('i');
        i.className = cls;
        i.setAttribute('aria-hidden', 'true');
        wrap.appendChild(i);
        return wrap;
    }

    function init() {
        if (built || !window.matchMedia(MQ).matches) { return; }
        var sidebar = document.getElementById('sidebar');
        if (!sidebar || !sidebar.classList.contains('hotel-sidebar')) { return; }
        var nav = sidebar.querySelector('.hotel-sidebar-nav');
        if (!nav) { return; }

        var grupos = [];
        Array.prototype.forEach.call(nav.querySelectorAll('.nav-section.hotel-nav-section'), function (sec) {
            var items = sec.querySelectorAll('a.nav-item');
            if (!items.length) { return; }
            var titulo = sec.querySelector('.nav-section-title span');
            grupos.push({
                clave: claveSeccion(sec),
                titulo: sentenceCase(titulo ? titulo.textContent : ''),
                items: Array.prototype.map.call(items, leerItem)
            });
        });
        if (!grupos.length) { return; }
        built = true;

        var rail = document.createElement('div');
        rail.className = 'ms-rail';

        var fly = document.createElement('div');
        fly.className = 'ms-rail-fly';
        fly.setAttribute('role', 'group');
        document.body.appendChild(fly);

        var abierto = null;   /* botón de grupo con flyout abierto */
        var hideT = 0;

        function cerrarFly() {
            fly.classList.remove('ms-open');
            if (abierto) { abierto.classList.remove('ms-open'); abierto.setAttribute('aria-expanded', 'false'); }
            abierto = null;
        }

        function abrirFly(grupo, boton) {
            clearTimeout(hideT);
            if (abierto && abierto !== boton) { abierto.classList.remove('ms-open'); abierto.setAttribute('aria-expanded', 'false'); }
            abierto = boton;
            boton.classList.add('ms-open');
            boton.setAttribute('aria-expanded', 'true');

            fly.innerHTML = '';
            fly.setAttribute('aria-label', grupo.titulo);
            var ft = document.createElement('div');
            ft.className = 'ms-rail-ft';
            ft.textContent = grupo.titulo;
            fly.appendChild(ft);

            grupo.items.forEach(function (it) {
                var a = document.createElement('a');
                a.className = 'ms-rail-fi' + (it.activo ? ' ms-on' : '');
                a.href = it.href;
                var i = document.createElement('i');
                i.className = it.icono;
                i.setAttribute('aria-hidden', 'true');
                a.appendChild(i);
                a.appendChild(document.createTextNode(it.etiqueta));
                var b = crearBadge(it.count, it.green, it.nov);
                if (b) { a.appendChild(b); }
                fly.appendChild(a);
            });

            var r = boton.getBoundingClientRect();
            var sb = sidebar.getBoundingClientRect();
            fly.style.left = Math.round(sb.right + 8) + 'px';
            fly.classList.add('ms-open');
            var alto = fly.offsetHeight;
            var top = r.top - 6;
            var max = window.innerHeight - alto - 8;
            fly.style.top = Math.round(Math.max(8, Math.min(top, max))) + 'px';
        }

        function programarCierre() {
            clearTimeout(hideT);
            hideT = window.setTimeout(cerrarFly, 240);
        }

        grupos.forEach(function (grupo) {
            var activo = grupo.items.some(function (it) { return it.activo; });
            var suma = { count: 0, green: false, nov: false };
            grupo.items.forEach(function (it) {
                if (!it.activo) {
                    suma.count += it.count;
                    suma.green = suma.green || it.green;
                    suma.nov = suma.nov || it.nov;
                }
            });

            var esDirecto = grupo.items.length === 1;
            var fila = document.createElement(esDirecto ? 'a' : 'button');
            fila.className = 'ms-rail-g' + (activo ? ' ms-on' : '');

            var icono = (esDirecto ? grupo.items[0].icono : ICONOS[grupo.clave]) || grupo.items[0].icono;
            fila.appendChild(crearIcono(icono));

            var lb = document.createElement('span');
            lb.className = 'ms-rail-lb';
            lb.textContent = esDirecto ? grupo.items[0].etiqueta : grupo.titulo;
            fila.appendChild(lb);

            var badge = crearBadge(suma.count, suma.green, suma.nov);
            if (badge) { fila.appendChild(badge); }

            if (esDirecto) {
                fila.href = grupo.items[0].href;
                fila.addEventListener('mouseenter', cerrarFly);
            } else {
                fila.type = 'button';
                fila.setAttribute('aria-haspopup', 'true');
                fila.setAttribute('aria-expanded', 'false');
                var ch = document.createElement('i');
                ch.className = 'fas fa-chevron-right ms-rail-ch';
                ch.setAttribute('aria-hidden', 'true');
                fila.appendChild(ch);

                fila.addEventListener('mouseenter', function () { abrirFly(grupo, fila); });
                fila.addEventListener('click', function () {
                    if (abierto === fila) { cerrarFly(); } else { abrirFly(grupo, fila); }
                });
                fila.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { cerrarFly(); }
                });
            }

            rail.appendChild(fila);
        });

        rail.addEventListener('mouseleave', programarCierre);
        fly.addEventListener('mouseenter', function () { clearTimeout(hideT); });
        fly.addEventListener('mouseleave', programarCierre);

        document.addEventListener('click', function (e) {
            if (abierto && !fly.contains(e.target) && !rail.contains(e.target)) { cerrarFly(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && abierto) { var f = abierto; cerrarFly(); f.focus(); }
        });
        window.addEventListener('resize', cerrarFly, { passive: true });
        window.addEventListener('scroll', cerrarFly, { passive: true, capture: true });

        nav.insertBefore(rail, nav.firstChild);
        sidebar.classList.add('ms-rail-on');
    }

    init();
    if (!built) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        }
        window.addEventListener('resize', function () {
            if (!built && window.matchMedia(MQ).matches) { init(); }
        }, { passive: true });
    }
})();
