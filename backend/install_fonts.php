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

$options = new Options();
$options->set('fontDir', $fontPath);
$options->set('fontCache', $fontPath);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$fontMetrics = $dompdf->getFontMetrics();

$publicFontsPath = __DIR__ . '/public/fonts';

// Registrar DM Sans
$fontMetrics->registerFont(
    ['family' => 'DM Sans', 'style' => 'normal', 'weight' => 'normal'],
    $publicFontsPath . '/DMSans-VariableFont.ttf'
);

$fontMetrics->registerFont(
    ['family' => 'DM Sans', 'style' => 'italic', 'weight' => 'normal'],
    $publicFontsPath . '/DMSans-Italic-VariableFont.ttf'
);

// Registrar Gelion
$fontMetrics->registerFont(
    ['family' => 'Gelion', 'style' => 'normal', 'weight' => 'normal'],
    $publicFontsPath . '/gelion-Regular.ttf'
);

$fontMetrics->registerFont(
    ['family' => 'Gelion', 'style' => 'normal', 'weight' => 'bold'],
    $publicFontsPath . '/gelion-Bold.ttf'
);

$fontMetrics->registerFont(
    ['family' => 'Gelion', 'style' => 'normal', 'weight' => '300'],
    $publicFontsPath . '/gelion-Light.ttf'
);

echo "Fuentes instaladas correctamente!\n";
echo "Verifica el archivo: " . $fontPath . "/installed-fonts.json\n";
