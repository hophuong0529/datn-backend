<?php

namespace App\Http\Controllers;

use App\Models\ProductColor;
use Illuminate\Http\Request;
use App\Repositories\Product\ProductRepository;
use Carbon\Carbon;
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

    public function storeProduct(Request $request)
    {
        //Insert Product color
        $data = $request->all();
        $product = $this->repository->create($data);

        //Insert ProductColor table
        $colors = json_decode($request->colors);
        foreach ($colors as $color) {
            ProductColor::insert([
                'product_id' => $product->id,
                'color_id' => $color->id,
                'quantity' => $color->quantity,
            ]);
        }

        //Insert ProductImage table
        $images = $request->images;
        foreach ($images as $image) {
            $path = time() . '-' . $image->getClientOriginalName();;
            ProductImage::insert([
                'path' => 'images/' . $path,
                'product_id' => $product->id,
            ]);
            $image->storeAs('public/images', $path);
        }

        return response()->json($images);
    }

    public function updateProduct($id, Request $request)
    {
        $data = $request->all();
        $data['updated_at'] = Carbon::now();

        $this->repository->edit($data, $id);

        $images = $request->images;
        if ($images) {
            ProductImage::where('product_id', $id)->delete();
            foreach ($images as $image) {
                $path = time() . '-' . $image->getClientOriginalName();
                ProductImage::insert([
                    'path' => 'images/' . $path,
                    'product_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $image->storeAs('public/images', $path);
            }
        }

        return response()->json("Edit Success.");
    }

    public function deleteProduct(Request $request)
    {
        $id = $request->productId;
        $this->repository->delete($id);
        ProductImage::where('product_id', $id)->delete();
        ProductColor::where('product_id', $id)->delete();

        return response()->json("Deleted Success.");
    }
}
