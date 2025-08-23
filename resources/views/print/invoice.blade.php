<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Print View</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            border: 1px solid #eee;
            padding: 10px;
            box-sizing: border-box;
        }
        .header,
        .footer {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #000;
        }
        .header p {
            margin: 0;
            font-size: 9px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .details-table td {
            padding: 3px;
            border: 1px solid #ddd;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 3px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .totals-table td {
            padding: 3px;
            border: 1px solid #ddd;
        }
        .totals-table .label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Company Name</h1>
            <p>Your Company Address, City, Country</p>
            <p>Phone: +123 456 7890 | Email: info@yourcompany.com</p>
            <h2>Invoice</h2>
        </div>

        <table class="details-table">
            <tr>
                <td><strong>Invoice Code:</strong> {{ $invoice->code }}</td>
                <td><strong>Issued At:</strong> {{ $invoice->issued_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            <tr>
                <td><strong>Shop:</strong> {{ $invoice->shop->name }}</td>
                <td><strong>Shop Address:</strong> {{ $invoice->shop->address }}</td>
            </tr>
            <tr>
                <td><strong>Shop Owner:</strong> {{ $invoice->shop->owner_name }}</td>
                <td><strong>Shop Phone:</strong> {{ $invoice->shop->owner_phone }}</td>
            </tr>
            <tr>
                <td><strong>Issued By:</strong> {{ $invoice->issuedBy->name ?? 'N/A' }}</td>
                <td></td>
            </tr>
        </table>

        <h3>Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Line Total</th>
                    <th class="text-right">Returned Empty</th>
                    <th class="text-right">Damaged/Lost</th>
                    <th class="text-right">Line Fine</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->product->sku }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-right">{{ $item->qty }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                        <td class="text-right">{{ $item->returned_empty }}</td>
                        <td class="text-right">{{ $item->damaged_lost }}</td>
                        <td class="text-right">{{ number_format($item->line_fine, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="label">Total Items:</td>
                <td class="text-right">{{ $invoice->total_items }}</td>
            </tr>
            <tr>
                <td class="label">Total Refillable:</td>
                <td class="text-right">{{ $invoice->total_refillable }}</td>
            </tr>
            <tr>
                <td class="label">Total Non-Refillable:</td>
                <td class="text-right">{{ $invoice->total_non_refillable }}</td>
            </tr>
            <tr>
                <td class="label">Total Cost:</td>
                <td class="text-right">{{ number_format($invoice->total_cost, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Fines Total:</td>
                <td class="text-right">{{ number_format($invoice->fines_total, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Final Total:</td>
                <td class="text-right">{{ number_format($invoice->final_total, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Returned Refillable Total:</td>
                <td class="text-right">{{ $invoice->returned_refillable_total }}</td>
            </tr>
            <tr>
                <td class="label">Damaged/Lost Total:</td>
                <td class="text-right">{{ $invoice->damaged_lost_total }}</td>
            </tr>
        </table>

        <div class="footer">
            <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
