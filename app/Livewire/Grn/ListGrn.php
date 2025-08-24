<?php

namespace App\Livewire\Grn;

use App\Models\Grn;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ListGrn extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public $search = '';

    #[Url(as: 'sort')]
    public $sortField = 'created_at';

    #[Url(as: 'direction')]
    public $sortDirection = 'desc';

    #[Url(as: 'filter_month')]
    public $filterMonth = '';

    #[Url(as: 'filter_year')]
    public $filterYear = '';

    #[Url(as: 'show_deleted')]
    public $showDeleted = false;

    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterMonth()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    public function updatingShowDeleted()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterMonth', 'filterYear', 'showDeleted']);
        $this->resetPage();
    }

    public function getGrnsProperty()
    {
        $query = Grn::query()
            ->with(['createdBy', 'items'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                      ->orWhere('delivery_person_name', 'like', '%' . $this->search . '%')
                      ->orWhere('delivery_person_contact', 'like', '%' . $this->search . '%')
                      ->orWhere('vehicle_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterMonth, function ($query) {
                $query->whereRaw("CAST(strftime('%m', delivered_at) AS INTEGER) = ?", [$this->filterMonth]);
            })
            ->when($this->filterYear, function ($query) {
                $query->whereRaw("strftime('%Y', delivered_at) = ?", [(string) $this->filterYear]);
            });

        if ($this->showDeleted) {
            $query->onlyTrashed();
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);
    }

    public function render()
    {
        // Get available years using SQLite compatible function
        $availableYears = Grn::selectRaw("strftime('%Y', delivered_at) as year")
                            ->whereNotNull('delivered_at')
                            ->groupBy('year')
                            ->orderBy('year', 'desc')
                            ->pluck('year')
                            ->filter(); // Remove any null values

        return view('livewire.grn.list-grn', [
            'grns' => $this->grns,
            'years' => $availableYears,
        ]);
    }
}
