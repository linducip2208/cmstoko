# Panduan Admin Toko (Operasional Harian)

## Dashboard & Laporan

- **Laporan Penjualan** (grup Laporan): pendapatan, pesanan dibayar, AOV untuk 7/30/90 hari + produk terlaris + export CSV.
- **Kartu Stok** (Persediaan): seluruh pergerakan stok dengan snapshot; export CSV.
- Semua aksi sensitif tercatat di **Pengaturan → Log Audit** (siapa, kapan, sebelum/sesudah; secret di-redaksi).

## Pesanan (grup Penjualan)

Detail pesanan menampilkan: timeline status, item (termasuk varian), catatan, alamat, pembayaran.

Aksi (semua lewat state machine — transisi invalid ditolak):

| Aksi | Efek |
|------|------|
| Invoice | Buat invoice sekali |
| Kirim | Partial/full; stok item dibatasi sisa; otomatis status Dikirim/Sebagian |
| Refund | Dibatasi sisa refundable; partial_refunded / refunded |
| Batalkan | Restock otomatis (ledger sale_cancel) |

Email otomatis ke pelanggan: pesanan diterima (dengan instruksi transfer), pembayaran terkonfirmasi, dikirim (dengan resi), selesai, dibatalkan.

## Konfirmasi Transfer Manual

Pesanan pending + metode transfer: cocokkan mutasi rekening (nominal harus sama dengan total), lalu ubah status → **Dibayar**. Untuk Midtrans, status berubah otomatis dari webhook.

## Stok

- Penyesuaian (mis. stok rusak) via produk → aksi stok atau modul Persediaan — SEMUA perubahan menulis kartu stok.
- Stok tidak boleh minus; oversell dicegah di level database (lock + conditional update).
- Cek kartu stok sebelum menuduh selisih: setiap perubahan punya referensi (pesanan/retur/penyesuaian).

## Retur (Pengembalian)

Alur: pelanggan ajukan (window 7 hari dari pesanan) → admin **Setujui** → barang diterima → **Barang Diterima (restock)** → **Dana Dikembalikan**. Kuantitas retur tidak bisa melebihi yang dibeli (semua pengajuan dihitung).

## ulasan

Ulasan pelanggan masuk sebagai **Pending**. Setujui/tolak di modul Ulasan. Rating produk hanya dari ulasan approved.

## Grup Pelanggan & Promosi

- Pelanggan → Grup Pelanggan: Retail (default), VIP, atau grup buatan sendiri.
- Aturan Keranjang bisa menarget grup tertentu (mis. VIP 10% setiap hari, Guest gratis ongkir min. belanja).
- Flash Sale: Promosi → Flash Sale — pilih produk + harga + jadwal.

## Redirect SEO (SEO → Redirects)

URL lama 404 → arahkan ke URL baru (301 permanen). Hit tercatat. Tujuan hanya path internal — domain lama/eksternal diblokir otomatis.
