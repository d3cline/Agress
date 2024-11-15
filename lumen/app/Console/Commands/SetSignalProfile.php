<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SetSignalProfile extends Command
{
    protected $signature = 'signal:set-profile';
    protected $description = 'Sets the profile for Signal with name, description, and avatar';

    protected $socketPath;
    protected $shopNumber;
    protected $profileName;
    protected $profileDescription;
    protected $profileImagePath;
    protected $adminNumber;

    public function __construct()
    {
        parent::__construct();
        // Initialize environment variables
        $this->socketPath = env('SIGNAL_SOCKET_PATH');
        $this->shopNumber = env('SIGNAL_SHOP_NUMBER');
        $this->profileName = env('SIGNAL_PROFILE_NAME');
        $this->profileDescription = env('SIGNAL_PROFILE_DESCRIPTION');
        $this->profileImagePath = env('SIGNAL_PROFILE_IMAGE_PATH');
        $this->adminNumber = env('SIGNAL_ADMIN_NUMBER');
    }

    public function handle()
    {
        Log::info('Attempting to set Signal profile...');

        if (!file_exists($this->profileImagePath)) {
            Log::error('Profile image not found at ' . $this->profileImagePath);
            $this->error('Profile image not found. Please check the path.');
            return 1;
        }

        // Prepare the command to update the Signal profile, passing the avatar as a file path
        $command = [
            'jsonrpc' => '2.0',
            'method' => 'updateProfile',
            'params' => [
                'recipient' => $this->shopNumber,
                'given-name' => $this->profileName,
                'description' => $this->profileDescription,
                'avatar' => $this->profileImagePath, // Pass the file path directly
            ],
            'id' => 1,
        ];

        // Send the command to Signal CLI
        $response = $this->sendJsonCommand($command);

        if ($response === null) {
            $this->error('No response from Signal CLI. Please check the socket connection.');
            return 1;
        }

        if (isset($response['error'])) {
            Log::error('Error setting Signal profile', ['response' => $response['error']]);
            $this->error('Failed to set Signal profile. Error: ' . json_encode($response['error']));
            return 1;
        }

        Log::info('Signal profile set successfully');
        $this->info('Signal profile set successfully');
        return 0;
    }

    protected function sendJsonCommand(array $command)
    {
        $socket = @stream_socket_client("unix://{$this->socketPath}", $errno, $errstr, 30);

        if (!$socket) {
            Log::error("Could not connect to Signal CLI socket: $errstr ($errno)");
            $this->error("Could not connect to Signal CLI socket.");
            return null;
        }

        $commandJson = json_encode($command);
        fwrite($socket, $commandJson . "\n");
        Log::info("Sent command to Signal CLI: $commandJson");

        stream_set_timeout($socket, 30);
        $response = '';

        while (!feof($socket)) {
            $line = fgets($socket);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (strpos($line, '}') !== false) {
                break;
            }
        }

        fclose($socket);

        if (trim($response) === '') {
            Log::info("No response received from Signal CLI.");
            return null;
        }

        Log::info("Received response from Signal CLI: $response");

        return json_decode($response, true);
    }
}
