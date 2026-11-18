<?php
/** Esquemas editables: ampliar campos aquí no cambia tablas académicas. */
function metadatosEsquemas(): array
{
    return [
        'articulo_cientifico' => ['label'=>'Artículo científico','fields'=>[
            'titulo'=>['label'=>'Título','type'=>'text','patterns'=>['/(?:t[ií]tulo|title)\s*[:\-]\s*(.{3,300})/iu']],
            'autores'=>['label'=>'Autores','type'=>'list','patterns'=>['/(?:autores?|authors?)\s*[:\-]\s*(.{3,500})/iu']],
            'revista'=>['label'=>'Revista','type'=>'text','patterns'=>['/(?:revista|journal)\s*[:\-]\s*(.{2,200})/iu']],
            'anio'=>['label'=>'Año','type'=>'text','patterns'=>['/(?:año|year|publicado\s+en)\s*[:\-]?\s*((?:19|20)\d{2})/iu']],
            'doi'=>['label'=>'DOI','type'=>'text','patterns'=>['/\b(10\.\d{4,9}\/[\-._;()\/:A-Z0-9]+)\b/iu']],
            'issn'=>['label'=>'ISSN','type'=>'text','patterns'=>['/ISSN\s*[:\-]?\s*(\d{4}-\d{3}[\dX])/iu']],
            'editorial'=>['label'=>'Editorial','type'=>'text','patterns'=>['/(?:editorial|publisher)\s*[:\-]\s*(.{2,200})/iu']],
        ]],
        'libro_capitulo' => ['label'=>'Libro o capítulo','fields'=>[
            'titulo'=>['label'=>'Título','type'=>'text','patterns'=>['/(?:t[ií]tulo|title)\s*[:\-]\s*(.{3,300})/iu']],
            'autor'=>['label'=>'Autoría','type'=>'list','patterns'=>['/(?:autor(?:es)?|author(?:s)?)\s*[:\-]\s*(.{3,500})/iu']],
            'editorial'=>['label'=>'Editorial','type'=>'text','patterns'=>['/(?:editorial|publisher)\s*[:\-]\s*(.{2,200})/iu']],
            'isbn'=>['label'=>'ISBN','type'=>'text','patterns'=>['/ISBN(?:-1[03])?\s*[:\-]?\s*([0-9Xx\- ]{10,24})/iu']],
            'anio'=>['label'=>'Año','type'=>'text','patterns'=>['/(?:año|year|publicado\s+en)\s*[:\-]?\s*((?:19|20)\d{2})/iu']],
            'capitulo'=>['label'=>'Capítulo','type'=>'text','patterns'=>['/(?:cap[ií]tulo|chapter)\s*[:\-]?\s*([^\r\n]{1,200})/iu']],
            'paginas'=>['label'=>'Páginas','type'=>'text','patterns'=>['/(?:p[aá]ginas?|pp\.)\s*[:\-]?\s*(\d{1,5}\s*(?:[-–]\s*\d{1,5})?)/iu']],
        ]],
        'constancia_diploma' => ['label'=>'Constancia o diploma','fields'=>[
            'participante'=>['label'=>'Nombre del participante','type'=>'text','patterns'=>['/(?:se\s+otorga\s+a|participante|nombre)\s*[:\-]?\s*([^\r\n]{3,180})/iu']],
            'actividad'=>['label'=>'Actividad','type'=>'text','patterns'=>['/(?:actividad|curso|taller|diplomado|conferencia)\s*[:\-]\s*([^\r\n]{3,250})/iu']],
            'institucion'=>['label'=>'Institución emisora','type'=>'text','patterns'=>['/(?:instituci[oó]n|organiza(?:do|da)\s+por|issuer)\s*[:\-]\s*([^\r\n]{2,200})/iu']],
            'fecha'=>['label'=>'Fecha','type'=>'text','patterns'=>['/(?:fecha|date)\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.](?:\d{2}|\d{4})|\d{1,2}\s+de\s+[[:alpha:]áéíóúñ]+\s+de\s+\d{4})/iu']],
            'duracion_horas'=>['label'=>'Duración / horas','type'=>'text','patterns'=>['/(\d{1,4}(?:[.,]\d+)?)\s*(?:horas?|hrs?\.?)/iu']],
            'tipo_participacion'=>['label'=>'Tipo de participación','type'=>'text','patterns'=>['/(?:participaci[oó]n|car[aá]cter)\s*[:\-]\s*([^\r\n]{2,120})/iu']],
        ]],
        'tesis_tutoria' => ['label'=>'Tesis o tutoría','fields'=>[
            'estudiante'=>['label'=>'Nombre del estudiante','type'=>'text','patterns'=>['/(?:estudiante|tesista|alumno\(a\)?)\s*[:\-]\s*([^\r\n]{3,180})/iu']],
            'titulo_trabajo'=>['label'=>'Título del trabajo','type'=>'text','patterns'=>['/(?:t[ií]tulo\s+(?:de\s+)?(?:la\s+)?(?:tesis|trabajo)|trabajo\s+titulado)\s*[:\-]?\s*([^\r\n]{3,300})/iu']],
            'grado'=>['label'=>'Grado','type'=>'text','patterns'=>['/(?:grado|nivel)\s*[:\-]\s*([^\r\n]{2,120})/iu']],
            'tipo_participacion'=>['label'=>'Tipo de participación','type'=>'text','patterns'=>['/(?:director(?:a)?|asesor(?:a)?|tutor(?:a)?|sinodal)\s*(?:de|:)?\s*([^\r\n]{0,120})/iu']],
            'periodo'=>['label'=>'Periodo','type'=>'text','patterns'=>['/(?:periodo|ciclo)\s*[:\-]\s*([^\r\n]{2,100})/iu']],
            'institucion'=>['label'=>'Institución','type'=>'text','patterns'=>['/(?:instituci[oó]n|universidad)\s*[:\-]\s*([^\r\n]{2,200})/iu']],
        ]],
        'ponencia_congreso' => ['label'=>'Ponencia o congreso','fields'=>[
            'evento'=>['label'=>'Evento','type'=>'text','patterns'=>['/(?:evento|congreso|simposio|seminario)\s*[:\-]\s*([^\r\n]{2,250})/iu']],
            'titulo_participacion'=>['label'=>'Título de la participación','type'=>'text','patterns'=>['/(?:t[ií]tulo|ponencia|participaci[oó]n)\s*[:\-]\s*([^\r\n]{3,300})/iu']],
            'autor_participante'=>['label'=>'Autor o participante','type'=>'list','patterns'=>['/(?:autor(?:es)?|ponente|participante)\s*[:\-]\s*([^\r\n]{3,350})/iu']],
            'fecha'=>['label'=>'Fecha','type'=>'text','patterns'=>['/(?:fecha|date)\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.](?:\d{2}|\d{4})|\d{1,2}\s+de\s+[[:alpha:]áéíóúñ]+\s+de\s+\d{4})/iu']],
            'institucion_organizadora'=>['label'=>'Institución organizadora','type'=>'text','patterns'=>['/(?:organiza(?:do|da)\s+por|instituci[oó]n\s+organizadora)\s*[:\-]?\s*([^\r\n]{2,200})/iu']],
            'modalidad'=>['label'=>'Modalidad','type'=>'text','patterns'=>['/(?:modalidad)\s*[:\-]\s*([^\r\n]{2,100})/iu']],
        ]],
        'documento_general' => ['label'=>'Documento académico','fields'=>[
            'titulo'=>['label'=>'Título','type'=>'text','patterns'=>['/(?:t[ií]tulo|title)\s*[:\-]\s*(.{3,300})/iu']],
            'autores'=>['label'=>'Autoría','type'=>'list','patterns'=>['/(?:autores?|authors?)\s*[:\-]\s*(.{3,500})/iu']],
            'institucion'=>['label'=>'Institución','type'=>'text','patterns'=>['/(?:instituci[oó]n|universidad|issuer)\s*[:\-]\s*([^\r\n]{2,200})/iu']],
            'fecha'=>['label'=>'Fecha','type'=>'text','patterns'=>['/(?:fecha|date)\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.](?:\d{2}|\d{4})|\d{1,2}\s+de\s+[[:alpha:]áéíóúñ]+\s+de\s+\d{4})/iu']],
        ]],
    ];
}

function metadatosIdentificarEsquema(array $documento,string $texto=''): array
{
    $tipo=mb_strtolower((string)($documento['tipo_documental']??''),'UTF-8');
    $titulo=mb_strtolower((string)($documento['titulo']??''),'UTF-8');
    $text=$tipo.' '.$titulo;
    $patterns=[
      'articulo_cientifico'=>'/art[ií]culo|paper|revista|journal|scientific article/u',
      'libro_capitulo'=>'/libro|book|cap[ií]tulo|chapter/u',
      'constancia_diploma'=>'/constancia|diploma|certificad|curso|taller/u',
      'tesis_tutoria'=>'/tesis|tutor[ií]a|direcci[oó]n|asesor[ií]a/u',
      'ponencia_congreso'=>'/ponencia|congreso|conferencia|simposio/u',
    ];
    $matches=[];foreach($patterns as $key=>$pattern)if(preg_match($pattern,$text))$matches[]=$key;
    if(count($matches)===1)return ['tipo'=>$matches[0],'estado'=>'inferido','confianza'=>0.72,'fuente'=>trim((string)($documento['tipo_documental']??''))?:'título de la evidencia'];
    if(count($matches)>1)return ['tipo'=>'documento_general','estado'=>'ambiguo','confianza'=>null,'candidatos'=>$matches,'fuente'=>trim($tipo.' '.$titulo)];
    $contentPatterns=[
      'articulo_cientifico'=>'/\bDOI\b|\bISSN\b|\b(?:abstract|resumen)\s*[:\r\n]/iu',
      'libro_capitulo'=>'/\bISBN\b|(?:cap[ií]tulo|chapter)\s+\d+/iu',
      'constancia_diploma'=>'/\bconstancia\b|\bse\s+otorga\b|\bdiploma\b|certificate\s+of\s+attendance/iu',
      'tesis_tutoria'=>'/\btesis\b|\bthesis\b|direcci[oó]n\s+de\s+tesis/iu',
      'ponencia_congreso'=>'/\bcongreso\b|\bconference\b|\bponencia\b|\bproceedings\b/iu',
    ];
    $contentMatches=[];foreach($contentPatterns as $key=>$pattern)if(preg_match($pattern,mb_substr($texto,0,10000)))$contentMatches[]=$key;
    if(count($contentMatches)===1)return ['tipo'=>$contentMatches[0],'estado'=>'inferido','confianza'=>0.60,'fuente'=>'contenido textual/OCR'];
    if(count($contentMatches)>1)return ['tipo'=>'documento_general','estado'=>'ambiguo','confianza'=>null,'candidatos'=>$contentMatches,'fuente'=>'contenido textual/OCR'];
    return ['tipo'=>'documento_general','estado'=>'no_encontrado','confianza'=>null,'fuente'=>null];
}

function metadatosCampoBase(string $id,array $def): array
{
    return ['campo'=>$id,'etiqueta'=>$def['label'],'valor'=>null,'fuente'=>null,'confianza'=>null,'estado'=>'no_encontrado'];
}

function metadatosExtraer(string $texto,array $documento=[],string $metodo='text'): array
{
    $confidence=str_starts_with($metodo,'ocr')||$metodo==='tesseract'?0.62:0.94;
    $identify=metadatosIdentificarEsquema($documento,$texto);$schemas=metadatosEsquemas();$schema=$schemas[$identify['tipo']]??$schemas['documento_general'];
    $fields=[];
    foreach($schema['fields'] as $id=>$def){
        $field=metadatosCampoBase($id,$def);$matches=[];
        foreach($def['patterns'] as $pattern){if(preg_match_all($pattern,$texto,$found,PREG_OFFSET_CAPTURE))foreach($found[1] as $hit){$value=trim(preg_replace('/\s+/u',' ',(string)$hit[0]));if($value!=='')$matches[]=['value'=>$value,'offset'=>(int)$hit[1]];}}
        $unique=[];foreach($matches as $m)$unique[mb_strtolower($m['value'],'UTF-8')]=$m;
        if(count($unique)===1){$m=array_values($unique)[0];$lineStart=strrpos(substr($texto,0,$m['offset']),"\n");$lineNo=substr_count(substr($texto,0,$m['offset']),"\n")+1;$line=trim(explode("\n",substr($texto,$lineStart===false?0:$lineStart+1),2)[0]);$value=$m['value'];
            if(($def['type']??'')==='list')$value=preg_split('/\s*(?:,|;|\s+y\s+|\s+and\s+)\s*/iu',$value,-1,PREG_SPLIT_NO_EMPTY)?:[$value];
            $field['valor']=$value;$field['fuente']=['linea'=>$lineNo,'fragmento'=>mb_substr($line,0,400),'metodo'=>$metodo];$field['confianza']=$confidence;$field['estado']='encontrado_explicito';
        } elseif(count($unique)>1){$field['estado']='ambiguo';$field['candidatos']=array_values(array_map(static fn($m)=>$m['value'],$unique));$field['confianza']=null;$field['fuentes_candidatas']=array_values(array_map(static function($m)use($texto){$lineStart=strrpos(substr($texto,0,$m['offset']),"\n");$line=trim(explode("\n",substr($texto,$lineStart===false?0:$lineStart+1),2)[0]);return ['linea'=>substr_count(substr($texto,0,$m['offset']),"\n")+1,'fragmento'=>mb_substr($line,0,400)];},array_values($unique)));}
        $fields[$id]=$field;
    }
    // El primer renglón solo se propone como título con incertidumbre explícita.
    if(($fields['titulo']['estado']??'')==='no_encontrado'){
        foreach(preg_split('/\R/u',$texto)?:[] as $lineNo=>$line){$line=trim(preg_replace('/\s+/u',' ',$line));if(mb_strlen($line)>=12&&mb_strlen($line)<=300){$fields['titulo']['valor']=$line;$fields['titulo']['fuente']=['linea'=>$lineNo+1,'fragmento'=>mb_substr($line,0,400),'metodo'=>$metodo];$fields['titulo']['confianza']=($confidence<0.7?0.30:0.45);$fields['titulo']['estado']='inferido';break;}}
    }
    return ['esquema'=>$identify['tipo'],'nombre_esquema'=>$schema['label'],'identificacion'=>$identify,'campos'=>$fields,'version'=>'1'];
}

function metadatosValidarEstructura($value): bool
{
    if(!is_array($value)||!isset($value['esquema'],$value['campos'])||!is_array($value['campos']))return false;
    foreach($value['campos'] as $id=>$field){if(!is_string($id)||!is_array($field)||($field['campo']??null)!==$id||!array_key_exists('valor',$field)||!in_array($field['estado']??'', ['encontrado_explicito','inferido','ambiguo','no_encontrado'],true))return false;}
    return true;
}

function metadatosTieneTablaRevision(mysqli $conn): bool
{
    $st=$conn->prepare("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='revisiones_metadatos_documentales'");$st->execute();$ok=(int)$st->get_result()->fetch_assoc()['n']===1;$st->close();return $ok;
}

function metadatosRevisionActual(mysqli $conn,int $evidencia,int $trabajo): array
{
    if(!metadatosTieneTablaRevision($conn))return ['revision'=>0,'campos'=>[],'disponible'=>false];
    $st=$conn->prepare('SELECT numero_revision,datos_json FROM revisiones_metadatos_documentales WHERE id_evidencia=? AND id_trabajo=? ORDER BY numero_revision DESC LIMIT 1');$st->bind_param('ii',$evidencia,$trabajo);$st->execute();$row=$st->get_result()->fetch_assoc();$st->close();
    $data=$row?json_decode((string)$row['datos_json'],true):[];
    return ['revision'=>(int)($row['numero_revision']??0),'campos'=>is_array($data['campos']??null)?$data['campos']:[],'disponible'=>true];
}

function metadatosGuardarRevision(mysqli $conn,int $evidencia,int $trabajo,int $usuarioId,int $revisionEsperada,array $entrada,bool $autorizarReemplazo): int
{
    if(!metadatosTieneTablaRevision($conn))throw new RuntimeException('La tabla de historial de revisión aún no está instalada.');
    $conn->begin_transaction();
    try{
        $st=$conn->prepare('SELECT id_evidencia FROM evidencias WHERE id_evidencia=? AND ocultar=0 FOR UPDATE');$st->bind_param('i',$evidencia);$st->execute();$exists=$st->get_result()->fetch_assoc();$st->close();if(!$exists)throw new RuntimeException('La evidencia ya no está disponible.');
        $st=$conn->prepare("SELECT r.metadatos_json FROM trabajos_documentales t JOIN origenes_trabajo_documental o ON o.id_trabajo=t.id_trabajo JOIN resultados_trabajo_documental r ON r.id_trabajo=t.id_trabajo WHERE t.id_trabajo=? AND o.id_evidencia=? AND t.estado IN ('PROCESADO','REQUIERE_REVISION') FOR UPDATE");$st->bind_param('ii',$trabajo,$evidencia);$st->execute();$row=$st->get_result()->fetch_assoc();$st->close();if(!$row)throw new RuntimeException('El resultado no pertenece a esta evidencia o no está disponible.');
        $metadata=json_decode((string)$row['metadatos_json'],true);$extraction=$metadata['extraccion']??null;if(!metadatosValidarEstructura($extraction))throw new RuntimeException('La extracción no tiene una estructura válida.');
        $current=metadatosRevisionActual($conn,$evidencia,$trabajo);if((int)$current['revision']!==$revisionEsperada)throw new DomainException('Los datos cambiaron en otra sesión. Recarga la revisión antes de guardar.');
        $allowed=['CONFIRMADO','CORREGIDO','RECHAZADO','PENDIENTE'];$fields=[];
        foreach($extraction['campos'] as $id=>$original){
            $input=$entrada[$id]??null;if(!is_array($input))throw new InvalidArgumentException('Falta un campo de revisión.');
            $value=$input['valor']??null;$state=(string)($input['estado']??'PENDIENTE');if(!in_array($state,$allowed,true))throw new InvalidArgumentException('Estado de revisión no válido.');
            if($value!==null&&!is_string($value))throw new InvalidArgumentException('El valor de un campo debe ser texto.');if(is_string($value)){ $value=trim($value);if(mb_strlen($value)>1000)throw new InvalidArgumentException('Un valor excede 1000 caracteres.');if($value==='')$value=null; }
            if(in_array($state,['CONFIRMADO','CORREGIDO'],true)&&$value===null)throw new InvalidArgumentException('Completa el valor antes de confirmarlo o marcarlo como corregido.');
            $old=$current['campos'][$id]??null;
            if(is_array($old)&&in_array(($old['estado']??''),['CONFIRMADO','CORREGIDO'],true)&&(!$old||$value!==($old['valor']??null)||$state!==($old['estado']??null))&&!$autorizarReemplazo)throw new DomainException('Para cambiar un dato ya confirmado debes autorizar explícitamente su reemplazo.');
            $fields[$id]=['campo'=>$id,'etiqueta'=>$original['etiqueta'],'valor_original'=>$original['valor'],'valor'=>$value,'fuente'=>$original['fuente']??null,'confianza'=>$original['confianza']??null,'estado_extraccion'=>$original['estado'],'estado'=>$state];
        }
        foreach($entrada as $id=>$value)if(!array_key_exists($id,$extraction['campos']))throw new InvalidArgumentException('Se envió un campo que no pertenece al esquema.');
        $next=(int)$current['revision']+1;$snapshot=['esquema'=>$extraction['esquema'],'campos'=>$fields,'reemplazo_autorizado'=>$autorizarReemplazo];$json=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
        $st=$conn->prepare('INSERT INTO revisiones_metadatos_documentales (id_evidencia,id_trabajo,numero_revision,datos_json,id_usuario) VALUES (?,?,?,?,?)');$st->bind_param('iiisi',$evidencia,$trabajo,$next,$json,$usuarioId);$st->execute();$st->close();$conn->commit();return $next;
    }catch(Throwable $e){$conn->rollback();throw $e;}
}
