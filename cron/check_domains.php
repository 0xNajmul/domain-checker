<?php
// cron/check_domains.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/whois.php';
require_once __DIR__ . '/../includes/utils.php';

// Set time limit to unlimited
set_time_limit(0);

// Get database connection
$pdo = getDbConnection();

// Get all domains
$stmt = $pdo->query("SELECT id, name FROM domains");
$domains = $stmt->fetchAll();

// Log file
$logFile = __DIR__ . '/domain_check.log';
$log = [];

// Update each domain's WHOIS information
foreach ($domains as $domain) {
    try {
        $startTime = microtime(true);
        $domainName = $domain['name'];
        
        // Log start
        $log[] = date('[Y-m-d H:i:s]') . " Checking: $domainName";
        
        // Ensure domain is normalized before checking
        $normalizedDomain = normalizeDomain($domainName);
        
        // Get WHOIS info
        $whoisInfo = getWhoisInfo($normalizedDomain);
        
        // Prepare update data
        $updateData = [
            'available' => $whoisInfo['available'] ? 1 : 0,
            'expiryDate' => $whoisInfo['expiryDate'],
            'whoisRaw' => $whoisInfo['whoisRaw'],
            'id' => $domain['id']
        ];
        
        // Update domain in database
        $stmt = $pdo->prepare("
            UPDATE domains 
            SET available = :available, 
                expiryDate = :expiryDate, 
                whoisRaw = :whoisRaw,
                updatedAt = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute($updateData);
        
        // Calculate processing time
        $processingTime = round(microtime(true) - $startTime, 2);
        
        // Log success
        $status = $whoisInfo['available'] ? 'Available' : 'Registered';
        $expiry = $whoisInfo['expiryDate'] ?: 'N/A';
        $log[] = sprintf("  %s | Status: %-9s | Expiry: %-10s | Time: %ss", 
            $domainName, $status, $expiry, $processingTime);
        
        // Add a small delay to avoid overwhelming WHOIS servers
        sleep(1);
        
    } catch (Exception $e) {
        $errorMsg = "Error updating $domainName: " . $e->getMessage();
        $log[] = "  %s $errorMsg";
        error_log($errorMsg);
    }
}

// Add completion message
$log[] = "Domain check completed at " . date('Y-m-d H:i:s');
$log[] = str_repeat("-", 80) . "\n";

// Save log to file
file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

// Output log to console
echo implode("\n", $log) . "\n";
