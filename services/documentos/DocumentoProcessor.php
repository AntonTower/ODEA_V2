<?php
require_once dirname(__DIR__,2).'/config/esquemas_metadatos_documentales.php';
interface DocumentoProcessorProvider
{
    public function nombre(): string;
    /** @return array{texto:string,metadatos:array,requiere_revision?:bool} */
    public function procesar(array $documento, string $ruta, int $limiteSegundos): array;
}

/** Puertos de pipeline para que cada fase pueda tener adaptadores propios.
 * Solo el extractor de texto/OCR tiene un adaptador en esta fase. */
interface DocumentoTextExtractorProvider extends DocumentoProcessorProvider {}
interface DocumentoIdentificationProvider { public function identificar(string $texto,array $metadatosTecnicos): array; }
interface DocumentoMetadataProvider { public function extraer(string $texto,array $identificacion): array; }
interface DocumentoSemanticClassifierProvider { public function clasificar(string $texto,array $metadatos): array; }
interface DocumentoCriteriaComparatorProvider { public function comparar(string $texto,array $clasificacion,int $idVersion): array; }

final class DocumentoIdentificadorReglas implements DocumentoIdentificationProvider
{
    public function identificar(string $texto,array $metadatosTecnicos): array { return metadatosIdentificarEsquema($metadatosTecnicos,$texto); }
}
final class DocumentoExtractorMetadatosReglas implements DocumentoMetadataProvider
{
    public function extraer(string $texto,array $identificacion): array
    {
        $documento=$identificacion['documento']??[];$metodo=(string)($identificacion['metodo']??'text');
        $result=metadatosExtraer($texto,is_array($documento)?$documento:[],$metodo);
        if(is_array($identificacion['esquema_identificado']??null))$result['identificacion']=$identificacion['esquema_identificado'];
        return $result;
    }
}

class DocumentoProcessingException extends RuntimeException
{
    public string $codigoError;
    public function __construct(string $mensajeSeguro, string $codigoError='provider_error')
    {
        $this->codigoError=$codigoError;
        parent::__construct($mensajeSeguro);
    }
}

final class ProcesadorDeshabilitado implements DocumentoTextExtractorProvider
{
    public function nombre(): string { return 'disabled'; }
    public function procesar(array $documento, string $ruta, int $limiteSegundos): array
    {
        throw new DocumentoProcessingException('El procesamiento documental no está configurado.', 'service_unavailable');
    }
}

final class ProcesadorLocalCLI implements DocumentoTextExtractorProvider
{
    public function nombre(): string { return 'local-cli'; }

    public function procesar(array $documento, string $ruta, int $limiteSegundos): array
    {
        $mime=(string)($documento['mime']??'');
        $metodo='';$paginasOcr=0;
        if ($mime==='application/pdf') {
            $bin=(string)(getenv('ODEA_PDFTOTEXT_BIN')?:'pdftotext');
            $texto=$this->ejecutar([$bin,'-layout','-enc','UTF-8',$ruta,'-'],$limiteSegundos);$metodo='pdftotext';
            if(mb_strlen(trim($texto))<10){[$texto,$paginasOcr]=$this->ocrPdf($ruta,$limiteSegundos);$metodo='ocr_pdf';}
        } elseif (in_array($mime,['image/jpeg','image/png','image/webp'],true)) {
            $image=@getimagesize($ruta);
            if(!$image||(int)$image[0]>12000||(int)$image[1]>12000||(int)$image[0]*(int)$image[1]>40000000)throw new DocumentoProcessingException('La imagen excede los límites de OCR permitidos.','invalid_document');
            $bin=(string)(getenv('ODEA_TESSERACT_BIN')?:'tesseract');
            $idioma=(string)(getenv('ODEA_OCR_LANG')?:'spa+eng');
            if (!preg_match('/^[a-zA-Z0-9_+.-]{1,40}$/',$idioma)) throw new DocumentoProcessingException('La configuración del idioma OCR no es válida.','configuration_error');
            $texto=$this->ejecutar([$bin,$ruta,'stdout','-l',$idioma],$limiteSegundos);$metodo='tesseract';
        } else {
            throw new DocumentoProcessingException('El formato no está habilitado para extracción local.','invalid_document');
        }
        if (strlen($texto)>5*1024*1024) $texto=substr($texto,0,5*1024*1024);
        $texto=mb_convert_encoding($texto,'UTF-8','UTF-8');
        $texto=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u','',$texto)??'';
        $texto=trim($texto);
        $identified=(new DocumentoIdentificadorReglas())->identificar($texto,$documento);
        $extraccion=(new DocumentoExtractorMetadatosReglas())->extraer($texto,['documento'=>$documento,'metodo'=>$metodo,'esquema_identificado'=>$identified]);
        return ['texto'=>$texto,'metadatos'=>[
            'mime'=>$mime,'bytes'=>(int)($documento['bytes']??0),'sha256'=>(string)($documento['sha256']??''),
            'caracteres_extraidos'=>mb_strlen($texto),'metodo'=>$metodo,'paginas_ocr'=>$paginasOcr,'limite_paginas_ocr'=>$metodo==='ocr_pdf'?12:null,'extraccion'=>$extraccion
        ],'requiere_revision'=>true];
    }

    private function ocrPdf(string $pdf,int $timeout): array
    {
        $raster=(string)(getenv('ODEA_PDFTOPPM_BIN')?:'pdftoppm');$ocr=(string)(getenv('ODEA_TESSERACT_BIN')?:'tesseract');
        $language=(string)(getenv('ODEA_OCR_LANG')?:'spa+eng');
        if(!preg_match('/^[a-zA-Z0-9_+.-]{1,40}$/',$language))throw new DocumentoProcessingException('Invalid OCR language configuration.','configuration_error');
        $dir=sys_get_temp_dir().DIRECTORY_SEPARATOR.'odea_ocr_'.bin2hex(random_bytes(10));
        if(!mkdir($dir,0700))throw new DocumentoProcessingException('Could not prepare local OCR.','service_unavailable');
        $prefix=$dir.DIRECTORY_SEPARATOR.'page';$all='';$count=0;$start=microtime(true);
        try{
            $this->ejecutar([$raster,'-f','1','-l','12','-scale-to','1800','-png',$pdf,$prefix],min($timeout,25));
            $pages=glob($prefix.'-*.png')?:[];sort($pages,SORT_NATURAL);
            foreach($pages as $page){$left=$timeout-(int)(microtime(true)-$start);if($left<=1)throw new DocumentoProcessingException('OCR timeout.','timeout');
                $text=$this->ejecutar([$ocr,$page,'stdout','-l',$language],$left);if(trim($text)!=='')$all.="\n\f\n".$text;$count++;
                if(strlen($all)>5*1024*1024)throw new DocumentoProcessingException('OCR output limit exceeded.','output_limit');
            }
            return [trim($all),$count];
        }finally{foreach(glob($dir.DIRECTORY_SEPARATOR.'*')?:[] as $file)if(is_file($file))@unlink($file);@rmdir($dir);}
    }

    private function ejecutar(array $args,int $timeout): string
    {
        $desc=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
        $proc=@proc_open($args,$desc,$pipes,null,null,['bypass_shell'=>true]);
        if (!is_resource($proc)) throw new DocumentoProcessingException('No se pudo iniciar el proveedor local.','service_unavailable');
        fclose($pipes[0]); stream_set_blocking($pipes[1],false); stream_set_blocking($pipes[2],false);
        $stdout=''; $stderr=''; $inicio=microtime(true); $timedOut=false;
        do {
            $status=proc_get_status($proc);
            $chunk=stream_get_contents($pipes[1]); if (is_string($chunk)) $stdout.=$chunk;
            $err=stream_get_contents($pipes[2]); if (is_string($err)) $stderr=substr($stderr.$err,-20000);
            if (strlen($stdout)>5*1024*1024) { proc_terminate($proc); throw new DocumentoProcessingException('El proveedor produjo una salida mayor al límite permitido.','output_limit'); }
            if ($status['running'] && microtime(true)-$inicio>$timeout) { $timedOut=true; proc_terminate($proc); break; }
            if ($status['running']) usleep(50000);
        } while ($status['running']);
        $exitCode=(int)($status['exitcode']??-1);
        $stdout.=stream_get_contents($pipes[1])?:''; $stderr=substr($stderr.(stream_get_contents($pipes[2])?:''),-20000);
        fclose($pipes[1]); fclose($pipes[2]); $closeCode=proc_close($proc);
        $code=$closeCode>=0?$closeCode:$exitCode;
        if ($timedOut) throw new DocumentoProcessingException('El procesamiento excedió el tiempo máximo.','timeout');
        if ($code!==0) {
            $msg=strtolower($stderr);
            if (str_contains($msg,'not recognized') || str_contains($msg,'not found') || str_contains($msg,'cannot find') || str_contains($msg,'unable to find') || str_contains($msg,'error=2') || str_contains($msg,'no such file')) {
                throw new DocumentoProcessingException('El proveedor local no está instalado o configurado.','service_unavailable');
            }
            throw new DocumentoProcessingException('El proveedor local no pudo procesar el documento.','provider_error');
        }
        return $stdout;
    }
}
