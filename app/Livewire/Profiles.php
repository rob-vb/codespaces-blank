<?php
declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Attributes\On;
use Throwable;

class Profiles extends Component
{
    public array $profiles = [];
    public ?int $editingProfileId = null;
    public string $editingLabel = '';
    public string $originalEditingLabel = '';
    public array $statusUpdates = [];

    public function mount(): void
    {
        $this->profiles = $this->getProfiles();
    }

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

    private function findProfileIndex(int $profileId): ?int
    {
        foreach ($this->profiles as $index => $profile) {
            if ((int) ($profile['id'] ?? 0) === $profileId) {
                return $index;
            }
        }

        return null;
    }

    #[On('update-profiles')]
    public function updateProfiles(): void
    {
        $this->profiles = $this->getProfiles();
    }

    public function setProfileStatus(int $profileId, bool $isActive): void
    {
        $index = $this->findProfileIndex($profileId);

        if ($index === null) {
            return;
        }

        $profile = $this->profiles[$index];

        if (Str::lower((string) ($profile['trade_mode'] ?? '')) !== 'live') {
            return;
        }

        $previousStatus = (string) ($profile['status'] ?? 'INACTIVE');
        $nextStatus = $isActive ? 'ACTIVE' : 'INACTIVE';

        $this->profiles[$index]['status'] = $nextStatus;
        $this->statusUpdates[$profileId] = true;

        try {
            $response = Http::remote()->post('/api/v2/profile/status/', [
                'profile_id' => $profileId,
                'status' => $nextStatus,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->profiles[$index]['status'] = $previousStatus;
            unset($this->statusUpdates[$profileId]);
            $this->addError("status.{$profileId}", 'Unable to update profile status. Please try again.');

            return;
        }

        if ($response->failed()) {
            $this->profiles[$index]['status'] = $previousStatus;
            unset($this->statusUpdates[$profileId]);

            $message = (string) ($response->json('error') ?? 'Unable to update profile status. Please try again.');
            $this->addError("status.{$profileId}", $message);

            return;
        }

        unset($this->statusUpdates[$profileId]);
        $this->resetErrorBag("status.{$profileId}");
    }

    public function startEditing(int $profileId): void
    {
        $index = $this->findProfileIndex($profileId);

        if ($index === null) {
            return;
        }

        $label = (string) ($this->profiles[$index]['label'] ?? '');
        $resolvedLabel = filled($label) ? $label : sprintf('Profile #%d', $profileId);

        $this->editingProfileId = $profileId;
        $this->editingLabel = $resolvedLabel;
        $this->originalEditingLabel = $resolvedLabel;
        $this->resetErrorBag('label');
    }

    public function cancelEditing(): void
    {
        $this->editingProfileId = null;
        $this->editingLabel = '';
        $this->originalEditingLabel = '';
        $this->resetErrorBag('label');
    }

    public function saveEditingLabel(): void
    {
        if ($this->editingProfileId === null) {
            return;
        }

        $profileId = $this->editingProfileId;
        $normalizedLabel = trim($this->editingLabel);
        $originalLabel = trim($this->originalEditingLabel);

        if ($normalizedLabel === '') {
            return;
        }

        if ($normalizedLabel === $originalLabel) {
            $this->cancelEditing();

            return;
        }

        $this->editingLabel = $normalizedLabel;
        $this->updateProfileLabel($profileId, $normalizedLabel);
        $this->cancelEditing();
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
        return view('livewire.profiles', [
            'profiles' => $this->profiles,
        ]);
    }
}
