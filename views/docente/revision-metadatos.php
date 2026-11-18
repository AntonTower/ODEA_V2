<?php
require_once __DIR__.'/../../config/paths.php';require_once CONFIG_DIR.'/conexion.php';require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/seguridad.php';require_once CONFIG_DIR.'/esquemas_metadatos_documentales.php';require_once CONFIG_DIR.'/procesamiento_documental.php';
$user=seguridadUsuarioActual($conn);if(!seguridadTieneRol($user,[1,2,4])){http_response_code(403);exit('No tienes permiso para revisar metadatos.');}
if(!documentoProcesamientoDisponible($conn)){http_response_code(503);exit('La infraestructura de extracción documental no está configurada.');}
$eid=(int)($_GET['id_evidencia']??0);$job=(int)($_GET['id_trabajo']??0);
$st=$conn->prepare('SELECT id_evidencia,id_docente,id_tipo_evidencia,ocultar,titulo,archivo FROM evidencias WHERE id_evidencia=?');$st->bind_param('i',$eid);$st->execute();$e=$st->get_result()->fetch_assoc();$st->close();
if(!$e||!seguridadPuedeEditarEvidencia($user,$e)||(int)$e['ocultar']!==0){http_response_code(404);exit('La evidencia no está disponible.');}
$st=$conn->prepare("SELECT t.estado,t.procesado_en,r.metadatos_json FROM trabajos_documentales t JOIN origenes_trabajo_documental o ON o.id_trabajo=t.id_trabajo JOIN resultados_trabajo_documental r ON r.id_trabajo=t.id_trabajo WHERE t.id_trabajo=? AND o.id_evidencia=? AND t.estado IN ('PROCESADO','REQUIERE_REVISION')");$st->bind_param('ii',$job,$eid);$st->execute();$row=$st->get_result()->fetch_assoc();$st->close();
if(!$row){http_response_code(404);exit('No hay una extracción disponible para esta evidencia.');}
$metadata=json_decode((string)$row['metadatos_json'],true);$extraction=$metadata['extraccion']??null;if(!metadatosValidarEstructura($extraction)){http_response_code(422);exit('La extracción no tiene una estructura válida.');}
$review=metadatosRevisionActual($conn,$eid,$job);$h=$review['campos'];
include COMPONENTS_DIR.'/layout/header.php';include COMPONENTS_DIR.'/layout/left-menu.php';
?>
<style>
.metadata-review-grid{display:grid;grid-template-columns:minmax(300px,1fr) minmax(300px,1fr);gap:18px;align-items:start}.metadata-original{position:sticky;top:82px}.metadata-original iframe{width:100%;height:min(72vh,850px);border:1px solid var(--border);border-radius:10px;background:#fff}.metadata-field{padding:14px 0;border-bottom:1px solid var(--border)}.metadata-field label{display:block;font-weight:600;margin-bottom:7px}.metadata-source{font-size:.88rem;color:var(--muted-foreground);margin:8px 0}.metadata-status{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.metadata-status select{max-width:230px}
@media(max-width:800px){.metadata-review-grid{grid-template-columns:1fr}.metadata-original{position:static}.metadata-original iframe{height:55vh}}
</style>
<section class="space-y-6">
  <header class="dashboard-hero"><div><p class="dashboard-eyebrow">Revisión humana de extracción</p><h1 class="text-2xl font-semibold"><?= htmlspecialchars((string)$e['titulo'],ENT_QUOTES,'UTF-8') ?></h1><p class="text-muted-foreground">Los valores detectados son sugerencias. Revisarlos no autentica ni aprueba el documento.</p></div><a class="btn" href="<?= BASE_URL ?>/views/docente/gestion-evidencias.php">Volver a evidencias</a></header>
  <p class="dashboard-note">Tipo documental sugerido: <?= htmlspecialchars((string)$extraction['nombre_esquema'],ENT_QUOTES,'UTF-8') ?> · Identificación: <?= htmlspecialchars((string)$extraction['identificacion']['estado'],ENT_QUOTES,'UTF-8') ?> · OCR/extracción: <?= htmlspecialchars((string)($metadata['metodo']??'local'),ENT_QUOTES,'UTF-8') ?><?php if(($metadata['metodo']??'')==='ocr_pdf'): ?> · Páginas procesadas: <?= (int)($metadata['paginas_ocr']??0) ?> de hasta <?= (int)($metadata['limite_paginas_ocr']??12) ?><?php endif; ?> · Revisión guardada: <?= (int)$review['revision'] ?></p>
  <?php if(!$review['disponible']): ?><p class="dashboard-note">La tabla de historial de revisión todavía no está instalada. No guardes cambios hasta aplicar la migración correspondiente.</p><?php else: ?>
  <div class="metadata-review-grid">
    <section class="card metadata-original"><div class="card-content"><div class="dashboard-section-heading"><h2 class="text-lg font-semibold">Documento original</h2><a class="btn" target="_blank" rel="noopener" href="<?= BASE_URL ?>/controllers/docente/evidencia-archivo.php?id=<?= $eid ?>">Abrir original</a></div><iframe src="<?= BASE_URL ?>/controllers/docente/evidencia-archivo.php?id=<?= $eid ?>&amp;contenido=1" title="Documento original"></iframe></div></section>
    <section class="card"><div class="card-content"><h2 class="text-lg font-semibold">Campos extraídos</h2><p class="text-muted-foreground">Confirma, corrige o rechaza cada sugerencia. La fuente y confianza de extracción se conservan.</p>
      <form id="metadataReviewForm" data-evidence="<?= $eid ?>" data-job="<?= $job ?>" data-revision="<?= (int)$review['revision'] ?>">
        <?php foreach($extraction['campos'] as $id=>$field): $saved=$h[$id]??null;$value=$saved['valor']??$field['valor'];if(is_array($value))$value=implode('; ',$value);$source=$field['fuente']??null; ?>
          <article class="metadata-field" data-field="<?= htmlspecialchars((string)$id,ENT_QUOTES,'UTF-8') ?>">
            <label for="meta-<?= htmlspecialchars((string)$id,ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars((string)$field['etiqueta'],ENT_QUOTES,'UTF-8') ?></label>
            <textarea class="input" rows="2" maxlength="1000" id="meta-<?= htmlspecialchars((string)$id,ENT_QUOTES,'UTF-8') ?>" data-value><?= htmlspecialchars((string)($value??''),ENT_QUOTES,'UTF-8') ?></textarea>
            <div class="metadata-source">Extracción: <?= htmlspecialchars((string)$field['estado'],ENT_QUOTES,'UTF-8') ?> · Confianza: <?= isset($field['confianza'])?number_format((float)$field['confianza']*100,0).'%':'no estimada' ?><?php if($source): ?> · Línea <?= (int)$source['linea'] ?>: “<?= htmlspecialchars((string)$source['fragmento'],ENT_QUOTES,'UTF-8') ?>”<?php endif; ?><?php if(!empty($field['candidatos'])): ?><br>Candidatos ambiguos: <?= htmlspecialchars(implode(' · ',$field['candidatos']),ENT_QUOTES,'UTF-8') ?><?php endif; ?></div>
            <div class="metadata-status"><label for="state-<?= htmlspecialchars((string)$id,ENT_QUOTES,'UTF-8') ?>">Validación</label><select class="input" id="state-<?= htmlspecialchars((string)$id,ENT_QUOTES,'UTF-8') ?>" data-state><?php $state=$saved['estado']??'PENDIENTE';foreach(['PENDIENTE'=>'Pendiente','CONFIRMADO'=>'Confirmado','CORREGIDO'=>'Corregido','RECHAZADO'=>'Rechazado'] as $code=>$label): ?><option value="<?= $code ?>" <?= $state===$code?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
          </article>
        <?php endforeach; ?>
        <label class="dashboard-note" style="display:block;margin:16px 0"><input type="checkbox" name="authorize-replace" value="1"> Autorizo reemplazar valores que ya había confirmado en una revisión anterior.</label>
        <button class="btn btn-primary" type="submit">Guardar revisión</button><span id="metadataReviewMessage" role="status" style="margin-left:10px"></span>
      </form>
    </div></section>
  </div>
  <?php endif; ?>
</section>
<script>
document.getElementById('metadataReviewForm')?.addEventListener('submit',async event=>{event.preventDefault();const form=event.currentTarget;const fields={};form.querySelectorAll('.metadata-field').forEach(row=>fields[row.dataset.field]={valor:row.querySelector('[data-value]').value||null,estado:row.querySelector('[data-state]').value});const body=new FormData();body.append('id_evidencia',form.dataset.evidence);body.append('id_trabajo',form.dataset.job);body.append('revision',form.dataset.revision);body.append('campos',JSON.stringify(fields));if(form.querySelector('[name="authorize-replace"]').checked)body.append('autorizar_reemplazo','1');const token=document.querySelector('meta[name="csrf-token"]')?.content||'';const msg=document.getElementById('metadataReviewMessage');try{const response=await fetch('<?= BASE_URL ?>/controllers/docente/metadatos-revision-save.php',{method:'POST',headers:{'X-CSRF-Token':token},body});const result=await response.json();if(!response.ok||result.status!=='ok')throw new Error(result.message||'No se pudo guardar la revisión.');form.dataset.revision=result.revision;msg.textContent=`Revisión ${result.revision} guardada.`;}catch(error){msg.textContent=error.message;}});
</script>
<?php include COMPONENTS_DIR.'/layout/footer.php'; ?>
