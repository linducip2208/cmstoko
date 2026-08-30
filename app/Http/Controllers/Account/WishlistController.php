<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::where('user_id', Auth::id())
            ->with('product.category', 'product.brand')
            ->latest()
            ->get()
            ->filter(fn (Wishlist $w) => $w->product && $w->product->is_active);

        return view('account.wishlist', ['wishlists' => $wishlists]);
    }

    public function toggle(Request $request)
    {
        $validated = $request->validate(['product_id' => ['required', 'integer']]);

        $product = Product::active()->whereKey($validated['product_id'])->firstOrFail();

        $existing = Wishlist::where('user_id', Auth::id())->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();

            return back()->with('wishlist_removed', $product->name);
        }

        Wishlist::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        return back()->with('wishlist_added', $product->name);
    }

    public function destroy(Wishlist $wishlist)
    {
        abort_unless($wishlist->user_id === Auth::id(), 403);
        $wishlist->delete();

        return back();
    }
}
