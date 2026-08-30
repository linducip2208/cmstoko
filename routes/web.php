<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Livewire\CartPage;
use App\Livewire\CheckoutPage;
use App\Livewire\TrackOrder;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/produk', ShopController::class)->name('shop');

Route::get('/produk/{slug}', [ProductController::class, 'show'])->name('product.show');

Route::get('/keranjang', CartPage::class)->name('cart');

Route::get('/checkout', CheckoutPage::class)->name('checkout');

Route::get('/lacak', TrackOrder::class)->name('track-order');

Route::get('/pesanan/{orderNumber}', [OrderController::class, 'success'])->name('order.success');

Route::get('/midtrans/finish', [OrderController::class, 'finish'])->name('midtrans.finish');

Route::post('/midtrans/webhook', [OrderController::class, 'webhook'])->name('midtrans.webhook');
