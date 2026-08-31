# 17 — FINAL 99 GAPS (open items)

Format: AREA — GAP — SEVERITY — CARA MENUJU 9.9

1. CI belum pernah dieksekusi runner GitHub asli — MEDIUM — push pertama memverifikasi; risiko konfigurasi kecil (mysql health, pint path).
2. PHPStan level 5 dengan baseline 793 — MEDIUM — utang lama terkunci; turunkan bertahap ke level 8 tanpa baseline untuk skor 9.9.
3. Payment driver Midtrans-only — MEDIUM — Xendit/Duitku/Tripay adapter harus bisa dipasang tanpa menyentuh core (contract sudah ada); butuh kontrak+driver generik kedua untuk membuktikan abstraksi.
4. Search LIKE — MEDIUM — kontrak siap-swap; driver Meilisearch/Typesense belum; predictive suggest belum include collections/pages/blog.
5. Multi-warehouse: belum ada stok reservation per gudang di checkout (flat stock = lock surface; level hanya mirror distribusi) — MEDIUM — pindahkan deduksi checkout ke level-level (allotment) untuk klaim penuh.
6. Returns: katalog alasan fixed — LOW — tabel return_reasons admin-managed.
7. Media: belum ada folder/grouping + replace-file workflow — LOW.
8. Menu builder: UI satu level — LOW (model mendukung nested).
9. Produk types: virtual/downloadable/bundle/grouped belum ada — HIGH untuk klaim "general-purpose CMS" — butuh ProductType strategy + secure download delivery.
10. Notifications: belum ada event Processing/Refund/ReturnRequested mail (paid/shipped/completed/cancelled/placed sudah) — LOW.
11. Visual QA: otomatis via DOM; belum screenshot human-review loop terinTEGRASI — LOW (disarankan manual go-live check).
12. Reports: belum per-variant/category/brand breakdown + period comparison — LOW.
13. Consent banner (GDPR-style) — belum ada (Indonesia tidak wajib; arsitektur consent-ready) — LOW.
14. Observability: structured logs + health endpoint /up ada, belum ada alerting hooks — LOW.

TIDAK AKTIF DIBANGUN (butuh keputusan bisnis/kredensial): WhatsApp provider live, Midtrans production key, domain produksi.
