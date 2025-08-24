<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">GRN Details: {{ $grn->code }}</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('filament.admin.resources.grns.edit', $grn) }}" 
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    <button onclick="window.print()" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>

        <div class="p-6">
            {{-- Basic Information --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">GRN Code</label>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->code }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Delivery Person</label>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->delivery_person_name }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Number</label>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->delivery_person_contact }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vehicle Number</label>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->vehicle_no }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Delivered At</label>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->delivered_at->format('M d, Y g:i A') }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created By</label>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->createdBy->name ?? 'Unknown' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created At</label>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->created_at->format('M d, Y g:i A') }}
                    </div>
                </div>

                @if($grn->updated_at != $grn->created_at)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
                    <div class="mt-1 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2">
                        {{ $grn->updated_at->format('M d, Y g:i A') }}
                    </div>
                </div>
                @endif
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Cost</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">${{ number_format($grn->total_cost, 2) }}</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600 dark:text-green-400">Total Items</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ number_format($grn->total_items) }}</div>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                    <div class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Refillable</div>
                    <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ number_format($grn->total_refillable) }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Non-Refillable</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($grn->total_non_refillable) }}</div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Items</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Unit Cost</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Line Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stock on Hand</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($grn->items as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $item->product->name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            SKU: {{ $item->product->sku }}
                                        </div>
                                        @if($item->product->flavor)
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $item->product->flavor }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->product->is_refillable ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $item->product->is_refillable ? 'Refillable' : 'Non-Refillable' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ number_format($item->qty) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        ${{ number_format($item->unit_cost, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        ${{ number_format($item->line_total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                            {{ number_format($stockOnHand[$item->product_id] ?? 0) }} units
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    Totals
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($grn->items->sum('qty')) }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    -
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    ${{ number_format($grn->items->sum('line_total'), 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    -
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Stock Movements --}}
            @if($grn->stockMovements->count() > 0)
                <div class="mt-8 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Stock Movements</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Direction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Occurred At</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($grn->stockMovements as $movement)
                                    <tr class="{{ $movement->reversal_of_id ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $movement->product->name ?? 'Unknown Product' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $movement->direction->value === 'IN' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                                                {{ $movement->direction->value }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ number_format($movement->quantity) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            ${{ number_format($movement->value, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $movement->occurred_at->format('M d, Y g:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($movement->reversal_of_id)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                                    Reversal
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                                    Active
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Back Button --}}
            <div class="mt-8 flex justify-start">
                <a href="{{ route('filament.admin.resources.grns.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to GRNs
                </a>
            </div>
        </div>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print:block {
                display: block !important;
            }
            
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</div>
