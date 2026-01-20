<?php
/**
 * Composer Autoloader
 * PSR-4 autoloading for PlanWise classes
 */

spl_autoload_register(function ($class) {
    // Base directory for the namespace prefix
    $baseDir = __DIR__ . '/../';
    
    // Define namespace to directory mapping
    $prefixes = [
        'Classes\\' => $baseDir . 'classes/',
        'Controllers\\' => $baseDir . 'controllers/',
        'Middleware\\' => $baseDir . 'middleware/',
        'Helpers\\' => $baseDir . 'helpers/',
    ];
    
    // Try to load from mapped directories
    foreach ($prefixes as $prefix => $directory) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        
        $relativeClass = substr($class, $len);
        $file = $directory . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    
    // Fallback: try loading from classes directory without namespace
    $classFile = $baseDir . 'classes/' . $class . '.php';
    if (file_exists($classFile)) {
        require $classFile;
        return;
    }
    
    // Fallback: try loading from controllers directory without namespace
    $controllerFile = $baseDir . 'controllers/' . $class . '.php';
    if (file_exists($controllerFile)) {
        require $controllerFile;
        return;
    }
    
    // Fallback: try loading from middleware directory without namespace
    $middlewareFile = $baseDir . 'middleware/' . $class . '.php';
    if (file_exists($middlewareFile)) {
        require $middlewareFile;
        return;
    }
});

// Load helper functions
$helperFiles = [
    __DIR__ . '/../helpers/functions.php',
    __DIR__ . '/../helpers/sanitize.php',
    __DIR__ . '/../helpers/response.php',
];

foreach ($helperFiles as $file) {
    if (file_exists($file) && filesize($file) > 0) {
        require_once $file;
    }
}
