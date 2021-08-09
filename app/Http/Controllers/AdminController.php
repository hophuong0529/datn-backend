<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderBuy;
use App\Models\OrderDetail;
use App\Models\Producer;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\OrderStatus;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\Product\ProductRepository;
use App\Models\ProductImage;
use App\Models\Color;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    protected $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        if (!Auth::guard('admin')->attempt($credentials)) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
        $admin = Auth::guard('admin')->user();
        $token = $request->user('admin')->createToken('Access Token')->plainTextToken;

        return response()->json(['success' => true, 'access_token' => $token, 'admin' => $admin]);
    }

    public function colors()
    {
        $colors = Color::with('details')->get();
        foreach ($colors as $color) {
            $quantity = 0;
            foreach ($color->details as $detail) {
                $quantity = $quantity + $detail->quantity;
            }
            $color['quantity_export'] = $quantity;
        }
        return $colors->toJson();
    }

    public function states()
    {
        $states = OrderStatus::all();
        return $states->toJson();
    }

    public function users()
    {
        $users = User::all();
        return $users->toJson();
    }

    public function categories()
    {
        $categories = Category::with('subs')->paginate(8);

        foreach ($categories as $category) {
            $quantity_export = 0;
            foreach ($category->subs as $item) {
                $productsId = [];
                foreach ($item->products as $product) {
                    array_push($productsId, $product->id);
                }
                $products_sold = OrderDetail::select('product_id', 'quantity')->whereIn('product_id', $productsId)->get();
                foreach ($products_sold as $product) {
                    $quantity_export = $quantity_export + $product->quantity;
                }
            }
            $category['quantity_export'] = $quantity_export;
        }
        return $categories->toJson();
    }

    public function subCategories()
    {
        $categories = SubCategory::with('category', 'products')->paginate(8);
        $this->getQuantityEx($categories);
        return $categories->toJson();
    }

    public function getQuantityEx($items)
    {
        foreach ($items as $item) {
            $quantity_export = 0;
            $productsId = [];
            foreach ($item->products as $product) {
                array_push($productsId, $product->id);
            }
            $products_sold = OrderDetail::select('product_id', 'quantity')->whereIn('product_id', $productsId)->get();
            foreach ($products_sold as $product) {
                $quantity_export = $quantity_export + $product->quantity;
            }
            $item['quantity_export'] = $quantity_export;
        }
    }

    public function orders()
    {
        $orders = OrderBuy::with('receiver', 'status', 'user', 'method')->get();
        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                $detail['product'] = Product::with('images')->where('id', $detail->product_id)->first();
                $detail->product['color'] = $detail->color->name;
            }
        }

        return $orders->toJson();
    }

    public function producers()
    {
        $producers = Producer::all();
        $this->getQuantityEx($producers);
        return $producers->toJson();
    }

    public function products()
    {
        $products = Product::with('images', 'colors', 'sub', 'producer')->orderBy('created_at', 'desc')->paginate(12);
        foreach ($products as $product) {
            foreach ($product->colors as $color) {
                $color['quantity'] = ProductColor::where([
                    'product_id' => $product->id,
                    'color_id' => $color->id
                ])->first()->quantity;
            }
        }
        return $products->toJson();
    }

    public function storeProduct(Request $request)
    {
        //Insert Product color
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'unique:product',
            'code' => 'unique:product'
        ], [
            'name.unique' => '* Tên sản phẩm đã tồn tại',
            'code.unique' => '* Mã code đã tồn tại'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->messages()
            ]);
        } else {
            $data['quantity'] = 0;
            $product = $this->repository->create($data);

            //Insert ProductColor table
            $colors = json_decode($request->colors);
            $product_quantity = 0;
            foreach ($colors as $color) {
                ProductColor::insert([
                    'product_id' => $product->id,
                    'color_id' => $color->id,
                    'quantity' => $color->quantity,
                ]);
                $product_quantity = $product_quantity + $color->quantity;
            }

            //Update quantity
            Product::find($product->id)->update([
                'quantity' => $product_quantity
            ]);

            //Insert ProductImage table
            $images = $request->images;
            if ($images) {
                foreach ($images as $image) {
                    $path = time() . '-' . $image->getClientOriginalName();
                    ProductImage::insert([
                        'path' => 'images/' . $path,
                        'product_id' => $product->id,
                    ]);
                    $image->storeAs('public/images', $path);
                }
            }

            return response()->json($product);
        }
    }

    public function updateProduct($id, Request $request)
    {
        //Update ProductColor table
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => Rule::unique('product')->ignore($id),
            'code' => Rule::unique('product')->ignore($id),
        ], [
            'name.unique' => '* Tên sản phẩm đã tồn tại',
            'code.unique' => '* Mã code đã tồn tại'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->messages()
            ]);
        } else {
            $product = $this->repository->edit($data, $id);

            //Insert ProductColor table
            $colors = json_decode($request->colors);
            $product_quantity = 0;
            if ($colors) {
                ProductColor::where('product_id', $id)->delete();
                foreach ($colors as $color) {
                    ProductColor::insert([
                        'product_id' => $id,
                        'color_id' => $color->id,
                        'quantity' => $color->quantity,
                    ]);
                    $product_quantity = $product_quantity + $color->quantity;
                }

                //Update quantity
                Product::find($id)->update([
                    'quantity' => $product_quantity
                ]);

                //Insert ProductImage table
                $images = $request->images;
                $preImages = json_decode($request->preImages);
                if ($images || $preImages) {
                    ProductImage::where('product_id', $id)->delete();
                    if ($images) {
                        foreach ($images as $image) {
                            $path = time() . '-' . $image->getClientOriginalName();
                            ProductImage::insert([
                                'product_id' => $id,
                                'path' => 'images/' . $path
                            ]);

                            $image->storeAs('public/images', $path);
                        }
                    }
                    if ($preImages) {
                        foreach ($preImages as $image) {
                            ProductImage::insert([
                                'product_id' => $id,
                                'path' => $image->path
                            ]);
                        }
                    }
                }
            }

            return response()->json($product);
        }
    }

    public function deleteProduct(Request $request)
    {
        $id = $request->productId;
        $detail = OrderDetail::where('product_id', $id)->first();
        if ($detail) {
            return response()->json("Delete failed.", 403);
        }
        $this->repository->delete($id);
        ProductImage::where('product_id', $id)->delete();
        ProductColor::where('product_id', $id)->delete();
        return response()->json("Delete success.");
    }

    public function storeCategory(Request $request)
    {
        Category::insert([
            'name' => $request->input('name')
        ]);

        return $this->categories();
    }

    public function updateCategory($id, Request $request)
    {
        Category::find($id)->update([
            'name' => $request->input('name')
        ]);

        return $this->categories();
    }

    public function deleteCategory(Request $request)
    {
        $id = $request->input('categoryId');
        $subs = SubCategory::where('category_id', $id)->get();
        $count_prd = 0;
        foreach ($subs as $sub) {
            $products = Product::where('subcategory_id', $sub->id)->get();
            $count_prd += count($products);
        }
        if ($count_prd != 0) {
            return response()->json("Delete failed.", 403);
        }
        Category::find($id)->delete();
        SubCategory::where('category_id', $id)->delete();
        return response()->json("Delete Success.");
    }

    public function storeSubCategory(Request $request)
    {
        SubCategory::insert([
            'name' => $request->input('name'),
            'category_id' => $request->input('categoryId')
        ]);

        return $this->subCategories();
    }

    public function updateSubCategory($id, Request $request)
    {
        SubCategory::find($id)->update([
            'name' => $request->input('name'),
            'category_id' => $request->input('categoryId')
        ]);

        return $this->subCategories();
    }

    public function deleteSubCategory(Request $request)
    {
        $id = $request->input('categoryId');
        $product = Product::where('subcategory_id', $id)->first();
        if ($product) {
            return response()->json("Delete failed.", 403);
        }
        SubCategory::find($id)->delete();
        return response()->json("Delete Success.");
    }

    public function storeProducer(Request $request)
    {
        Producer::insert([
            'name' => $request->input('name'),
            'address' => $request->input('address'),
            'quantity_import' => $request->input('quantity')
        ]);

        return $this->producers();
    }

    public function updateProducer($id, Request $request)
    {
        Producer::find($id)->update([
            'name' => $request->input('name'),
            'address' => $request->input('address'),
            'quantity_import' => $request->input('quantity')
        ]);

        return $this->producers();
    }

    public function deleteProducer(Request $request)
    {
        $id = $request->input('producerId');
        $product = Product::where('producer_id', $id)->first();
        if ($product) {
            return response()->json("Delete failed.", 403);
        }
        Producer::find($id)->delete();
        return response()->json("Delete Success.");
    }

    public function updateOrder($id, Request $request)
    {
        $status_id = $request->input('statusId');
        $order = OrderBuy::with('details')->where('id', $id)->first();
        $order->update([
            'status_id' => $status_id,
        ]);
        if ($status_id == 12) {
            foreach ($order->details as $detail) {
                $product = Product::find($detail->product_id);
                $product->update([
                    'quantity' => ($product->quantity + $detail->quantity)
                ]);
            }
        }

        return $this->orders();
    }

    public function updateUser($id, Request $request)
    {
        User::find($id)->update([
            'is_active' => $request->input('isActive'),
        ]);

        return $this->users();
    }

    public function storeColor(Request $request)
    {
        Color::insert([
            'name' => $request->input('name'),
            'code' => $request->input('code')
        ]);

        return $this->colors();
    }

    public function updateColor($id, Request $request)
    {
        Color::find($id)->update([
            'name' => $request->input('name'),
            'code' => $request->input('code')
        ]);

        return $this->colors();
    }

    public function deleteColor(Request $request)
    {
        $id = $request->input('colorId');
        $detail = OrderDetail::where('color_id', $id)->first();
        if ($detail) {
            return response()->json("Delete failed.", 403);
        }
        Color::find($id)->delete();
        return response()->json("Delete Success.");
    }

    public function statistic()
    {
        $orders = OrderBuy::all();
        $users = User::all();
        $products = Product::with('details')->get();
        $revenue = 0;
        foreach ($products as $product) {
            $quantity = 0;
            foreach ($product->details as $detail) {
                $quantity = $quantity + $detail->quantity;
            }
            $revenue = $revenue + $quantity * ($product->price - $product->price_import);
        }

        return response()->json([
            'product' => $products->count(),
            'user' => $users->count(),
            'order' => $orders->count(),
            'revenue' => $revenue,
        ]);
    }

    public function statisticProduct()
    {
        $products = Product::with('images', 'details')->paginate(6);
        foreach ($products as $product) {
            $quantity = 0;
            foreach ($product->details as $detail) {
                $quantity = $quantity + $detail->quantity;
            }
            $product['quantity_export'] = $quantity;
            $product['quantity_import'] = $quantity + $product->quantity;
        }
        return $products->toJson();
    }

    public function searchProduct($keyword)
    {
        $products = Product::with('images', 'colors')
            ->where('name', 'like', '%' . $keyword . '%')
            ->orWhere('code', 'like', '%' . $keyword . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return response()->json([
                'products' => $products,
                'keyword' => $keyword
            ]
        );
    }
}
