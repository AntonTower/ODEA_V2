<?php
if(PHP_SAPI!=='cli')exit(1);
chdir(dirname(__DIR__));
$_SESSION=['SESUSUARIO'=>'Prueba','ROL'=>2];
function ayudaCheck(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);echo "OK: $message\n";}
function renderHelp(string $route,int $role):string{$_SESSION['ROL']=$role;$_SERVER['SCRIPT_NAME']='/views/'.$route;ob_start();include dirname(__DIR__).'/components/ui/ayuda-contextual.php';return (string)ob_get_clean();}
$checks=0;
try{
$routes=['admin/gestion-usuarios.php','admin/instrumentos.php','admin/tipos-evidencia.php','admin/atributos-tipo.php','admin/periodos-academicos.php','admin/convocatorias.php','admin/criterios-evaluacion.php','admin/fuentes-normativas.php','docente/gestion-evidencias.php','docente/expediente.php','docente/revision-metadatos.php','docente/aplicaciones-academicas.php','docente/calificaciones-recibidas.php','docente/evidencia-detalle.php','evaluador/evaluacion.php','evaluador/permisos-instrumentos.php','evaluador/campos-instrumentos-evaluador.php','index.php'];
foreach($routes as $route){$role=(str_starts_with($route,'admin/')||$route==='evaluador/permisos-instrumentos.php')?2:(str_starts_with($route,'evaluador/')?3:(str_starts_with($route,'docente/')?4:1));$html=renderHelp($route,$role);ayudaCheck(str_contains($html,'aria-label="Ayuda contextual"')&&str_contains($html,'Resultado esperado')&&str_contains($html,'Siguiente paso'),"Guia contextual disponible para $route");$checks++;}
$teacherAdmin=renderHelp('admin/instrumentos.php',4);ayudaCheck(!str_contains($teacherAdmin,'aria-label="Ayuda contextual"'), 'Docente sin ayuda administrativa');$checks++;
$evalAdmin=renderHelp('admin/criterios-evaluacion.php',3);ayudaCheck(!str_contains($evalAdmin,'aria-label="Ayuda contextual"'), 'Evaluador sin ayuda administrativa');$checks++;
$academic=renderHelp('admin/criterios-evaluacion.php',2);ayudaCheck(str_contains($academic,'publicada')&&str_contains($academic,'fuentes normativas')&&str_contains($academic,'Ejemplo ilustrativo'), 'La guia academica describe el flujo y marca ejemplos ilustrativos');$checks++;
$component=file_get_contents('components/ui/ayuda-contextual.php');ayudaCheck(str_contains($component,'<details>')&&str_contains($component,'<summary>')&&str_contains($component,'focus-visible'), 'Componente nativo accesible mediante teclado');$checks++;
ayudaCheck(str_contains($component,'el.placeholder=value')&&!str_contains($component,'el.value=value'), 'Los ejemplos son placeholders, no valores persistidos');$checks++;
echo "$checks verificaciones correctas.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
