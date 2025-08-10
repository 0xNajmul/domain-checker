<?php
// includes/utils.php

/**
 * Normalize domain input by removing protocol (http/https), www, and trailing slashes
 * 
 * @param string $domain The domain to normalize
 * @return string Normalized domain name
 */
function normalizeDomain($domain) {
    // Trim whitespace
    $domain = trim($domain);
    
    // Remove protocol (http:// or https://)
    $domain = preg_replace('~^https?://~i', '', $domain);
    
    // Remove www. prefix if present
    $domain = preg_replace('~^www\.~i', '', $domain);
    
    // Remove any path or query string
    $domain = parse_url('https://' . $domain, PHP_URL_HOST);
    
    // Remove trailing dot if present
    $domain = rtrim($domain, '.');
    
    // Convert to lowercase
    $domain = strtolower($domain);
    
    return $domain;
}

/**
 * Validate if the input is a valid domain name
 * 
 * @param string $domain The domain to validate
 * @return bool True if valid, false otherwise
 */
function isValidDomain($domain) {
    $domain = normalizeDomain($domain);
    return (preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $domain) // Valid chars check
            && preg_match('/^.{1,253}$/', $domain) // Overall length check
            && preg_match('/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $domain)); // Length of each label
}
