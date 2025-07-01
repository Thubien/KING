<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Perform a health check of the application
     */
    public function check(): JsonResponse
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'services' => []
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['services']['database'] = [
                'status' => 'ok',
                'connection' => config('database.default')
            ];
        } catch (\Exception $e) {
            $checks['services']['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed'
            ];
            $checks['status'] = 'error';
        }

        // Redis check
        try {
            Redis::ping();
            $checks['services']['redis'] = [
                'status' => 'ok'
            ];
        } catch (\Exception $e) {
            $checks['services']['redis'] = [
                'status' => 'error', 
                'message' => 'Redis connection failed'
            ];
            $checks['status'] = 'error';
        }

        // Cache check
        try {
            Cache::put('health_check', 'ok', 60);
            $cacheValue = Cache::get('health_check');
            
            $checks['services']['cache'] = [
                'status' => $cacheValue === 'ok' ? 'ok' : 'error'
            ];
        } catch (\Exception $e) {
            $checks['services']['cache'] = [
                'status' => 'error',
                'message' => 'Cache system failed'
            ];
            $checks['status'] = 'error';
        }

        // Queue check
        try {
            $queueSize = DB::table('jobs')->count();
            $checks['services']['queue'] = [
                'status' => 'ok',
                'pending_jobs' => $queueSize
            ];
        } catch (\Exception $e) {
            $checks['services']['queue'] = [
                'status' => 'error',
                'message' => 'Queue system check failed'
            ];
        }

        // Storage check
        try {
            $storageWritable = is_writable(storage_path());
            $checks['services']['storage'] = [
                'status' => $storageWritable ? 'ok' : 'error',
                'writable' => $storageWritable
            ];
            
            if (!$storageWritable) {
                $checks['status'] = 'error';
            }
        } catch (\Exception $e) {
            $checks['services']['storage'] = [
                'status' => 'error',
                'message' => 'Storage check failed'
            ];
            $checks['status'] = 'error';
        }

        $statusCode = $checks['status'] === 'ok' ? 200 : 503;
        
        return response()->json($checks, $statusCode);
    }

    /**
     * Simple ping endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'pong',
            'timestamp' => now()->toISOString()
        ]);
    }
} 