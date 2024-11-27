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
            'image' => 'nullable|string', // Allows base64 or file input
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $productData = $request->only(['name', 'price', 'description']);
        
        if ($request->has('image')) {
            $imageInput = $request->input('image');

            // Check if the image is embedded (base64) or a file
            if (preg_match('/^data:image\/(\w+);base64,/', $imageInput, $matches)) {
                $imageType = strtolower($matches[1]);
                if (!in_array($imageType, ['jpeg', 'png', 'webp'])) {
                    return response()->json(['errors' => ['image' => 'Unsupported image format']], 422);
                }

                // Decode the base64 image
                $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageInput));
                if ($imageData === false) {
                    return response()->json(['errors' => ['image' => 'Invalid base64 data']], 422);
                }

                // Convert and encode as WebP
                $gdImage = imagecreatefromstring($imageData);
                if (!$gdImage) {
                    return response()->json(['errors' => ['image' => 'Invalid image data']], 422);
                }

                ob_start();
                imagewebp($gdImage, null, 80); // Adjust quality as needed
                $webpData = ob_get_clean();
                imagedestroy($gdImage);

                $productData['image'] = 'data:image/webp;base64,' . base64_encode($webpData);
            } elseif ($request->hasFile('image') && $request->file('image')->isValid()) {
                $image = $request->file('image');
                $imagePath = $image->getRealPath();

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

                ob_start();
                imagewebp($gdImage, null, 80);
                $webpData = ob_get_clean();
                imagedestroy($gdImage);

                $productData['image'] = 'data:image/webp;base64,' . base64_encode($webpData);
            } else {
                return response()->json(['errors' => ['image' => 'Invalid image input']], 422);
            }
        }

        $product = Product::create($productData);
        return response()->json($product, 201);
    }

    // Update an existing product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $productData = $request->only(['name', 'price', 'description']);

        if ($request->has('image')) {
            $imageInput = $request->input('image');

            if (preg_match('/^data:image\/(\w+);base64,/', $imageInput, $matches)) {
                $imageType = strtolower($matches[1]);
                if (!in_array($imageType, ['jpeg', 'png', 'webp'])) {
                    return response()->json(['errors' => ['image' => 'Unsupported image format']], 422);
                }

                $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageInput));
                if ($imageData === false) {
                    return response()->json(['errors' => ['image' => 'Invalid base64 data']], 422);
                }

                $gdImage = imagecreatefromstring($imageData);
                if (!$gdImage) {
                    return response()->json(['errors' => ['image' => 'Invalid image data']], 422);
                }

                ob_start();
                imagewebp($gdImage, null, 80);
                $webpData = ob_get_clean();
                imagedestroy($gdImage);

                $productData['image'] = 'data:image/webp;base64,' . base64_encode($webpData);
            } elseif ($request->hasFile('image') && $request->file('image')->isValid()) {
                $image = $request->file('image');
                $imagePath = $image->getRealPath();

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

                ob_start();
                imagewebp($gdImage, null, 80);
                $webpData = ob_get_clean();
                imagedestroy($gdImage);

                $productData['image'] = 'data:image/webp;base64,' . base64_encode($webpData);
            } else {
                return response()->json(['errors' => ['image' => 'Invalid image input']], 422);
            }
        }

        $product->update($productData);
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
