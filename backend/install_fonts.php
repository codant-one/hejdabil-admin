<?php
/**
 * Script para instalar fuentes en dompdf.
 * Copia los TTF a storage/fonts y genera installed-fonts.json manualmente.
 */

require_once 'vendor/autoload.php';

use FontLib\Font;

// Rutas
$fontPath = __DIR__ . '/storage/fonts';
$publicFontsPath = __DIR__ . '/public/fonts';
$installedFontsFile = $fontPath . '/installed-fonts.json';

// Crear la carpeta storage/fonts si no existe
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

// Helper: genera archivo .ufm desde TTF
function generateUFM($ttfPath, $ufmPath) {
    try {
        $font = Font::load($ttfPath);
        if ($font) {
            $font->parse();
            
            // Generar datos UFM
            $ufmData = [];
            $ufmData['cw'] = [];
            
            // Obtener métricas básicas
            $head = $font->getData("head");
            $hhea = $font->getData("hhea");
            $post = $font->getData("post");
            $os2 = $font->getData("OS/2");
            
            $unitsPerEm = $head['unitsPerEm'] ?? 1000;
            $scale = 1000 / $unitsPerEm;
            
            // Obtener anchos de caracteres
            $hmtx = $font->getData("hmtx");
            $cmap = $font->getUnicodeCharMap();
            
            if ($cmap) {
                foreach ($cmap as $unicode => $glyphIndex) {
                    if ($unicode >= 0 && $unicode <= 65535 && isset($hmtx[$glyphIndex])) {
                        $width = round($hmtx[$glyphIndex][0] * $scale);
                        $ufmData['cw'][$unicode] = $width;
                    }
                }
            }
            
            // Métricas de fuente
            $ufmData['Ascender'] = isset($hhea['ascent']) ? round($hhea['ascent'] * $scale) : 750;
            $ufmData['Descender'] = isset($hhea['descent']) ? round($hhea['descent'] * $scale) : -250;
            $ufmData['UnderlinePosition'] = isset($post['underlinePosition']) ? round($post['underlinePosition'] * $scale) : -100;
            $ufmData['UnderlineThickness'] = isset($post['underlineThickness']) ? round($post['underlineThickness'] * $scale) : 50;
            $ufmData['MissingWidth'] = 500;
            
            // Guardar como JSON (formato que dompdf puede leer)
            file_put_contents($ufmPath, json_encode($ufmData));
            
            $font->close();
            return true;
        }
    } catch (Exception $e) {
        echo "Error generando UFM para $ttfPath: " . $e->getMessage() . "\n";
    }
    return false;
}

echo "\nInstalando fuentes...\n\n";

$installedFonts = [];

// ===== DM Sans =====
$dmSansNormal = findFileCI($publicFontsPath, 'DMSans-VariableFont.ttf');
$dmSansItalic = findFileCI($publicFontsPath, 'DMSans-Italic-VariableFont.ttf');

if ($dmSansNormal) {
    $srcPath = $publicFontsPath . '/' . $dmSansNormal;
    $destName = 'dm_sans';
    $destTtf = $fontPath . '/' . $destName . '.ttf';
    $destUfm = $fontPath . '/' . $destName . '.ufm.json';
    
    copy($srcPath, $destTtf);
    generateUFM($srcPath, $destUfm);
    
    $installedFonts['dm sans'] = [
        'normal' => $destName,
        'bold' => $destName,
        '600' => $destName
    ];
    echo "DM Sans instalada.\n";
}

if ($dmSansItalic) {
    $srcPath = $publicFontsPath . '/' . $dmSansItalic;
    $destName = 'dm_sans_italic';
    $destTtf = $fontPath . '/' . $destName . '.ttf';
    $destUfm = $fontPath . '/' . $destName . '.ufm.json';
    
    copy($srcPath, $destTtf);
    generateUFM($srcPath, $destUfm);
    
    $installedFonts['dm sans']['italic'] = $destName;
    $installedFonts['dm sans']['bold_italic'] = $destName;
    echo "DM Sans Italic instalada.\n";
}

// ===== Gelion =====
$gelionFiles = [
    ['search' => 'gelion-regular.ttf', 'dest' => 'gelion', 'weight' => 'normal'],
    ['search' => 'gelion-bold.ttf', 'dest' => 'gelion_bold', 'weight' => 'bold'],
    ['search' => 'gelion-light.ttf', 'dest' => 'gelion_light', 'weight' => '300']
];

$installedFonts['gelion'] = [];

foreach ($gelionFiles as $gf) {
    $found = findFileCI($publicFontsPath, $gf['search']);
    if ($found) {
        $srcPath = $publicFontsPath . '/' . $found;
        $destName = $gf['dest'];
        $destTtf = $fontPath . '/' . $destName . '.ttf';
        $destUfm = $fontPath . '/' . $destName . '.ufm.json';
        
        copy($srcPath, $destTtf);
        generateUFM($srcPath, $destUfm);
        
        $installedFonts['gelion'][$gf['weight']] = $destName;
        echo "Gelion {$gf['weight']} instalada. (archivo: $found)\n";
    } else {
        echo "Gelion {$gf['weight']} NO encontrada.\n";
    }
}

// Si no hay gelion normal pero hay otro, usar ese como fallback
if (empty($installedFonts['gelion']['normal']) && !empty($installedFonts['gelion'])) {
    $first = reset($installedFonts['gelion']);
    $installedFonts['gelion']['normal'] = $first;
}

// Eliminar familias vacías
foreach ($installedFonts as $fam => $variants) {
    if (empty($variants)) {
        unset($installedFonts[$fam]);
    }
}

// Escribir installed-fonts.json
$json = json_encode($installedFonts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($installedFontsFile, $json);

echo "\n¡Fuentes instaladas correctamente!\n";
echo "Archivos en storage/fonts:\n";
$files = glob($fontPath . '/*');
foreach ($files as $file) {
    echo "  - " . basename($file) . "\n";
}

echo "\nContenido de installed-fonts.json:\n";
echo $json . "\n";
