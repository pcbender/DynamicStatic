<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Development Server Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #f0fff0; padding: 10px; border-radius: 5px; }
        .info { color: blue; background: #f0f8ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .server-info { background: #fff8dc; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🚀 PHP Development Server Working!</h1>
    
    <div class="success">
        ✅ PHP is executing successfully!
    </div>
    
    <div class="server-info">
        <h3>Server Information:</h3>
        <ul>
            <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
            <li><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
            <li><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></li>
            <li><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></li>
            <li><strong>HTTP Host:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></li>
        </ul>
    </div>
    
    <div class="info">
        <h3>📝 Dynamic Content Test:</h3>
        <p>Current timestamp: <strong><?php echo time(); ?></strong></p>
        <p>Random number: <strong><?php echo rand(1, 1000); ?></strong></p>
        <p>Refresh the page to see the values change!</p>
    </div>
    
    <div class="info">
        <h3>🔧 Development Tools Available:</h3>
        <ul>
            <li><strong>PHP Server Extension:</strong> Right-click on this file → "PHP Server: Serve project"</li>
            <li><strong>VS Code Task:</strong> Ctrl+Shift+P → "Tasks: Run Task" → "Start PHP Server"</li>
            <li><strong>Built-in PHP Server:</strong> <code>php -S localhost:8080</code> in terminal</li>
        </ul>
    </div>
    
    <?php
    // Test PHP functionality
    echo "<div class='info'>";
    echo "<h3>🧪 PHP Features Test:</h3>";
    echo "<ul>";
    echo "<li>Date/Time: " . date('l, F j, Y') . "</li>";
    echo "<li>Math: 5 + 3 = " . (5 + 3) . "</li>";
    echo "<li>String: " . strtoupper("hello world") . "</li>";
    echo "<li>Array: " . implode(", ", ["PHP", "VS Code", "Development"]) . "</li>";
    echo "</ul>";
    echo "</div>";
    ?>
</body>
</html>
