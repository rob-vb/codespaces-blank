<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\Component;

final class Toast extends Component
{
    public string $message = '';

    public string $variant = 'success';

    public bool $visible = false;

    private const VARIANT_CLASSES = [
        'success' => 'alert-success',
        'error' => 'alert-error',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];

    #[On('toast')]
    public function notify(string $message, string $variant = 'success'): void
    {
        $this->message = $message;
        $this->variant = Arr::exists(self::VARIANT_CLASSES, $variant) ? $variant : 'info';
        $this->visible = true;
    }

    public function hide(): void
    {
        $this->visible = false;
    }

    public function getAlertClassesProperty(): string
    {
        return self::VARIANT_CLASSES[$this->variant] ?? self::VARIANT_CLASSES['info'];
    }

    public function render(): View
    {
        return view('livewire.notifications.toast');
    }
}

