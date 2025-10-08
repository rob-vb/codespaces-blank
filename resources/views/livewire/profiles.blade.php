<div class="overflow-x-auto">
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
            @if($profiles)
                @foreach($profiles as $profile)
                    @php
                        $resolvedLabel = filled($profile['label'] ?? null)
                            ? $profile['label']
                            : 'Profile #' . $profile['id'];
                    @endphp
                    <tr
                        wire:key="profile-{{ $profile['id'] }}"
                        x-data="{
                            id: {{ $profile['id'] }},
                            originalLabel: @js($resolvedLabel),
                            label: @js($resolvedLabel),
                            editing: false,
                            loading: false,
                            startEditing() {
                                this.editing = true;
                                this.label = this.originalLabel;
                                this.$nextTick(() => this.$refs.labelInput.focus());
                            },
                            cancelEditing() {
                                this.editing = false;
                                this.label = this.originalLabel;
                            },
                            async save() {
                                if (this.loading) {
                                    return;
                                }

                                this.loading = true;
                                try {
                                    const nextLabel = this.label.trim();

                                    if (!nextLabel.length) {
                                        this.loading = false;
                                        return;
                                    }

                                    if (nextLabel === this.originalLabel) {
                                        this.label = this.originalLabel;
                                        this.editing = false;
                                        this.loading = false;
                                        return;
                                    }

                                    await this.$wire.call('updateProfileLabel', this.id, nextLabel);
                                    this.originalLabel = nextLabel;
                                    this.label = nextLabel;
                                    this.editing = false;
                                } catch (error) {
                                    console.error(error);
                                } finally {
                                    this.loading = false;
                                }
                            }
                        }"
                    >
                        <td>
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-1" x-show="!editing">
                                    <span class="font-medium" x-text="originalLabel"></span>
                                    <button
                                        type="button"
                                        class="btn btn-square btn-xs btn-primary btn-soft btn-ghost"
                                        x-on:click="startEditing()"
                                    >
                                        <span class="sr-only">Edit profile name</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil w-4 h-4"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                </div>
                                <div class="flex flex-col gap-2" x-show="editing" x-cloak>
                                    <input
                                        type="text"
                                        name="label"
                                        class="input input-bordered input-sm w-full md:w-auto"
                                        maxlength="120"
                                        required
                                        x-model="label"
                                        x-ref="labelInput"
                                        x-on:keydown.enter.prevent="save()"
                                        x-on:keydown.escape.prevent="cancelEditing()"
                                    />
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            x-on:click="save()"
                                            :disabled="loading"
                                        >
                                            <span x-show="!loading">Save</span>
                                            <span x-show="loading" class="loading loading-spinner loading-xs"></span>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-ghost"
                                            x-on:click="cancelEditing()"
                                            :disabled="loading"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                    @error('label')
                                        <p class="text-sm text-error" x-show="editing">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </td>
                        <td>
                            {{ $profile['trade_mode'] ?? '' }}
                        </td>
                        <td>
                            {{ $profile['exchange_id'] ?? '' }}
                        </td>
                        <td>
                            {{ $profile['trade_mode'] === 'paper' ? number_format($profile['paper_fee_bps'], 2) : '' }}
                        </td>
                        <td>

                        </td>
                        <td class="text-right">
                            <button
                                type="button"
                                class="btn btn-sm btn-error btn-soft"
                                wire:click="deleteProfile({{ $profile['id'] }})"
                                wire:confirm="Are you sure you want to delete this profile?"
                                wire:loading.attr="disabled"
                                wire:target="deleteProfile({{ $profile['id'] }})"
                            >
                                <span
                                    class="flex items-center gap-2"
                                    wire:loading.remove
                                    wire:target="deleteProfile({{ $profile['id'] }})"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash w-4 h-4"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Delete
                                </span>
                                <span
                                    class="flex items-center gap-2"
                                    wire:loading
                                    wire:target="deleteProfile({{ $profile['id'] }})"
                                >
                                    <span class="loading loading-spinner loading-xs"></span>
                                    Deleting...
                                </span>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif
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
                x-on:click="$el.closest('dialog').close()"
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
