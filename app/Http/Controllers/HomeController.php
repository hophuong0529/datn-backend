<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Color;
use App\Models\OrderReceiver;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderDetail;


class HomeController extends Controller
{
    public function allProduct()
    {
        $products = Product::with('images', 'colors', 'sub')->paginate(12);

        return $products->toJson();
    }

    public function productLatest()
    {
        $products = Product::with('images', 'colors')->orderBy('created_at', 'desc')->limit(8)->get();

        return $products->toJson();
    }

    public function productSale()
    {
        $products = Product::with('images', 'colors')->where('discount', '!=', 0)->orderBy('discount', 'desc')->limit(8)->get();

        return $products->toJson();
    }

    public function productTop()
    {
        $products = Product::with('images', 'colors')->where('is_top', 1)->orderBy('created_at', 'desc')->limit(8)->get();

        return $products->toJson();
    }

    public function productRelated($slug)
    {
        $names = Product::pluck('id', 'name');
        foreach ($names as $name => $id) {
            if (Str::slug($name) == $slug) {
                $product_id = $id;
                $product = Product::find($product_id);
                $products = Product::with('images')->where('subcategory_id', $product->subcategory_id)->get();

                return $products->toJson();
            }
        }
    }

    public function categories()
    {
        $categories = Category::with('subs')->get();

        return $categories->toJson();
    }

    public function detailProduct($slug)
    {
        $product = Product::with('images', 'colors', 'sub')->find($slug);

        // Thêm key "quantity" vào array
        foreach ($product->colors as $color) {
            $color['quantity'] = ProductColor::where('color_id', $color->id)->first()->quantity;
        }

        // Tìm chi tiết sp theo slug là tên sp
        if (!$product) {
            $names = Product::pluck('id', 'name');
            foreach ($names as $name => $id) {
                if (Str::slug($name) == $slug) {
                    $product_id = $id;
                    $product = Product::with('images', 'color', 'sub')->find($product_id);
                }
            }
        }

        return $product->toJson();
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        if (Auth::attempt($credentials)) {
            return response()->json(Auth::user());
        } else {
            return response()->json('Tên đăng nhập hoặc mật khẩu không chính xác.', 422);
        }
    }

    public function search($keyword)
    {
        $keyword = str_replace("+", " ", $keyword);
        $products = Product::with('images', 'colors')->where('name', 'like', '%' . $keyword . '%')->paginate(12);

        return response()->json([
                'products' => $products,
                'keyword' => $keyword
            ]
        );
    }

    public function categoryProduct($slug)
    {
        $category = Category::with('subs')->find($slug);
        $sub_categories = Category::with('subs')->find($slug)->subs;
        $products = Product::with('images', 'colors')->whereIn('subcategory_id', $sub_categories->pluck('id'))->paginate(12);
        return response()->json([
            'category' => $category,
            'subs' => $sub_categories,
            'products' => $products
        ]);
    }

    public function subCategoryProduct($slug)
    {
        $sub_category = SubCategory::find($slug);
        $category_id = SubCategory::find($slug)->category_id;
        $related = Category::find($category_id)->subs;
        $products = Product::with('images', 'colors')->where('subcategory_id', $slug)->paginate(12);
        return response()->json([
            'sub_category' => $sub_category,
            'related' => $related,
            'products' => $products
        ]);
    }

    public function addToCart($userId, Request $request)
    {
        $cart = Cart::where('user_id', $userId)->first();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $userId,
            ]);
            $cart->save();
        }

        $productId = $request->input('productId');
        $colorId = $request->input('colorId');
        $quantity = $request->input('quantity');
        $price = $request->input('price');
        $priceSale = $request->input('priceSale');
        $cartId = $cart->id;

        $exist = CartItem::where([
            ['cart_id', '=', $cartId],
            ['product_id', '=', $productId],
            ['color_id', '=', $colorId]
        ])->first();

        if ($exist) {
            $new_quantity = $exist->quantity + $quantity;
            CartItem::find($exist->id)->update([
                'quantity' => $new_quantity,
                'total_item' => $exist->price_sale * $new_quantity
            ]);
        } else {
            CartItem::insert([
                'cart_id' => $cartId,
                'product_id' => $productId,
                'color_id' => $colorId,
                'quantity' => $quantity,
                'price' => $price,
                'price_sale' => $priceSale,
                'total_item' => $priceSale * $quantity
            ]);
        }

        return response()->json("Add Success.");
    }

    public function cart($userId)
    {
        $cartId = Cart::with('items')->where('user_id', $userId)->first()->id;
        $cartItems = CartItem::with('product', 'color')->where('cart_id', $cartId)->get();
        $totalCart = 0;
        foreach ($cartItems as $item) {
            $product = $item->product;
            $product['color'] = $item->color->name;
            $product['quantity'] = $item->quantity;
            $product['images'] = $product->images;
            $totalCart += $item->total_item;
        }

        return response()->json([
            'cartItems' => $cartItems
        ]);
    }

    public function deleteCart(Request $request)
    {
        $cartId = Cart::where('user_id', $request->input('userId'))->first()->id;
        $colorId = Color::where('name', $request->input('color'))->first()->id;
        CartItem::where([
            ['cart_id', '=', $cartId],
            ['product_id', '=', $request->input('productId')],
            ['color_id', '=', $colorId]
        ])->delete();

        return response()->json("Delete Success.");
    }

    public function editCart(Request $request)
    {
        $cartId = Cart::where('user_id', $request->input('userId'))->first()->id;
        $colorId = Color::where('name', $request->input('color'))->first()->id;
        $new_quantity = $request->input('quantity');

        $cartItemId = CartItem::where([
            ['cart_id', '=', $cartId],
            ['product_id', '=', $request->input('productId')],
            ['color_id', '=', $colorId]
        ])->first()->id;
        $cartItem = CartItem::find($cartItemId);
        CartItem::find($cartItemId)->update([
            'quantity' => $new_quantity,
            'total_item' => $cartItem->price_sale * $new_quantity
        ]);

        return response()->json("Edit success.");
    }

    public function order(Request $request)
    {
        Order::insert([
            'user_id' => $request->input('account.id'),
            'method_id' => $request->input('method'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $order = Order::orderBy('id', 'desc')->first();

        OrderReceiver::insert([
            'order_id' => $order->id,
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'address' => $request->input('address'),
            'note' => $request->input('note')
        ]);

        foreach ($request->input('cartItems') as $item):

            OrderDetail::insert([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);

            $product = Product::find($item['id']);
            $product->update([
                'quantity' => ($product->quantity - $item['quantity'])
            ]);

        endforeach;

        return response()->json("Order Success.");
    }

}
