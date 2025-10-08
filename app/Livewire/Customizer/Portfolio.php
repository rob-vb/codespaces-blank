<?php

namespace App\Livewire\Customizer;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class Portfolio extends Component
{
    private function getBuckets()
    {
        try {
            $response = Http::remote()->get('/api/v2/buckets');
            return $response->json('data') ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return null;
        }
    }

    private function getTimeframes()
    {
        try {
            $response = Http::remote()->get('/api/v2/timeframes');
            return $response->json('data');
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return null;
        }
    }

    public function render()
    {
        $buckets = $this->getBuckets();
        $timeframes = $this->getTimeframes();
        return view('livewire.customizer.portfolio', [
            'buckets' => $buckets,
            'timeframes' => $timeframes
        ]);
    }
}
