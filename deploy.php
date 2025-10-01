<?php
/**
 * Deployment hook - runs migrations automatically on git push
 */

// Run auto-deployment
require_once 'backend/cron/auto_deploy.php';
?>