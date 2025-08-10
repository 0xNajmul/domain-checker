<?php
// add_domain.php
error_log("=== Starting add_domain.php ===");

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/whois.php';
require_once __DIR__ . '/includes/utils.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log all input parameters
error_log("Raw input parameters: " . print_r($_GET, true));

$domain = $_GET['domain'] ?? '';
$check = isset($_GET['check']);
error_log("Domain from input: '$domain', Check mode: " . ($check ? 'true' : 'false'));

// Normalize and validate domain
$domain = trim($domain);
if (empty($domain)) {
    $error = 'Domain cannot be empty';
    error_log("Validation error: $error");
    header('Location: index.php?error=' . urlencode($error));
    exit;
}

// Normalize the domain
$normalizedDomain = normalizeDomain($domain);
error_log("Normalized domain: '$normalizedDomain'");

// Validate domain after normalization
if (!isValidDomain($normalizedDomain)) {
    $error = 'Invalid domain format: ' . $normalizedDomain;
    error_log("Validation error: $error");
    header('Location: index.php?error=' . urlencode($error));
    exit;
}

error_log("Domain validation passed");

try {
    $pdo = getDbConnection();
    error_log("Database connection successful");

    // Check if domain already exists (case-insensitive check)
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE LOWER(name) = LOWER(?)");
    error_log("Checking if domain exists in database");
    $stmt->execute([$normalizedDomain]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        error_log("Domain exists in database with ID: " . $existing['id']);
    } else {
        error_log("Domain does not exist in database");
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    throw $e;
}

try {
    error_log("--- Starting WHOIS lookup ---");
    error_log("Calling getWhoisInfo() for domain: " . $normalizedDomain);
    
    // Get WHOIS info
    $whoisInfo = getWhoisInfo($normalizedDomain);
    
    error_log("WHOIS lookup completed. Response keys: " . implode(', ', array_keys($whoisInfo)));
    error_log("WHOIS available: " . ($whoisInfo['available'] ? 'true' : 'false'));
    error_log("WHOIS expiry date: " . ($whoisInfo['expiryDate'] ?? 'null'));
    error_log("WHOIS error: " . ($whoisInfo['error'] ?? 'none'));
    
    // Validate WHOIS info
    error_log("Validating WHOIS response...");
    
    if (isset($whoisInfo['error'])) {
        $error = 'WHOIS lookup failed: ' . $whoisInfo['error'];
        error_log("VALIDATION ERROR: $error");
        throw new Exception($error);
    }
    
    if (!isset($whoisInfo['whoisRaw'])) {
        $error = "Missing WHOIS data in response. Connection to WHOIS server may have failed.";
        error_log("VALIDATION ERROR: $error");
        error_log("WHOIS response: " . print_r($whoisInfo, true));
        throw new Exception($error);
    }
    
    error_log("WHOIS response validation passed");
    
    // Prepare common data
    $domainData = [
        'available' => $whoisInfo['available'] ? 1 : 0,
        'expiryDate' => $whoisInfo['expiryDate'] ?? null,
        'whoisRaw' => $whoisInfo['whoisRaw']
    ];
    
    error_log("Prepared domain data:");
    error_log("- Available: " . ($domainData['available'] ? 'Yes' : 'No'));
    error_log("- Expiry Date: " . ($domainData['expiryDate'] ?? 'Not available'));
    error_log("- WHOIS Raw Length: " . strlen($domainData['whoisRaw']) . " characters");

    if ($existing) {
        // Update existing domain
        error_log("Updating existing domain record");
        try {
            $stmt = $pdo->prepare("
                UPDATE domains 
                SET available = :available,
                    expiryDate = :expiryDate,
                    whoisRaw = :whoisRaw,
                    updatedAt = NOW()
                WHERE id = :id
            ");
            $domainData['id'] = $existing['id'];
            error_log("Executing UPDATE query with data: " . print_r($domainData, true));
            $stmt->execute($domainData);
            $message = 'Domain updated successfully';
            error_log("Domain update successful");
        } catch (PDOException $e) {
            error_log("Database UPDATE error: " . $e->getMessage());
            throw $e;
        }
    } else {
        // Insert new domain
        error_log("Inserting new domain record");
        try {
            $stmt = $pdo->prepare("
                INSERT INTO domains (name, available, expiryDate, whoisRaw, createdAt, updatedAt)
                VALUES (:name, :available, :expiryDate, :whoisRaw, NOW(), NOW())
            ");
            $domainData['whoisRaw'] = $domainData['whois_raw'];
            unset($domainData['whois_raw']);
            $domainData['name'] = $normalizedDomain;
            
            error_log("Executing INSERT query with data: " . print_r($domainData, true));
            $stmt->execute($domainData);
            $message = 'Domain added successfully';
            error_log("Domain insertion successful");
        } catch (PDOException $e) {
            error_log("Database INSERT error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Log success and redirect
    error_log("=== Operation completed successfully ===\n");
    header('Location: index.php?success=' . urlencode($message));
    exit;
    
} catch (Exception $e) {
    // Log detailed error information
    $errorMsg = 'Error processing domain ' . $normalizedDomain . ': ' . $e->getMessage();
    error_log("=== ERROR ===");
    error_log($errorMsg);
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("=== END ERROR ===\n");
    
    // Redirect with error message
    header('Location: index.php?error=' . urlencode('Error processing domain. Please try again.'));
    exit;
}