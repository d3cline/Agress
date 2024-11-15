<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


use App\Models\Product;

class ProductController extends Controller
{
    // Display a listing of products
    public function index()
    {
        $products = Product::all();
        return response()->json($products);
    }

    // Show a specific product
    public function show($id)
    {
        $product = Product::find($id);
        if ($product) {
            return response()->json($product);
        } else {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    // Store a new product
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|mimes:webp,jpg,png|max:2048'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $productData = $request->all();
    
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $imagePath = $image->getRealPath();
    
            // Load the image based on its MIME type, including WebP support
            switch ($image->getMimeType()) {
                case 'image/jpeg':
                    $gdImage = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $gdImage = imagecreatefrompng($imagePath);
                    break;
                case 'image/webp':
                    $gdImage = imagecreatefromwebp($imagePath);
                    break;
                default:
                    return response()->json(['errors' => ['image' => 'Unsupported image format']], 422);
            }
    
            // Convert and save the image as WebP format in a temporary buffer
            ob_start();
            imagewebp($gdImage, null, 80); // Adjust the quality as needed
            $webpData = ob_get_clean();
    
            // Free up memory
            imagedestroy($gdImage);
    
            // Convert WebP data to base64 with the required prefix
            $imageData = 'data:image/webp;base64,' . base64_encode($webpData);
            $productData['image'] = $imageData;
        }
    
        $product = Product::create($productData);
        return response()->json($product, 201);
    }
    

    // Update an existing product
    public function update(Request $request, $id)
    {
        echo 'Function started<br>';

        $product = Product::find($id);
        if (!$product) {
            echo 'Product not found<br>';
            return response()->json(['error' => 'Product not found'], 404);
        }
        echo 'Product found<br>';

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'price'       => 'sometimes|required|numeric',
            'currency'    => 'sometimes|required|string|max:10',
            'description' => 'nullable|string',
            'image'       => 'nullable|mimes:webp,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            echo 'Validation failed<br>';
            return response()->json(['errors' => $validator->errors()], 422);
        }
        echo 'Validation passed<br>';

        $productData = $request->only(['name', 'price', 'currency', 'description']);
        echo 'Product data before adding image: <pre>';
        print_r($productData);
        echo '</pre>';

        // Process the image if it's uploaded
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            echo 'Image file detected and is valid<br>';
            $image     = $request->file('image');
            $imagePath = $image->getRealPath();

            echo 'Image path: ' . $imagePath . '<br>';

            // Load the image based on its MIME type
            switch ($image->getMimeType()) {
                case 'image/jpeg':
                    echo 'Image is JPEG<br>';
                    $gdImage = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    echo 'Image is PNG<br>';
                    $gdImage = imagecreatefrompng($imagePath);
                    break;
                case 'image/webp':
                    echo 'Image is WEBP<br>';
                    $gdImage = imagecreatefromwebp($imagePath);
                    break;
                default:
                    echo 'Unsupported image format<br>';
                    return response()->json(['errors' => ['image' => 'Unsupported image format']], 422);
            }

            // Convert and save the image as WebP format
            ob_start();
            imagewebp($gdImage, null, 80); // Adjust quality as needed
            $webpData = ob_get_clean();

            // Free up memory
            imagedestroy($gdImage);

            // Convert WebP data to base64
            $imageData            = 'data:image/webp;base64,' . base64_encode($webpData);
            $productData['image'] = $imageData;
        }

        // Final check before updating
        echo 'Product data to be updated: <pre>';
        print_r($productData);
        echo '</pre>';

        // Update the product with the new data
        if ($product->update($productData)) {
            echo 'Product updated successfully<br>';
        } else {
            echo 'Product update failed<br>';
        }

        return response()->json($product->fresh());
    }



    // Delete a product
    public function destroy($id)
    {
        $deleted = Product::destroy($id);
        if ($deleted) {
            return response()->json(['message' => 'Product deleted']);
        } else {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }
}
