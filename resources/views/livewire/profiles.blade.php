<div class="overflow-x-auto">
    @error('api')
        <div class="alert alert-error mb-4">
            <span>{{ $message }}</span>
        </div>
    @enderror
    <table class="table">
        <thead>
            <tr>
                <th class="text-sm font-semibold uppercase tracking-wide text-gray-500">Name</th>
                <th class="text-sm font-semibold uppercase tracking-wide text-gray-500">Mode</th>
                <th class="text-sm font-semibold uppercase tracking-wide text-gray-500">Exchange</th>
                <th class="text-sm font-semibold uppercase tracking-wide text-gray-500">Paper Fee (bps)</th>
                <th class="text-sm font-semibold uppercase tracking-wide text-gray-500">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($profiles as $profile)
                @php
                    $profileId = (int) ($profile['id'] ?? 0);
                    $label = (string) ($profile['label'] ?? '');
                    $resolvedLabel = filled($label) ? $label : 'Profile #' . $profileId;
                    $tradeMode = strtolower((string) ($profile['trade_mode'] ?? ''));
                    $status = strtolower((string) ($profile['status'] ?? 'inactive'));
                    $isLiveProfile = $tradeMode === 'live';
                    $isActive = $status === 'active';
                    $statusLoading = (bool) ($statusUpdates[$profileId] ?? false);
                    $isEditing = $editingProfileId === $profileId;
                @endphp
                <tr wire:key="profile-{{ $profileId }}">
                    <td>
                        @if($isEditing)
                            <form
                                wire:submit.prevent="saveEditingLabel"
                                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                            >
                                <div class="w-full md:w-64">
                                    <input
                                        type="text"
                                        name="label"
                                        class="input input-bordered input-sm w-full"
                                        maxlength="120"
                                        required
                                        wire:model.defer="editingLabel"
                                    />
                                    @error('label')
                                        <p class="mt-2 text-xs text-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-primary"
                                        wire:loading.attr="disabled"
                                        wire:target="saveEditingLabel"
                                    >
                                        <span wire:loading.remove wire:target="saveEditingLabel">Save</span>
                                        <span
                                            class="loading loading-spinner loading-xs"
                                            wire:loading
                                            wire:target="saveEditingLabel"
                                        ></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-ghost"
                                        wire:click="cancelEditing"
                                        wire:loading.attr="disabled"
                                        wire:target="saveEditingLabel"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-base-content">{{ $resolvedLabel }}</span>
                                <button
                                    type="button"
                                    class="btn btn-square btn-xs btn-primary btn-soft btn-ghost"
                                    wire:click="startEditing({{ $profileId }})"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil w-4 h-4"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>
                                </button>
                            </div>
                        @endif
                    </td>
                    <td>
                        {{ $profile['trade_mode'] ? ucfirst($profile['trade_mode']) : '' }}
                    </td>
                    <td>
                        {{ $profile['exchange_id'] ?? '' }}
                    </td>
                    <td>
                        {{ $tradeMode === 'paper' ? number_format((float) ($profile['paper_fee_bps'] ?? 0), 2) : '' }}
                    </td>
                    <td>
                        @if($isLiveProfile)
                            <div class="flex items-center gap-3">
                                <span
                                    @class([
                                        'badge',
                                        'badge-sm',
                                        'badge-success' => $isActive,
                                        'badge-neutral badge-soft' => ! $isActive,
                                    ])
                                >
                                    {{ $isActive ? 'Active' : 'Inactive' }}
                                </span>
                                <label class="inline-flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        class="toggle toggle-success toggle-sm"
                                        role="switch"
                                        wire:change="setProfileStatus({{ $profileId }}, $event.target.checked)"
                                        @checked($isActive)
                                        @disabled($statusLoading)
                                        aria-checked="{{ $isActive ? 'true' : 'false' }}"
                                        aria-label="Toggle status for {{ $resolvedLabel }}"
                                    />
                                </label>
                                @if($statusLoading)
                                    <span class="loading loading-spinner loading-xs text-success"></span>
                                @endif
                            </div>
                            @error("status.{$profileId}")
                                <p class="mt-2 text-xs text-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </td>
                    <td class="text-right">
                        <button
                            type="button"
                            class="btn btn-sm btn-error btn-soft"
                            wire:click="deleteProfile({{ $profileId }})"
                            wire:confirm="Are you sure you want to delete this profile?"
                            wire:loading.attr="disabled"
                            wire:target="deleteProfile({{ $profileId }})"
                        >
                            <span
                                class="flex items-center gap-2"
                                wire:loading.remove
                                wire:target="deleteProfile({{ $profileId }})"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash w-4 h-4"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                Delete
                            </span>
                            <span
                                class="flex items-center gap-2"
                                wire:loading
                                wire:target="deleteProfile({{ $profileId }})"
                            >
                                <span class="loading loading-spinner loading-xs"></span>
                                Deleting...
                            </span>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-sm text-base-content/70">
                        No profiles found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <dialog
        id="modal_create_profile"
        class="modal"
    >
        <div class="modal-box relative">
            <button
                type="button"
                class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                onclick="this.closest('dialog').close()"
            >
                ✕
            </button>

            <h3 class="text-lg font-bold mb-4">Create New Profile</h3>

            @error('form')
                <p class="text-sm text-error mb-4">{{ $message }}</p>
            @enderror

            <livewire:profile.create />
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</div>
