<?php

// Simplified autoload.php for PhpOffice/PhpSpreadsheet
// This handles the basic autoloading needs for the plugin

// Register autoloader
spl_autoload_register(function($className) {
    $vendorDir = __DIR__;
    
    // PHPSpreadsheet autoloading
    if (strpos($className, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        $classPath = str_replace('PhpOffice\\PhpSpreadsheet\\', '', $className);
        $classPath = str_replace('\\', '/', $classPath);
        $file = $vendorDir . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/' . $classPath . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Psr autoloading
    if (strpos($className, 'Psr\\') === 0) {
        $classPath = str_replace('Psr\\', '', $className);
        $classPath = str_replace('\\', '/', $classPath);
        // Try different PSR directories
        $possiblePaths = [
            $vendorDir . '/psr/simple-cache/src/' . $classPath . '.php',
            $vendorDir . '/psr/http-client/src/' . $classPath . '.php',
            $vendorDir . '/psr/http-factory/src/' . $classPath . '.php',
            $vendorDir . '/psr/http-message/src/' . $classPath . '.php'
        ];
        
        foreach ($possiblePaths as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Other vendor autoloading patterns
    $namespacePrefixes = [
        'Maennchen\\ZipStream\\' => 'maennchen/zipstream-php/src/',
        'Matrix\\' => 'markbaker/matrix/classes/src/',
        'Complex\\' => 'markbaker/complex/classes/src/',
        'Ezyang\\HTMLPurifier\\' => 'ezyang/htmlpurifier/library/HTMLPurifier/'
    ];
    
    foreach ($namespacePrefixes as $prefix => $baseDir) {
        if (strpos($className, $prefix) === 0) {
            $classPath = str_replace($prefix, '', $className);
            $classPath = str_replace('\\', '/', $classPath);
            $file = $vendorDir . '/' . $baseDir . $classPath . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Load essential files for PhpSpreadsheet
$essentialFiles = [
    __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/IOFactory.php',
    __DIR__ . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php'
];

foreach ($essentialFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}