@extends('layout.app', ['title' => 'Customizer'])

@section('body')
<div class="p-4 py-8 sm:p-8 w-full lg:w-2/3 mx-auto">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h1 class="text-gray-800 text-3xl mb-2">Custom M.A.E.V.E</h1>
            <p>Welcome, {{ 'name' }}. Your beta access is <span class="text-success">active</span>. Build your custom M.A.E.V.E here.</p>
        </div>
    </div>

    <div class="tabs tabs-box sticky top-0 z-99 -ml-4 -mr-4 px-4 shadow-none">
        <input type="radio" name="tabs" class="tab" aria-label="Portfolio" data-id="portfolio" checked="checked" />
        <input type="radio" name="tabs" class="tab" aria-label="Timeframes" data-id="timeframes" />
        <input type="radio" name="tabs" class="tab" aria-label="Currencies" data-id="currencies" />
    </div>

    <livewire:customizer.portfolio />
    <livewire:customizer.timeframes />
    <livewire:customizer.currencies />
</div>
@endsection
