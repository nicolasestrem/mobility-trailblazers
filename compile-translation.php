<?php
/**
 * Simple translation compiler
 * Compiles .po files to .mo files
 */

// Include WordPress functions if available
if (file_exists('wp-load.php')) {
    require_once 'wp-load.php';
} elseif (file_exists('../../../wp-load.php')) {
    require_once '../../../wp-load.php';
}

function compile_po_to_mo($po_file, $mo_file) {
    if (!file_exists($po_file)) {
        echo "Error: PO file not found: $po_file\n";
        return false;
    }

    $po_content = file_get_contents($po_file);
    if ($po_content === false) {
        echo "Error: Could not read PO file: $po_file\n";
        return false;
    }

    // Parse PO file
    $translations = [];
    $lines = explode("\n", $po_content);
    $current_msgid = '';
    $current_msgstr = '';
    $in_msgid = false;
    $in_msgstr = false;

    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || $line[0] === '#') {
            // Handle end of translation block
            if (!empty($current_msgid) && !empty($current_msgstr)) {
                $translations[$current_msgid] = $current_msgstr;
            }
            $current_msgid = '';
            $current_msgstr = '';
            $in_msgid = false;
            $in_msgstr = false;
            continue;
        }

        if (strpos($line, 'msgid ') === 0) {
            // Handle end of previous translation
            if (!empty($current_msgid) && !empty($current_msgstr)) {
                $translations[$current_msgid] = $current_msgstr;
            }
            $current_msgid = substr($line, 6);
            $current_msgid = trim($current_msgid, '"');
            $current_msgstr = '';
            $in_msgid = true;
            $in_msgstr = false;
        } elseif (strpos($line, 'msgstr ') === 0) {
            $current_msgstr = substr($line, 7);
            $current_msgstr = trim($current_msgstr, '"');
            $in_msgid = false;
            $in_msgstr = true;
        } elseif ($line[0] === '"' && $in_msgid) {
            $current_msgid .= trim($line, '"');
        } elseif ($line[0] === '"' && $in_msgstr) {
            $current_msgstr .= trim($line, '"');
        }
    }

    // Handle last translation
    if (!empty($current_msgid) && !empty($current_msgstr)) {
        $translations[$current_msgid] = $current_msgstr;
    }

    // Create MO file content
    $mo_content = '';
    
    // MO file header
    $mo_content .= pack('V', 0x950412de); // Magic number
    $mo_content .= pack('V', 0); // Version
    $mo_content .= pack('V', count($translations)); // Number of strings
    $mo_content .= pack('V', 28); // Offset of key table
    $mo_content .= pack('V', 28 + count($translations) * 8); // Offset of value table

    $keys = '';
    $values = '';
    $key_offsets = [];
    $value_offsets = [];

    foreach ($translations as $key => $value) {
        if (empty($key) || empty($value)) continue;
        
        $key_offsets[] = [strlen($key), strlen($keys)];
        $keys .= $key . "\0";
        
        $value_offsets[] = [strlen($value), strlen($values)];
        $values .= $value . "\0";
    }

    // Write key table
    foreach ($key_offsets as $offset) {
        $mo_content .= pack('V', $offset[0]); // Length
        $mo_content .= pack('V', 28 + count($translations) * 16 + $offset[1]); // Offset
    }

    // Write value table
    foreach ($value_offsets as $offset) {
        $mo_content .= pack('V', $offset[0]); // Length
        $mo_content .= pack('V', 28 + count($translations) * 16 + strlen($keys) + $offset[1]); // Offset
    }

    // Write keys and values
    $mo_content .= $keys . $values;

    if (file_put_contents($mo_file, $mo_content) === false) {
        echo "Error: Could not write MO file: $mo_file\n";
        return false;
    }

    echo "Successfully compiled $po_file to $mo_file\n";
    return true;
}

// Compile German translations
$po_file = __DIR__ . '/languages/mobility-trailblazers-de_DE.po';
$mo_file = __DIR__ . '/languages/mobility-trailblazers-de_DE.mo';

echo "Compiling translations...\n";
if (compile_po_to_mo($po_file, $mo_file)) {
    echo "Translation compilation completed successfully!\n";
} else {
    echo "Translation compilation failed!\n";
}