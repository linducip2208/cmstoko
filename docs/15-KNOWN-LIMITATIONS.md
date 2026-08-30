# 15 — KNOWN LIMITATIONS (honest list)
- Search default SQL LIKE dengan kontrak SearchEngine siap-swap; driver Meilisearch/Typesense belum ditulis.
- Media: belum ada konversi responsif otomatis (srcset/WebP) — upload hardening & metadata sudah.
- Concurrency test interleaved sequential; belum ada harness multi-proses paralel.
- RajaOngkir tetap Starter (provinsi/kota + cost); tanpa tracking/international.
- Menu builder: satu level nesting di UI; lebih dalam butuh edit langsung.
- WhatsApp channel = stub (log + endpoint opsional); butuh kredensial provider untuk aktif.
- Retur: katalog alasan fixed; window dari config.
- Visual QA via assertion DOM otomatis; review screenshot manusia (320–1920px) disarankan sebelum go-live.
