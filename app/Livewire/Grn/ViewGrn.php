<?php

namespace App\Livewire\Grn;

use App\Models\Grn;
use App\Services\InventoryService;
use Livewire\Component;

class ViewGrn extends Component
{
    public Grn $grn;
    public $stockOnHand = [];

    public function mount(Grn $grn)
    {
        $this->grn = $grn->load(['items.product', 'createdBy', 'stockMovements']);
        $this->calculateStockOnHand();
    }

    public function calculateStockOnHand()
    {
        $inventoryService = app(InventoryService::class);
        
        foreach ($this->grn->items as $item) {
            $this->stockOnHand[$item->product_id] = $inventoryService->stockOnHand($item->product);
        }
    }

    public function render()
    {
        return view('livewire.grn.view-grn');
    }
}
