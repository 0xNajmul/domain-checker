<?php
// check_domains.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/whois.php';

$pdo = getDbConnection();
$stmt = $pdo->query("SELECT * FROM domains");
$domains = $stmt->fetchAll();

foreach ($domains as $domain) {
    $whoisInfo = getWhoisInfo($domain['name']);
    
    $stmt = $pdo->prepare("
        UPDATE domains 
        SET available = ?, 
            expiryDate = ?, 
            whoisRaw = ?,
            updatedAt = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $whoisInfo['available'] ? 1 : 0,
        $whoisInfo['expiryDate'],
        $whoisInfo['whoisRaw'],
        $domain['id']
    ]);
    
    echo "Checked {$domain['name']}: " . 
         ($whoisInfo['available'] ? 'Available' : 'Registered') . 
         ($whoisInfo['expiryDate'] ? " (Expires: {$whoisInfo['expiryDate']})" : '') . 
         "\n";
}