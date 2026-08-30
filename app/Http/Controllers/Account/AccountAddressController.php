<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountAddressController extends Controller
{
    public function index()
    {
        return view('account.addresses', [
            'addresses' => Auth::user()->addresses()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAddress($request);

        DB::transaction(function () use ($validated) {
            $address = Auth::user()->addresses()->create($validated);

            if ($address->is_default) {
                Auth::user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }
        });

        return back()->with('status', 'Alamat berhasil disimpan.');
    }

    public function update(Request $request, CustomerAddress $address)
    {
        $this->authorizeAddress($address);

        $validated = $this->validateAddress($request);

        DB::transaction(function () use ($address, $validated) {
            $address->update($validated);

            if ($address->is_default) {
                Auth::user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }
        });

        return back()->with('status', 'Alamat berhasil diperbarui.');
    }

    public function destroy(CustomerAddress $address)
    {
        $this->authorizeAddress($address);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            Auth::user()->addresses()->latest()->first()?->update(['is_default' => true]);
        }

        return back()->with('status', 'Alamat dihapus.');
    }

    public function setDefault(CustomerAddress $address)
    {
        $this->authorizeAddress($address);

        DB::transaction(function () use ($address) {
            Auth::user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('status', 'Alamat utama diperbarui.');
    }

    protected function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:25'],
            'province_id' => ['required', 'integer'],
            'city_id' => ['required', 'integer'],
            'province_name' => ['required', 'string', 'max:60'],
            'city_name' => ['required', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'address' => ['required', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }

    protected function authorizeAddress(CustomerAddress $address): void
    {
        abort_unless($address->user_id === Auth::id(), 403);
    }
}
