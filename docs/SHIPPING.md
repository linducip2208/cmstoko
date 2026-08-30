# Pengiriman

## Dua mode

| Mode | Kapan | Perilaku |
|------|-------|----------|
| API (RajaOngkir Starter) | `RAJAONGKIR_API_KEY` terisi | Provinsi → kota dropdown; ongkir per kurir (JNE/POS/TIKI) berdasarkan berat keranjang + origin city |
| Flat | tanpa API key | Tarif flat `config('shop.flat_shipping_cost')` + service/ETA default |

Checkout memilih kurir → layanan; **total dikunci di server saat order dibuat** (perubahan tarif setelahnya tidak memengaruhi pesanan existing).

## Fulfillment

- `OrderFulfillmentService::ship()` — partial/full; kuantitas per item dibatasi sisa; otomatis status `partially_shipped`/`shipped`.
- Shipment punya: nomor (`SHP/...`), kurir, layanan, resi, biaya, tanggal.
- Beberapa shipment per pesanan didukung (kirim sebagian dulu).

## Gratis ongkir

Dua jalur:
1. **Aturan Keranjang** aksi `free_shipping` (kondisi apa pun: minimum belanja, grup, produk tertentu) — ongkir jadi 0 di checkout dan tercatat di `orders.applied_rules`.
2. Kebijakan "minimum gratis ongkir" di settings dipakai sebagai konten/informasi (bukan otomatis) — gunakan Aturan Keranjang untuk enforcement.

## Pengembangan driver baru

Implement kontrak di `App\Contracts\ShippingProvider` (extract driver berikutnya) atau tambahkan metode pada `ShippingService`; pertahankan perilaku: input berat gram + kota tujuan → daftar `{service, description, cost, etd}`.
