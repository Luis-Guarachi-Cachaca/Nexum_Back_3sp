# Guía de Integración Frontend: Buscador de Profesionales y Exportación de CV

Este documento detalla el funcionamiento de los dos nuevos endpoints implementados en el backend para la exportación del CV (PDF) y el motor de búsqueda pública de profesionales. Sirve como referencia técnica para el equipo de Frontend.

---

## 1. Exportación de CV (PDF)

El sistema ahora permite que un usuario exporte su perfil como un CV. En lugar de generar el PDF en el backend, la arquitectura delega el renderizado visual al Frontend (recomendado usar `@react-pdf/renderer` en React). 

El backend se encarga de entregar los datos exactos del portafolio **respetando los switches de privacidad del usuario**, garantizando que ninguna información configurada como oculta viaje a la vista.

### Endpoint
`GET /api/v1/portfolio/export`

- **Autenticación:** Requerida (`Bearer Token`). Es un endpoint diseñado para que el profesional logueado descargue sus propios datos.
- **Headers:**
  - `Accept: application/json`
  - `Authorization: Bearer <tu_token>`

### Lógica de Privacidad (Importante)
A diferencia de la vista pública de un portafolio (que devuelve `404` si `global_privacy` es `private`), este endpoint **ignora el candado global**. Esto permite que un profesional pueda descargar su propio CV en PDF para enviarlo a empresas, incluso si tiene su perfil público apagado.

Sin embargo, **SÍ respeta los switches individuales**. Si el usuario configuró `show_projects = false`, el array de `projects` no se incluirá en absoluto en el JSON de respuesta.

### Datos Devueltos
El payload tiene la misma estructura que el portafolio público e incluye:
- Datos del usuario (nombre, apellido, **email**).
- Información general (profesión, bio, links sociales, avatar).
- Arrays relacionales cargados solo si el usuario los marcó como visibles:
  - `projects` (con sus tecnologías e imágenes)
  - `skills` (habilidades de su portafolio)
  - `certifications`
  - `work_experiences`

**Recomendación Frontend:** Crea un componente de React que reciba este JSON como `props` y utilice `<Document>`, `<Page>` y `<View>` de `@react-pdf/renderer` para darle diseño al CV.

---

## 2. Buscador de Profesionales

Motor de búsqueda global diseñado tanto para visitantes sin cuenta como para usuarios registrados. Combina búsquedas textuales y filtrado estricto, buscando referencias no solo en el perfil del usuario, sino **dentro de los proyectos que han subido**.

### Endpoint
`GET /api/v1/search/professionals`

- **Autenticación:** Opcional. 
  - Si no se envía Token: Se simula un visitante anónimo.
  - Si se envía Token: Se simula un usuario de la comunidad registrado.

### Lógica de Privacidad y Permisos
- Solo se devuelven usuarios con cuentas activas (`is_active = true` y no suspendidos por admin).
- **Si no hay Token (Visitante):** Solo devuelve perfiles donde `global_privacy` sea `public`.
- **Si hay Token (Registrado):** Devuelve perfiles `public` y también los `private`. (La regla de negocio dicta que los perfiles privados solo son visibles para otros miembros registrados de la plataforma).

### Parámetros de Búsqueda (Query Params)

El endpoint soporta 3 tipos de filtros que pueden combinarse:

| Parámetro | Tipo | Descripción y Comportamiento |
| :--- | :--- | :--- |
| `q` | `string` | **Barra de búsqueda general**. Busca coincidencias parciales (case-insensitive) en: <br> - Nombre y Apellido del profesional. <br> - Profesión principal (Ej: "Full Stack"). <br> - Nombre de Habilidades en su portafolio. <br> - **Nombre de habilidades utilizadas dentro de sus proyectos**. |
| `area` | `string` | **Filtro estricto por área**. Busca coincidencias exactas o parciales exclusivamente en el campo `profession` del portafolio. |
| `skills[]` | `array` | **Filtro estricto por habilidades**. Ideal para menús de checkboxes. Puedes enviar múltiples variables en la URL: `?skills[]=React&skills[]=Node`. |
| `per_page` | `integer` | (Opcional) Número de resultados por página. Default: 15. |

#### Comportamiento Avanzado de `q` (Deep Search)
Si un usuario nunca agregó "React" a su pestaña de Habilidades Principales, pero sí lo seleccionó como tecnología al subir un **Proyecto**, el backend es lo suficientemente inteligente para encontrarlo si alguien busca `?q=react`. 

### Paginación
El endpoint devuelve la respuesta estandarizada paginada de Laravel. 
En el Frontend, los resultados estarán en el array `response.data`, y los datos para tu componente de paginación estarán en `response.meta` (total de páginas, página actual) y `response.links` (URLs prev/next).
