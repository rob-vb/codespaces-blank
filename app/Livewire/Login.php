<?php

namespace App\Livewire;

use Livewire\Component;

class Login extends Component
{
    public int $test;

    public function mount()
    {
        $this->test = 0;
    }

    public function up()
    {
        $this->test++;
    }

    public function render()
    {
        return view('livewire.login');
    }
}
