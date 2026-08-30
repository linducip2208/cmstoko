# Pembayaran

## Arsitektur

```
PaymentGateway (contract)
├── MidtransGateway        Snap + webhook terverifikasi
├── ManualTransferGateway  Instruksi + konfirmasi admin
└── PaymentManager         resolver driver aktif
```

Driver aktif: Midtrans bila `MIDTRANS_SERVER_KEY` terisi, selain itu transfer manual.

## Midtrans

1. Checkout → `PaymentService::snapToken($order)` (Snap API, item details + diskon + ongkir).
2. Popup Snap; selesai → redirect `/midtrans/finish` (hanya meneruskan ke halaman pesanan).
3. Sumber kebenaran = webhook `POST /midtrans/webhook`:

| Jaminan | Implementasi |
|---------|--------------|
| Signature asli | `hash_equals(sha512(order_id + status_code + gross_amount + serverKey))` |
| Nominal cocok | gross_amount === order.total (anti-tamper) |
| Idempoten | ledger `payment_transactions` unique (gateway, transaction_id); replay diabaikan |
| Anti-regresi | pending tidak pernah menurunkan paid; paid+ tidak pernah kembali pending |
| Expire/deny/cancel | → cancelled + restock |
| Challenge | tetap pending sampai accept |

Catatan: transaksi penipuan (deny) pada pesanan sudah paid → tetap paid, hanya ledger dicatat (barang mungkin sudah dikirim — keputusan manual).

## Transfer Manual

- Instruksi rekening dari **Pengaturan → Pembayaran → Rekening Transfer** (tampil di checkout, order success, email, lacak pesanan).
- Konfirmasi admin: cocokkan nominal → status Dibayar (state machine).
- Pembatalan pesanan transfer → restock otomatis.

## Aturan uang

- Semua nominal integer IDR (tanpa pecahan).
- Diskon = kupon + cart rules; total tidak pernah minus.
- Refund dibatasi `refundableAmount()` (total − refund terproses).
