<?php

namespace App\Livewire\Customizer;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class Timeframes extends Component
{
    public function saveTimeframes()
    {

    }

    private function getTimeframes()
    {
        try {
            $response = Http::remote()->get('/api/v2/timeframes');
            return $response->json('data') ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return null;
        }
    }

    public function render()
    {
        $timeframes = $this->getTimeframes();
        return view('livewire.customizer.timeframes', [
            'timeframes' => $timeframes
        ]);
    }
}
