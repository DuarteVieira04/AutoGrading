<x-layouts.main-content>

    @if (isset($operations))

        <div class="space-y-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">O Meu Cartão Grocery Club</h1>

            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-gray-800 dark:text-green-400" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-gray-800 dark:text-red-400" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold mb-2 dark:text-white">Detalhes do Cartão</h2>
                <p class="dark:text-gray-300"><strong>Titular:</strong> {{ $user->name }}</p>
                <p class="dark:text-gray-300"><strong>Número do Cartão:</strong> {{ chunk_split($card->card_number, 4, ' ') }}</p>
                <h3 class="text-2xl font-bold mt-2 dark:text-white">Saldo: {{ number_format($card->balance, 2, ',', '.') }} €</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <div class="md:col-span-5">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold mb-4 dark:text-white">Carregar Saldo</h2>
                        <form action="{{ route('card.topup') }}" method="POST" id="topup-form" class="space-y-4">
                            @csrf
                            <div>
                                <label for="amount" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Valor a carregar (€)</label>
                                <input type="number" step="0.01" min="5" id="amount" name="amount" value="{{ old('amount') }}" required
                                       class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            </div>

                            <div>
                                <label for="payment_type" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Método de Pagamento</label>
                                <select id="payment_type" name="payment_type" required
                                        class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option value="">Selecione um método</option>
                                    <option value="Visa" @if(old('payment_type') == 'Visa') selected @endif>Visa</option>
                                    <option value="PayPal" @if(old('payment_type') == 'PayPal') selected @endif>PayPal</option>
                                    <option value="MB WAY" @if(old('payment_type') == 'MB WAY') selected @endif>MB WAY</option>
                                </select>
                            </div>

                            <div id="visa-fields" class="payment-fields space-y-4" style="display: none;">
                                <div>
                                    <label for="payment_reference_visa_card" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Número do Cartão Visa</label>
                                    <input type="text" name="payment_reference_visa_card" placeholder="16 dígitos" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label for="payment_reference_visa_cvc" class="block font-medium text-sm text-gray-700 dark:text-gray-300">CVC</label>
                                    <input type="text" name="payment_reference_visa_cvc" placeholder="3 dígitos" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                                </div>
                            </div>

                            <div id="paypal-fields" class="payment-fields" style="display: none;">
                                <label for="payment_reference_paypal" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Email do PayPal</label>
                                <input type="email" name="payment_reference_paypal" placeholder="user@example.com" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                            </div>

                            <div id="mbway-fields" class="payment-fields" style="display: none;">
                                <label for="payment_reference_mbway" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Telemóvel MB WAY</label>
                                <input type="text" name="payment_reference_mbway" placeholder="9xxxxxxxx" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm">
                            </div>

                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Carregar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="md:col-span-7">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-gray-700">
                         <h2 class="text-lg font-semibold mb-4 dark:text-white">Histórico de Movimentos</h2>
                         <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($operations as $operation)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($operation->date)->format('d/m/Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                @if($operation->type == 'credit') Carregamento via {{ $operation->payment_type }} @else Compra (Encomenda #{{ $operation->order_id }}) @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right @if($operation->type == 'credit') text-green-500 @else text-red-500 @endif">
                                                {{ $operation->type == 'credit' ? '+' : '-' }} {{ number_format($operation->value, 2, ',', '.') }} €
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">Não existem movimentos.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentTypeSelect = document.getElementById('payment_type');
            const paymentFields = { 'Visa': document.getElementById('visa-fields'), 'PayPal': document.getElementById('paypal-fields'), 'MB WAY': document.getElementById('mbway-fields') };
            function toggleFields() {
                for (const key in paymentFields) { if (paymentFields[key]) { paymentFields[key].style.display = 'none'; } }
                const selectedType = paymentTypeSelect.value;
                if (paymentFields[selectedType]) { paymentFields[selectedType].style.display = 'block'; }
            }
            toggleFields();
            paymentTypeSelect.addEventListener('change', toggleFields);
        });
        </script>

    @else

        <div class="flex flex-col space-y-6">
            <div class="max-full">
                <section>
                    @if (isset($card) && $card->id)
                        <div class="flex flex-wrap justify-start items-center gap-4 mb-4">
                            <a href="{{ route('admin.cards.edit', ['card' => $card->id]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md">Edit</a>
                            <a href="{{ route('admin.cards.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md">New</a>
                            <form method="POST" action="{{ route('admin.cards.destroy', ['card' => $card->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md">Delete</button>
                            </form>
                        </div>
                    @else
                        <div class="p-4 text-red-700 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-300">
                            <strong>Erro:</strong> A informação do cartão não está disponível.
                        </div>
                    @endif

                    <div style="user-select: none; pointer-events: none;">
                        @include('cards.partials.fields', ['card' => $card, 'readonly' => true, 'disabled' => true])
                    </div>
                </section>
            </div>
        </div>
      @endif

</x-layouts.main-content>
