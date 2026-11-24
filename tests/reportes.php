<?php
if (PHP_SAPI !== 'cli') exit;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
chdir(dirname(__DIR__));
require_once 'config/reportes.php';
$admin = new mysqli('127.0.0.1', 'root', '', '', 3308);
$name = 'gestor_test_reportes_' . bin2hex(random_bytes(5));
$created = false;
$checks = 0;
function reporteCheck(bool $ok, string $label): void
{
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
    echo "OK: $label\n";
}
try {
    $admin->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
    $created = true;
    $db = new mysqli('127.0.0.1', 'root', '', $name, 3308);
    $db->query("CREATE TABLE evidencia_valores_atributo (
        id_evidencia INT NOT NULL,id_eva INT NOT NULL,id_ate INT NOT NULL,indice INT NOT NULL,
        valor_texto TEXT NULL,valor_largo TEXT NULL,valor_int INT NULL,valor_decimal DECIMAL(10,2) NULL,
        valor_fecha DATE NULL,valor_bool TINYINT NULL,valor_archivo VARCHAR(255) NULL,valor_json LONGTEXT NULL
    ) ENGINE=InnoDB");
    $db->query("INSERT INTO evidencia_valores_atributo (id_evidencia,id_eva,id_ate,indice,valor_texto) VALUES
      (1,1,4,0,'uno'),(1,2,5,0,'dos'),(2,3,4,0,'tres'),(3,4,6,0,'cuatro')");

    $values = reportesCargarValoresAtributos($db, [2, 1, 2, 0, -1]);
    reporteCheck(count($values) === 2 && isset($values[1][4], $values[1][5], $values[2][4]), 'La carga por lotes agrupa valores por evidencia y atributo sin duplicar IDs');
    reporteCheck($values[1][4][0]['valor_texto'] === 'uno' && $values[2][4][0]['valor_texto'] === 'tres', 'Los valores recuperados conservan el contenido y el propietario de evidencia');
    reporteCheck(reportesCargarValoresAtributos($db, []) === [], 'Un reporte sin evidencias no emite consulta IN vacía ni errores');
    $db->close();
    echo "$checks pruebas de carga de atributos en reportes correctas.\n";
} finally {
    if ($created) $admin->query("DROP DATABASE `$name`");
    $admin->close();
}
