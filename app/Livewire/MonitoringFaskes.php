<?php

namespace App\Livewire;

use App\Models\Faskes;
use Livewire\Component;

class MonitoringFaskes extends Component
{
    public function render()
    {
        return view('livewire.monitoring-faskes', [
            'faskesList' => Faskes::withCount('ewsAssessments')->orderBy('nama_faskes')->get(),
        ])->layout('layouts.app', ['title' => 'Monitoring Faskes']);
    }
}
