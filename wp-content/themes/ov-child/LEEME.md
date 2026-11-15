# Tema hijo OV Child — portafolio.saludgo.org

Tema hijo de **Divi**. Aquí va todo lo propio del sitio: si se toca Divi
directamente, la próxima actualización se lo lleva por delante.

## Qué hay dentro

| Archivo | Para qué |
|---|---|
| `functions.php` | Carga los módulos y aplica la limpieza de cabeceras |
| `style.css` | Cabecera del tema hijo + estilos del CV y de la ficha de proyecto |
| `includes/cv-post-types.php` | Tipos de contenido **Experiencia** y **Estudios** |
| `includes/cv-metaboxes.php` | Campos de esas fichas (entidad, cargo, fechas, soporte) |
| `includes/cv-shortcodes.php` | `[cv_experiencia]`, `[cv_estudios]`, `[cv_lista]` |
| `includes/proyecto-meta.php` | Ficha técnica de los proyectos + `[proyecto_ficha]` |
| `includes/cv-importar.php` | Herramientas → Importar CV (pasa lo de Divi a fichas) |
| `includes/social_icons.php` | Sustituye el de Divi: quita Google+, añade LinkedIn/GitHub/WhatsApp |

## Cómo añadir experiencia o estudios

1. **Escritorio → Experiencia → Añadir puesto** (o **Estudios → Añadir estudio**).
2. El **título** de la ficha es solo para el listado interno. Lo que se ve en la
   web sale de los campos **Empresa/Institución** y **Cargo/Título obtenido**.
3. **Desde** y **Hasta** son selectores de mes. Si sigues en el puesto, deja
   *Hasta* vacío y marca la casilla: el periodo dirá «Actualidad».
4. La descripción va en el editor grande de arriba.
5. Se ordenan solas, de la más reciente a la más antigua. Las que no tienen
   fecha van al final; para colocarlas a mano, usa el campo *Orden* de
   **Atributos de página**.

No hace falta tocar el maquetador para añadir una entrada nueva.

## Cómo se pintan en la página «Acerca»

Dentro de un módulo de texto de Divi:

```
[cv_experiencia]
[cv_estudios]
```

Atributos opcionales:

```
[cv_lista tipo="experiencia" columnas="2" limite="3"]
```

- `tipo`: `experiencia` (por defecto) o `estudios`
- `columnas`: `1`, `2` o `3` — en móvil siempre baja a 1
- `limite`: número de fichas; `-1` (por defecto) las muestra todas

## Cómo añadir un proyecto

1. **Escritorio → Proyectos → Añadir nuevo** (es el tipo de contenido de Divi,
   el mismo que alimenta el portafolio filtrable de la página Portafolio).
2. En la columna derecha aparece **Ficha técnica del proyecto**: cliente, año,
   rol, tecnologías (separadas por comas), enlace en vivo y repositorio.
3. En el contenido, donde quieras que salga esa tabla, pon `[proyecto_ficha]`.
4. Asigna una **categoría de proyecto** para que se pueda filtrar en la página
   Portafolio, y una **imagen destacada** para la miniatura.

## Migración inicial

La experiencia y los estudios estaban escritos a mano dentro del maquetador de
Divi, duplicando módulos. **Herramientas → Importar CV** los convierte en fichas
de una vez (con las erratas corregidas). Se puede pulsar varias veces: comprueba
por título y no duplica. Después hay que sustituir esos módulos de texto sueltos
de la página «Acerca» por los dos shortcodes.

## Iconos sociales

Las URL de LinkedIn, GitHub y WhatsApp se editan arriba del todo en
`includes/social_icons.php`. La que se deje vacía no se pinta.
Facebook, X, Instagram y RSS siguen saliendo de **Divi → Opciones del tema →
Integración/Redes**.
