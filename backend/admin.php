<?php
session_start();
require_once 'db.php';

// Redireccionar si no hay BD configurada
if (!isset($pdo)) {
    die("La base de datos no está configurada.");
}

// ======================== LOGOUT ========================
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// ======================== LOGIN ========================
$error_login = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id, username, password, nombre, rol FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: admin.php");
        exit;
    } else {
        $error_login = "Usuario o contraseña incorrectos.";
    }
}

// Si no está logueado, mostrar pantalla de login
if (!isset($_SESSION['user_id'])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Secretaría ISTAE</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #EDF3F7; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 350px; text-align: center; }
        .login-box h2 { color: #16394F; margin-top: 0; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #1C2523; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 10px; border: 1.5px solid #D4E1EA; border-radius: 8px; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #16394F; }
        .btn { background: #16394F; color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: #0B2333; }
        .error { color: #e74c3c; font-size: 0.9rem; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Panel Administrativo</h2>
        <p style="color:#5B6A66; font-size:0.9rem; margin-bottom:25px;">Ingresa tus credenciales para acceder</p>
        <?php if($error_login): ?><div class="error"><?= $error_login ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="login" value="1">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Ingresar</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// ======================== ACCIONES DEL PANEL ========================

// 1. Actualizar Estado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_estado'])) {
    $id = $_POST['solicitud_id'];
    $estado = $_POST['estado'];
    $pdo->prepare("UPDATE solicitudes SET estado = ? WHERE id = ?")->execute([$estado, $id]);
    header("Location: admin.php" . (!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : ''));
    exit;
}

// 2. Actualizar Número Físico
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_numero'])) {
    $id = $_POST['solicitud_id'];
    $numero_fisico = trim($_POST['numero_fisico']);
    $pdo->prepare("UPDATE solicitudes SET numero_fisico = ? WHERE id = ?")->execute([$numero_fisico, $id]);
    header("Location: admin.php" . (!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : ''));
    exit;
}

// 3. Acciones de Usuarios (Solo ADMIN)
$msg_usuarios = '';
if ($_SESSION['rol'] === 'ADMIN' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear_usuario'])) {
        $u = trim($_POST['new_username']);
        $p = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $n = trim($_POST['new_nombre']);
        $r = $_POST['new_rol'];
        try {
            $pdo->prepare("INSERT INTO usuarios (username, password, nombre, rol) VALUES (?, ?, ?, ?)")->execute([$u, $p, $n, $r]);
            $msg_usuarios = "<div class='success'>Usuario creado exitosamente.</div>";
        } catch(PDOException $e) {
            $msg_usuarios = "<div class='error'>Error al crear usuario (el username ya existe).</div>";
        }
    }
    if (isset($_POST['eliminar_usuario'])) {
        $uid = $_POST['user_id'];
        if ($uid != $_SESSION['user_id']) { // Prevenir auto-eliminación
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$uid]);
            $msg_usuarios = "<div class='success'>Usuario eliminado.</div>";
        }
    }
}

// ======================== OBTENER DATOS (PAGINACIÓN Y FILTROS) ========================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = "1=1";
$params = [];

if ($search !== '') {
    $where .= " AND (cedula LIKE ? OR nombre LIKE ? OR codigo LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Total de registros
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM solicitudes WHERE $where");
$stmt_count->execute($params);
$total_rows = $stmt_count->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Obtener registros paginados
$sql = "SELECT * FROM solicitudes WHERE $where ORDER BY fecha DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Exportar los datos a JS para la regeneración de documentos
$solicitudes_json = json_encode($solicitudes);

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'solicitudes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo - ISTAE</title>
    <!-- Incluir scripts necesarios para generar PDF y Word localmente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx@8.5.0/build/index.min.js"></script>
    <script src="../js/config.js"></script>
    
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #1e293b; }
        .header { background: #16394F; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .header-info { font-size: 0.9rem; }
        .header-info a { color: #38bdf8; text-decoration: none; margin-left: 15px; font-weight: bold; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .tabs { border-bottom: 2px solid #cbd5e1; margin-bottom: 20px; display: flex; }
        .tabs a { padding: 10px 20px; text-decoration: none; color: #64748b; font-weight: bold; border-bottom: 3px solid transparent; margin-bottom: -2px; }
        .tabs a.active { color: #16394F; border-bottom-color: #16394F; }
        
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .search-box input { padding: 8px 12px; width: 300px; border: 1px solid #cbd5e1; border-radius: 4px; }
        .search-box button { padding: 8px 15px; background: #16394F; color: white; border: none; border-radius: 4px; cursor: pointer; }
        
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        th { background: #f1f5f9; font-weight: bold; color: #475569; }
        tr:hover { background: #f8fafc; }
        
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: white; }
        .bg-pendiente { background: #f59e0b; }
        .bg-revision { background: #3b82f6; }
        .bg-aprobada { background: #10b981; }
        .bg-rechazada { background: #ef4444; }
        
        .actions form { display: inline-block; margin: 0; }
        .actions select, .actions input[type=text] { padding: 4px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; }
        .actions button { padding: 4px 8px; background: #e2e8f0; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
        .actions button:hover { background: #cbd5e1; }
        
        .btn-gen { background: #10b981 !important; color: white; border-color: #059669 !important; }
        .btn-gen-w { background: #3b82f6 !important; color: white; border-color: #2563eb !important; }
        
        .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 5px; }
        .pagination a { padding: 8px 12px; background: white; border: 1px solid #cbd5e1; text-decoration: none; color: #16394F; border-radius: 4px; }
        .pagination a.active { background: #16394F; color: white; border-color: #16394F; }
        
        .form-panel { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); max-width: 600px; margin-bottom: 20px; }
        .form-panel .form-group { margin-bottom: 15px; }
        .form-panel label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-panel input, .form-panel select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .form-panel button { padding: 10px 15px; background: #16394F; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .success { color: #10b981; font-weight: bold; margin-bottom: 15px; }
        .error { color: #ef4444; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Panel Administrativo ISTAE</h1>
        <div class="header-info">
            Usuario: <b><?= htmlspecialchars($_SESSION['nombre']) ?></b> (<?= $_SESSION['rol'] ?>)
            <a href="?action=logout">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <div class="tabs">
            <a href="?tab=solicitudes" class="<?= $tab == 'solicitudes' ? 'active' : '' ?>">Gestión de Solicitudes</a>
            <?php if($_SESSION['rol'] === 'ADMIN'): ?>
            <a href="?tab=usuarios" class="<?= $tab == 'usuarios' ? 'active' : '' ?>">Gestión de Usuarios</a>
            <?php endif; ?>
        </div>
        
        <?php if($tab == 'solicitudes'): ?>
        
        <div class="toolbar">
            <div class="search-box">
                <form method="GET">
                    <input type="text" name="search" placeholder="Buscar por código, nombre o cédula..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Buscar</button>
                    <?php if($search): ?><a href="admin.php" style="margin-left:10px; font-size:0.9rem;">Limpiar</a><?php endif; ?>
                </form>
            </div>
            <div>
                Total de registros: <b><?= $total_rows ?></b>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Código/Cédula</th>
                    <th>Estudiante</th>
                    <th>Trámite / Unidad</th>
                    <th>Estado</th>
                    <th>Nº Físico</th>
                    <th>Documento</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($solicitudes) == 0): ?>
                <tr><td colspan="7" style="text-align:center;">No se encontraron solicitudes.</td></tr>
                <?php endif; ?>
                <?php foreach($solicitudes as $row): 
                    $badge_class = 'bg-pendiente';
                    if($row['estado'] == 'EN REVISION') $badge_class = 'bg-revision';
                    if($row['estado'] == 'APROBADA') $badge_class = 'bg-aprobada';
                    if($row['estado'] == 'RECHAZADA') $badge_class = 'bg-rechazada';
                ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
                    <td>
                        <b><?= $row['codigo'] ?></b><br>
                        <?= $row['cedula'] ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($row['nombre']) ?><br>
                        <small style="color:#64748b;"><?= $row['carrera'] ?> - <?= $row['jornada'] ?></small>
                    </td>
                    <td>
                        <?= $row['tramite'] ?><br>
                        <small style="color:#64748b;"><?= $row['destinatario'] ?: 'No especificado' ?> <?= $row['unidadOtra'] ? "({$row['unidadOtra']})" : "" ?></small>
                    </td>
                    <td class="actions">
                        <form method="POST">
                            <input type="hidden" name="update_estado" value="1">
                            <input type="hidden" name="solicitud_id" value="<?= $row['id'] ?>">
                            <select name="estado" onchange="this.form.submit()">
                                <option value="PENDIENTE" <?= $row['estado']=='PENDIENTE'?'selected':'' ?>>PENDIENTE</option>
                                <option value="EN REVISION" <?= $row['estado']=='EN REVISION'?'selected':'' ?>>EN REVISIÓN</option>
                                <option value="APROBADA" <?= $row['estado']=='APROBADA'?'selected':'' ?>>APROBADA</option>
                                <option value="RECHAZADA" <?= $row['estado']=='RECHAZADA'?'selected':'' ?>>RECHAZADA</option>
                            </select>
                        </form>
                    </td>
                    <td class="actions">
                        <form method="POST" style="display:flex; gap:2px;">
                            <input type="hidden" name="update_numero" value="1">
                            <input type="hidden" name="solicitud_id" value="<?= $row['id'] ?>">
                            <input type="text" name="numero_fisico" value="<?= htmlspecialchars($row['numero_fisico']) ?>" placeholder="Nº..." style="width:50px;">
                            <button type="submit">Guardar</button>
                        </form>
                    </td>
                    <td class="actions">
                        <button class="btn-gen" onclick="generarDocumentoAdmin(<?= $row['id'] ?>, 'pdf')" title="Descargar PDF">PDF</button>
                        <button class="btn-gen-w" onclick="generarDocumentoAdmin(<?= $row['id'] ?>, 'word')" title="Descargar Word">DOC</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? "&search=".urlencode($search) : "" ?>" class="<?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        
        <?php elseif($tab == 'usuarios' && $_SESSION['rol'] === 'ADMIN'): ?>
        
        <?= $msg_usuarios ?>
        
        <div style="display:flex; gap: 30px;">
            <div class="form-panel" style="flex: 1;">
                <h3>Crear Nuevo Usuario</h3>
                <form method="POST">
                    <input type="hidden" name="crear_usuario" value="1">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="new_nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Nombre de Usuario</label>
                        <input type="text" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Rol</label>
                        <select name="new_rol">
                            <option value="SECRETARIA">SECRETARIA</option>
                            <option value="ADMIN">ADMINISTRADOR</option>
                        </select>
                    </div>
                    <button type="submit">Crear Usuario</button>
                </form>
            </div>
            
            <div style="flex: 2;">
                <h3>Usuarios Actuales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Username</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nombre");
                        while($u = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nombre']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><span class="badge <?= $u['rol']=='ADMIN'?'bg-aprobada':'bg-revision' ?>"><?= $u['rol'] ?></span></td>
                            <td class="actions">
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('¿Eliminar usuario?');">
                                    <input type="hidden" name="eliminar_usuario" value="1">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" style="background:#ef4444; color:white; border-color:#dc2626;">Eliminar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

<script>
// ==================== LÓGICA DE GENERACIÓN DE DOCUMENTOS EN EL PANEL ====================
// Inyectamos los datos de PHP a JS
const solicitudes = <?= $solicitudes_json ?: '[]' ?>;

function getSolicitudDatos(id) {
    const row = solicitudes.find(s => parseInt(s.id) === id);
    if (!row) return null;
    return {
        nombre: row.nombre,
        cedula: row.cedula,
        carrera: row.carrera,
        nivel: row.nivel,
        jornada: row.jornada,
        destinatario: row.destinatario,
        unidadOtra: row.unidadOtra,
        tramite: row.tramite,
        detalle: row.detalle,
        contacto: row.contacto,
        codigo: row.codigo,
        numero_fisico: row.numero_fisico
    };
}

function destinatarioAdmin(datos){
  const base = CONFIG.destinatarios[datos.destinatario] || CONFIG.destinatarios['Rector'];
  const d = {tratamiento: base.tratamiento, nombre: base.nombre, cargo: base.cargo};
  if(datos.destinatario === 'Coordinación de Carrera'){
    d.nombre = CONFIG.coordinadores[datos.carrera] || '';
    d.cargo = 'COORDINADOR/A DE LA CARRERA DE ' + (datos.carrera||'________________').toUpperCase();
    if(!d.nombre) d.tratamiento = '';
  }
  if(datos.destinatario === 'Otra unidad'){
    d.cargo = (datos.unidadOtra || 'UNIDAD DEL ISTAE').toUpperCase();
  }
  return d;
}

function cuerpoAdmin(datos){
  const plantilla = (CONFIG.tramites[datos.tramite] && CONFIG.tramites[datos.tramite].texto) || (d=>d.detalle);
  const de = destinatarioAdmin(datos);
  const trato = de.nombre ? 'me dirijo a usted' : 'me dirijo a ustedes';
  return `Yo, ${datos.nombre.toUpperCase()}, con cédula de ciudadanía N.º ${datos.cedula}, estudiante legalmente matriculado/a en la carrera de ${datos.carrera}, ${datos.nivel.toLowerCase()}, jornada ${datos.jornada.toLowerCase()}, período académico ${CONFIG.periodo}, ${trato} de la manera más respetuosa para solicitar ${plantilla(datos)}`;
}

function fechaLargaAdmin(){
  const meses=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  const h=new Date();
  return `${CONFIG.ciudad}, ${h.getDate()} de ${meses[h.getMonth()]} de ${h.getFullYear()}`;
}

const CIERRE_ADMIN = 'Por la favorable atención que se digne dar a la presente, le anticipo mis más sinceros agradecimientos.';

// Generador PDF
function generarPDF(datos) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({unit:'mm', format:'a4'});
  const ancho = 210, margen = 25, util = ancho - margen*2;
  let y = 15;
  const logoW = 80, logoH = logoW * LOGO_RATIO;
  doc.addImage('data:image/png;base64,'+LOGO_B64, 'PNG', (ancho-logoW)/2, y, logoW, logoH);
  y += logoH + 4;
  doc.setFont('times','normal'); doc.setFontSize(10);
  doc.text(CONFIG.subtitulo, ancho/2, y, {align:'center'}); y+=4;
  doc.setDrawColor(22,57,79); doc.setLineWidth(.8);
  doc.line(margen, y, ancho-margen, y); y+=12;

  doc.setFontSize(12);
  doc.setFont('times','bold');
  const nf = datos.numero_fisico ? datos.numero_fisico : '____________';
  doc.text('Solicitud ' + (datos.codigo||'') + ' — N.º ' + nf, ancho-margen, y, {align:'right'}); y+=7;
  doc.setFont('times','normal');
  doc.text(fechaLargaAdmin(), ancho-margen, y, {align:'right'}); y+=12;

  const de = destinatarioAdmin(datos);
  if(de.nombre){
    if(de.tratamiento){ doc.text(de.tratamiento, margen, y); y+=6; }
    doc.setFont('times','bold'); doc.text(de.nombre, margen, y); y+=6;
    doc.setFont('times','normal');
    doc.splitTextToSize(de.cargo, util).forEach(l=>{doc.text(l,margen,y);y+=6;});
  } else {
    doc.text('Señores', margen, y); y+=6;
    doc.setFont('times','bold');
    doc.splitTextToSize(de.cargo, util).forEach(l=>{doc.text(l,margen,y);y+=6;});
    doc.setFont('times','normal');
  }
  doc.text('Presente.-', margen, y); y+=12;

  doc.setFont('times','bold'); doc.text('Asunto: ', margen, y);
  doc.setFont('times','normal'); doc.text(datos.tramite, margen+18, y); y+=12;

  doc.text('De mi consideración:', margen, y); y+=10;

  doc.splitTextToSize(cuerpoAdmin(datos), util).forEach(l=>{
    if(y>270){doc.addPage(); y=25;}
    doc.text(l, margen, y, {maxWidth:util, align:'justify'}); y+=6.5;
  });
  y+=4;
  doc.splitTextToSize(CIERRE_ADMIN, util).forEach(l=>{
    if(y>270){doc.addPage(); y=25;}
    doc.text(l, margen, y); y+=6.5;
  });
  y+=10;
  if(y>230){doc.addPage(); y=40;}
  doc.text('Atentamente,', margen, y); y+=28;
  doc.line(ancho/2-40, y, ancho/2+40, y); y+=6;
  doc.setFont('times','bold');
  doc.text(datos.nombre.toUpperCase(), ancho/2, y, {align:'center'}); y+=6;
  doc.setFont('times','normal');
  doc.text('C.C.: '+datos.cedula, ancho/2, y, {align:'center'}); y+=6;
  doc.text('Contacto: '+datos.contacto, ancho/2, y, {align:'center'});

  doc.save(`${datos.codigo}_${datos.cedula}.pdf`);
}

// Generador Word
function generarWord(datos){
  const D = window.docx;
  const P = (texto, opts={}) => new D.Paragraph({
    alignment: opts.align || D.AlignmentType.JUSTIFIED,
    spacing: { after: opts.despues !== undefined ? opts.despues : 200 },
    children: [ new D.TextRun({ text:texto, font:'Times New Roman', size:24, bold:!!opts.bold }) ]
  });

  const nf = datos.numero_fisico ? datos.numero_fisico : '____________';
  const de = destinatarioAdmin(datos);

  const doc = new D.Document({
    sections: [{
      properties: { page: { margin: { top: 1440, bottom: 1440, left: 1700, right: 1700 } } },
      children: [
        new D.Paragraph({
          alignment: D.AlignmentType.CENTER,
          spacing: { after: 60 },
          children: [
            new D.ImageRun({
              data: logoBytes(),
              transformation: { width: 300, height: Math.round(300 * LOGO_RATIO) }
            })
          ]
        }),
        new D.Paragraph({
          alignment: D.AlignmentType.CENTER,
          spacing: { after: 120 },
          children: [ new D.TextRun({ text: CONFIG.subtitulo, font: 'Times New Roman', size: 20 }) ],
          borders: { bottom: { style: D.BorderStyle.SINGLE, size: 12, color: '16394F', space: 6 } }
        }),
        new D.Paragraph({
          alignment: D.AlignmentType.RIGHT,
          spacing: { after: 80 },
          children: [ new D.TextRun({ text: 'Solicitud ' + (datos.codigo||'') + ' — N.º ' + nf, font: 'Times New Roman', size: 24, bold: true }) ]
        }),
        P(fechaLargaAdmin(), { align: D.AlignmentType.RIGHT, despues: 300 }),
        ...(()=>{ 
          return de.nombre ? [
            ...(de.tratamiento? [P(de.tratamiento,{align:D.AlignmentType.LEFT,despues:0})]:[]),
            P(de.nombre,{align:D.AlignmentType.LEFT,bold:true,despues:0}),
            P(de.cargo,{align:D.AlignmentType.LEFT,despues:0})
          ] : [
            P('Señores',{align:D.AlignmentType.LEFT,despues:0}),
            P(de.cargo,{align:D.AlignmentType.LEFT,bold:true,despues:0})
          ]; })(),
        P('Presente.-', { align: D.AlignmentType.LEFT, despues: 300 }),
        new D.Paragraph({
          spacing: { after: 300 },
          children: [
            new D.TextRun({ text: 'Asunto: ', font: 'Times New Roman', size: 24, bold: true }),
            new D.TextRun({ text: datos.tramite, font: 'Times New Roman', size: 24 })
          ]
        }),
        P('De mi consideración:', { align: D.AlignmentType.LEFT }),
        P(cuerpoAdmin(datos)),
        P(CIERRE_ADMIN, { despues: 600 }),
        P('Atentamente,', { align: D.AlignmentType.LEFT, despues: 900 }),
        P('_______________________________', { align: D.AlignmentType.CENTER, despues: 0 }),
        P(datos.nombre.toUpperCase(), { align: D.AlignmentType.CENTER, bold: true, despues: 0 }),
        P('C.C.: ' + datos.cedula, { align: D.AlignmentType.CENTER, despues: 0 }),
        P('Contacto: ' + datos.contacto, { align: D.AlignmentType.CENTER })
      ]
    }]
  });

  D.Packer.toBlob(doc).then(blob => {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${datos.codigo}_${datos.cedula}.docx`;
    a.click(); 
    URL.revokeObjectURL(a.href);
  });
}

function generarDocumentoAdmin(id, tipo) {
    const d = getSolicitudDatos(id);
    if(!d) { alert('Error: No se encontraron los datos de la solicitud.'); return; }
    if(tipo === 'pdf') generarPDF(d);
    else generarWord(d);
}
</script>

</body>
</html>
