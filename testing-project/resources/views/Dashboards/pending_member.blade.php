<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Finalize o seu Registo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-6" role="alert">
                <h3 class="font-bold text-lg">O seu registo está quase concluído!</h3>
                <p class="mt-2">
                    Para aceder à loja e a todos os benefícios do Grocery Club, precisa de pagar a quota de adesão.
                </p>
                <p class="mt-4 text-xl font-semibold">
                    Valor da Quota: {{ number_format($membershipFee, 2) }} €
                </p>
                <div class="mt-6">
                    <x-primary-button onclick="window.location.href='{{ route('membership.payment') }}'">
                        {{ __('Pagar Quota Agora') }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
