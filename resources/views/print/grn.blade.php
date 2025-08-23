<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN Print View</title>
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
            <h2>Goods Received Note</h2>
        </div>

        <table class="details-table">
            <tr>
                <td><strong>GRN Code:</strong> {{ $grn->code }}</td>
                <td><strong>Delivered At:</strong> {{ $grn->delivered_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            <tr>
                <td><strong>Delivery Person:</strong> {{ $grn->delivery_person_name }}</td>
                <td><strong>Contact:</strong> {{ $grn->delivery_person_contact }}</td>
            </tr>
            <tr>
                <td><strong>Vehicle No:</strong> {{ $grn->vehicle_no }}</td>
                <td><strong>Created By:</strong> {{ $grn->createdBy->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <h3>Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Flavor</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $item)
                    <tr>
                        <td>{{ $item->product->sku }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->product->flavor }}</td>
                        <td class="text-right">{{ $item->qty }}</td>
                        <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="label">Total Items:</td>
                <td class="text-right">{{ $grn->total_items }}</td>
            </tr>
            <tr>
                <td class="label">Total Refillable:</td>
                <td class="text-right">{{ $grn->total_refillable }}</td>
            </tr>
            <tr>
                <td class="label">Total Non-Refillable:</td>
                <td class="text-right">{{ $grn->total_non_refillable }}</td>
            </tr>
            <tr>
                <td class="label">Total Cost:</td>
                <td class="text-right">{{ number_format($grn->total_cost, 2) }}</td>
            </tr>
        </table>

        <div class="footer">
            <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
