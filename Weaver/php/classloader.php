<?php
// Project class loader for Weaver namespace. Composer vendor autoload is optional.

spl_autoload_register(function(string $class): void {
    $prefix = 'Weaver\\';
    $len = strlen($prefix);
    if (strncmp($class, $prefix, $len) !== 0) { return; }
    $relative = substr($class, $len);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) { require $file; }
});

// Optionally include Composer vendor autoloader if present.
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($vendorAutoload)) { require $vendorAutoload; }
