<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('account');
        }

        return view('auth.register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string', Password::min(8)->max(64), 'confirmed'],
        ]);

        $user = User::create([
            ...$validated,
            'role_id' => Role::where('slug', Role::CUSTOMER)->value('id'),
            // New customers default to Retail (Guest group = checkout tanpa akun).
            'customer_group_id' => CustomerGroup::where('slug', CustomerGroup::SLUG_RETAIL)->value('id'),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account');
    }
}
