<x-layouts.app.sidebar>
   <div class="flex flex-1 flex-col overflow-y-auto">
        <main class="flex-1 lg:pl-72">
            <div class="p-4 sm:p-6 lg:p-8">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    {{ __('Perfil') }}
                </h2>

                @php
                    $viewerIsAdmin = auth()->user()->type === \App\Enums\UserType::BOARD;
                    $isViewingOwnProfile = auth()->id() === $user->id;
                    $profileIsEmployee = $user->type === \App\Enums\UserType::EMPLOYEE;
                    $canEditDetails = $viewerIsAdmin || ($isViewingOwnProfile && !$profileIsEmployee);
                @endphp

                <div class="mt-8 max-w-7xl space-y-6">

                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-zinc-800/50">
                        <div class="max-w-xl">
                            <section>
                                <header>
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        Informação do Perfil
                                    </h2>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Atualize a informação do seu perfil e o seu endereço de email.
                                    </p>
                                </header>

                                <form method="post" action="{{ route('profile.update', $user) }}" class="mt-6 space-y-6">
                                    @csrf
                                    @method('patch')

                                    <div>
                                        <x-input-label for="name" :value="__('Name')" />
                                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name"
                                                      :disabled="!$canEditDetails" />
                                    </div>

                                    <div>
                                        <x-input-label for="email" :value="__('Email')" />
                                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username"
                                                      :disabled="!$canEditDetails" />
                                    </div>

                                    @if ($user->type === \App\Enums\UserType::MEMBER || $user->type === \App\Enums\UserType::BOARD)
                                        <div>
                                            <x-input-label for="nif" :value="__('NIF')" />
                                            <x-text-input id="nif" name="nif" type="text" class="mt-1 block w-full" :value="old('nif', $user->nif)"
                                                          :disabled="!$canEditDetails" />
                                        </div>
                                    @endif

                                    @if ($canEditDetails)
                                        <div class="flex items-center gap-4">
                                            <x-primary-button>{{ __('Guardar') }}</x-primary-button>
                                            @if (session('status') === 'profile-updated')
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Guardado.') }}</p>
                                            @endif
                                        </div>
                                    @else
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">Não tem permissão para alterar estes dados.</p>
                                    @endif
                                </form>
                            </section>
                        </div>
                    </div>

                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-zinc-800/50">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</x-layouts.app.sidebar>
