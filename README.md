# Portafolio — Luis Guillermo Nieto

Código del sitio [portafolio.saludgo.org](https://portafolio.saludgo.org): un WordPress con
**Divi** como tema padre y un tema hijo propio, **OV Child**, que es lo que contiene este repositorio.

## Qué hay aquí

| Ruta | Qué es |
|---|---|
| [`wp-content/themes/ov-child/`](wp-content/themes/ov-child/) | El tema hijo: tipos de contenido del CV (experiencia, estudios, aficiones), ficha técnica y portadas de los proyectos, formulario de contacto con captcha propio, iconos sociales y estilos. Detalle en su [LEEME.md](wp-content/themes/ov-child/LEEME.md). |
| `.htaccess` | Reglas de seguridad y caché del servidor de producción (Apache/LiteSpeed). |
| `arrancar-local.bat` | Arranca la copia local en Windows: MariaDB de XAMPP + servidor embebido de PHP. |
| `router-local.php` | Router del servidor embebido de PHP (hace de `.htaccess` en local). |
| `wp-config-local.php` | Ajustes solo para desarrollo local. **No subir al servidor.** |
| [`sitio-estatico/`](sitio-estatico/) | Copia HTML del sitio que se publica en Render. **Generada: no se edita a mano.** |
| [`herramientas/exportar-estatico.py`](herramientas/exportar-estatico.py) | Genera `sitio-estatico/` desde el WordPress local. |
| `render.yaml` | Configuración de Render (sitio estático gratuito). |

## Qué NO está, a propósito

- **`wp-config.php`**: contiene credenciales de producción.
- **El código PHP de WordPress, Divi y los plugins**: se instalan aparte. Divi es un
  tema comercial y no se puede redistribuir. En `sitio-estatico/` solo van el CSS, JS
  e imágenes que cualquier visitante del sitio ya descarga.
- **Cachés, logs y volcados de base de datos.**

## Publicación en Render

El sitio público es la copia estática de `sitio-estatico/`. Render la sirve gratis
desde su CDN y **no se duerme**. El formulario de contacto funciona sin PHP: en la
copia estática se envía a [FormSubmit](https://formsubmit.co), que reenvía cada
mensaje al correo (ver `ov-child/includes/modo-estatico.php`).

Para publicar un cambio:

1. Edita en el WordPress local (`arrancar-local.bat` → http://localhost:8080/wp-admin).
2. `python herramientas/exportar-estatico.py`
3. `git add -A`, `git commit -m "..."` y `git push`. Render publica solo en un minuto.

## Montarlo en local (Windows)

Requisitos: WordPress 6.9, Divi 4.27, XAMPP (MariaDB y PHP 8.0 o superior).

1. Copia WordPress, Divi y este repositorio en la misma carpeta.
2. Crea la base `portafolio_local` en MariaDB (puerto 3307) e importa un volcado del sitio.
3. Ejecuta `arrancar-local.bat` y abre http://localhost:8080.

## Stack

WordPress · Divi · PHP · JavaScript · CSS · MariaDB
