<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GrnResource\Pages;
use App\Models\Grn;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use League\Csv\Writer;

class GrnResource extends Resource
{
    protected static ?string $model = Grn::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Operations';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'keeper']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('delivery_person_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('delivery_person_contact')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('vehicle_no')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('delivered_at')
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
                                    $set('unit_cost', $product->purchase_cost);
                                }
                            }),
                        Forms\Components\TextInput::make('qty')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $unitCost = $get('unit_cost');
                                $set('line_total', $state * $unitCost);
                            }),
                        Forms\Components\TextInput::make('unit_cost')
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
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->live()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                        $totalCost = 0;
                        $totalItems = 0;
                        $totalRefillable = 0;
                        $totalNonRefillable = 0;

                        foreach ($get('items') as $item) {
                            $totalCost += $item['line_total'];
                            $totalItems += $item['qty'];

                            $product = Product::find($item['product_id']);
                            if ($product) {
                                if ($product->is_refillable) {
                                    $totalRefillable += $item['qty'];
                                } else {
                                    $totalNonRefillable += $item['qty'];
                                }
                            }
                        }

                        $set('total_cost', $totalCost);
                        $set('total_items', $totalItems);
                        $set('total_refillable', $totalRefillable);
                        $set('total_non_refillable', $totalNonRefillable);
                    }),

                Forms\Components\Section::make('Totals')
                    ->compact()
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('total_cost')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
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
                    ->copyMessage('GRN code copied')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('delivery_person_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('delivery_person_contact')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vehicle_no')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
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
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
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
                                'ID', 'Code', 'Delivery Person', 'Contact', 'Vehicle No', 'Delivered At', 'Total Cost', 'Total Items', 'Total Refillable', 'Total Non-Refillable', 'Created By', 'Created At', 'Updated At',
                            ]);

                            foreach ($records as $record) {
                                $csv->insertOne([
                                    $record->id,
                                    $record->code,
                                    $record->delivery_person_name,
                                    $record->delivery_person_contact,
                                    $record->vehicle_no,
                                    $record->delivered_at->format('Y-m-d H:i:s'),
                                    $record->total_cost,
                                    $record->total_items,
                                    $record->total_refillable,
                                    $record->total_non_refillable,
                                    $record->createdBy->name ?? 'N/A',
                                    $record->created_at->format('Y-m-d H:i:s'),
                                    $record->updated_at->format('Y-m-d H:i:s'),
                                ]);
                            }

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv->toString();
                            }, 'grns.csv', [
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
            'index' => Pages\ListGrns::route('/'),
            'create' => Pages\CreateGrn::route('/create'),
            'edit' => Pages\EditGrn::route('/{record}/edit'),
        ];
    }
}
