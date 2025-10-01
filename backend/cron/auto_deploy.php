<?php
/**
 * Auto-deployment script that runs migrations on code push
 */

// Run migrations automatically
require_once '../migrations/auto_migrate.php';

try {
    $migration = new AutoMigration();
    $migration->runMigrations();
    
    // Log successful deployment
    $logFile = '../logs/deployment.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Auto-deployment completed successfully\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    echo "Auto-deployment completed successfully!";
    
} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Auto-deployment failed: " . $e->getMessage() . "\n";
    file_put_contents('../logs/deployment.log', $logEntry, FILE_APPEND | LOCK_EX);
    
    echo "Auto-deployment failed: " . $e->getMessage();
}
?>