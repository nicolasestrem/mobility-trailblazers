<?php
/**
 * Compile .po files to .mo files
 * Run this script to compile translations
 */

$po_file = __DIR__ . '/../languages/mobility-trailblazers-de_DE.po';
$mo_file = __DIR__ . '/../languages/mobility-trailblazers-de_DE.mo';

// Read PO file
$po_content = file_get_contents($po_file);
if (!$po_content) {
    die("Could not read PO file\n");
}

// Parse PO file
$translations = [];
$current_msgid = '';
$current_msgstr = '';
$in_msgid = false;
$in_msgstr = false;

$lines = explode("\n", $po_content);
foreach ($lines as $line) {
    $line = trim($line);
    
    if (strpos($line, 'msgid "') === 0) {
        if ($current_msgid && $current_msgstr) {
            $translations[$current_msgid] = $current_msgstr;
        }
        $current_msgid = substr($line, 7, -1);
        $current_msgstr = '';
        $in_msgid = true;
        $in_msgstr = false;
    } elseif (strpos($line, 'msgstr "') === 0) {
        $current_msgstr = substr($line, 8, -1);
        $in_msgid = false;
        $in_msgstr = true;
    } elseif (strpos($line, '"') === 0 && strrpos($line, '"') === strlen($line) - 1) {
        $content = substr($line, 1, -1);
        if ($in_msgid) {
            $current_msgid .= $content;
        } elseif ($in_msgstr) {
            $current_msgstr .= $content;
        }
    }
}

// Add last translation
if ($current_msgid && $current_msgstr) {
    $translations[$current_msgid] = $current_msgstr;
}

// Create MO file structure
$mo_data = pack('L', 0x950412de); // Magic number
$mo_data .= pack('L', 0); // Version
$mo_data .= pack('L', count($translations)); // Number of strings
$mo_data .= pack('L', 28); // Offset of table with original strings
$mo_data .= pack('L', 28 + count($translations) * 8); // Offset of table with translations
$mo_data .= pack('L', 0); // Size of hashing table
$mo_data .= pack('L', 28 + count($translations) * 16); // Offset of hashing table

// Calculate string positions
$current_offset = 28 + count($translations) * 16;
$original_offsets = [];
$translation_offsets = [];

foreach ($translations as $original => $translation) {
    $original_offsets[] = [$current_offset, strlen($original)];
    $current_offset += strlen($original) + 1;
}

foreach ($translations as $original => $translation) {
    $translation_offsets[] = [$current_offset, strlen($translation)];
    $current_offset += strlen($translation) + 1;
}

// Write offset tables
$i = 0;
foreach ($translations as $original => $translation) {
    $mo_data .= pack('L', $original_offsets[$i][1]); // Length of original
    $mo_data .= pack('L', $original_offsets[$i][0]); // Offset of original
    $i++;
}

$i = 0;
foreach ($translations as $original => $translation) {
    $mo_data .= pack('L', $translation_offsets[$i][1]); // Length of translation
    $mo_data .= pack('L', $translation_offsets[$i][0]); // Offset of translation
    $i++;
}

// Write strings
foreach ($translations as $original => $translation) {
    $mo_data .= $original . "\0";
}

foreach ($translations as $original => $translation) {
    $mo_data .= $translation . "\0";
}

// Write MO file
file_put_contents($mo_file, $mo_data);

echo "Successfully compiled {$po_file} to {$mo_file}\n";
echo "Total translations: " . count($translations) . "\n";