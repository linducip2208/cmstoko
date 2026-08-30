<?php

namespace App\Filament\Pages;

use App\Support\Audit;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Exceptions\Halt;
use UnitEnum;

class ManageSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    public string $view = 'filament.pages.manage-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan Toko';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('settings.update') ?? false;
    }

    /** Nested settings state, flattened to dot-keys on save. */
    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'store' => [
                'name' => Settings::get('store.name'),
                'tagline' => Settings::get('store.tagline'),
                'logo' => Settings::get('store.logo'),
                'favicon' => Settings::get('store.favicon'),
                'email' => Settings::get('store.email'),
                'phone' => Settings::get('store.phone'),
                'whatsapp' => Settings::get('store.whatsapp'),
                'address' => Settings::get('store.address'),
                'social' => [
                    'instagram' => Settings::get('store.social.instagram'),
                    'tiktok' => Settings::get('store.social.tiktok'),
                    'facebook' => Settings::get('store.social.facebook'),
                    'youtube' => Settings::get('store.social.youtube'),
                ],
            ],
            'header' => [
                'announcement_enabled' => (bool) Settings::get('header.announcement_enabled', true),
                'announcement' => Settings::get('header.announcement'),
            ],
            'footer' => [
                'about' => Settings::get('footer.about'),
                'copyright' => Settings::get('footer.copyright'),
            ],
            'seo' => [
                'home_title' => Settings::get('seo.home_title'),
                'home_description' => Settings::get('seo.home_description'),
                'og_image' => Settings::get('seo.og_image'),
            ],
            'policy' => [
                'return_days' => Settings::get('policy.return_days', 7),
                'free_shipping_min' => Settings::get('policy.free_shipping_min'),
            ],
            'payments' => [
                'bank_accounts' => Settings::get('payments.bank_accounts', []),
            ],
        ];

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Pengaturan')
                    ->tabs([
                        Tab::make('Toko')
                            ->schema([
                                Section::make('Identitas')
                                    ->schema([
                                        TextInput::make('store.name')->label('Nama Toko')->required()->maxLength(60),
                                        TextInput::make('store.tagline')->label('Tagline')->maxLength(120)->columnSpanFull(),
                                        FileUpload::make('store.logo')
                                            ->label('Logo')
                                            ->image()
                                            ->directory('branding')
                                            ->maxSize(1024)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                                            ->columnSpanFull(),
                                        FileUpload::make('store.favicon')
                                            ->label('Favicon')
                                            ->directory('branding')
                                            ->maxSize(256)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Section::make('Kontak')
                                    ->schema([
                                        TextInput::make('store.email')->label('Email')->email()->maxLength(150),
                                        TextInput::make('store.phone')->label('Telepon')->maxLength(25),
                                        TextInput::make('store.whatsapp')->label('WhatsApp')->numeric()->maxLength(20)->helperText('Format internasional tanpa +, mis. 6281234567890'),
                                        TextInput::make('store.address')->label('Alamat')->maxLength(200)->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Section::make('Media Sosial')
                                    ->schema([
                                        TextInput::make('store.social.instagram')->label('Instagram URL')->url()->maxLength(200),
                                        TextInput::make('store.social.tiktok')->label('TikTok URL')->url()->maxLength(200),
                                        TextInput::make('store.social.facebook')->label('Facebook URL')->url()->maxLength(200),
                                        TextInput::make('store.social.youtube')->label('YouTube URL')->url()->maxLength(200),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('Header & Footer')
                            ->schema([
                                Section::make('Header')
                                    ->schema([
                                        Toggle::make('header.announcement_enabled')->label('Tampilkan Pengumuman')->live(),
                                        TextInput::make('header.announcement')->label('Teks Pengumuman')->maxLength(200)->columnSpanFull(),
                                    ])
                                    ->columns(1),
                                Section::make('Footer')
                                    ->schema([
                                        \Filament\Forms\Components\Textarea::make('footer.about')->label('Tentang Toko')->maxLength(300)->rows(3)->columnSpanFull(),
                                        TextInput::make('footer.copyright')->label('Copyright')->maxLength(120),
                                    ])
                                    ->columns(1),
                            ]),
                        Tab::make('SEO')
                            ->schema([
                                Section::make('Beranda')
                                    ->schema([
                                        TextInput::make('seo.home_title')->label('Judul Beranda')->maxLength(120),
                                        \Filament\Forms\Components\Textarea::make('seo.home_description')->label('Meta Deskripsi Beranda')->maxLength(320)->rows(2)->columnSpanFull(),
                                        FileUpload::make('seo.og_image')->label('Gambar OG Default')->image()->directory('branding')->maxSize(2048)->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),
                        Tab::make('Kebijakan')
                            ->schema([
                                Section::make('Retur & Ongkir')
                                    ->schema([
                                        TextInput::make('policy.return_days')->label('Jangka Waktu Retur (hari)')->numeric()->minValue(0)->maxValue(90),
                                        TextInput::make('policy.free_shipping_min')->label('Minimum Gratis Ongkir (Rp)')->numeric()->minValue(0),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('Pembayaran')
                            ->schema([
                                Section::make('Rekening Transfer Manual')
                                    ->description('Ditampilkan pada instruksi pembayaran transfer untuk pesanan manual.')
                                    ->schema([
                                        Repeater::make('payments.bank_accounts')
                                            ->label('Rekening')
                                            ->schema([
                                                TextInput::make('bank')->label('Bank')->required()->maxLength(30),
                                                TextInput::make('number')->label('Nomor Rekening')->required()->maxLength(30),
                                                TextInput::make('holder')->label('Nama Pemilik')->required()->maxLength(80),
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasPermission('settings.update') ?? false, 403);

        $state = $this->form->getState();

        $before = Settings::bag();

        $this->persist($state);

        Settings::flush();

        Audit::record('settings.update', subject: null, before: ['settings' => 'snapshot omitted'], after: [
            'keys' => array_keys($this->flatten($state)),
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Pengaturan tersimpan')
            ->success()
            ->send();
    }

    protected function persist(array $state, string $prefix = ''): void
    {
        foreach ($state as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && ! array_is_list($value) && $path !== 'payments.bank_accounts') {
                $this->persist($value, $path);

                continue;
            }

            if ($value === '') {
                Settings::set($path, null, $this->groupFor($path));

                continue;
            }

            Settings::set($path, $value, $this->groupFor($path));
        }
    }

    protected function groupFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'header.') => 'header',
            str_starts_with($key, 'footer.') => 'footer',
            str_starts_with($key, 'seo.') => 'seo',
            str_starts_with($key, 'payments.') => 'payments',
            str_starts_with($key, 'policy.') => 'policies',
            default => 'branding',
        };
    }

    protected function flatten(array $state, string $prefix = ''): array
    {
        $out = [];

        foreach ($state as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && ! array_is_list($value)) {
                $out += $this->flatten($value, $path);

                continue;
            }

            $out[$path] = $value;
        }

        return $out;
    }

    public function title(): string
    {
        return 'Pengaturan Toko';
    }

    public function getHeading(): string
    {
        return 'Pengaturan Toko';
    }

    public function getSubheading(): ?string
    {
        return 'Identitas toko, pengumuman, SEO, kebijakan, dan rekening pembayaran.';
    }
}
