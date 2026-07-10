# Documentación Técnica: Asistente de Solicitudes ISTAE

## Arquitectura del Proyecto
El proyecto ha sido refactorizado pasando de un monolito HTML a una arquitectura estándar cliente-servidor (Frontend + Backend PHP) interactuando con una base de datos relacional MySQL/MariaDB para garantizar una mejor mantenibilidad, seguridad y funcionalidades dinámicas.

### 1. Estructura de Archivos
```text
/solicitudes/
 ├── index.html         # Estructura principal y contenedores del DOM (Chatbot).
 ├── consulta.html      # Portal de seguimiento por cédula.
 ├── css/
 │   └── style.css      # Reglas de estilo (variables, grid, flexbox, ui chat).
 ├── js/
 │   ├── config.js      # Diccionarios de datos editables (carreras, plantillas) y Base64.
 │   └── app.js         # Lógica central: Chatbot, Fetch al backend, jsPDF y docx.
 └── backend/
     ├── db.php         # Archivo de conexión central a la base de datos (PDO).
     ├── api.php        # Script encargado de recibir y guardar las solicitudes en BD.
     ├── admin.php      # Panel administrativo, gestión de usuarios, login y regeneración.
     └── setup.sql      # Script de inicialización de tablas (solicitudes y usuarios).
```

## 2. Dependencias (Frontend)
El cliente requiere tres librerías principales cargadas vía CDN:
- **jsPDF (v2.5.1):** Utilizado para renderizar las coordenadas y generar el PDF localmente en el navegador. `https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js`
- **docx (v8.5.0):** Generador de documentos Word a través de instanciación de objetos. `https://cdnjs.cloudflare.com/ajax/libs/docx/8.5.0/docx.umd.min.js`
- **SweetAlert2 (v11):** Usada en el panel administrativo para alertas dinámicas, modales de confirmación e interacciones (como la edición de fechas). `https://cdn.jsdelivr.net/npm/sweetalert2@11`

> [!WARNING]
> Se implementó un parche en `app.js` para reemplazar `border` por `borders` (sintaxis requerida en v8+) solucionando el fallo nativo en la descarga de Word.

## 3. Lógica de Integración (Backend PHP)
El ecosistema backend maneja múltiples flujos:

1. **Gestión de Solicitudes (`api.php`):** 
   - Expone un endpoint POST que recibe un JSON. Inserta los datos directamente en la tabla `solicitudes` usando *prepared statements*.
   - **Fallback Local:** Si el servidor Apache/PHP colapsa, `app.js` detecta el error y aplica un algoritmo de respaldo, generando un folio híbrido usando `localStorage` local del estudiante.

2. **Panel Administrativo (`admin.php`):**
   - **Autenticación:** Utiliza `password_hash` y `password_verify` de PHP. Acceso bloqueado mediante variables de `$_SESSION`.
   - **Operaciones de BD (CRUD):** 
     - **Regeneración:** Extrae el JSON de la base de datos hacia JavaScript para dibujar los PDF/Word exactamente como se crearon originalmente (utilizando la fecha original de la solicitud y ocultando el ID numérico secuencial en el documento).
     - **Eliminación Masiva:** Utiliza una consulta optimizada `WHERE id IN (?,?,?)` generada dinámicamente según los *checkboxes* seleccionados en la tabla.
     - **Edición de Fecha:** A través de SweetAlert2, se captura la nueva fecha y se formatea con `datetime-local` para sobreescribir el atributo `fecha` en MySQL, garantizando que el nuevo documento mantenga precisión cronológica.
     - **Gestión de Usuarios:** Creación, edición y eliminación (solo para rol `ADMIN`). La edición permite cambiar nombres y roles, actualizando la contraseña solo si el campo no se deja en blanco.
