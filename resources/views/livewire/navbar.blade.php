<div class="navbar flex justify-between bg-base-100 shadow-sm">
    <div class="flex items-center">
        <img class="w-6 h-6" src="{{ asset('/images/nmst.png') }}" alt="NMST" />
        <span class="ml-2 font-bold text-gray-800">Beta v0.4</span>
    </div>

    @if($this->isLoggedIn())
        <div class="flex items-center gap-4 ml-auto mt-2 sm:mt-0">
            <div class="flex items-center gap-2">
                <label for="active-profile-select" class="text-sm font-medium text-gray-600">Profile</label>
                <div class="relative">
                    <select
                        id="active-profile-select"
                        name="user_profile_id"
                        class="select select-bordered select-sm min-w-[9rem]"
                        wire:model.live="selectedProfileId"
                        wire:loading.attr="disabled"
                        wire:target="selectedProfileId,reloadProfiles"
                    >
                        <option value="" disabled {{ $selectedProfileId === null ? 'selected' : '' }}>Select profile</option>
                        @foreach($profiles as $profile)
                            <option value="{{ $profile['id'] }}">
                                {{ $profile['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button">
                    <div class="avatar avatar-placeholder cursor-pointer">
                        <div class="bg-neutral text-neutral-content w-8 rounded-full">
                            {{ !empty($this->getUser()['name']) ? Str::ucfirst(Str::substr($this->getUser()['name'], 0, 1))  : '' }}
                        </div>
                    </div>
                </div>

                <ul tabindex="0"
                    class="menu dropdown-content bg-base-100 rounded-box border-1 border-gray-200 z-1 mt-2 w-52 p-2 shadow-md">
                    <li>
                        <a class="flex items-center gap-2" href="/trades">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-line-chart w-4 h-4"><path d="M3 3v18h18"/><path d="M19 9l-4 4-2-2-3 3"/></svg>
                            Trades
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center gap-2" href="/customizer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot-icon lucide-bot w-4 h-4"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                            Customizer
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center gap-2" href="/profiles">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round-icon lucide-users-round w-4 h-4"><path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/></svg>
                            Profiles
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center gap-2" href="/settings">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings-icon lucide-settings w-4 h-4"><path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/><circle cx="12" cy="12" r="3"/></svg>
                            Settings
                        </a>
                    </li>
                    <li>
                        <a href="/logout" class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out w-4 h-4"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    @endif


</div>
