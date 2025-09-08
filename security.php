<?php
// Security configuration and headers

// Set security headers
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self';");
    
    // Prevent caching of sensitive pages
    if (basename($_SERVER['PHP_SELF']) === 'login.php' || basename($_SERVER['PHP_SELF']) === 'auth.php') {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting for login attempts
function checkLoginRateLimit($username) {
    $attempts_file = 'login_attempts.json';
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes
    
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true);
    } else {
        $attempts = [];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = $ip . '_' . $username;
    
    if (isset($attempts[$key])) {
        if ($attempts[$key]['count'] >= $max_attempts && 
            (time() - $attempts[$key]['time']) < $lockout_time) {
            return false; // Still locked out
        } elseif ((time() - $attempts[$key]['time']) >= $lockout_time) {
            unset($attempts[$key]); // Reset after lockout period
        }
    }
    
    return true;
}

function recordLoginAttempt($username, $success) {
    $attempts_file = 'login_attempts.json';
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = $ip . '_' . $username;
    
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true);
    } else {
        $attempts = [];
    }
    
    if ($success) {
        unset($attempts[$key]); // Clear attempts on successful login
    } else {
        if (!isset($attempts[$key])) {
            $attempts[$key] = ['count' => 0, 'time' => time()];
        }
        $attempts[$key]['count']++;
        $attempts[$key]['time'] = time();
    }
    
    file_put_contents($attempts_file, json_encode($attempts));
}

// Input sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Set security headers on all pages
setSecurityHeaders();
?>
