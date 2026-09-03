<?php

namespace App\Livewire\Inventory\Alerts;

use App\Actions\Inventory\GetInventoryAlerts;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.inventory.alerts.index', (new GetInventoryAlerts)());
    }
}
