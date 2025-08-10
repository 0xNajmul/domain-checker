<?php
// includes/whois.php
function getWhoisInfo($domain) {
    // Initialize response array with consistent keys
    $info = [
        'available' => false,
        'expiryDate' => null,
        'whoisRaw' => '',
        'error' => null
    ];
    
    error_log("=== Starting WHOIS lookup for domain: $domain ===");
    
    try {
        // Clean and validate domain
        $domain = trim($domain);
        error_log("Trimmed domain: '$domain'");
        
        if (empty($domain)) {
            $error = 'Domain cannot be empty';
            error_log("VALIDATION ERROR: $error");
            throw new Exception($error);
        }
        
        // Remove www. prefix if present
        $domain = preg_replace('~^www\.~i', '', $domain);
        error_log("Domain after removing www: '$domain'");
        
        // Validate domain format
        if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i', $domain)) {
            $error = 'Invalid domain format';
            error_log("VALIDATION ERROR: $error - Domain: '$domain'");
            throw new Exception($error);
        }
        
        // Common WHOIS servers for different TLDs
        $whois_servers = [
            'com' => 'whois.verisign-grs.com',
            'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org',
            'info' => 'whois.afilias.net',
            'app' => 'whois.nic.google',
            'io' => 'whois.nic.io',
            'co' => 'whois.nic.co',
            'dev' => 'whois.nic.google',
            'ai' => 'whois.nic.ai',
            'me' => 'whois.nic.me',
            'tv' => 'whois.nic.tv',
            'us' => 'whois.nic.us',
            'uk' => 'whois.nic.uk',
            'ca' => 'whois.cira.ca',
            'au' => 'whois.audns.net.au',
            'de' => 'whois.denic.de',
            'fr' => 'whois.nic.fr',
            'es' => 'whois.nic.es',
            'it' => 'whois.nic.it',
            'nl' => 'whois.domain-registry.nl',
            'ru' => 'whois.tcinet.ru',
            'jp' => 'whois.jprs.jp',
            'cn' => 'whois.cnnic.cn',
            'in' => 'whois.registry.in',
            'br' => 'whois.registro.br',
            'mx' => 'whois.mx',
            'nz' => 'whois.srs.net.nz'
        ];
        
        // Extract TLD and validate domain structure
        $domain_parts = explode('.', $domain);
        error_log("Domain parts: " . print_r($domain_parts, true));
        
        if (count($domain_parts) < 2) {
            $error = 'Invalid domain format: must have at least one dot';
            error_log("VALIDATION ERROR: $error");
            throw new Exception($error);
        }
        
        $tld = strtolower(end($domain_parts));
        error_log("Extracted TLD: $tld");
        
        // Get the appropriate WHOIS server with fallback to IANA
        $whois_server = $whois_servers[$tld] ?? 'whois.iana.org';
        error_log("Using WHOIS server: $whois_server");
        
        // For IANA, we need to query in two steps
        $is_iana = ($whois_server === 'whois.iana.org');
        error_log("Is IANA server: " . ($is_iana ? 'Yes' : 'No'));
        
        // Connect to the WHOIS server with timeout
        $timeout = 10; // seconds
        error_log("Attempting to connect to WHOIS server: $whois_server:43 (timeout: {$timeout}s)");
        
        $fp = @fsockopen($whois_server, 43, $errno, $errstr, $timeout);
        
        if (!$fp) {
            error_log("First connection attempt failed: $errstr ($errno)");
            
            // Try one more time in case of temporary connection issue
            error_log("Retrying connection to WHOIS server...");
            $fp = @fsockopen($whois_server, 43, $errno, $errstr, $timeout);
            
            if (!$fp) {
                $error = "Could not connect to WHOIS server ($whois_server): $errstr ($errno)";
                error_log("CONNECTION ERROR: $error");
                throw new Exception($error);
            }
        }
        
        error_log("Successfully connected to WHOIS server");
        
        // Set timeout for reading
        stream_set_timeout($fp, $timeout);
        error_log("Set socket read timeout to $timeout seconds");
        
        // Send the domain query with proper line ending
        $query = "$domain\r\n";
        error_log("Sending query: '$query'");
        $bytes_written = fwrite($fp, $query);
        
        if ($bytes_written === false) {
            $error = "Failed to send query to WHOIS server";
            error_log("WRITE ERROR: $error");
            throw new Exception($error);
        }
        
        error_log("Successfully sent $bytes_written bytes to WHOIS server");
        
        // For IANA, we need to do a second lookup
        if ($is_iana) {
            error_log("Performing IANA lookup to find actual WHOIS server...");
            $response = '';
            $chunk_count = 0;
            
            while (!feof($fp)) {
                $chunk = fread($fp, 4096);
                $chunk_count++;
                
                if ($chunk === false) {
                    error_log("Read error on chunk $chunk_count");
                    break;
                }
                
                $response .= $chunk;
                
                // Check for timeout
                $meta = stream_get_meta_data($fp);
                if ($meta['timed_out']) {
                    error_log("Read timed out after $chunk_count chunks");
                    break;
                }
            }
            
            error_log("Received IANA response (chunks: $chunk_count, length: " . strlen($response) . ")");
            
            // Try to find the actual WHOIS server in the IANA response
            error_log("Searching for WHOIS server in IANA response...");
            if (preg_match('/whois:\s+([^\s]+)/i', $response, $matches)) {
                $whois_server = trim($matches[1]);
                error_log("Found WHOIS server in IANA response: $whois_server");
                
                fclose($fp);
                
                // Connect to the actual WHOIS server
                error_log("Connecting to actual WHOIS server: $whois_server:43");
                $fp = @fsockopen($whois_server, 43, $errno, $errstr, $timeout);
                
                if ($fp) {
                    error_log("Successfully connected to $whois_server");
                    $query = "$domain\r\n";
                    error_log("Sending query to $whois_server: '$query'");
                    $bytes = fwrite($fp, $query);
                    
                    if ($bytes === false) {
                        error_log("Failed to send query to $whois_server");
                        return [
                            'available' => false,
                            'expiryDate' => null,
                            'whoisRaw' => $response,
                            'error' => 'Failed to send query to WHOIS server'
                        ];
                    }
                    
                    error_log("Successfully sent query to $whois_server ($bytes bytes)");
                } else {
                    // If we can't connect to the referred WHOIS server, return the IANA response
                    error_log("Failed to connect to WHOIS server $whois_server: $errstr ($errno)");
                    error_log("Returning IANA response instead");
                    return [
                        'available' => false,
                        'expiryDate' => null,
                        'whoisRaw ' => $response,
                        'error' => "Could not connect to WHOIS server: $errstr"
                    ];
                }
            }
        }
        
        // Read the response
        error_log("Reading response from WHOIS server...");
        $response = '';
        $chunk_count = 0;
        $info = [];
        
        while (!feof($fp)) {
            $chunk = fread($fp, 4096);
            $chunk_count++;
            
            if ($chunk === false) {
                $error = 'Error reading from WHOIS server';
                error_log("READ ERROR: $error");
                throw new Exception($error);
            }
            
            $response .= $chunk;
            
            // Check for timeout
            $meta = stream_get_meta_data($fp);
            if ($meta['timed_out']) {
                $error = 'Connection timed out while reading response';
                error_log("TIMEOUT ERROR: $error");
                throw new Exception($error);
            }
        }
        
        fclose($fp);
        error_log("Received complete response from WHOIS server (chunks: $chunk_count, length: " . strlen($response) . ")");
        
        // Initialize info array with default values
        $info = [
            'available' => false,
            'expiryDate' => null,
            'whoisRaw' => $response,
            'error' => null
        ];
        
        error_log("Checking for domain availability patterns...");
        
        // Check for common "not found" patterns in the response
        $not_found_patterns = [
            '/No match for/i' => 'No match for',
            '/NOT FOUND/i' => 'NOT FOUND',
            '/No Data Found/i' => 'No Data Found',
            '/Domain not found/i' => 'Domain not found',
            '/No entries found/i' => 'No entries found',
            '/Status: *free/i' => 'Status: free',
            '/Status: *available/i' => 'Status: available',
            '/No Object Found/i' => 'No Object Found',
            '/No such domain/i' => 'No such domain'
        ];
        
        foreach ($not_found_patterns as $pattern => $pattern_name) {
            if (preg_match($pattern, $response)) {
                error_log("Found availability pattern: $pattern_name");
                $info['available'] = true;
                break;
            }
        }
        
        error_log("Domain available: " . ($info['available'] ? 'Yes' : 'No'));
        
        // Try to extract expiry date if domain is registered
        if (!$info['available']) {
            error_log("Attempting to extract expiry date...");
            
            // Try different patterns for expiry date
            $expiry_patterns = [
                '/[Ee]xpir(?:ation|y|y date)[\s:]+([^\n]+)/i' => 'Expiry pattern 1',
                '/[Ee]xpir(?:ation|y|y date)[\s:]+(.*?)(?:\n|$)/i' => 'Expiry pattern 2',
                '/Registry Expiry Date:[\s]+([^\n]+)/i' => 'Registry Expiry Date',
                '/Expiration Date:[\s]+([^\n]+)/i' => 'Expiration Date'
            ];
            
            foreach ($expiry_patterns as $pattern => $pattern_name) {
                if (preg_match($pattern, $response, $matches)) {
                    $date_str = trim($matches[1]);
                    error_log("Found date string with $pattern_name: '$date_str'");
                    
                    $expiry_date = strtotime($date_str);
                    if ($expiry_date !== false) {
                        $formatted_date = date('Y-m-d H:i:s', $expiry_date);
                        $info['expiryDate'] = $formatted_date;
                        error_log("Successfully parsed expiry date: $formatted_date");
                        break;
                    } else {
                        error_log("Failed to parse date: '$date_str'");
                    }
                }
            }
            
            if (empty($info['expiryDate'])) {
                error_log("Could not determine expiry date from WHOIS response");
            }
        }
        
        // Check for common "not found" patterns in the response
        $not_found_patterns = [
            '/No match for/i',
            '/NOT FOUND/i',
            '/No Data Found/i',
            '/Domain not found/i',
            '/No entries found/i',
            '/Status: *free/i',
            '/Status: *available/i',
            '/No Object Found/i'
        ];
        
        foreach ($not_found_patterns as $pattern) {
            if (preg_match($pattern, $response)) {
                $info['available'] = true;
                break;
            }
        }
        
        // Try to extract expiry date if domain is registered
        if (!$info['available'] && preg_match('/[Ee]xpir(?:ation|y|y date)[\s:]+(.*?)(?:\n|$)/', $response, $matches)) {
            $expiry_date = strtotime(trim($matches[1]));
            if ($expiry_date !== false) {
                $info['expiryDate'] = date('Y-m-d H:i:s', $expiry_date);
            }
        }
        
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        $info['error'] = $error_msg;
        error_log("=== WHOIS ERROR ===");
        error_log("Domain: $domain");
        error_log("Error: $error_msg");
        error_log("File: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
        error_log("Stack Trace: " . $e->getTraceAsString());
        error_log("=== END WHOIS ERROR ===");
    }
    
    error_log("=== WHOIS LOOKUP COMPLETE ===");
    error_log("Domain: $domain");
    error_log("Available: " . ($info['available'] ? 'Yes' : 'No'));
    error_log("Expiry Date: " . ($info['expiryDate'] ?? 'Not found'));
    error_log("Error: " . ($info['error'] ?? 'None'));
    error_log("Response length: " . strlen($info['whoisRaw']) . " bytes");
    error_log("==================================\n");
    
    return $info;
}