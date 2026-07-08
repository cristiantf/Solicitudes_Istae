// Renderizar logo en la cabecera
document.getElementById('logo-img').src = 'data:image/png;base64,' + LOGO_B64;

const datos = { nombre:'', cedula:'', carrera:'', nivel:'', jornada:'', tramite:'', detalle:'', contacto:'', codigo:'' };

/* ============ ASIGNACIÓN DE CÓDIGO (CONECTADO A PHP) ============ */
async function asignarCodigo(){
  const sigla = CONFIG.siglas[datos.carrera] || 'GEN';
  
  try {
    const res = await fetch('backend/api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...datos, carrera_sigla: sigla })
    });
    
    if(!res.ok) throw new Error("Network response was not ok");
    
    const json = await res.json();
    datos.codigo = json.codigo;
    
  } catch(e) {
    console.warn("No se pudo conectar al servidor PHP. Usando fallback local.", e);
    // 1) Fallback local temporal si falla la conexión a PHP
    let secuencial = null;
    try {
      const k = 'istae_contador_solicitudes_offline';
      const n = (parseInt(localStorage.getItem(k), 10) || 0) + 1;
      localStorage.setItem(k, String(n));
      secuencial = n;
    } catch(e) {}

    const h = new Date();
    const sufijo = secuencial !== null
      ? String(secuencial).padStart(4,'0')
      : `${String(h.getMonth()+1).padStart(2,'0')}${String(h.getDate()).padStart(2,'0')}-${String(h.getHours()).padStart(2,'0')}${String(h.getMinutes()).padStart(2,'0')}`;
    
    datos.codigo = `SOL-${sigla}-${h.getFullYear()}-OFF${sufijo}`;
  }
  return datos.codigo;
}

const pasos = [
  { clave:'nombre',  pregunta:'¡Hola! 👋 Soy la secretaría virtual del ISTAE. Te ayudaré a generar tu solicitud lista para imprimir y firmar. Para empezar, escribe tus nombres y apellidos completos.', tipo:'texto', valida:v=>v.trim().split(' ').length>=2 || 'Escribe al menos nombre y apellido.' },
  { clave:'cedula',  pregunta:'Gracias. Ahora escribe tu número de cédula (10 dígitos).', tipo:'texto', valida:v=>/^\d{10}$/.test(v.trim()) || 'La cédula debe tener 10 dígitos, sin puntos ni guiones.' },
  { clave:'carrera', pregunta:'¿En qué carrera estás matriculado/a?', tipo:'opciones', opciones:()=>CONFIG.carreras },
  { clave:'nivel',   pregunta:'¿En qué nivel te encuentras?', tipo:'opciones', opciones:()=>CONFIG.niveles },
  { clave:'jornada', pregunta:'¿Cuál es tu jornada?', tipo:'opciones', opciones:()=>CONFIG.jornadas },
  { clave:'tramite', pregunta:'¿Qué trámite deseas solicitar?', tipo:'opciones', opciones:()=>Object.keys(CONFIG.tramites) },
  { clave:'detalle', pregunta:'Cuéntame el detalle o motivo de tu solicitud (por ejemplo: fechas, asignaturas, razón del trámite). Sé breve y claro.', tipo:'texto', valida:v=>v.trim().length>=10 || 'Dame un poco más de detalle (mínimo 10 caracteres).' },
  { clave:'contacto',pregunta:'Por último, escribe un correo o teléfono de contacto.', tipo:'texto', valida:v=>v.trim().length>=7 || 'Escribe un correo o número válido.' }
];

let paso = 0;
const hilo = document.getElementById('hilo');
const zona = document.getElementById('zonaEntrada');
const campo = document.getElementById('campoTexto');
const btnEnviar = document.getElementById('btnEnviar');

function scrollFin(){ hilo.scrollTop = hilo.scrollHeight; }

function msgBot(texto){
  const d=document.createElement('div'); d.className='msg bot'; d.textContent=texto;
  hilo.appendChild(d); scrollFin();
}
function msgUser(texto){
  const d=document.createElement('div'); d.className='msg user'; d.textContent=texto;
  hilo.appendChild(d); scrollFin();
}

function mostrarPaso(){
  if(paso >= pasos.length){ return resumenFinal(); }
  const p = pasos[paso];
  setTimeout(()=>{
    msgBot(p.pregunta);
    if(p.tipo==='texto'){
      zona.style.display='flex'; campo.value=''; campo.focus();
    }else{
      zona.style.display='none';
      const cont=document.createElement('div'); cont.className='opciones';
      p.opciones().forEach(op=>{
        const b=document.createElement('button'); b.textContent=op;
        b.onclick=()=>{ cont.remove(); responder(op); };
        cont.appendChild(b);
      });
      hilo.appendChild(cont); scrollFin();
    }
  }, 300);
}

function responder(valor){
  const p = pasos[paso];
  if(p.tipo==='texto' && p.valida){
    const ok = p.valida(valor);
    if(ok !== true){ msgBot('⚠️ ' + ok); return; }
  }
  msgUser(valor);
  datos[p.clave] = valor.trim();
  zona.style.display='none';
  paso++;
  pintarPrevia();
  mostrarPaso();
}

btnEnviar.onclick = ()=>{ if(campo.value.trim()) responder(campo.value); };
campo.addEventListener('keydown', e=>{ if(e.key==='Enter' && campo.value.trim()) responder(campo.value); });

function resumenFinal(){
  setTimeout(async ()=>{
    // Ocultar campo de texto temporalmente y mostrar cargando
    zona.style.display='none';
    msgBot('⌛ Generando número de solicitud y notificando a secretaría...');
    
    await asignarCodigo();
    pintarPrevia();
    
    msgBot('¡Listo, ' + datos.nombre.split(' ')[0] + '! Tu solicitud quedó registrada con el código ' + datos.codigo + '. Revisa la vista previa y descárgala en el formato que prefieras. Recuerda imprimirla, firmarla y entregarla en Secretaría.');
    
    const cont=document.createElement('div'); cont.className='descargas';
    
    const b1=document.createElement('button'); b1.className='btn-pdf'; b1.textContent='⬇ Descargar PDF';
    b1.onclick=generarPDF;
    
    const b2=document.createElement('button'); b2.className='btn-word'; b2.textContent='⬇ Descargar Word';
    b2.onclick=generarWord;
    
    const b3=document.createElement('button'); b3.className='btn-otra'; b3.textContent='↺ Nueva solicitud';
    b3.onclick=()=>location.reload();
    
    cont.append(b1,b2,b3); hilo.appendChild(cont); scrollFin();
  },300);
}

/* ================== TEXTO DEL DOCUMENTO ================== */
function fechaLarga(){
  const meses=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  const h=new Date();
  return `${CONFIG.ciudad}, ${h.getDate()} de ${meses[h.getMonth()]} de ${h.getFullYear()}`;
}

function cuerpoSolicitud(){
  const plantilla = CONFIG.tramites[datos.tramite] || (d=>d.detalle);
  return `Yo, ${datos.nombre.toUpperCase()}, con cédula de ciudadanía N.º ${datos.cedula}, estudiante legalmente matriculado/a en la carrera de ${datos.carrera}, ${datos.nivel.toLowerCase()}, jornada ${datos.jornada.toLowerCase()}, período académico ${CONFIG.periodo}, me dirijo a usted de la manera más respetuosa para solicitar ${plantilla(datos)}`;
}
const CIERRE = 'Por la favorable atención que se digne dar a la presente, le anticipo mis más sinceros agradecimientos.';

/* ================== VISTA PREVIA ================== */
function pintarPrevia(){
  const hoja=document.getElementById('hoja');
  const cuerpo = datos.tramite
    ? cuerpoSolicitud()
    : `Yo, ${datos.nombre? datos.nombre.toUpperCase():'________________________'}, con cédula de ciudadanía N.º ${datos.cedula||'__________'}, estudiante de la carrera de ${datos.carrera||'____________________'}, ${datos.nivel? datos.nivel.toLowerCase():'______ nivel'}, jornada ${datos.jornada? datos.jornada.toLowerCase():'__________'}, me dirijo a usted respetuosamente para solicitar…`;
  hoja.innerHTML = `
    <div class="memb"><img src="data:image/png;base64,${LOGO_B64}" alt="ISTAE" style="max-width:300px;width:80%;display:block;margin:0 auto 4px"><small>${CONFIG.subtitulo}</small></div>
    <hr>
    ${datos.codigo ? `<p style="text-align:right"><b>Solicitud N.º ${datos.codigo}</b></p>` : ''}
    <p style="text-align:right">${fechaLarga()}</p><br>
    <p>${CONFIG.autoridad.tratamiento}<br><b>${CONFIG.autoridad.nombre}</b><br>${CONFIG.autoridad.cargo}<br>Presente.-</p><br>
    <p><b>Asunto:</b> ${datos.tramite || '<span class="campo">Tipo de trámite</span>'}</p><br>
    <p>De mi consideración:</p><br>
    <p style="text-align:justify">${cuerpo}</p><br>
    <p style="text-align:justify">${CIERRE}</p>
    <div class="firma">
      <p>Atentamente,</p><br><br>
      <span class="raya"><b>${datos.nombre ? datos.nombre.toUpperCase() : 'Firma del/de la estudiante'}</b><br>
      C.C.: ${datos.cedula||'__________'}<br>
      Contacto: ${datos.contacto||'__________'}</span>
    </div>`;
}

/* ================== PDF ================== */
function generarPDF(){
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
  doc.text('Solicitud N.º ' + (datos.codigo||'PENDIENTE'), ancho-margen, y, {align:'right'}); y+=7;
  doc.setFont('times','normal');
  doc.text(fechaLarga(), ancho-margen, y, {align:'right'}); y+=12;

  doc.text(CONFIG.autoridad.tratamiento, margen, y); y+=6;
  doc.setFont('times','bold'); doc.text(CONFIG.autoridad.nombre, margen, y); y+=6;
  doc.setFont('times','normal');
  doc.splitTextToSize(CONFIG.autoridad.cargo, util).forEach(l=>{doc.text(l,margen,y);y+=6;});
  doc.text('Presente.-', margen, y); y+=12;

  doc.setFont('times','bold'); doc.text('Asunto: ', margen, y);
  doc.setFont('times','normal'); doc.text(datos.tramite, margen+18, y); y+=12;

  doc.text('De mi consideración:', margen, y); y+=10;

  doc.splitTextToSize(cuerpoSolicitud(), util).forEach(l=>{
    if(y>270){doc.addPage(); y=25;}
    doc.text(l, margen, y, {maxWidth:util, align:'justify'}); y+=6.5;
  });
  y+=4;
  doc.splitTextToSize(CIERRE, util).forEach(l=>{
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

/* ================== WORD ================== */
function generarWord(){
  const D = window.docx;
  
  // Helpers para docx
  const P = (texto, opts={}) => new D.Paragraph({
    alignment: opts.align || D.AlignmentType.JUSTIFIED,
    spacing: { after: opts.despues !== undefined ? opts.despues : 200 },
    children: [ new D.TextRun({ text:texto, font:'Times New Roman', size:24, bold:!!opts.bold }) ]
  });

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
          // CORRECCIÓN: 'borders' en lugar de 'border' para docx >= 8.0
          borders: { bottom: { style: D.BorderStyle.SINGLE, size: 12, color: '16394F', space: 6 } }
        }),
        new D.Paragraph({
          alignment: D.AlignmentType.RIGHT,
          spacing: { after: 80 },
          children: [ new D.TextRun({ text: 'Solicitud N.º ' + (datos.codigo||'PENDIENTE'), font: 'Times New Roman', size: 24, bold: true }) ]
        }),
        P(fechaLarga(), { align: D.AlignmentType.RIGHT, despues: 300 }),
        P(CONFIG.autoridad.tratamiento, { align: D.AlignmentType.LEFT, despues: 0 }),
        P(CONFIG.autoridad.nombre, { align: D.AlignmentType.LEFT, bold: true, despues: 0 }),
        P(CONFIG.autoridad.cargo, { align: D.AlignmentType.LEFT, despues: 0 }),
        P('Presente.-', { align: D.AlignmentType.LEFT, despues: 300 }),
        new D.Paragraph({
          spacing: { after: 300 },
          children: [
            new D.TextRun({ text: 'Asunto: ', font: 'Times New Roman', size: 24, bold: true }),
            new D.TextRun({ text: datos.tramite, font: 'Times New Roman', size: 24 })
          ]
        }),
        P('De mi consideración:', { align: D.AlignmentType.LEFT }),
        P(cuerpoSolicitud()),
        P(CIERRE, { despues: 600 }),
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
  }).catch(err => {
    console.error("Error al generar el documento de Word: ", err);
    alert("Hubo un error al generar el archivo Word.");
  });
}

/* ================== INICIO ================== */
pintarPrevia();
mostrarPaso();
