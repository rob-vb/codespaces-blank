<?php

namespace App\Livewire\Customizer;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class Currencies extends Component
{
    private function getCurrencies()
    {
        try {
            $response = Http::remote()->get('/api/v2/currencies');
            return $response->json('data') ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return null;
        }
    }

    public function render()
    {
        $currencies = $this->getCurrencies();

        return view('livewire.customizer.currencies', [
            'currencies' => $currencies
        ]);
    }
}
