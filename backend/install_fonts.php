<?php

require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('fontDir', storage_path('fonts'));
$options->set('fontCache', storage_path('fonts'));

$dompdf = new Dompdf($options);
$fontMetrics = $dompdf->getFontMetrics();

// Registrar DM Sans
$fontMetrics->registerFont(
    ['family' => 'DM Sans', 'style' => 'normal', 'weight' => 'normal'],
    public_path('fonts/DMSans-VariableFont.ttf')
);

$fontMetrics->registerFont(
    ['family' => 'DM Sans', 'style' => 'italic', 'weight' => 'normal'],
    public_path('fonts/DMSans-Italic-VariableFont.ttf')
);

// Registrar Gelion
$fontMetrics->registerFont(
    ['family' => 'Gelion', 'style' => 'normal', 'weight' => 'normal'],
    public_path('fonts/gelion-Regular.ttf')
);

$fontMetrics->registerFont(
    ['family' => 'Gelion', 'style' => 'normal', 'weight' => 'bold'],
    public_path('fonts/gelion-Bold.ttf')
);

$fontMetrics->registerFont(
    ['family' => 'Gelion', 'style' => 'normal', 'weight' => '300'],
    public_path('fonts/gelion-Light.ttf')
);

echo "Fuentes instaladas correctamente!\n";
