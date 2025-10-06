@extends('layout.app')

@section('body')
<div class="flex grow h-full items-center justify-center p-8">
    <div class="card bg-base-100 shadow-sm">
        <figure>
            <img src="https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExNnczdWpyNXBxZHZ6ZXVnNzYxZmlnYnVwcmJyaXk2NXk1Ymc5aWgybyZlcD12MV9naWZzX3NlYXJjaCZjdD1n/d2lcHJTG5Tscg/giphy.gif" />
        </figure>
        <div class="card-body">
            <h2 class="card-title">Sorry, no access!</h2>
            <p class="max-w-[452px]">Your account doesn't have beta access yet. <a class="text-primary underline" href="https://x.com/NMST_io" target="_blank">Join us on X</a> to gain access.</p>
        </div>
    </div>
</div>
@endsection
