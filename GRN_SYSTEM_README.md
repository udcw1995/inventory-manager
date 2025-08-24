# GRN (Good Received Note) System

## Overview

The GRN system has been completely redesigned with custom Livewire components to provide a modern, mobile-friendly, and dark/light mode compatible interface for managing inventory receipts.

## Features

### Create GRN
- **User-editable fields**: delivery_person_name, delivery_person_contact, vehicle_no
- **Delivered at**: Defaults to current date/time but editable
- **Auto-generated code**: Generated via NumberGeneratorService on save
- **Products grid**: Shows all active products with quantity fields (0 default)
- **Unit cost prefilled**: From product's purchase_cost (editable)
- **Inline product creation**: Modal dialog for creating new products
- **Live calculations**: Real-time totals for items, cost, refillable/non-refillable counts

### Edit GRN
- **Full editing capabilities**: All fields including products grid
- **Inventory adjustments**: Automatically reverses previous movements and posts new ones
- **Live calculations**: Same as create mode

### View GRN
- **Comprehensive view**: All GRN details, items, and related stock movements
- **Stock on hand**: Shows current inventory levels for each product
- **Print functionality**: Clean print view
- **Stock movement history**: Shows all related inventory movements

### List GRN
- **Advanced filtering**: Search, month/year filters, show deleted toggle
- **Sortable columns**: Click column headers to sort
- **Responsive design**: Mobile-friendly table with responsive columns
- **Pagination**: Configurable page size

## Inventory Integration

### On Save (Create/Edit)
1. Creates GRN record with calculated totals
2. Creates GRN items for products with quantity > 0
3. Posts IN movements via InventoryService for each item
4. Sets created_by to current authenticated user

### On Edit
1. Reverses all previous stock movements for the GRN
2. Updates GRN record with new data
3. Deletes and recreates GRN items
4. Posts new IN movements for updated quantities

### On Delete
1. Reverses all stock movements via GrnObserver
2. Soft deletes the GRN record
3. Inventory levels are correctly adjusted

## Technical Components

### Livewire Components
- `App\Livewire\Grn\CreateGrn`: Create form with products grid
- `App\Livewire\Grn\EditGrn`: Edit form with existing data
- `App\Livewire\Grn\ViewGrn`: Read-only view with stock levels
- `App\Livewire\Grn\ListGrn`: Filterable, sortable listing

### Database Compatibility
- **SQLite Compatible**: Uses `strftime()` functions for date filtering
- **MySQL Compatible**: Can be easily adapted for MySQL `YEAR()` and `MONTH()` functions
- **Date Filtering**: Month and year filters work correctly with SQLite

### Services Used
- `NumberGeneratorService`: Auto-generates GRN codes
- `InventoryService`: Handles stock movements (IN/OUT/reversals)

### Models
- `Grn`: Main GRN record
- `GrnItem`: Individual line items
- `StockMovement`: Inventory movement records

## Usage

### Navigation
- **List**: Access via Filament admin panel → Operations → Good Received Notes
- **Create**: Click "Create GRN" button from list view
- **Edit**: Click edit icon from list or view page
- **View**: Click view icon from list

### Creating a GRN
1. Fill in delivery information (person, contact, vehicle)
2. Adjust delivered date/time if needed
3. Enter quantities for products (use search/filter if many products)
4. Unit costs are pre-filled but editable
5. Use "New Product" button to create products inline if needed
6. Watch live totals update as you enter data
7. Click "Save GRN" when complete

### Editing a GRN
1. Navigate to edit page
2. Modify any fields as needed
3. Add/remove/change product quantities
4. System automatically handles inventory adjustments
5. Click "Update GRN" to save changes

### Mobile Support
- Responsive design works on all screen sizes
- Touch-friendly controls
- Optimized table layouts for mobile
- Collapsible sections for better mobile experience

### Dark/Light Mode
- Fully compatible with Filament's theme system
- Automatic color scheme detection
- Consistent styling across all views

## Testing

The system includes comprehensive tests covering:
- GRN creation and inventory updates
- Edit operations and inventory adjustments  
- Deletion and inventory reversals
- Stock calculation accuracy

Run tests with:
```bash
php artisan test tests/Feature/GrnWorkflowTest.php
```

## Troubleshooting

### Common Issues
1. **Products not showing**: Ensure products are marked as active
2. **Inventory not updating**: Check that InventoryService is working and StockMovement records are being created
3. **Totals not calculating**: Verify JavaScript is enabled and Livewire is working

### Debug Commands
```bash
# Check stock levels
php artisan tinker
>>> use App\Models\Product; use App\Services\InventoryService;
>>> $product = Product::first();
>>> app(InventoryService::class)->stockOnHand($product);

# List recent stock movements
>>> use App\Models\StockMovement;
>>> StockMovement::with('product')->latest()->take(10)->get();
```
