#!/usr/bin/env bash
# Cria wordpress/dist/tantie-lorraine.zip, pronto a carregar no WordPress
# (Aparência > Temas > Adicionar novo tema > Carregar tema).
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/tantie-lorraine.zip
python3 - <<'PY'
import os, zipfile
with zipfile.ZipFile("dist/tantie-lorraine.zip", "w", zipfile.ZIP_DEFLATED) as z:
    for raiz, pastas, ficheiros in os.walk("tantie-lorraine"):
        pastas.sort()
        for f in sorted(ficheiros):
            if f != ".DS_Store":
                z.write(os.path.join(raiz, f))
PY
echo "Criado: $(pwd)/dist/tantie-lorraine.zip ($(du -h dist/tantie-lorraine.zip | cut -f1))"
