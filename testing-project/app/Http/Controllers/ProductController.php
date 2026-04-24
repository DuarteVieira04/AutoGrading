<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFormRequest;
use App\Models\Product;
use Illuminate\View\View;
use Illuminate\Http\Request;

use function Pest\Laravel\call;

class ProductController extends Controller
{

    public function index(Request $request) : View
    {
        $name = $request->input('name');
        $categoryId = $request->input('category_id');
        $price = $request->input('price');
        $outOfStock = $request->boolean('outOfStock');
        $belowThreshold = $request->boolean('belowThreshold');


        $query = Product::query();

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = [
            'id', 'name', 'description', 'price', 'stock', 'category_id', 'stock_lower_limit', 'stock_upper_limit'
        ];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $query->orderBy($sort, $direction);
        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($price) {
            $query->where('price', '>=', $price);
        }
        if ($belowThreshold) {
            $query->whereColumn('stock', '<', 'stock_lower_limit');
        }
        if ($outOfStock) {

            $query->where('stock', 0);
        }

        $products = $query->paginate(20);

        return view('products.index', [
            'products' => $products,
            'name' => $name,
            'categoryId' => $categoryId,
            'price' => $price,
            'outOfStock' => $outOfStock,
            'belowThreshold' => $belowThreshold,
        ]);
    }

    public function showCase(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        if ($request->boolean('only_discount')) {
            $query->whereNotNull('discount')
                ->where('discount', '>', 0)
                ->whereNotNull('discount_min_qty')
                ->where('discount_min_qty', '>', 0);
        }

        if ($request->filled('price_order')) {
            $direction = $request->input('price_order') === 'desc' ? 'desc' : 'asc';
            $query->orderBy('price', $direction);
        }

        $products = $query->paginate(24);

        return view('products.showcase', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(ProductFormRequest $request)
    {
        $newProduct = Product::create($request->validated());

        $url = route('admin.products.show', ['product' => $newProduct]);
        $htmlMessage = "Product <a href='$url'><strong>{$newProduct->id}</strong>
                    - </a> New Product has been created successfully!";
        return redirect()->route('products.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', $htmlMessage);
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }


    public function update(ProductFormRequest $request, Product $product)
    {

        $product->update($request->validated());

        return redirect()->route('products.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Product deleted successfully!');
    }
}
