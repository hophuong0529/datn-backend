<?php

namespace App\Http\Controllers;

use App\Models\OrderMethod;
use App\Models\StatusOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Color;
use App\Models\Receiver;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\SubCategory;
use App\Models\OrderBuy;
use App\Models\OrderDetail;


class UserController extends Controller
{
    public function updateProfile($id, Request $request)
    {
        User::find($id)->update([
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'address' => $request->input('address')
        ]);

        return response()->json('success');
    }

    public function allProduct()
    {
        $products = Product::with('images', 'colors', 'sub', 'producer')->paginate(12);
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
        $product = Product::with('images', 'colors', 'sub', 'producer')->find($slug);

        // Thêm key "quantity" vào array
        foreach ($product->colors as $color) {
            $color['quantity'] = ProductColor::where([
                'product_id' => $slug,
                'color_id' => $color->id
            ])->first()->quantity;
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
        $credentials = $request->only(['username', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
        $user = Auth::user();
        $token = $request->user()->createToken('Access Token')->plainTextToken;

        return response()->json(['success' => true, 'access_token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();;
        return response()->json("success");
    }


    public function search($keyword)
    {
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
        //Check tồn tại user_id
        $cart = Cart::where('user_id', $userId)->first();

        //Nếu không tồn tại thì tạo mới Cart
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

        //Check tồn tại CartItem
        $exist = CartItem::where([
            ['cart_id', '=', $cartId],
            ['product_id', '=', $productId],
            ['color_id', '=', $colorId]
        ])->first();


        //Nếu đã tồn tại CartItem, thay đổi số lượng và tổng
        if ($exist) {
            $new_quantity = $exist->quantity + $quantity;
            CartItem::find($exist->id)->update([
                'quantity' => $new_quantity,
                'total_item' => $exist->price_sale * $new_quantity
            ]);
        } //Nếu k tồn tại thì tạo mới CartItem
        else {
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

    public function storeOrder(Request $request)
    {
        $codeOrder = rand(10000000, 99999999);
        //Thêm vào OrderBuy
        OrderBuy::insert([
            'code' => $codeOrder,
            'user_id' => $request->input('userId'),
            'method_id' => $request->input('methodId'),
            'total_bill' => $request->input('totalCart') + 35000
        ]);

        //Thêm vào Receiver
        $order = OrderBuy::orderBy('id', 'desc')->first();
        Receiver::insert([
            'order_id' => $order->id,
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'note' => $request->input('note')
        ]);

        //Thêm vào OrderDetail
        foreach ($request->input('cartItems') as $item) {
            OrderDetail::insert([
                'order_id' => $order->id,
                'product_id' => $item['id'],
                'color_id' => Color::where('name', $item['color'])->first()->id,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'price_sale' => $item['price'] * (100 - $item['discount']) / 100,
                'total_item' => ($item['price'] * (100 - $item['discount']) / 100) * $item['quantity']
            ]);

            //Update quantity ProductColor
            $productColorId = ProductColor::where([
                'product_id' => $item['id'],
                'color_id' => Color::where('name', $item['color'])->first()->id,
            ])->first()->id;

            $productColor = ProductColor::find($productColorId);
            ProductColor::find($productColorId)->update([
                'quantity' => ($productColor->quantity - $item['quantity'])
            ]);

            //Update quantity Product
            $product = Product::find($item['id']);
            Product::find($item['id'])->update([
                'quantity' => ($product->quantity - $item['quantity'])
            ]);
        }

        //Xóa CartItem
        $cartId = Cart::where('user_id', $request->input('userId'))->first()->id;
        CartItem::where('cart_id', $cartId)->delete();

        return response()->json($codeOrder);
    }

    public function orders($userId)
    {
        $orders = OrderBuy::with('receiver', 'details')->where('user_id', $userId)->get();
        foreach ($orders as $order) {
            $order['method'] = OrderMethod::where('id', $order->method_id)->first()->name;
            $order['status'] = StatusOrder::where('id', $order->status_id)->first()->status;
            foreach ($order->details as $item) {
                $product = $item->product;
                $product['color'] = $item->color->name;
                $product['quantity'] = $item->quantity;
                $product['images'] = $product->images;
            }
        }

        return $orders->toJson();
    }

    public function orderMethods()
    {
        $methods = OrderMethod::all();
        return $methods->toJson();
    }

    public function changePassword($id, Request $request)
    {
        $user = User::find($id)->first();
        if (Hash::check($request->input('oldPassword'), $user->password)) {
            $user = User::find($id)->update([
                'password' => Hash::make($request->input('newPassword'))
            ]);
            return response()->json(11111);
        } else {
            return response()->json(['error' => 'Sai mật khẩu!'], 401);
        }
    }
}
