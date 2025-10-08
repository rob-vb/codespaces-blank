<?php
declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\On;
use Throwable;

class Profiles extends Component
{
    public array $profiles;

    private function getProfiles(): array
    {
        try {
            $response = Http::remote()->get('/api/v2/profiles');

            return $response->json('data') ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return [];
        }
    }

    #[On('update-profiles')]
    public function updateProfiles(): void
    {
        $this->profiles = $this->getProfiles();
    }

    public function updateProfileLabel(int $profileId, string $label): void
    {
        $normalizedLabel = trim($label);

        $validated = validator(
            ['label' => $normalizedLabel],
            ['label' => ['required', 'string', 'max:120']]
        )->validate();

        try {
            $response = Http::remote()->post('/api/v2/profile/save-label/', [
                'profile_id' => $profileId,
                'label' => $validated['label'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'label' => 'Unable to save profile name. Please try again.',
            ]);
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

        $savedLabel = trim((string) ($response->json('data.label') ?? $validated['label']));

        foreach ($this->profiles as $index => $profile) {
            if (($profile['id'] ?? null) === $profileId) {
                $this->profiles[$index]['label'] = $savedLabel;
                break;
            }
        }

        $this->resetErrorBag('label');
    }

    public function deleteProfile(int $profileId): void
    {
        try {
            $response = Http::remote()->delete('/api/v2/profile/delete/', [
                'profile_id' => $profileId,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Unable to delete profile. Please try again.');

            return;
        }

        if ($response->failed()) {
            $message = (string) ($response->json('error') ?? 'Unable to delete profile. Please try again.');
            $this->addError('api', $message);

            return;
        }

        $deleted = (bool) ($response->json('success') ?? false);

        if (! $deleted) {
            $this->addError('api', 'Profile deletion was not confirmed by the server.');

            return;
        }

        $this->profiles = array_values(
            array_filter(
                $this->profiles,
                static fn (array $profile): bool => (int) ($profile['id'] ?? 0) !== $profileId
            )
        );

        $this->resetErrorBag('api');
        $this->dispatch('update-profiles');
    }

    public function render()
    {
        $this->profiles = $this->getProfiles();

        return view('livewire.profiles', [
            'profiles' => $this->profiles,
        ]);
    }
}
