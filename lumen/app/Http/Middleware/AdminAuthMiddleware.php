<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Retrieve API token from the request header
        $apiToken = $request->bearerToken();
        echo 'Received API Token: ' . ($apiToken ?: 'No Token Provided') . "\n";

        if (!$apiToken) {
            echo 'No API token provided in the request.' . "\n";
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Output the length of the received token
        echo 'Length of Received Token: ' . strlen($apiToken) . "\n";

        // Trim the token to remove any leading/trailing whitespace
        $apiTokenTrimmed = trim($apiToken);

        // Output the trimmed token and its length
        echo 'Trimmed API Token: ' . $apiTokenTrimmed . "\n";
        echo 'Length of Trimmed Token: ' . strlen($apiTokenTrimmed) . "\n";

        // Retrieve the user with matching API token
        $admin = User::where('api_token', $apiTokenTrimmed)->first();

        if (!$admin) {
            echo 'No user found with the provided API token.' . "\n";

            // For further debugging, retrieve all API tokens from the database
            $allTokens = User::pluck('api_token')->toArray();
            echo 'List of API tokens in the database:' . "\n";
            foreach ($allTokens as $index => $token) {
                echo ($index + 1) . '. ' . $token . ' (Length: ' . strlen($token) . ')' . "\n";
            }

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        echo 'Authorized user: ' . $admin->email . "\n";

        // Allow the request to proceed if valid
        return $next($request);
    }
}
