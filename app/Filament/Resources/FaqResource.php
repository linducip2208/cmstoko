<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Faqs\FaqResource\Pages;
use App\Models\Faq;
use BackedEnum;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?string $modelLabel = 'FAQ';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pertanyaan Umum')
                ->description('Ditampilkan pada section FAQ homepage dan halaman bantuan. Jawaban harus faktual.')
                ->schema([
                    TextInput::make('question')->label('Pertanyaan')->required()->maxLength(300)->columnSpanFull(),
                    Textarea::make('answer')->label('Jawaban')->required()->rows(4)->columnSpanFull(),
                    TextInput::make('group')->label('Grup')->maxLength(80)->helperText('Opsional — untuk memfilter section FAQ per grup'),
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
                TextColumn::make('question')->label('Pertanyaan')->searchable()->limit(60)->weight('semibold'),
                TextColumn::make('group')->label('Grup')->badge()->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('sort_order')->label('Urutan')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
