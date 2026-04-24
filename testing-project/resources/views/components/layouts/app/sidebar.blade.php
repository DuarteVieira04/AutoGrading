<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <div class="flex min-h-screen">

            <flux:sidebar sticky stashable class="w-72 border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

                <a href="{{ route('home') }}" class="mb-0" wire:navigate>
                    <x-app-logo />
                </a>

                @guest
                    <flux:navlist variant="outline">
                        <flux:navlist.group :heading="'Welcome!'" class="grid">
                            <flux:navlist.item icon="cube" :href="route('products.showcase')" :current="request()->routeIs('products.showcase')" wire:navigate>Catalog</flux:navlist.item>
                            <flux:navlist.item icon="shopping-cart" :href="route('cart.show')" :current="request()->routeIs('cart.show')" wire:navigate>My Cart</flux:navlist.item>
                            <flux:navlist.item icon="key" :href="route('login')" :current="request()->routeIs('login')" wire:navigate>Login</flux:navlist.item>
                            <flux:navlist.item icon="user-plus" :href="route('register')" :current="request()->routeIs('register')" wire:navigate>Register</flux:navlist.item>
                        </flux:navlist.group>
                    </flux:navlist>
                @endguest

                @auth
                    @php
                        $userType = auth()->user()?->type;
                        $isBoard = $userType === \App\Enums\UserType::BOARD;
                        $isEmployee = $userType === \App\Enums\UserType::EMPLOYEE;
                        $isMember = $userType === \App\Enums\UserType::MEMBER;
                        $isPending = $userType === \App\Enums\UserType::PENDING_MEMBER;
                    @endphp


                    @if($isPending)
                        <div class="m-2">
                            <a href="#" class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                Pay Membership Fee
                            </a>
                        </div>
                    @endif

                    <flux:navlist variant="outline">
                        <flux:navlist.group :heading="'Store'" class="grid">
                            <flux:navlist.item icon="cube" :href="route('products.showcase')" :current="request()->routeIs('products.showcase')" wire:navigate>Catalog</flux:navlist.item>
                            <flux:navlist.item icon="shopping-cart" :href="route('cart.show')" :current="request()->routeIs('cart.show')" wire:navigate>My Cart</flux:navlist.item>

                            <flux:navlist.item icon="credit-card" :href="route('card.my.show')" :current="request()->routeIs('card.my.show')" wire:navigate>My Card</flux:navlist.item>
                            <flux:navlist.item icon="list-bullet" :href="route('orders.showcase')" :current="request()->routeIs('orders.showcase')" wire:navigate>My Orders</flux:navlist.item>

                        </flux:navlist.group>
                    </flux:navlist>

                    @if($isEmployee || $isBoard)
                        <section class="mt-4">
                            <h3 class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Staff</h3>
                            <flux:navlist variant="outline" class="grid">
                                <flux:navlist.group :heading="'Store Management'" class="grid">
                                    <flux:navlist.item icon="shopping-cart" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')" wire:navigate>Orders</flux:navlist.item>
                                    <flux:navlist.item icon="truck" :href="route('admin.supply-orders.index')" :current="request()->routeIs('admin.supply-orders.*')" wire:navigate>Supply Orders</flux:navlist.item>
                                    <flux:navlist.item icon="adjustments-horizontal" :href="route('admin.inventory.index')" :current="request()->routeIs('admin.inventory.index')" wire:navigate>Stock Adjustments</flux:navlist.item>
                                </flux:navlist.group>
                            </flux:navlist>
                        </section>
                    @endif

                    @if($isBoard)
                        <section class="mt-4">
                            <h3 class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Admin</h3>
                            <flux:navlist variant="outline">
                                <flux:navlist.group :heading="'Catalog'" class="grid">
                                    <flux:navlist.item icon="cube" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')" wire:navigate>Products</flux:navlist.item>
                                    <flux:navlist.item icon="tag" :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')" wire:navigate>Categories</flux:navlist.item>
                                </flux:navlist.group>
                            </flux:navlist>
                            <flux:navlist variant="outline">
                                <flux:navlist.group :heading="'Users'" class="grid">
                                    <flux:navlist.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>Users</flux:navlist.item>
                                    <flux:navlist.item icon="credit-card" :href="route('admin.cards.index')" :current="request()->routeIs('admin.cards.*')" wire:navigate>Cards</flux:navlist.item>
                                </flux:navlist.group>
                            </flux:navlist>
                            <flux:navlist variant="outline">
                                <flux:navlist.group :heading="'Settings & Reports'" class="grid">
                                    <flux:navlist.item icon="chart-bar" :href="route('admin.statistics.index')" :current="request()->routeIs('admin.statistics.*')" wire:navigate>Statistics</flux:navlist.item>
                                    <flux:navlist.item icon="wrench-screwdriver" :href="route('admin.operations.index')" :current="request()->routeIs('admin.operations.*')" wire:navigate>Operations</flux:navlist.item>
                                    <flux:navlist.item icon="cog" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')" wire:navigate>General Settings</flux:navlist.item>
                                    <flux:navlist.item icon="truck" :href="route('admin.settingsShippingCosts.index')" :current="request()->routeIs('admin.settingsShippingCosts.*')" wire:navigate>Shipping Costs</flux:navlist.item>
                                </flux:navlist.group>
                            </flux:navlist>
                        </section>
                    @endif

                    <flux:spacer/>

                    <flux:dropdown position="bottom" align="start">
                        <flux:profile :name="auth()->user()?->name" :initials="auth()->user()?->firstLastInitial()" icon-trailing="chevrons-up-down" />
                        <flux:menu class="w-[220px]">
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()?->firstLastInitial()}}
                                        </span>
                                    </span>
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()?->name}}</span>
                                        <span class="truncate text-xs">{{ auth()->user()?->email }}</span>
                                    </div>
                                </div>
                            </div>
                            <flux:menu.separator />
                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Profile</flux:menu.item>
                            </flux:menu.radio.group>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                    Log Out
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @endauth
            </flux:sidebar>

            <div class="flex-1">
                <flux:header class="lg:hidden">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                    <flux:spacer />
                </flux:header>

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>

        </div>

        @fluxScripts
    </body>
</html>
