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

class AdminController extends Controller
{
    protected $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function colors()
    {
        $colors = Color::all();
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

        return $categories->toJson();
    }

    public function orders()
    {
        $orders = OrderBuy::with('receiver', 'status', 'user', 'method')->get();
        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                $detail['product'] = Product::with('images')->where('id', $detail->product_id)->first();
            }
        }

        return $orders->toJson();
    }

    public function producers()
    {
        $producers = Producer::all();

        foreach ($producers as $producer) {
            $quantity_export = 0;
            $productsId = [];
            foreach ($producer->products as $product) {
                array_push($productsId, $product->id);
            }
            $products_sold = OrderDetail::select('product_id', 'quantity')->whereIn('product_id', $productsId)->get();
            foreach ($products_sold as $product) {
                $quantity_export = $quantity_export + $product->quantity;
            }
            $producer['quantity_export'] = $quantity_export;
        }

        return $producers->toJson();
    }

    public function storeProduct(Request $request)
    {
        //Insert Product color
        $data = $request->all();
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
        foreach ($images as $image) {
            $path = time() . '-' . $image->getClientOriginalName();
            ProductImage::insert([
                'path' => 'images/' . $path,
                'product_id' => $product->id,
            ]);
            $image->storeAs('public/images', $path);
        }

        return response()->json($product);
    }

    public function updateProduct($id, Request $request)
    {
        //Update ProductColor table
        $data = $request->all();

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

    public function deleteProduct(Request $request)
    {
        $id = $request->productId;
        $this->repository->delete($id);
        ProductImage::where('product_id', $id)->delete();
        ProductColor::where('product_id', $id)->delete();

        return response()->json("Delete Success.");
    }

    public function subCategories()
    {
        $categories = SubCategory::with('category')->paginate(8);
        return $categories->toJson();
    }

    public function storeCategory(Request $request)
    {
        Category::insert([
            'name' => $request->input('name')
        ]);

        return $this->categories();
    }

    public function storeSubCategory(Request $request)
    {
        SubCategory::insert([
            'name' => $request->input('name'),
            'category_id' => $request->input('categoryId')
        ]);

        return $this->subCategories();
    }

    public function updateCategory($id, Request $request)
    {
        Category::find($id)->update([
            'name' => $request->input('name')
        ]);

        return $this->categories();
    }

    public function updateSubCategory($id, Request $request)
    {
        SubCategory::find($id)->update([
            'name' => $request->input('name'),
            'category_id' => $request->input('categoryId')
        ]);

        return $this->subCategories();
    }

    public function deleteCategory(Request $request)
    {
        $id = $request->input('categoryId');
        Category::find($id)->delete();

        return response()->json("Delete Success.");
    }

    public function deleteSubCategory(Request $request)
    {
        $id = $request->input('categoryId');
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
        Producer::find($id)->delete();

        return response()->json("Delete Success.");
    }

    public function updateOrder($id, Request $request)
    {
        OrderBuy::find($id)->update([
            'status_id' => $request->input('statusId'),
        ]);

        return $this->orders();
    }

    public function updateUser($id, Request $request)
    {
        User::find($id)->update([
            'is_active' => $request->input('isActive'),
        ]);

        return $this->users();
    }
}
