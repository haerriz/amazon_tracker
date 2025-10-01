<?php
header('Content-Type: text/html');

class UnitTester {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    
    public function test($name, $callback) {
        $this->tests[] = ['name' => $name, 'callback' => $callback];
    }
    
    public function run() {
        echo "<h2>Running Unit Tests...</h2>\n";
        
        foreach ($this->tests as $test) {
            echo "<div class='test-case'>";
            echo "<h3>Testing: {$test['name']}</h3>";
            
            try {
                $result = call_user_func($test['callback']);
                if ($result === true) {
                    echo "<div class='success'>✓ PASSED</div>";
                    $this->passed++;
                } else {
                    echo "<div class='error'>✗ FAILED: $result</div>";
                    $this->failed++;
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ ERROR: " . $e->getMessage() . "</div>";
                $this->failed++;
            }
            
            echo "</div>\n";
        }
        
        echo "<div class='summary'>";
        echo "<h3>Test Summary</h3>";
        echo "<p>Passed: {$this->passed}</p>";
        echo "<p>Failed: {$this->failed}</p>";
        echo "<p>Total: " . ($this->passed + $this->failed) . "</p>";
        echo "</div>";
    }
}

function testDatabaseConnection() {
    try {
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        return $db ? true : "Database connection failed";
    } catch (Exception $e) {
        return "Database error: " . $e->getMessage();
    }
}

function testAPIEndpoints() {
    $endpoints = [
        'https://amazon-tracker.haerriz.com/backend/api/products.php',
        'https://amazon-tracker.haerriz.com/backend/debug.php'
    ];
    
    foreach ($endpoints as $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return "Endpoint $url returned HTTP $httpCode";
        }
    }
    
    return true;
}

function testFilePermissions() {
    $files = [
        '../api/products.php',
        '../api/realtime_scraper.php',
        '../config/database.php'
    ];
    
    foreach ($files as $file) {
        if (!file_exists($file)) {
            return "File missing: $file";
        }
        
        if (!is_readable($file)) {
            return "File not readable: $file";
        }
    }
    
    return true;
}

$tester = new UnitTester();
$tester->test('Database Connection', 'testDatabaseConnection');
$tester->test('API Endpoints', 'testAPIEndpoints');
$tester->test('File Permissions', 'testFilePermissions');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Unit Tests</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-case { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .summary { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Unit Tests</h1>
    <?php $tester->run(); ?>
</body>
</html>