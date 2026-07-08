# Documentación Técnica: Asistente de Solicitudes ISTAE

## Arquitectura del Proyecto
El proyecto ha sido refactorizado pasando de un monolito HTML a una arquitectura estándar cliente-servidor (Frontend + Backend PHP) para garantizar una mejor mantenibilidad y funcionalidades dinámicas.

### 1. Estructura de Archivos
```text
/solicitudes/
 ├── index.html         # Estructura principal y contenedores del DOM.
 ├── css/
 │   └── style.css      # Reglas de estilo (variables, grid, flexbox, ui chat).
 ├── js/
 │   ├── config.js      # Diccionarios de datos editables (carreras, plantillas) y Base64.
 │   └── app.js         # Lógica central: Chatbot, Fetch al backend, jsPDF y docx.
 └── backend/
     ├── api.php        # Script encargado del contador secuencial y correos.
     └── contador.txt   # Archivo de texto plano usado como base de datos del contador.
```

## 2. Dependencias (Frontend)
El cliente requiere dos librerías principales cargadas vía CDN:
- **jsPDF (v2.5.1):** Utilizado para renderizar las coordenadas y generar el PDF localmente en el navegador. `https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js`
- **docx (v8.5.0):** Generador de documentos Word a través de instanciación de objetos. `https://cdnjs.cloudflare.com/ajax/libs/docx/8.5.0/docx.umd.min.js`
  > [!WARNING]
  > Se implementó un parche en `app.js` para reemplazar `border` por `borders` (sintaxis requerida en v8+) solucionando el fallo nativo en la descarga de Word.

## 3. Lógica de Integración (Backend PHP)
El archivo `api.php` expone un endpoint POST que recibe un JSON (`php://input`).
1. **Control de Concurrencia:** Utiliza `flock($fp, LOCK_EX)` sobre el archivo `contador.txt`. Esto garantiza que si dos estudiantes finalizan el trámite en el mismo milisegundo, no obtengan el mismo número de folio.
2. **Fallback Local:** Si el servidor Apache/PHP colapsa, `app.js` detecta el error en el `fetch()` y aplica un algoritmo de respaldo, generando un folio híbrido usando `localStorage` local del estudiante y la fecha actual (con sufijo `-OFF`).
3. **Notificación por Correo:** PHP ejecuta nativamente `mail()`.
   > [!IMPORTANT]
   > Para que los correos sean entregados correctamente, el servidor Moodle (Webmin) debe tener configurado y habilitado un agente de envío de correos (MTA como Postfix o Sendmail), o bien, el administrador debe modificar `api.php` para integrar una librería externa como PHPMailer y un SMTP (ej: Gmail).
