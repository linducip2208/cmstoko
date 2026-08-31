<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Testimonials\TestimonialResource\Pages;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Testimoni';

    protected static ?string $modelLabel = 'Testimoni';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Testimoni')
                ->description('Rating opsional — hanya tampil jika diisi. Jangan mengarang rating; gunakan nilai asli dari pelanggan.')
                ->schema([
                    TextInput::make('name')->label('Nama')->required()->maxLength(120),
                    TextInput::make('role_company')->label('Peran / Perusahaan')->maxLength(160),
                    FileUpload::make('avatar')->label('Foto')->image()->disk('public')->directory('testimonials')->maxSize(2048)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                    Select::make('rating')->label('Rating (opsional)')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])->native(false),
                    Textarea::make('quote')->label('Kutipan')->required()->rows(4)->columnSpanFull(),
                    TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                    Toggle::make('is_active')->label('Aktif')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('quote')->label('Kutipan')->limit(50),
                TextColumn::make('rating')->label('Rating')->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('sort_order')->label('Urutan')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
