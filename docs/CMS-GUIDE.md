# Panduan CMS (Pemilik Toko)

Semua konten dikelola dari panel `/admin`. Akun: `admin@tokokita.test`.

## Homepage (Tampilan → Homepage)

Section = blok konten berurutan. Klik **Buat Section**, pilih tipe:

| Tipe | Fungsi |
|------|--------|
| Hero | Headline besar + 1-2 tombol + gambar produk/gambar sendiri |
| Produk (Grid) | Sumber: unggulan / terbaru / terlaris / diskon / koleksi / kategori / pilih manual |
| Kategori (Grid) | Kartu kategori utama + jumlah produk |
| Teks Kaya | HTML terbatas (script otomatis dibuang) |
| Banner (Split) | Gambar + eyebrow + tombol |
| Bar Kepercayaan | Item dari **Pengaturan → trust_bar.items** |
| Newsletter | Form berlangganan |
| CTA | Ajakan aksi |
| FAQ | Pertanyaan aktif (bisa filter grup, dari Konten → FAQ) |
| Testimoni | Testimoni aktif; rating hanya tampil bila diisi |
| Artikel Blog | Artikel terbit terbaru (bisa filter kategori) |

Setiap section bisa: aktif/nonaktif, jadwal tayang (mulai–sampai), urut-ulang (drag), duplikat.

## Menu Navigasi (Tampilan → Menu Navigasi)

- Lokasi: **Header** (menggantikan navigasi kategori default) atau **Footer** (kolom Jelajahi).
- Item: label + target (URL kustom / Kategori / Merek / Halaman CMS), bisa dibuat sub-item, tab baru, urutan.
- Target yang dihapus otomatis disembunyikan dari storefront. URL eksternal diblokir.
- Tanpa menu aktif, storefront memakai navigasi kategori otomatis.

## Halaman CMS (Konten → Halaman)

Status: Draft / Terjadwal / Terbit / Arsip. Isi HTML terbatas (sanitized). SEO tab: meta title + description. Otomatis muncul di footer "Bantuan" dan sitemap.

## Blog (Konten → Blog, Kategori Blog)

Artikel: judul, slug otomatis, ringkasan, isi (rich editor), sampul, kategori, tag, penulis, status + tanggal terbit, SEO. Tombol cepat **Terbitkan** di tabel. Draft/terjadwal tidak terlihat publik.

## FAQ & Testimoni (Konten)

FAQ: pertanyaan + jawaban + grup + urutan. Testimoni: nama, peran, foto, kutipan, rating opsional (jangan mengarang rating — kosongkan bila tidak ada).

## Media (Konten → Media)

Upload gambar (JPG/PNG/WebP/AVIF/GIF/SVG maks 5 MB). Nama file diacak, SVG dibersihkan otomatis. Isi alt text untuk SEO/aksesibilitas. File yang masih dipakai katalog tidak bisa dihapus.

## Pengaturan (Pengaturan → Pengaturan Toko)

- Toko: nama, tagline, logo, favicon, kontak, media sosial
- Header/Footer: pengumuman berjalan, tentang, copyright
- SEO: judul & deskripsi beranda, gambar OG
- Kebijakan: masa retur, minimum gratis ongkir
- Pembayaran: rekening transfer manual (muncul di instruksi pembayaran)

## Tema (Tampilan → Tema)

Pilih preset (Editorial, Minimal, Luxury, Fashion, Tech, Market, Bold) atau override 3 warna inti. Perubahan langsung aktif tanpa rebuild.

## Promosi (Promosi)

- **Kupon**: kode manual, persen/nominal, minimum belanja, jendela, kuota.
- **Aturan Keranjang**: otomatis di checkout — kondisi (subtotal, produk, kategori+merek, jumlah) + aksi (persen/nominal/gratis ongkir) + grup pelanggan (Retail/VIP/Guest) + jadwal + kuota. Beberapa aturan bisa menumpuk; total dibatasi subtotal. Terlihat sebagai baris "Promo" di ringkasan checkout.
- **Flash Sale**: produk + harga flash + jadwal. Harga otomatis turun di seluruh storefront saat aktif dan kembali saat berakhir.
