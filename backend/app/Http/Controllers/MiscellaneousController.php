<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Models\Category;
use App\Models\Product;
use App\Models\FaqCategory;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Tag;
use App\Models\ProductLike;
use App\Models\ProductTag;
use App\Models\Color;
use App\Models\Service;
use App\Models\ServiceTag;
use App\Models\Flavor;
use App\Models\Filling;
use App\Models\CakeType;
use App\Models\CakeSize;

class MiscellaneousController extends Controller
{
    public function categories($slug): JsonResponse
    {
        try {

            $products = [];

            $category = Category::with(['banner1', 'banner2', 'banner3', 'banner4', 'children'])
                                ->where('slug', $slug)
                                ->first();
    
            if($category)
                $products = 
                    Product::with(['user.userDetail', 'user.supplier', 'colors.categories'])
                           ->whereHas('colors.categories', function($query) use ($category) {
                                $query->where('category_id', $category->id);
                           })
                           ->orderBy('created_at', 'desc')
                           ->limit(5)
                           ->get();

            return response()->json([
                'success' => true,
                'data' => [            
                    'category' => $category,
                    'products' => $products
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function categoriesAll(): JsonResponse
    {
        try {

            $categories = Category::whereNull('category_id')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function products(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 12;
            $wholesale = 0;

            $query = Product::with([
                                'user.userDetail', 
                                'user.supplier', 
                                'order',
                                'colors'
                            ])
                            ->isFavorite()
                            ->where('state_id', 3)
                            ->applyFilters(
                                $request->only([
                                    'searchPublic',
                                    'orderByField',
                                    'orderBy',
                                    'category',
                                    'subcategory',
                                    'colorId',
                                    'min',
                                    'max',
                                    'wholesalers',
                                    'sortBy',
                                    'rating'
                                ])
                            );

            $count = $query->count();
                           
            $products = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'colors' => Color::where('name', '<>', 'Ninguno')->get(),
                    'products' => $products,
                    'productsTotalCount' => $count                    
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function productDetail($slug): JsonResponse
    {
        try {
    
            $wholesale = 0;

            $product = Product::with([
                                'user.userDetail', 
                                'user.supplier',
                                'brand', 
                                'colors.color', 
                                'colors.images',
                                'colors.categories.category', 
                                'tags.tag',
                                'detail',
                                'reviews.client.user'
                              ])
                              ->where('slug', $slug)
                              ->first();

            $recommendations = 
                Product::with(['user.userDetail', 'user.supplier'])
                       ->where('favourite', true)
                       ->orderBy('created_at', 'desc')
                       ->limit(5)
                       ->get();

            // Validate if the user is authenticated
            if (Auth::check()) {

                $productTag = ProductTag::where('product_id', $product->id)->first();
                    
                if ($productTag) {
                    $tag_id = $productTag->tag_id;

                    $recommendations = 
                            Product::with(['user.userDetail', 'user.supplier', 'tags'])
                                   ->whereHas('tags', function($query) use ($tag_id) {
                                        $query->where('tag_id', $tag_id);
                                   })
                                   ->orderBy('created_at', 'desc')
                                   ->limit(5)
                                   ->get();
                }

            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => $product,
                    'recommendations' => $recommendations,
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function faqs(): JsonResponse
    {
        try {

            $categories = 
                FaqCategory::with(['faqs'=> 
                        function ($query) {
                            $query->orderBy('order_id', 'asc');
                        }
                    ])->get();
        
            return response()->json([
                'success' => true,
                'data' => [ 
                    'categories' => $categories
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function popularsBlogs(Request $request): JsonResponse
    {
        try {

            $category = $request->has('category') ? $request->category : false;
            $search = $request->has('search') ? $request->search : false;
            $query = Blog::with(['user']);

            if($category) {
                $query = $query->whereHas('category', function ($q) use ($category) {
                            $q->where('slug', $category );
                         })->orderBy('order_id', 'asc');
            } else 
                $query = $query->orderBy('is_popular_blog', 'DESC');

            if($search)
                $query = $query->where('title', 'LIKE', '%' . $search . '%')
                               ->orWhere('description', 'LIKE', '%' . $search . '%');

            $blogs = $query->get();
                
            $categories = BlogCategory::withCount('blogs')->get();

            $latestBlogs = Blog::orderBy('created_at', 'desc')
                               ->limit(5)          
                               ->get();
        
            return response()->json([
                'success' => true,
                'data' => [ 
                    'blogs' => $blogs,
                    'categories' => $categories,
                    'latestBlogs' => $latestBlogs
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function blogDetail($slug): JsonResponse
    {
        try {

            $blog = Blog::with(['category', 'user', 'tags.tag'])
                        ->where('slug',$slug)
                        ->first();

            $categories = BlogCategory::withCount('blogs')->get();

            $latestBlogs = Blog::orderBy('created_at', 'desc')
                               ->limit(5)          
                               ->get();
        
            return response()->json([
                'success' => true,
                'data' => [ 
                    'blog' => $blog,
                    'categories' => $categories,
                    'latestBlogs' => $latestBlogs
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function colors(Request $request): JsonResponse
    {
        try {

            return response()->json([
                'success' => true,
                'data' => [
                    'colors' => Color::where('name', '<>', 'Ninguno')->get(),                 
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function services(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 12;

            $query = Service::with([
                                'user.userDetail', 
                                'user.supplier',
                                'order',
                                'cupcakes.cake_size.cake_type'
                            ])
                            ->where('state_id', 3)
                            ->applyFilters(
                                $request->only([
                                    'searchPublic',
                                    'orderByField',
                                    'orderBy',
                                    'category',
                                    'subcategory',
                                    'min',
                                    'max',
                                    'sortBy',
                                    'rating'
                                ])
                            );

            $count = $query->count();
                           
            $services = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'services' => $services,
                    'servicesTotalCount' => $count                    
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function serviceDetail($slug): JsonResponse
    {
        try {
            $service = Service::with([
                                'user.userDetail', 
                                'user.supplier',
                                'brand',  
                                'images',
                                'categories.category', 
                                'tags.tag',
                                'cupcakes.cake_size.cake_type'
                              ])
                              ->where('slug', $slug)
                              ->first();

            $recommendations = 
                Service::with(['user.userDetail', 'user.supplier', 'cupcakes.cake_size.cake_type'])
                       ->where('favourite', true)
                       ->orderBy('created_at', 'desc')
                       ->limit(5)
                       ->get();

            // Validate if the user is authenticated
            if (Auth::check()) {

                $serviceTag = ServiceTag::where('service_id', $service->id)->first();
                    
                if ($serviceTag) {
                    $tag_id = $serviceTag->tag_id;

                    $recommendations = 
                        Service::with(['user.userDetail', 'user.supplier', 'tags', 'cupcakes.cake_size.cake_type'])
                               ->whereHas('tags', function($query) use ($tag_id) {
                                    $query->where('tag_id', $tag_id);
                               })
                               ->orderBy('created_at', 'desc')
                               ->limit(5)
                               ->get();
                }

            }

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'recommendations' => $recommendations
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function cupcakes(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'flavors' => Flavor::all(),
                    'fillings' => Filling::all(),
                    'cakeTypes' => CakeType::all(),
                    'cakeSizes' => CakeSize::all()                 
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }
    
}
