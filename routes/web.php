<?php

use App\Http\Controllers\Account\AccountAddressController;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AccountOrderController;
use App\Http\Controllers\Account\ReturnController;
use App\Http\Controllers\Account\WishlistController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Livewire\CartPage;
use App\Livewire\CheckoutPage;
use App\Livewire\TrackOrder;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');

Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');

Route::get('/robots.txt', \App\Http\Controllers\RobotsController::class)->name('robots');

Route::get('/produk', ShopController::class)->name('shop');

Route::get('/produk/{slug}', [ProductController::class, 'show'])->name('product.show');

Route::get('/keranjang', CartPage::class)->name('cart');

Route::get('/checkout', CheckoutPage::class)->name('checkout');

Route::get('/lacak', TrackOrder::class)->name('track-order');

Route::get('/pesanan/{orderNumber}', [OrderController::class, 'success'])->name('order.success');

Route::post('/newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:10,1')
    ->name('newsletter.subscribe');

/*
|--------------------------------------------------------------------------
| CMS pages
|--------------------------------------------------------------------------
*/
Route::get('/halaman/{slug}', [CmsPageController::class, 'show'])->name('pages.show');

/*
|--------------------------------------------------------------------------
| Auth (customer)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'show'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->name('login.store');

    Route::get('/daftar', [RegisterController::class, 'show'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/lupa-kata-sandi', [PasswordResetLinkController::class, 'show'])->name('password.request');
    Route::post('/lupa-kata-sandi', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/atur-kata-sandi/{token}', [NewPasswordController::class, 'show'])->name('password.reset');
    Route::post('/atur-kata-sandi', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('/keluar', [LoginController::class, 'destroy'])->name('logout');

/*
|--------------------------------------------------------------------------
| Customer account
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('akun')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('account');

    Route::get('/profil', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/profil', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/profil/kata-sandi', [AccountController::class, 'updatePassword'])->name('account.password.update');

    Route::get('/pesanan', [AccountOrderController::class, 'index'])->name('account.orders');
    Route::get('/pesanan/{orderNumber}', [AccountOrderController::class, 'show'])->name('account.orders.show');
    Route::post('/pesanan/{orderNumber}/ulasan', [AccountOrderController::class, 'storeReview'])
        ->middleware('throttle:10,1')
        ->name('account.orders.review');

    Route::get('/alamat', [AccountAddressController::class, 'index'])->name('account.addresses');
    Route::post('/alamat', [AccountAddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/alamat/{address}', [AccountAddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/alamat/{address}', [AccountAddressController::class, 'destroy'])->name('account.addresses.destroy');
    Route::post('/alamat/{address}/utama', [AccountAddressController::class, 'setDefault'])->name('account.addresses.default');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('account.wishlist');
    Route::post('/wishlist', [WishlistController::class, 'toggle'])->name('account.wishlist.toggle');
    Route::delete('/wishlist/{wishlist}', [WishlistController::class, 'destroy'])->name('account.wishlist.destroy');

    Route::get('/pengembalian', [ReturnController::class, 'index'])->name('account.returns');
    Route::post('/pengembalian', [ReturnController::class, 'store'])->name('account.returns.store');
});

/*
|--------------------------------------------------------------------------
| Payment gateway webhook (public, signature-verified)
|--------------------------------------------------------------------------
*/
Route::post('/midtrans/webhook', [OrderController::class, 'webhook'])->name('midtrans.webhook');

Route::get('/midtrans/finish', [OrderController::class, 'finish'])->name('midtrans.finish');
