<?php

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Crear la carpeta storage/fonts si no existe
$fontPath = __DIR__ . '/storage/fonts';
if (!file_exists($fontPath)) {
    mkdir($fontPath, 0775, true);
    echo "Carpeta storage/fonts creada.\n";
}

// Limpiar archivos de caché de fuentes existentes
$files = glob($fontPath . '/*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
        echo "Eliminado: " . basename($file) . "\n";
    }
}

$options = new Options();
$options->set('fontDir', $fontPath);
$options->set('fontCache', $fontPath);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$fontMetrics = $dompdf->getFontMetrics();

$publicFontsPath = __DIR__ . '/public/fonts';

// Verificar que las fuentes existan
$fontsToInstall = [
    'DMSans-VariableFont.ttf',
    'DMSans-Italic-VariableFont.ttf',
    'gelion-Regular.ttf',
    'gelion-Bold.ttf',
    'gelion-Light.ttf'
];

foreach ($fontsToInstall as $font) {
    $path = $publicFontsPath . '/' . $font;
    if (!file_exists($path)) {
        echo "ERROR: No se encontró: $path\n";
        exit(1);
    }
}

echo "\nTodas las fuentes encontradas. Instalando...\n\n";

// Registrar DM Sans
$fontMetrics->registerFont(
    ['family' => 'dm sans', 'style' => 'normal', 'weight' => 'normal'],
    $publicFontsPath . '/DMSans-VariableFont.ttf'
);
echo "DM Sans normal instalada.\n";

$fontMetrics->registerFont(
    ['family' => 'dm sans', 'style' => 'normal', 'weight' => 'bold'],
    $publicFontsPath . '/DMSans-VariableFont.ttf'
);
echo "DM Sans bold instalada.\n";

$fontMetrics->registerFont(
    ['family' => 'dm sans', 'style' => 'italic', 'weight' => 'normal'],
    $publicFontsPath . '/DMSans-Italic-VariableFont.ttf'
);
echo "DM Sans italic instalada.\n";

// Registrar Gelion
$fontMetrics->registerFont(
    ['family' => 'gelion', 'style' => 'normal', 'weight' => 'normal'],
    $publicFontsPath . '/gelion-Regular.ttf'
);
echo "Gelion normal instalada.\n";

$fontMetrics->registerFont(
    ['family' => 'gelion', 'style' => 'normal', 'weight' => 'bold'],
    $publicFontsPath . '/gelion-Bold.ttf'
);
echo "Gelion bold instalada.\n";

$fontMetrics->registerFont(
    ['family' => 'gelion', 'style' => 'normal', 'weight' => '300'],
    $publicFontsPath . '/gelion-Light.ttf'
);
echo "Gelion light instalada.\n";

echo "\n¡Fuentes instaladas correctamente!\n";
echo "Verifica el archivo: " . $fontPath . "/installed-fonts.json\n";

// Mostrar contenido del archivo installed-fonts.json
$installedFonts = $fontPath . '/installed-fonts.json';
if (file_exists($installedFonts)) {
    echo "\nContenido de installed-fonts.json:\n";
    echo file_get_contents($installedFonts) . "\n";
}
