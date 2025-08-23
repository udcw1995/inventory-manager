<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalog';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'keeper']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('flavor')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_refillable')
                    ->required()
                    ->live()
                    ->dehydrated(function ($state, $record) {
                        if ($record && ! $record->is_refillable && $state && $record->bottleMovements()->exists()) {
                            throw new \Exception('Cannot change to refillable if product has existing bottle movements.');
                        }

                        return $state;
                    }),
                Forms\Components\TextInput::make('purchase_cost')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('selling_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('fine_per_damaged')
                    ->numeric()
                    ->prefix('$')
                    ->requiredIf('is_refillable', true)
                    ->hidden(fn (Forms\Get $get): bool => ! $get('is_refillable')),
                Forms\Components\Toggle::make('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('flavor')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_refillable')
                    ->boolean(),
                Tables\Columns\TextColumn::make('purchase_cost')
                    ->money('USD')
                    ->sortable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->money('USD')
                    ->sortable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('fine_per_damaged')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_refillable'),
                Tables\Filters\TernaryFilter::make('active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateSellingPrices')
                        ->form([
                            Forms\Components\TextInput::make('new_selling_price')
                                ->label('New Selling Price')
                                ->numeric()
                                ->required()
                                ->prefix('$'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(function (Product $product) use ($data) {
                                $product->update(['selling_price' => $data['new_selling_price']]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
