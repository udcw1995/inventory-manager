<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use League\Csv\Writer;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Operations';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'delivery']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('shop_id')
                    ->relationship('shop', 'name', fn (Builder $query) => $query->whereNull('deleted_at'))
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('owner_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('owner_phone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255),
                    ]),
                Forms\Components\DateTimePicker::make('issued_at')
                    ->default(now())
                    ->required(),

                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->options(Product::query()->whereNull('deleted_at')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $product = Product::find($state);
                                if ($product) {
                                    $set('unit_price', $product->selling_price);
                                    $set('is_refillable', $product->is_refillable);
                                    $set('fine_per_damaged', $product->fine_per_damaged);
                                }
                            }),
                        Forms\Components\Hidden::make('is_refillable'),
                        Forms\Components\Hidden::make('fine_per_damaged'),
                        Forms\Components\TextInput::make('qty')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $unitPrice = $get('unit_price');
                                $set('line_total', $state * $unitPrice);
                            }),
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $qty = $get('qty');
                                $set('line_total', $state * $qty);
                            }),
                        Forms\Components\TextInput::make('line_total')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('returned_empty')
                            ->numeric()
                            ->default(0)
                            ->hidden(fn (Forms\Get $get): bool => ! $get('is_refillable')),
                        Forms\Components\TextInput::make('damaged_lost')
                            ->numeric()
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $finePerDamaged = $get('fine_per_damaged');
                                $set('line_fine', $state * $finePerDamaged);
                            })
                            ->hidden(fn (Forms\Get $get): bool => ! $get('is_refillable')),
                        Forms\Components\TextInput::make('line_fine')
                            ->numeric()
                            ->readOnly()
                            ->default(0)
                            ->hidden(fn (Forms\Get $get): bool => ! $get('is_refillable')),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->live()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        $totalItems = 0;
                        $totalRefillable = 0;
                        $totalNonRefillable = 0;
                        $totalCost = 0;
                        $finesTotal = 0;

                        foreach ($get('items') as $item) {
                            $totalItems += $item['qty'];
                            $totalCost += $item['line_total'];
                            $finesTotal += $item['line_fine'];

                            $product = Product::find($item['product_id']);
                            if ($product) {
                                if ($product->is_refillable) {
                                    $totalRefillable += $item['qty'];
                                } else {
                                    $totalNonRefillable += $item['qty'];
                                }
                            }
                        }

                        $finalTotal = $totalCost + $finesTotal;

                        $set('total_items', $totalItems);
                        $set('total_refillable', $totalRefillable);
                        $set('total_non_refillable', $totalNonRefillable);
                        $set('total_cost', $totalCost);
                        $set('fines_total', $finesTotal);
                        $set('final_total', $finalTotal);
                    }),

                Forms\Components\Section::make('Totals')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('total_items')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('total_refillable')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('total_non_refillable')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('total_cost')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('fines_total')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('final_total')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->badge()
                    ->copyable()
                    ->copyMessage('Invoice code copied')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('shop.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->money('USD')
                    ->sortable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('fines_total')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('final_total')
                    ->money('USD')
                    ->sortable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('total_items')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('total_refillable')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('total_non_refillable')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('returned_refillable_total')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('damaged_lost_total')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignRight(),
                Tables\Columns\TextColumn::make('issuedBy.name')
                    ->label('Issued By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('downloadCsv')
                        ->label('Download CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $csv = Writer::createFromString();
                            $csv->insertOne([
                                'ID', 'Code', 'Shop', 'Issued At', 'Total Cost', 'Fines Total', 'Final Total', 'Total Items', 'Total Refillable', 'Total Non-Refillable', 'Returned Refillable Total', 'Damaged Lost Total', 'Issued By', 'Created At', 'Updated At',
                            ]);

                            foreach ($records as $record) {
                                $csv->insertOne([
                                    $record->id,
                                    $record->code,
                                    $record->shop->name ?? 'N/A',
                                    $record->issued_at->format('Y-m-d H:i:s'),
                                    $record->total_cost,
                                    $record->fines_total,
                                    $record->final_total,
                                    $record->total_items,
                                    $record->total_refillable,
                                    $record->total_non_refillable,
                                    $record->returned_refillable_total,
                                    $record->damaged_lost_total,
                                    $record->issuedBy->name ?? 'N/A',
                                    $record->created_at->format('Y-m-d H:i:s'),
                                    $record->updated_at->format('Y-m-d H:i:s'),
                                ]);
                            }

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv->toString();
                            }, 'invoices.csv', [
                                'Content-Type' => 'text/csv',
                            ]);
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
