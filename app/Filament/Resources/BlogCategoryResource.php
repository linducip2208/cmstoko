<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Blog\BlogCategoryResource\Pages;
use App\Models\BlogCategory;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Kategori Blog';

    protected static ?string $modelLabel = 'Kategori Blog';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(120)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->maxLength(140)->unique(ignoreRecord: true),
            Textarea::make('description')->label('Deskripsi')->maxLength(500)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                TextColumn::make('posts_count')->label('Artikel')->counts('posts')->alignEnd(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogCategories::route('/'),
            'create' => Pages\CreateBlogCategory::route('/create'),
            'edit' => Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
