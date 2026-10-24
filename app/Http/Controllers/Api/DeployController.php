<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    public function deploy(Request $request)
    {
        // 1. Verify token to ensure security
        if (!$this->isAuthorized($request)) {
            Log::warning('Unauthorized deployment attempt.', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $scriptPath = base_path('deploy.sh');
        $logPath = storage_path('logs/deploy.log');

        if (!file_exists($scriptPath)) {
            Log::error('Deploy script not found at ' . $scriptPath);
            return response()->json(['message' => 'Deployment script not found.'], 404);
        }

        // 2. Run the deployment script in the background
        // We use nohup and & so the PHP request doesn't wait for the script to finish
        $basePath = base_path();
        $command = "cd {$basePath} && nohup bash {$scriptPath} > {$logPath} 2>&1 &";
        exec($command);

        Log::info('Deployment triggered successfully via webhook.');

        return response()->json([
            'success' => true,
            'message' => 'Deployment started in the background.',
            'log_file' => 'storage/logs/deploy.log'
        ]);
    }

    public function logs(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $logPath = storage_path('logs/deploy.log');

        if (!file_exists($logPath)) {
            return response()->json(['message' => 'No logs found yet.'], 404);
        }

        $logContent = file_get_contents($logPath);
        return response($logContent)->header('Content-Type', 'text/plain');
    }

    /**
     * The token comes from DEPLOY_TOKEN only. With no token configured the
     * endpoints stay disabled instead of falling back to a guessable default.
     */
    private function isAuthorized(Request $request): bool
    {
        $expectedToken = (string) config('app.deploy_token');
        $token = (string) ($request->header('X-Deploy-Token') ?? $request->query('token', ''));

        return $expectedToken !== '' && $token !== '' && hash_equals($expectedToken, $token);
    }
}
