<?php

declare(strict_types=1);

namespace FlightCms\App\Middleware;

use Flight;

class HeaderSecurity {
    public static string $nonce = '';

    public function before() {
        // In development mode, we set the minimum set of secure headers
        // without CSP, which can block necessary JavaScript
        Flight::response()->header('X-Frame-Options', 'SAMEORIGIN');
        Flight::response()->header('X-Content-Type-Options', 'nosniff');
        
        // Add a simple CSP that allows everything in development mode
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            // Maximum allowing CSP for development
            Flight::response()->header('Content-Security-Policy', "default-src * 'unsafe-inline' 'unsafe-eval' data: blob:;");
            
            // In development mode, we do not set caching to see changes immediately
            Flight::response()->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            Flight::response()->header('Pragma', 'no-cache');
            Flight::response()->header('Expires', '0');
        } else {
            // In production mode, we can add more strict settings
            // But for now, we leave basic security
            Flight::response()->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
            
            // Caching headers for production - important for optimization
            $this->setCachingHeaders();
        }
    }

    /**
     * Sets caching headers depending on the content type
     */
    private function setCachingHeaders() {
        // Get the current request path
        $requestPath = Flight::request()->url;
        
        // Determine the content type by the file extension
        $extension = pathinfo($requestPath, PATHINFO_EXTENSION);
        
        // Set caching headers depending on the resource type
        switch ($extension) {
            case 'css':
            case 'js':
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
            case 'svg':
            case 'woff':
            case 'woff2':
            case 'ttf':
            case 'eot':
                // Static resources can be cached for a long time
                Flight::response()->header('Cache-Control', 'public, max-age=31536000, immutable'); // 1 year
                Flight::response()->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
                break;
                
            case 'html':
            case 'htm':
                // HTML pages are cached for less time
                Flight::response()->header('Cache-Control', 'public, max-age=3600'); // 1 hour
                Flight::response()->header('Expires', gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                break;
                
            default:
                // For dynamic content, we use conditional caching
                if (empty($extension)) {
                    // For API requests and dynamic pages
                    Flight::response()->header('Cache-Control', 'private, max-age=0, must-revalidate');
                    Flight::response()->header('ETag', '"' . md5(Flight::response()->body()) . '"');
                    Flight::response()->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
                }
                break;
        }
    }

    /**
     * A simple after method that does nothing
     * When the main functionality is working, we can add functionality
     */
    public static function after() {
        // Empty method to prevent errors
    }
}