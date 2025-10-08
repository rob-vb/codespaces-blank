@extends('layout.app', ['title' => 'Profiles'])

@section('body')
<div class="p-4 md:p-8">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <section class="card bg-base-100 shadow-sm">
            <div class="card-body space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="card-title text-2xl font-semibold text-accent">M.A.E.V.E V2.0</h2>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary btn-soft btn-outline"
                        onclick="modal_create_profile.showModal()"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus-icon lucide-plus w-4 h-4"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Add new
                    </button>
                </div>

                <livewire:profiles />
            </div>
        </section>
    </div>
</div>
@endsection
