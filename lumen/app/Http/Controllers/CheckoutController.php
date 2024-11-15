<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Jobs\ProcessSignalJob;

class CheckoutController extends Controller
{
    /**
     * Handle the checkout process.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processCheckout(Request $request)
    {


        // Validate the CAPTCHA token
        $captchaResponse = $request->input('h-captcha-response'); // or 'h-captcha-response' if using hCaptcha g-recaptcha-response for google
        if (!$this->validateCaptcha($captchaResponse)) {
            return response()->json(['message' => 'CAPTCHA validation failed.'], 422);
        }

        // Capture all data from the request
        $orderData = $request->only([
            'fullName',
            'email',
            'phoneNumber',
            'shippingAddress',
            'city',
            'postalCode',
            'total',
            'cart',
        ]);

        // Create a new order
        $order = Order::create($orderData);

        // Log the order creation for inspection
        Log::info('Order Created:', $order->toArray());

        // Dispatch a job to handle the Signal processing
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatchNow(new ProcessSignalJob($order));


        // Respond with a success message and the order ID
        return response()->json([
            'message' => 'Order placed successfully.',
            'order_id' => $order->id,
        ], 200);
    }

    /**
     * Validate CAPTCHA using Google reCAPTCHA or hCaptcha API.
     *
     * @param string $captchaResponse
     * @return bool
     */
    protected function validateCaptcha($captchaResponse)
    {
        // Log the received CAPTCHA response token
        Log::info('Received CAPTCHA response:', ['captcha_response' => $captchaResponse]);
    
        // Check if the CAPTCHA response is empty
        if (empty($captchaResponse)) {
            Log::error('CAPTCHA response token is empty.');
            return false;
        }
    
        $captchaSecret = env('CAPTCHA_SECRET_KEY');
    
        // Log the secret key being used (be cautious if sharing logs as it exposes the key)
        Log::info('Using CAPTCHA secret key:', ['secret' => $captchaSecret]);
    
        try {
            $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
                'secret' => $captchaSecret,
                'response' => $captchaResponse,
            ]);
    
            // Log the response from hCaptcha
            Log::info('hCaptcha verification response:', ['hCaptcha_response' => $response->json()]);
    
            return $response->json()['success'] ?? false;
        } catch (\Exception $e) {
            // Log any exceptions during the HTTP request
            Log::error('Error during hCaptcha verification request:', ['error' => $e->getMessage()]);
            return false;
        }
    }


    /**
     * Check the status of the Signal job.
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderStatus($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        // You might need to implement a status tracking method in the Order model or in the job itself.
        $status = $order->status ?? 'Processing';

        return response()->json([
            'order_id' => $order->id,
            'status' => $status,
        ], 200);
    }
}
