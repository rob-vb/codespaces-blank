<?php
declare(strict_types=1);

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class Create extends Component
{
    public string $profileLabel;
    public string $profileTradeMode = 'live';
    public float $profilePaperFee = 10;
    public float $profileStartingBalance = 10000;

    public function createProfile(): void
    {
        $postData = [
            'label' => $this->profileLabel,
            'trade_mode' => $this->profileTradeMode,
            'paper_fee_bps' => $this->profilePaperFee,
            'starting_balance' => $this->profileStartingBalance
        ];

        try {
            $response = Http::remote()->post('/api/v2/profile/create/', $postData);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');
        }

        if ($response->failed()) {
            if ($response->status() === 422) {
                $errors = $response->json('errors') ?? ['label' => ['There was a validation error.']];

                throw ValidationException::withMessages($errors);
            }

            $message = (string) ($response->json('error') ?? 'Unable to save profile name. Please try again.');

            throw ValidationException::withMessages([
                'label' => $message,
            ]);
        }

        $this->js('document.getElementById("modal_create_profile").close()');
        $this->dispatch('update-profiles');
    }

    public function render()
    {
        return view('livewire.profile.create');
    }
}
