// js/consulta.js

// Renderizar logo en la cabecera
document.getElementById('logo-img').src = 'data:image/png;base64,' + LOGO_B64;

document.getElementById('formConsulta').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const cedula = document.getElementById('cedula').value.trim();
  
  if(!cedula) return;
  
  const divRes = document.getElementById('resultado');
  const divLoading = document.getElementById('loading');
  
  divRes.style.display = 'none';
  divLoading.style.display = 'block';
  divRes.innerHTML = '';
  
  try {
    const res = await fetch('backend/consulta_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cedula })
    });
    
    const data = await res.json();
    divLoading.style.display = 'none';
    
    if(!res.ok) {
      divRes.style.display = 'block';
      divRes.style.borderColor = '#e74c3c';
      divRes.innerHTML = `<p style="color:#e74c3c; font-weight:bold; margin:0;">⚠️ ${data.error || 'Error al consultar'}</p>`;
      return;
    }
    
    divRes.style.display = 'block';
    divRes.style.borderColor = 'transparent';
    divRes.style.background = 'transparent';
    divRes.style.padding = '0';
    
    let html = `<h3 style="margin-bottom: 20px; color: var(--verde-osc);">Historial de Solicitudes de: ${data[0].nombre}</h3>`;
    
    data.forEach(solicitud => {
      const claseEstado = solicitud.estado.replace(' ', '_');
      
      let mensaje = '';
      if(solicitud.estado === 'PENDIENTE') mensaje = 'Tu solicitud ha sido recibida pero aún no ha sido revisada por Secretaría.';
      if(solicitud.estado === 'EN REVISION') mensaje = 'Tu solicitud está siendo procesada en este momento. Por favor espera.';
      if(solicitud.estado === 'APROBADA') mensaje = '¡Tu solicitud ha sido <b>aprobada</b>! Acércate a Secretaría o revisa tu correo si aplica.';
      if(solicitud.estado === 'RECHAZADA') mensaje = 'Tu solicitud fue rechazada. Por favor comunícate con Secretaría para más detalles.';

      html += `
      <div style="margin-bottom: 20px; padding: 20px; border-radius: 8px; border: 1px solid var(--linea); background: var(--papel);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
          <span class="badge-estado estado-${claseEstado}">${solicitud.estado}</span>
          <span style="color:var(--tinta-suave); font-size:0.85rem; font-weight:bold;">${solicitud.codigo}</span>
        </div>
        <h4 style="margin:5px 0; color:var(--verde-osc); font-size:1.1rem;">${solicitud.tramite}</h4>
        <p style="margin:5px 0; font-size:0.9rem; color:var(--tinta-suave);">Fecha: <b>${solicitud.fecha}</b></p>
        <hr style="border:none; border-top:1px solid var(--linea); margin:15px 0;">
        <p style="margin:0; font-size:0.95rem; color:var(--tinta); line-height:1.4;">${mensaje}</p>
      </div>`;
    });
    
    divRes.innerHTML = html;
    
  } catch(err) {
    divLoading.style.display = 'none';
    divRes.style.display = 'block';
    divRes.style.borderColor = '#e74c3c';
    divRes.innerHTML = `<p style="color:#e74c3c; font-weight:bold; margin:0;">⚠️ Error de conexión con el servidor.</p>`;
  }
});
