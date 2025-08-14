<?php
// Test PHP setup
echo "✅ PHP is working!\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Current directory: " . __DIR__ . "\n";

// Test autoloader
require_once 'vendor/autoload.php';
echo "✅ Composer autoloader working!\n";

// Test a simple function
function testFunction($name) {
    return "Hello, $name! PHP development is ready in VS Code.";
}

echo testFunction("Developer") . "\n";
?>
