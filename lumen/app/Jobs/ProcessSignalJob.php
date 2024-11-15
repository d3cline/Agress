<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use App\Models\Product;


class ProcessSignalJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $adminNumber;
    protected $shopNumber;
    protected $socketPath;
    protected $profileName;
    protected $profileDescription;
    protected $profileImagePath;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->adminNumber = env('SIGNAL_ADMIN_NUMBER');
        $this->shopNumber = env('SIGNAL_SHOP_NUMBER');
        $this->socketPath = env('SIGNAL_SOCKET_PATH');
        $this->profileName = env('SIGNAL_PROFILE_NAME');
        $this->profileDescription = env('SIGNAL_PROFILE_DESCRIPTION');
        $this->profileImagePath = env('SIGNAL_PROFILE_IMAGE_PATH');
    }

    public function handle()
    {
        try {
            Log::info('Processing Signal job for order ID: ' . $this->order->id);

            if ($this->isSignalRegistered($this->order->phoneNumber)) {
                $this->startSignalGroupChat($this->order->phoneNumber);
                $this->order->status = 'Completed';
            } else {
                $this->sendFallbackEmail();
                $this->order->status = 'Email Sent';
            }

            $this->order->save();

        } catch (\Exception $e) {
            Log::error('Signal processing failed for order ID: ' . $this->order->id, ['error' => $e->getMessage()]);
            $this->order->status = 'Failed';
            $this->order->save();
        }
    }

    protected function isSignalRegistered($phoneNumber)
    {
        $command = [
            'jsonrpc' => '2.0',
            'method' => 'getUserStatus',
            'params' => [
                'recipient' => [$phoneNumber],
            ],
            'id' => 1,
        ];

        $response = $this->sendJsonCommand($command);

        if ($response === null) {
            Log::info('No response from getUserStatus for phone number: ' . $phoneNumber);
            return false;
        }

        if (isset($response['error'])) {
            Log::error('Error in getUserStatus response', ['response' => $response]);
            throw new \Exception('Failed to check registration via Signal');
        }

        $isRegistered = $response['result'][0]['isRegistered'] ?? false;
        Log::info('Phone number ' . ($isRegistered ? 'is' : 'is not') . ' registered on Signal: ' . $phoneNumber);

        return $isRegistered;
    }

    protected function startSignalGroupChat($customerNumber)
    {
        $command = [
            'jsonrpc' => '2.0',
            'method' => 'updateGroup',
            'params' => [
                'number' => $this->shopNumber,
                'admin' => [$this->adminNumber, $this->shopNumber,], 
                'members' => [$customerNumber, $this->adminNumber, $this->shopNumber,],
                'name' => $this->profileName . ' order for ' . $this->order->fullName,
                'description' => 'Checkout for order ID: ' . $this->order->id,
                'setPermissionAddMember' => 'only-admins',
                'setPermissionSendMessages' => 'every-member',
                "setPermissionEditDetails" => "only-admins",
                'link' => 'enabledWithApproval', // Enable link in case group link approval is needed
            ],
            'id' => 2,
        ];

        $response = $this->sendJsonCommand($command);
        Log::info($response);
        
        if (isset($response['error'])) {
            Log::error('Error creating group', ['response' => $response]);
            throw new \Exception('Failed to create Signal group');
        }

        $groupId = $response['result']['groupId'] ?? null;
        
        if (!$groupId) {
            Log::error('Group ID not found in response', ['response' => $response]);
            throw new \Exception('Failed to retrieve Signal group ID');
        }

        Log::info('Signal group created with ID: ' . $groupId);

        $orderMessage = $this->generateOrderMessage();
        Log::info($orderMessage);
        $command = [
            'jsonrpc' => '2.0',
            'method' => 'send',
            'params' => [
                'account' => $this->shopNumber,
                'groupId' => $groupId,
                'message' => $orderMessage,
            ],
            'id' => 3,
        ];

        $response = $this->sendJsonCommand($command);

        if (isset($response['error'])) {
            Log::error('Error sending message to group', ['response' => $response]);
            throw new \Exception('Failed to send message to Signal group');
        }

        Log::info('Signal group chat started between admin and customer.');
    }

    protected function sendJsonCommand(array $command)
    {
        $socket = @stream_socket_client("unix://{$this->socketPath}", $errno, $errstr, 30);
    
        if (!$socket) {
            Log::error("Could not connect to Signal CLI socket: $errstr ($errno)");
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
    
            // Attempt to decode each line to check for the `method: receive`
            $decodedLine = json_decode($line, true);
            if (isset($decodedLine['method']) && $decodedLine['method'] === 'receive') {
                Log::info("Received 'method: receive' notification, ignoring: $line");
                $response = ''; // Clear response to ignore this line and wait for the next one
                continue; // Continue to listen for the actual response
            }
    
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
    
    protected function sendFallbackEmail()
    {
        Log::debug('Starting sendFallbackEmail() for order ID: ' . $this->order->id);
    
        // Generate the order message
        $orderMessage = $this->generateOrderMessage();
        Log::debug('Generated order message: ' . $orderMessage);
    
        // Prepare customer email details
        $to = $this->order->email;
        $subject = 'Order Confirmation';
        $body = "Thank you for your order. Our team will reach out to you shortly.\n\n" . $orderMessage;
        Log::debug("Email details - To: $to, Subject: $subject, Body: $body");
        
        try {
            $transport = new Swift_SmtpTransport(env('MAIL_HOST'), env('MAIL_PORT'));
            $transport->setUsername(env('MAIL_USERNAME'))
                      ->setPassword(env('MAIL_PASSWORD'))
                      ->setEncryption(env('MAIL_ENCRYPTION'));
        
            $mailer = new Swift_Mailer($transport);
        
            $message = (new Swift_Message($subject))
                        ->setFrom([env('MAIL_FROM_ADDRESS') => env('MAIL_FROM_NAME')])
                        ->setTo([$to])
                        ->setBody($body, 'text/plain');
        
            $result = $mailer->send($message);
        
            Log::info('Fallback email sent to customer.');
        
        } catch (\Exception $e) {
            Log::error("Failed to send fallback email: " . $e->getMessage(), ['exception' => $e]);
        }
    

        // After sending the email, send a Signal message to the admin with order details
        $adminMessage = "New fallback email sent for order ID: {$this->order->id}\n";
        $adminMessage .= "Customer Info:\n";
        $adminMessage .= "Name: {$this->order->fullName}\n";
        $adminMessage .= "Phone: {$this->order->phoneNumber}\n";
        $adminMessage .= "Email: {$this->order->email}\n\n";
        $adminMessage .= "Order Details:\n";

        foreach ($this->order->cart as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $adminMessage .= "- {$product->name} (Price: {$product->currency} {$product->price})\n";
            } else {
                $adminMessage .= "- Unknown item (Product ID: {$productId})\n";
            }
        }

        // Send the constructed message to the admin
        $this->sendSignalMessageToAdmin($adminMessage);




    }
    
    
    protected function sendSignalMessageToAdmin($message)
    {
        $command = [
            'jsonrpc' => '2.0',
            'method' => 'send',
            'params' => [
                'account' => $this->shopNumber,
                'recipient' => [$this->adminNumber],
                'message' => $message,
            ],
            'id' => 4,
        ];
    
        $response = $this->sendJsonCommand($command);
    
        if (isset($response['error'])) {
            Log::error('Error sending fallback notification to admin via Signal', ['response' => $response]);
            throw new \Exception('Failed to send fallback notification to admin via Signal');
        }
    
        Log::info('Fallback notification sent to admin via Signal.');
    }

    

    protected function generateOrderMessage()
    {
        $message = "Hello, thanks for ordering with {$this->profileName}! Here is your order:\n\n";
        
        // Add customer information
        $message .= "Customer Information:\n";
        $message .= "Name: {$this->order->fullName}\n";
        $message .= "Email: {$this->order->email}\n";
        $message .= "Phone: {$this->order->phoneNumber}\n";
        $message .= "Address: {$this->order->shippingAddress}, {$this->order->city}, {$this->order->postalCode}\n\n";
        
        // Initialize totals for each currency
        $totals = [];
    
        // Add order details
        $message .= "Order Details:\n";
        foreach ($this->order->cart as $productId) {
            $product = Product::find($productId);
            
            if ($product) {
                $message .= "- {$product->name} (Price: {$product->currency} {$product->price})\n";
                if (!isset($totals[$product->currency])) {
                    $totals[$product->currency] = 0;
                }
                $totals[$product->currency] += $product->price;
            } else {
                $message .= "- Unknown item (Product ID: {$productId})\n";
            }
        }
    
        // Add cart totals by currency
        $message .= "\nCart Totals:\n";
        foreach ($totals as $currency => $total) {
            $message .= "- {$currency} {$total}\n";
        }
    
        // Placeholder for billing information hook
        $message .= "\nBilling Information:\n";
        $message .= "[Insert billing info here]\n";
    
        return $message;
    }
    
    



}
