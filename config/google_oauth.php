<?php
// config/google_oauth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Konfigurasi Google OAuth 2.0
 * 
 * Client ID dan Client Secret resmi dari Google Cloud Console
 */
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '469386952813-uo468j0bd9vlrucfn6qophtpa6t1b256.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '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');

/**
 * Deteksi otomatis Redirect URI berdasarkan host saat ini (mendukung localhost & hosting HTTPS/Cloudflare)
 */
function getGoogleRedirectUri() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    
    $scriptDir = '';
    if (!empty($_SERVER['SCRIPT_NAME'])) {
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if ($dir !== '.' && $dir !== '/' && $dir !== '') {
            if ($dir === '/admin' || $dir === '/views') {
                $dir = dirname($dir);
            }
            $scriptDir = ($dir !== '.' && $dir !== '/') ? $dir : '';
        }
    }
    
    return $scheme . '://' . $host . $scriptDir . '/google-callback.php';
}

/**
 * Cek apakah kredensial Google OAuth sudah dikonfigurasi nyata oleh pengguna
 */
function isGoogleOAuthConfigured() {
    $id = GOOGLE_CLIENT_ID;
    $secret = GOOGLE_CLIENT_SECRET;
    
    if (empty($id) || empty($secret)) return false;
    if (strpos($id, 'YOUR_GOOGLE_CLIENT_ID') !== false) return false;
    if (strpos($secret, 'YOUR_GOOGLE_CLIENT_SECRET') !== false) return false;
    
    return true;
}

/**
 * Menghasilkan URL Autentikasi Google
 */
function getGoogleAuthUrl($state) {
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => getGoogleRedirectUri(),
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'state'         => $state,
        'prompt'        => 'select_account'
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Menukar authorization code dengan Access Token Google
 */
function exchangeGoogleCodeForToken($code) {
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postData = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => getGoogleRedirectUri(),
        'grant_type'    => 'authorization_code'
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }
        return json_decode($response, true);
    } else {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($postData),
                'ignore_errors' => true
            ]
        ]);
        $response = file_get_contents($tokenUrl, false, $context);
        return json_decode($response, true);
    }
}

/**
 * Mengambil informasi user (nama, email, foto profil) dari Google UserInfo API
 */
function getGoogleUserProfile($accessToken) {
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }
        return json_decode($response, true);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer $accessToken\r\n",
                'ignore_errors' => true
            ]
        ]);
        $response = file_get_contents($userInfoUrl, false, $context);
        return json_decode($response, true);
    }
}
