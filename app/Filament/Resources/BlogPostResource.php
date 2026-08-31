<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Blog\BlogPostResource\Pages;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Blog';

    protected static ?string $modelLabel = 'Artikel';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) BlogPost::where('status', BlogPost::STATUS_DRAFT)->count() ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Artikel')->tabs([
                Tab::make('Konten')
                    ->schema([
                        TextInput::make('title')->label('Judul')->required()->maxLength(200)->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        TextInput::make('slug')->required()->maxLength(220)->unique(ignoreRecord: true),
                        Textarea::make('excerpt')->label('Ringkasan')->maxLength(500)->rows(2)->columnSpanFull(),
                        RichEditor::make('content')->label('Isi Artikel')->required()->columnSpanFull()->toolbarButtons([
                            'bold', 'italic', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'blockquote', 'undo', 'redo',
                        ]),
                        FileUpload::make('cover')->label('Sampul')->image()->directory('blog')->maxSize(4096)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/avif'])->columnSpanFull(),
                    ])->columns(2),
                Tab::make('Organisasi')
                    ->schema([
                        Select::make('blog_category_id')->label('Kategori')
                            ->options(fn () => BlogCategory::orderBy('name')->pluck('name', 'id'))
                            ->searchable(),
                        Select::make('user_id')->label('Penulis')
                            ->options(fn () => User::whereNotNull('role_id')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->default(auth()->id()),
                        Select::make('tags')->label('Tag')
                            ->relationship('tags', 'name')
                            ->createOptionForm([
                                TextInput::make('name')->label('Nama')->required()->maxLength(80),
                            ])
                            ->multiple()->preload(),
                    ])->columns(1),
                Tab::make('Publikasi')
                    ->schema([
                        ToggleButtons::make('status')->label('Status')->options(BlogPost::STATUSES)->default('draft')->inline(),
                        DateTimePicker::make('published_at')->label('Tanggal Terbit')->helperText('Kosongkan untuk terbit saat disimpan (status Terbit).'),
                    ])->columns(2),
                Tab::make('SEO')
                    ->schema([
                        TextInput::make('seo.meta_title')->label('Meta Title')->maxLength(180)->columnSpanFull(),
                        Textarea::make('seo.meta_description')->label('Meta Description')->maxLength(320)->rows(3)->columnSpanFull(),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'author']))
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->limit(50)->weight('semibold'),
                TextColumn::make('category.name')->label('Kategori')->placeholder('—'),
                TextColumn::make('author.name')->label('Penulis')->placeholder('—'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => BlogPost::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        BlogPost::STATUS_PUBLISHED => 'success',
                        BlogPost::STATUS_SCHEDULED => 'warning',
                        BlogPost::STATUS_ARCHIVED => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('published_at')->label('Terbit')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('publish')
                    ->label('Terbitkan')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BlogPost $record) => $record->status !== BlogPost::STATUS_PUBLISHED)
                    ->action(fn (BlogPost $record) => $record->update([
                        'status' => BlogPost::STATUS_PUBLISHED,
                        'published_at' => $record->published_at ?? now(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
