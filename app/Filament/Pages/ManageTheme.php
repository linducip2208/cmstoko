<?php

namespace App\Filament\Pages;

use App\Support\Audit;
use App\Support\Settings;
use App\Support\Theme;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use UnitEnum;

class ManageTheme extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    public string $view = 'filament.pages.manage-theme';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string|UnitEnum|null $navigationGroup = 'Tampilan';

    protected static ?string $navigationLabel = 'Tema';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('appearance.update') ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $custom = (array) (Settings::get('theme.custom') ?? []);

        $this->data = [
            'preset' => Theme::activePreset(),
            'custom' => [
                '--color-paper' => $custom['--color-paper'] ?? null,
                '--color-accent' => $custom['--color-accent'] ?? null,
                '--color-ink' => $custom['--color-ink'] ?? null,
            ],
        ];

        $this->form->fill($this->data);
    }

    public function form(Schema $schema): Schema
    {
        $presetOptions = [];

        foreach (Theme::presets() as $key => $preset) {
            $presetOptions[$key] = $preset['label'];
        }

        $customFields = [];

        foreach (config('theme-presets.overridable', []) as $var => $meta) {
            $customFields[] = ColorPicker::make("custom.{$var}")
                ->label($meta['label'])
                ->helperText("Kosongkan untuk mengikuti preset. {$var}")
                ->regex('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/');
        }

        return $schema
            ->components([
                Section::make('Preset Tema')
                    ->description('Satu sistem komponen, token warna/font/radius yang berbeda. Mengubah tema tidak pernah menyentuh logika toko.')
                    ->schema([
                        Radio::make('preset')
                            ->label('')
                            ->options($presetOptions)
                            ->descriptions(collect(Theme::presets())->map(fn ($p) => $p['description'])->all())
                            ->required()
                            ->columnSpanFull(),
                    ]),
                Section::make('Kustomisasi (opsional)')
                    ->description('Override warna di atas preset aktif. Kosong = ikuti preset.')
                    ->schema($customFields)
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasPermission('appearance.update') ?? false, 403);

        $state = $this->form->getState();

        $before = ['preset' => Theme::activePreset(), 'custom' => Settings::get('theme.custom')];

        Settings::set('theme.preset', $state['preset'], 'appearance');
        Settings::set('theme.custom', array_filter($state['custom'] ?? [], fn ($v) => $v !== null && $v !== ''), 'appearance');

        Theme::flush();

        Audit::record('settings.update', subject: null, before: $before, after: [
            'preset' => $state['preset'],
        ]);

        \Filament\Notifications\Notification::make()->title('Tema tersimpan')->success()->send();
    }

    public function title(): string
    {
        return 'Tema';
    }

    public function getHeading(): string
    {
        return 'Tema';
    }

    public function getSubheading(): ?string
    {
        return 'Preset tampilan storefront + kustomisasi warna. Perubahan langsung aktif.';
    }
}
