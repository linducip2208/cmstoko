<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)->latest()->take(5)->get();
        $stats = [
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'active_orders' => Order::where('user_id', $user->id)->whereIn('status', [
                Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_PROCESSING,
                Order::STATUS_READY_TO_SHIP, Order::STATUS_PARTIALLY_SHIPPED, Order::STATUS_SHIPPED,
            ])->count(),
            'wishlist' => Wishlist::where('user_id', $user->id)->count(),
            'reviews' => Review::where('user_id', $user->id)->count(),
            'returns' => ReturnRequest::where('user_id', $user->id)->count(),
        ];

        return view('account.dashboard', [
            'user' => $user,
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    public function profile()
    {
        return view('account.profile', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:25'],
        ]);

        $user->update($validated);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8)->max(64), 'confirmed'],
        ]);

        $user = Auth::user();
        $user->update(['password' => $validated['password']]);

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
