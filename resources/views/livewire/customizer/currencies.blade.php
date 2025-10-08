<div class="card bg-base-100 mt-2 shadow-sm hidden js-customizer-tab" id="currencies">
    <div class="card-body">
        <div class="card-title mb-4 text-gray-800">Enable currencies</div>

        @if($currencies)
            @foreach($currencies as $currency)
                @dump($currency)
            @endforeach
        @endif
    </div>
</div>
