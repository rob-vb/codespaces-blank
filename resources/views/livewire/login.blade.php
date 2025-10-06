<div class="flex grow h-full items-center justify-center p-8 px-4">
    <div class="card md:min-w-96 max-w-xl bg-base-100 shadow-sm">
        <div class="card-body">
            <div class="card-title text-gray-800">Sign in to NMST</div>
            <p>Use your existing <strong>CFGI</strong> email and password.</p>

            <form wire:submit.prevent="signIn">
                <div class="mt-3">
                    <p class="mb-1">Email</p>
                    <label class="input w-full validator">
                        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <g
                                stroke-linejoin="round"
                                stroke-linecap="round"
                                stroke-width="2.5"
                                fill="none"
                                stroke="currentColor"
                            >
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </g>
                        </svg>
                        <input type="email" name="login-email" placeholder="mail@site.com" wire:model.defer="email" required />
                    </label>
                    @error('email') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mt-1">
                    <p class="mb-1">Password</p>
                    <label class="input w-full">
                        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <g
                                stroke-linejoin="round"
                                stroke-linecap="round"
                                stroke-width="2.5"
                                fill="none"
                                stroke="currentColor"
                            >
                            <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z">
                            </path>
                            <circle cx="16.5" cy="7.5" r=".5" fill="currentColor"></circle>
                        </g>
                        </svg>
                        <input
                            type="password"
                            name="login-password"
                            required
                            placeholder="Password"
                            minlength="4"
                            wire:model.defer="password"
                        />
                    </label>
                    @error('password') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full mt-4">Sign in</button>

                <div class="mt-2 text-center text-xs">
                    Forgot your password? Reset on <a class="text-accent" href="https://cfgi.io/recover-password" target="_blank">CFGI</a>.
                </div>
            </form>
        </div>
    </div>
</div>
