#!/usr/bin/env python3
"""
Genera la copia estatica del portafolio que se publica en Render.

Uso, con el sitio local arrancado (arrancar-local.bat):

    python herramientas/exportar-estatico.py
    python herramientas/exportar-estatico.py --url https://portafolio-xxxx.onrender.com

Recorre el WordPress local siguiendo los enlaces desde la portada. Pide cada
pagina con la cabecera «X-OVC-Estatico: 1» (ver ov-child/includes/
modo-estatico.php: el formulario pasa a enviarse por FormSubmit y se quita lo
que necesita PHP), descarga las paginas y todo lo que usan -CSS, JS, fuentes,
imagenes- y lo deja en sitio-estatico/ con las direcciones de localhost
cambiadas.

Sin --url los enlaces quedan relativos a la raiz (/acerca/), que funciona en
cualquier dominio. Con --url quedan absolutos, que es lo que prefieren Google
y las vistas previas de WhatsApp/LinkedIn para la etiqueta canonical y og:url.

Despues:  git add -A  &&  git commit -m "..."  &&  git push
Render detecta el push y publica solo.

Solo usa la biblioteca estandar de Python 3.8+.
"""
import argparse
import html
import posixpath
import re
import shutil
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from collections import deque
from pathlib import Path

ORIGEN = "http://localhost:8080"
RAIZ = Path(__file__).resolve().parent.parent
DESTINO = RAIZ / "sitio-estatico"
PAGINA_404 = "/esta-pagina-no-existe-ovc/"

# Rutas de pagina que no se exportan: necesitan PHP o no las ve un visitante.
EXCLUIR = re.compile(
    r"^/(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|wp-cron\.php|wp-sitemap|"
    r"feed|comments/feed|author/|wp-content/|wp-includes/)|/feed/?$|/embed/?$"
)
TEXTO = {".html", ".css", ".js", ".json", ".svg", ".xml", ".txt"}

# Direcciones del sitio dentro de HTML/CSS/JS, normales o escapadas en JSON (\/).
RE_ABSOLUTA = re.compile(r"""(?:https?:)?\\?/\\?/localhost:8080((?:\\?/[^"'\s<>()\\]*)*)""")
# Atributos con ruta relativa a la raiz: src="/wp-content/..."
RE_RAIZ = re.compile(r"""(?:src|href|data-src|poster)\s*=\s*["'](/(?!/)[^"'#\s]*)""")
# srcset con varias direcciones separadas por comas.
RE_SRCSET = re.compile(r"""(?:srcset|data-srcset)\s*=\s*["']([^"']+)""")
# url(...) e @import dentro de CSS.
RE_CSS = re.compile(r"""url\(\s*['"]?([^'")]+?)['"]?\s*\)|@import\s+['"]([^'"]+)['"]""")


def log(*a):
    print(*a, flush=True)


def normalizar(ruta):
    """'\\/wp-content\\/a.css?ver=1&amp;x' -> ('/wp-content/a.css', 'ver=1&x')."""
    ruta = html.unescape(ruta.replace("\\/", "/")).split("#", 1)[0]
    ruta, _, consulta = ruta.partition("?")
    ruta = posixpath.normpath(ruta) + ("/" if ruta.endswith("/") and ruta != "/" else "")
    if not ruta.startswith("/"):
        ruta = "/" + ruta
    return ruta.replace("//", "/"), consulta


def es_pagina(ruta):
    return posixpath.splitext(ruta)[1].lower() in ("", ".html", ".htm")


def archivo_local(base, ruta, pagina):
    rel = urllib.parse.unquote(ruta).lstrip("/")
    if pagina and (rel == "" or rel.endswith("/")):
        rel += "index.html"
    elif pagina and posixpath.splitext(rel)[1] == "":
        rel += "/index.html"
    destino = (base / rel).resolve()
    if base.resolve() not in destino.parents:
        raise ValueError(f"ruta fuera de la carpeta: {ruta}")
    return destino


def descargar(ruta, consulta=""):
    url = ORIGEN + urllib.parse.quote(ruta, safe="/%:@&=+$,;~-._") + (f"?{consulta}" if consulta else "")
    req = urllib.request.Request(url, headers={"X-OVC-Estatico": "1", "User-Agent": "exportar-estatico/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=180) as r:
            return r.status, r.headers.get_content_type(), r.read()
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get_content_type(), e.read()


def reescribir(texto, base):
    """Cambia http://localhost:8080 por la direccion publica (o la quita)."""
    esc = base.replace("/", "\\/")
    texto = re.sub(r"(?:https?:)?\\/\\/localhost:8080(?=\\/)", lambda m: esc, texto)
    texto = re.sub(r"(?:https?:)?\\/\\/localhost:8080", lambda m: esc + "\\/", texto)
    texto = re.sub(r"(?:https?:)?//localhost:8080(?=/)", lambda m: base, texto)
    texto = re.sub(r"(?:https?:)?//localhost:8080", lambda m: base + "/", texto)
    texto = re.sub(r"https?%3A%2F%2Flocalhost%3A8080", lambda m: urllib.parse.quote(base, safe=""), texto, flags=re.I)
    return texto


def enlaces_html(texto):
    for m in RE_ABSOLUTA.finditer(texto):
        yield m.group(1) or "/"
    for m in RE_RAIZ.finditer(texto):
        yield m.group(1)
    for m in RE_SRCSET.finditer(texto):
        for parte in m.group(1).split(","):
            url = parte.strip().split(" ")[0]
            if url.startswith("/") and not url.startswith("//"):
                yield url


def enlaces_css(texto, ruta_css):
    carpeta = posixpath.dirname(ruta_css) + "/"
    for m in RE_CSS.finditer(texto):
        url = (m.group(1) or m.group(2) or "").strip()
        if not url or url.startswith(("data:", "#", "about:")):
            continue
        if "localhost:8080" in url:
            url = RE_ABSOLUTA.search(url).group(1) or "/"
        elif re.match(r"^(?:[a-z]+:)?//", url, re.I):
            continue  # Externo (Google Fonts...)
        elif not url.startswith("/"):
            url = urllib.parse.urljoin(carpeta, url)
        yield url


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--url", default="", help="direccion publica, p. ej. https://portafolio-xxxx.onrender.com")
    args = ap.parse_args()
    base = args.url.rstrip("/")

    try:
        descargar("/wp-includes/images/blank.gif")
    except OSError:
        sys.exit(f"No responde {ORIGEN}. Arranca antes el sitio local con arrancar-local.bat.")

    tmp = DESTINO.with_name(DESTINO.name + ".tmp")
    if tmp.exists():
        shutil.rmtree(tmp)
    tmp.mkdir(parents=True)

    pendientes = deque([("/", "", True), (PAGINA_404, "", True)])
    vistos = set()
    paginas = recursos = 0
    errores = []
    inicio = time.time()

    while pendientes:
        ruta, consulta, pagina = pendientes.popleft()
        clave = (ruta, pagina)
        if clave in vistos:
            continue
        vistos.add(clave)

        estado, tipo, cuerpo = descargar(ruta, consulta)
        es_404 = ruta == PAGINA_404
        if estado != 200 and not (es_404 and estado == 404):
            errores.append(f"{estado}  {ruta}")
            continue
        if pagina and tipo != "text/html":
            continue  # Una "pagina" que resulto no serlo (carpeta, feed...)

        destino = tmp / "404.html" if es_404 else archivo_local(tmp, ruta, pagina)
        destino.parent.mkdir(parents=True, exist_ok=True)
        ext = ".html" if pagina else posixpath.splitext(ruta)[1].lower()

        if ext in TEXTO:
            texto = cuerpo.decode("utf-8", "surrogateescape")
            if ext == ".html":
                encontrados = list(enlaces_html(texto))
            elif ext == ".css":
                encontrados = list(enlaces_css(texto, ruta)) + [m.group(1) or "/" for m in RE_ABSOLUTA.finditer(texto)]
            else:
                encontrados = [m.group(1) or "/" for m in RE_ABSOLUTA.finditer(texto)]

            for enlace in encontrados:
                r, q = normalizar(enlace)
                if r.lower().endswith(".php") or ".." in r.split("/"):
                    continue
                if es_pagina(r):
                    if not q and not EXCLUIR.search(r):
                        pendientes.append((r, "", True))
                else:
                    pendientes.append((r, q, False))

            cuerpo = reescribir(texto, base).encode("utf-8", "surrogateescape")

        destino.write_bytes(cuerpo)
        if pagina:
            paginas += 1
            log(f"  pagina   {ruta}")
        else:
            recursos += 1

    # Comprobacion: no debe quedar ninguna referencia a localhost.
    restos = []
    for f in tmp.rglob("*"):
        if f.suffix in TEXTO and b"localhost:8080" in f.read_bytes():
            restos.append(str(f.relative_to(tmp)))

    if DESTINO.exists():
        shutil.rmtree(DESTINO)
    tmp.rename(DESTINO)

    peso = sum(f.stat().st_size for f in DESTINO.rglob("*") if f.is_file()) / 1024 / 1024
    log(f"\nListo en {time.time() - inicio:.0f} s: {paginas} paginas, {recursos} recursos, {peso:.1f} MB en {DESTINO}")
    if errores:
        log(f"\n{len(errores)} direcciones no se pudieron descargar:")
        for e in sorted(set(errores)):
            log("  " + e)
    if restos:
        log(f"\nAVISO: {len(restos)} archivos aun mencionan localhost:8080:")
        for r in restos[:20]:
            log("  " + r)


if __name__ == "__main__":
    main()
