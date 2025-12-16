<?php
/**
 * Script para instalar fuentes en dompdf.
 * Deja que dompdf genere installed-fonts.json automáticamente con el formato correcto.
 */

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Crear la carpeta storage/fonts si no existe
$fontPath = __DIR__ . '/storage/fonts';
if (!file_exists($fontPath)) {
    mkdir($fontPath, 0775, true);
    echo "Carpeta storage/fonts creada.\n";
}

// Limpiar archivos de caché de fuentes existentes EXCEPTO los .ttf originales de dompdf
$files = glob($fontPath . '/*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
        echo "Eliminado: " . basename($file) . "\n";
    }
}

// Helper: busca un archivo en $dir por nombre ignorando mayúsculas/minúsculas
function findFileCI($dir, $name) {
    if (!is_dir($dir)) return false;
    $files = scandir($dir);
    foreach ($files as $f) {
        if (strcasecmp($f, $name) === 0) {
            return $f;
        }
    }
    return false;
}

$publicFontsPath = __DIR__ . '/public/fonts';

// Configurar dompdf para que guarde las métricas en storage/fonts
$options = new Options();
$options->set('fontDir', $fontPath);
$options->set('fontCache', $fontPath);
$options->set('isRemoteEnabled', true);
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);
$fontMetrics = $dompdf->getFontMetrics();

// Verificar que las fuentes DM Sans existan (case-insensitive)
$dmSansNormal = findFileCI($publicFontsPath, 'DMSans-VariableFont.ttf');
$dmSansItalic = findFileCI($publicFontsPath, 'DMSans-Italic-VariableFont.ttf');

if (!$dmSansNormal) {
    echo "ERROR: No se encontró DMSans-VariableFont.ttf en $publicFontsPath\n";
    exit(1);
}

echo "\nInstalando fuentes...\n\n";

// Registrar DM Sans
$fontMetrics->registerFont(
    ['family' => 'dm sans', 'style' => 'normal', 'weight' => 'normal'],
    $publicFontsPath . '/' . $dmSansNormal
);
echo "DM Sans normal instalada.\n";

$fontMetrics->registerFont(
    ['family' => 'dm sans', 'style' => 'normal', 'weight' => 'bold'],
    $publicFontsPath . '/' . $dmSansNormal
);
echo "DM Sans bold instalada.\n";

if ($dmSansItalic) {
    $fontMetrics->registerFont(
        ['family' => 'dm sans', 'style' => 'italic', 'weight' => 'normal'],
        $publicFontsPath . '/' . $dmSansItalic
    );
    echo "DM Sans italic instalada.\n";
}

// Intentar registrar Gelion si existe (case-insensitive)
$gelionFonts = [
    ['file' => 'gelion-regular.ttf', 'weight' => 'normal', 'name' => 'Gelion normal'],
    ['file' => 'gelion-bold.ttf', 'weight' => 'bold', 'name' => 'Gelion bold'],
    ['file' => 'gelion-light.ttf', 'weight' => '300', 'name' => 'Gelion light']
];

foreach ($gelionFonts as $gelion) {
    $found = findFileCI($publicFontsPath, $gelion['file']);
    if ($found !== false) {
        $path = $publicFontsPath . '/' . $found;
        $fontMetrics->registerFont(
            ['family' => 'gelion', 'style' => 'normal', 'weight' => $gelion['weight']],
            $path
        );
        echo $gelion['name'] . " instalada. (archivo: $found)\n";
    } else {
        echo $gelion['name'] . " NO encontrada (opcional).\n";
    }
}

// Guardar el fontMetrics para que dompdf escriba installed-fonts.json
$fontMetrics->saveFontFamilies();

echo "\n¡Fuentes instaladas correctamente!\n";

// Mostrar contenido del archivo installed-fonts.json generado por dompdf
$installedFonts = $fontPath . '/installed-fonts.json';
if (file_exists($installedFonts)) {
    echo "\nContenido de installed-fonts.json:\n";
    echo file_get_contents($installedFonts) . "\n";
} else {
    echo "\nADVERTENCIA: installed-fonts.json no fue creado.\n";
    echo "Listando archivos en storage/fonts:\n";
    $files = glob($fontPath . '/*');
    foreach ($files as $file) {
        echo "  - " . basename($file) . "\n";
    }
}
