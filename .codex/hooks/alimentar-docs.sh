#!/usr/bin/env bash
# Hook de Stop: gatillo automatico de la regla de alimentacion (ver CLAUDE.md).
# Si la sesion modifico el repo, UNA VEZ por sesion le exige al modelo revisar
# si hay que alimentar CLAUDE.md / CEMENTERIO.md / docs/QA-RECETAS.md.
# Silencioso en todos los demas casos.

input=$(cat)
sid=$(printf '%s' "$input" | sed -n 's/.*"session_id"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
[ -z "$sid" ] && sid="sin-id"

# Solo actua si el arbol de trabajo tiene cambios
if [ -z "$(git status --porcelain 2>/dev/null)" ]; then
  exit 0
fi

# Una vez por sesion (marcador en TEMP)
marker="${TEMP:-/tmp}/claude-alimentar-docs-$sid"
[ -f "$marker" ] && exit 0
: > "$marker"

cat <<'EOF'
{"decision":"block","reason":"Recordatorio automatico (una vez por sesion): esta sesion modifico el repo. Aplica la regla de alimentacion de los documentos vivos: (a) hallazgo no obvio que ahorre tokens -> CLAUDE.md; (b) enfoque intentado que fallo o camino obvio que resulto muerto -> CEMENTERIO.md; (c) flujo verificado en el navegador o hueco pendiente ejecutado -> docs/QA-RECETAS.md. Alimenta lo que aplique ahora, o declara brevemente en tu respuesta que no hubo nada que registrar. Ten presente esta regla durante el resto de la sesion; este recordatorio no volvera a aparecer."}
EOF
