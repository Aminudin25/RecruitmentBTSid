<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Products", description="Product management endpoints")
 */
class ProductController extends Controller
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * @OA\Get(
     *     path="/api/products",
     *     tags={"Products"},
     *     summary="Get all products with optional filters",
     *     @OA\Parameter(name="search", in="query", description="Search by title", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category", in="query", description="Filter by category", @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", description="Items per page (default 10)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", description="Page number (default 1)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of products")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $search   = $request->query('search');
        $category = $request->query('category');
        $limit    = (int) $request->query('limit', 10);
        $page     = (int) $request->query('page', 1);

        $limit = min(max($limit, 1), 100);

        $cacheKey = 'products:' . md5(json_encode($request->query()));

        $result = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($search, $category, $limit) {
            $query = Product::query();

            if ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            }

            if ($category) {
                $query->where('category', 'ilike', "%{$category}%");
            }

            return $query->orderBy('created_at', 'desc')->paginate($limit);
        });

        return response()->json([
            'success' => true,
            'data'    => $result->items(),
            'meta'    => [
                'total'        => $result->total(),
                'per_page'     => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     summary="Get a product by ID",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product detail"),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $cacheKey = "product:{$id}";

        $product = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Product::find($id);
        });

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => "Product with ID {$id} not found",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","price","category","images"},
     *             @OA\Property(property="title", type="string", example="Awesome T-Shirt"),
     *             @OA\Property(property="price", type="number", example=99.99),
     *             @OA\Property(property="description", type="string", example="High-quality cotton t-shirt"),
     *             @OA\Property(property="category", type="string", example="Clothes"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string"), example={"https://placeimg.com/640/480/any"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Product created"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:100',
            'images'      => 'required|array|min:1',
            'images.*'    => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $user    = auth('api')->user();
        $product = Product::create([
            'title'          => $request->title,
            'price'          => $request->price,
            'description'    => $request->description,
            'category'       => $request->category,
            'images'         => $request->images,
            'created_by'     => $user->username,
            'created_by_id'  => (string) $user->id,
            'updated_by'     => $user->username,
            'updated_by_id'  => (string) $user->id,
        ]);

        $this->clearProductListCache();

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data'    => $product,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     summary="Update a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Product updated"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => "Product with ID {$id} not found",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|string|max:255',
            'price'       => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'category'    => 'sometimes|string|max:100',
            'images'      => 'sometimes|array|min:1',
            'images.*'    => 'url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 400);
        }

        $user = auth('api')->user();

        $product->update(array_merge(
            $request->only(['title', 'price', 'description', 'category', 'images']),
            [
                'updated_by'    => $user->username,
                'updated_by_id' => (string) $user->id,
            ]
        ));

        Cache::forget("product:{$id}");
        $this->clearProductListCache();

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data'    => $product->fresh(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product deleted"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => "Product with ID {$id} not found",
            ], 404);
        }

        $product->delete();

        Cache::forget("product:{$id}");
        $this->clearProductListCache();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    private function clearProductListCache(): void
    {
        // Clear all product list caches using Redis pattern
        $redis = Cache::getStore()->getRedis();
        $keys  = $redis->keys(config('cache.prefix') . ':products:*');
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }
}
