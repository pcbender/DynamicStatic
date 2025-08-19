<?php
// Single project autoloader (Composer vendor directory may be excluded in deployment).

spl_autoload_register(function(string $class): void {
    $prefix = 'Weaver\\';
    $len = strlen($prefix);
    if (strncmp($class, $prefix, $len) !== 0) { return; }
    $relative = substr($class, $len);
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) { require $file; }
});

// NOTE: Composer vendor autoload not automatically included here by design.
