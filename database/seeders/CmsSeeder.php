<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'admin@tokokita.test')->value('id');

        CmsPage::updateOrCreate(['slug' => 'tentang-kami'], [
            'title' => 'Tentang Kami',
            'excerpt' => 'Cerita singkat tentang siapa kami dan cara kami memilih produk.',
            'status' => CmsPage::STATUS_PUBLISHED,
            'user_id' => $author,
            'content' => <<<'HTML'
                <p>Kami adalah toko online yang berfokus pada kurasi produk sehari-hari dengan material pilihan dan desain tahan uji. Setiap produk melalui pengecekan sebelum dikirim.</p>
                <h2>Cara kami memilih produk</h2>
                <p>Kami bekerja dengan merek yang teruji dan memeriksa sampel sebelum masuk katalog. Bila produk tidak lolos standar, produk itu tidak dijual di sini.</p>
                <h2>Layanan pelanggan</h2>
                <p>Tim kami siap membantu melalui email dan WhatsApp pada jam kerja. Pesanan dapat dilacak kapan saja melalui halaman Lacak Pesanan.</p>
                HTML,
        ]);

        CmsPage::updateOrCreate(['slug' => 'syarat-ketentuan'], [
            'title' => 'Syarat & Ketentuan',
            'excerpt' => 'Ketentuan penggunaan layanan toko.',
            'status' => CmsPage::STATUS_PUBLISHED,
            'user_id' => $author,
            'content' => <<<'HTML'
                <h2>Pesanan dan pembayaran</h2>
                <p>Pesanan diproses setelah pembayaran terkonfirmasi. Harga yang berlaku adalah harga saat pesanan dibuat.</p>
                <h2>Pengembalian</h2>
                <p>Pengajuan pengembalian dapat dilakukan dari halaman akun sesuai kebijakan yang berlaku di toko ini.</p>
                HTML,
        ]);

        CmsPage::updateOrCreate(['slug' => 'kebijakan-privasi'], [
            'title' => 'Kebijakan Privasi',
            'excerpt' => 'Bagaimana kami menangani data pribadi kamu.',
            'status' => CmsPage::STATUS_PUBLISHED,
            'user_id' => $author,
            'content' => <<<'HTML'
                <p>Kami hanya mengumpulkan data yang diperlukan untuk memproses pesanan: nama, kontak, dan alamat pengiriman. Data tidak dijual atau dibagikan ke pihak yang tidak terkait pemrosesan pesanan.</p>
                <h2>Keamanan</h2>
                <p>Kata sandi disimpan dalam bentuk terenkripsi. Pembayaran diproses oleh penyedia pembayaran tersertifikasi.</p>
                HTML,
        ]);
    }
}
