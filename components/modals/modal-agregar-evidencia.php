<?php

// Tipos de evidencia
$tipos = [];
$qT = "SELECT id_tipo_evidencia, nombre_tipo FROM tipos_de_evidencia ORDER BY nombre_tipo ASC";
if ($resT = mysqli_query($conn, $qT)) {
  while ($row = mysqli_fetch_assoc($resT)) $tipos[] = $row;
}
$atributosPorTipo = [];
$qA = "SELECT a.id_tipo_evidencia, a.nombre_atributo, a.descripcion, a.requerido, ta.nombre_tipo AS tipo_dato, ta.grupo_storage
       FROM atributos_tipo_evidencia a JOIN tipos_atributo ta ON ta.id_tipo_atributo=a.id_tipo_atributo
       ORDER BY a.id_tipo_evidencia, a.orden, a.id_ate";
if ($resA = mysqli_query($conn, $qA)) {
  while ($row = mysqli_fetch_assoc($resA)) $atributosPorTipo[(int)$row['id_tipo_evidencia']][] = $row;
}
$docentes = [];
if (in_array((int)($user_role ?? 0), [1, 2], true)) {
  $qD = "SELECT id_usuario, nombre, apellidop, apellidom FROM usuarios WHERE rol = 4 AND activo = 1 ORDER BY apellidop, apellidom, nombre";
  if ($resD = mysqli_query($conn, $qD)) {
    while ($row = mysqli_fetch_assoc($resD)) $docentes[] = $row;
  }
}
?>
<div class="dialog-backdrop" id="dlgAddEviBackdrop"></div>
<div class="dialog" id="dlgAddEvi" role="dialog" aria-modal="true" aria-labelledby="dlgAddEviTitle" data-open="false">
  <div class="dialog-card modal-card-scroll" style="max-width:760px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgAddEviTitle">Agregar evidencia</h3>
      <p class="dialog-desc">Selecciona el tipo, escribe un título y sube el archivo (PDF o imagen, máx. 10 MB).</p>
    </div>

    <div class="dialog-body dialog-body-scroll">
      <form id="formAddEvi" class="space-y-4" onsubmit="return false;" enctype="multipart/form-data">
        <div class="form-group">
          <label class="label" for="ae_titulo">Título de la evidencia</label>
          <input class="input" id="ae_titulo" name="titulo" type="text" maxlength="255" placeholder="Ej. Libro XYZ / Certificado de conferencia..." required />
        </div>

        <?php if (in_array((int)($user_role ?? 0), [1, 2], true)): ?>
          <div class="form-group">
            <label class="label" for="ae_docente">Docente propietario</label>
            <select class="input select-white" id="ae_docente" name="id_docente" required>
              <?php if ($docentes): ?>
                <option value="" selected disabled>Selecciona un docente</option>
                <?php foreach ($docentes as $docente): ?>
                  <?php $nombreDocente = trim($docente['nombre'].' '.$docente['apellidop'].' '.$docente['apellidom']); ?>
                  <option value="<?= (int)$docente['id_usuario'] ?>"><?= htmlspecialchars($nombreDocente, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="" selected disabled>No hay docentes activos</option>
              <?php endif; ?>
            </select>
          </div>
        <?php endif; ?>

        <div class="grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div class="form-group">
            <label class="label" for="ae_tipo">Tipo de evidencia</label>
            <select class="input select-white" id="ae_tipo" name="id_tipo_evidencia" required>
              <?php if ($tipos): ?>
                <option value="" selected disabled>Selecciona un tipo…</option>
                <?php foreach ($tipos as $t): ?>
                  <option value="<?= (int)$t['id_tipo_evidencia'] ?>"><?= htmlspecialchars($t['nombre_tipo'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="" selected disabled>No hay tipos; solicita al administrador que registre uno</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="label" for="ae_archivo">Archivo</label>
            <input class="input" id="ae_archivo" name="archivo" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required />
            <p class="text-muted" style="margin:4px 0 0;">PDF / JPG / PNG / WEBP &nbsp;•&nbsp; Máx. 10 MB</p>
          </div>
        </div>
        <div id="ae_tipo_info" class="alert" role="status" aria-live="polite">Selecciona un tipo para consultar sus campos.</div>
        <div id="ae_upload_progress_wrap" hidden aria-live="polite">
          <progress id="ae_upload_progress" max="100" value="0" style="width:100%;"></progress>
          <span id="ae_upload_progress_text" class="text-muted">Preparando carga…</span>
        </div>
      </form>
    </div>

    <div class="dialog-actions dialog-actions-sticky">
      <button type="button" class="btn" id="btnCancelAddEvi">Cancelar</button>
      <button type="submit" class="btn btn-primary" id="btnSubmitAddEvi" form="formAddEvi">Agregar</button>
    </div>
  </div>
</div>

<style>
.modal-card-scroll { max-height: 90vh; display:flex; flex-direction:column; }
.dialog-body-scroll { overflow:auto; padding:0 0 12px 0; -webkit-overflow-scrolling:touch; }
.dialog-actions-sticky { position:sticky; bottom:0; background:var(--card); border-top:1px solid var(--border); padding-top:12px; margin-top:0; box-shadow:0 -6px 12px rgba(0,0,0,0.06); }
</style>

<script>
(function(){
  const modal     = document.getElementById('dlgAddEvi');
  const backdrop  = document.getElementById('dlgAddEviBackdrop');
  const openBtn   = document.getElementById('openModalAgregarEvidencia');
  const btnCancel = document.getElementById('btnCancelAddEvi');
  const form      = document.getElementById('formAddEvi');
  const tipoInfo = document.getElementById('ae_tipo_info');
  const tipoAttrs = <?= json_encode($atributosPorTipo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const tipoSelect = document.getElementById('ae_tipo');
  const progressWrap = document.getElementById('ae_upload_progress_wrap');
  const progressBar = document.getElementById('ae_upload_progress');
  const progressText = document.getElementById('ae_upload_progress_text');

  tipoSelect.addEventListener('change', function(){
    const attrs = tipoAttrs[this.value] || [];
    tipoInfo.replaceChildren();
    const title=document.createElement('strong'); title.textContent='Campos del tipo seleccionado'; tipoInfo.appendChild(title);
    if (!attrs.length) { tipoInfo.append(' — Este tipo no tiene atributos definidos.'); return; }
    const required=attrs.filter(a=>Number(a.requerido)===1), optional=attrs.filter(a=>Number(a.requerido)!==1);
    const render=(label,list)=>{const p=document.createElement('p');p.style.margin='6px 0 0';const b=document.createElement('strong');b.textContent=label+': ';p.appendChild(b);p.append(list.length?list.map(a=>a.nombre_atributo).join(', '):'Ninguno');tipoInfo.appendChild(p);};
    render('Obligatorios',required); render('Opcionales',optional);
    const described=attrs.filter(a=>a.descripcion);
    if(described.length){const p=document.createElement('p');p.style.margin='6px 0 0';p.textContent='Indicaciones: '+described.map(a=>a.nombre_atributo+' — '+a.descripcion).join(' | ');tipoInfo.appendChild(p);}
    const note=document.createElement('p');note.style.margin='6px 0 0';note.textContent='Los atributos se capturan o completan después de crear la evidencia.';tipoInfo.appendChild(note);
  });

  // Promise compatible con el manejador existente y seguimiento del progreso de carga.
  function fetch(url, options){
    return new Promise((resolve,reject)=>{
      const xhr=new XMLHttpRequest(); xhr.open(options.method||'GET',url);
      const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
      if(csrf) xhr.setRequestHeader('X-CSRF-Token',csrf);
      xhr.upload.addEventListener('progress',event=>{if(event.lengthComputable){progressWrap.hidden=false;const pct=Math.round(event.loaded*100/event.total);progressBar.value=pct;progressText.textContent='Cargando archivo: '+pct+'%';}});
      xhr.onload=()=>resolve({status:xhr.status,text:()=>Promise.resolve(xhr.responseText)});
      xhr.onerror=()=>reject(new Error('No se pudo completar la carga. Verifica tu conexión e inténtalo de nuevo.'));
      xhr.send(options.body);
    });
  }

  function open() {
    modal.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(true);
  }
  function close() {
    modal.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    if (typeof window.lockPageScroll === 'function') lockPageScroll(false);
    if (form) form.reset();
    if (progressWrap) progressWrap.hidden = true;
    if (progressBar) progressBar.value = 0;
    if (tipoInfo) tipoInfo.textContent = 'Selecciona un tipo para consultar sus campos.';
  }

  if (openBtn)   openBtn.addEventListener('click', open);
  if (btnCancel) btnCancel.addEventListener('click', close);
  if (backdrop)  backdrop.addEventListener('click', close);

  window.openAddEviModal  = open;
  window.closeAddEviModal = close;

  const btnSubmit = document.getElementById('btnSubmitAddEvi');
  form.addEventListener('submit', function(e){
    e.preventDefault();

    const titulo = document.getElementById('ae_titulo').value.trim();
    const tipo   = document.getElementById('ae_tipo').value;
    const file   = document.getElementById('ae_archivo').files[0];

    if (!titulo || !tipo || !file) return;

    btnSubmit.disabled = true;
    const fd = new FormData(form);
    progressWrap.hidden = false; progressBar.value = 0; progressText.textContent = 'Preparando carga…';

    fetch('<?= BASE_URL ?>/controllers/docente/evidencia-insert.php', { method:'POST', body:fd })
      .then(async r=>{
        const body = await r.text();
        try { return JSON.parse(body); }
        catch (_) { throw new Error('El servidor devolvió una respuesta no válida (HTTP ' + r.status + ').'); }
      })
      .then(d=>{
        if (d && d.status === 'ok') {
          if (typeof window.closeAddEviModal === 'function') window.closeAddEviModal();
          if (typeof loadTableE === 'function') loadTableE();
          const warnings = Array.isArray(d.advertencias) ? d.advertencias : [];
          if (warnings.length && window.Swal) { Swal.fire({icon:'warning', title:'Creada con advertencia', text:warnings.join(' ')}); return; }
          if (d.procesamiento && d.procesamiento.estado === 'NO_DISPONIBLE' && window.Swal) { Swal.fire({icon:'success', title:'Evidencia creada', text:'El archivo se guardó. El módulo de procesamiento aún no está disponible.'}); return; }
          if (window.Swal) Swal.fire({icon:'success', title:'Creada', text:'La evidencia se creó correctamente.'});
        } else {
          const msg = (d && d.message) ? d.message : 'No se pudo crear la evidencia.';
          if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
        }
      })
      .catch(error=>{
        if (window.Swal) Swal.fire({icon:'error', title:'Error', text:error.message || 'Error al crear la evidencia.'});
      })
      .finally(()=>{ btnSubmit.disabled = false; });
  });
})();
</script>
