# Asistente Virtual de Solicitudes ISTAE

Plataforma de gestión documental "Cero Papeles" para el Instituto Superior Tecnológico "Alberto Enríquez" (ISTAE). Permite a los estudiantes generar solicitudes académicas formales (PDF/Word) mediante un chatbot interactivo y a Secretaría administrarlas desde un panel web profesional.

## Características Principales

### Para el Estudiante
*   **Chatbot Interactivo:** Interfaz amigable para rellenar datos paso a paso.
*   **Generación Automática:** Crea documentos PDF y Word con el formato oficial del instituto.
*   **Destinatario Dinámico:** Enruta el trámite a la autoridad correspondiente (Rectorado, Secretaría, Coordinación).
*   **Portal de Consulta:** Seguimiento del historial de trámites utilizando únicamente el número de cédula.

### Para Administración / Secretaría
*   **Panel de Control Seguro:** Acceso restringido por roles (`ADMIN` y `SECRETARIA`).
*   **Gestión de Estados y Fechas:** Permite actualizar el estado de las solicitudes (Pendiente, Revisión, Aprobada, Rechazada) y modificar la fecha original de emisión.
*   **Gestión de Usuarios:** Creación, edición (nombres, roles, contraseñas) y eliminación de personal de secretaría.
*   **Eliminación Masiva:** Selección múltiple mediante checkboxes para borrar registros por lotes.
*   **Control Físico:** Opción de digitar el "Número Físico" correlativo tras sellar el documento impreso.
*   **Regeneración "Al Vuelo":** Reimpresión idéntica de PDFs/Word directamente desde la base de datos sin ocupar almacenamiento en disco. El documento oculta inteligentemente el ID secuencial y muestra solo la base del código.
*   **Alto Rendimiento:** Tabla de datos con paginación optimizada para no saturar la memoria del servidor e interfaz mejorada con SweetAlert2.

## Instalación y Configuración (Entorno XAMPP/Moodle)

1.  **Clonar/Copiar el Repositorio:**
    Coloca esta carpeta dentro de tu directorio público de XAMPP (`htdocs/solicitudes`) o en el servidor de Moodle.
2.  **Base de Datos:**
    - Abre `phpMyAdmin`.
    - Crea una base de datos llamada `solicitudes_istae`.
    - Ve a la pestaña **Importar** y selecciona el archivo `backend/setup.sql`. Esto creará las tablas `solicitudes` y `usuarios`, y añadirá los usuarios por defecto.
3.  **Configuración de Credenciales:**
    - Si tu base de datos tiene contraseña (en producción), edita el archivo `backend/db.php` y actualiza los campos `$user` y `$pass`.
4.  **Configuración del Instituto (Frontend):**
    - Edita `js/config.js` para modificar las carreras, nombres de autoridades y plantillas de trámites.

## Credenciales por Defecto
*   **Administrador General:**
    *   Usuario: `admin`
    *   Clave: `istae2026`
*   **Personal de Secretaría:**
    *   Usuario: `secretaria`
    *   Clave: `istae2026`

*(Se recomienda cambiar las contraseñas desde el panel de Gestión de Usuarios).*

## Estructura de Rutas
*   `http://localhost/solicitudes/index.html` → Interfaz principal del chatbot para estudiantes.
*   `http://localhost/solicitudes/consulta.html` → Portal para consultar estado de trámites.
*   `http://localhost/solicitudes/backend/admin.php` → Panel administrativo.

## Tecnologías Utilizadas
*   **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+).
*   **Librerías Frontend:** jsPDF (v2.5.1), docx (v8.5.0), SweetAlert2 (v11).
*   **Backend:** PHP 8+ (PDO, prepared statements, LOCK TABLES).
*   **Base de Datos:** MySQL / MariaDB.
