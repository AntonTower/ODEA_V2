<?php

// Tipos de evidencia
$tipos = [];
$qT = "SELECT id_tipo_evidencia, nombre_tipo FROM tipos_de_evidencia ORDER BY nombre_tipo ASC";
if ($resT = mysqli_query($conn, $qT)) {
  while ($row = mysqli_fetch_assoc($resT)) $tipos[] = $row;
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
            <input class="input" id="ae_archivo" name="archivo" type="file" accept="application/pdf,image/*" required />
            <p class="text-muted" style="margin:4px 0 0;">PDF / JPG / PNG / WEBP &nbsp;•&nbsp; Máx. 10 MB</p>
          </div>
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
