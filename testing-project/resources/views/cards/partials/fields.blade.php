<div>
    <label for="id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">User ID</label>
    <input type="number"
           name="id"
           id="id"
           placeholder="ID do utilizador"
           value="{{ old('id', $card->id ?? '') }}"
           required
           @if(isset($mode) && $mode === 'edit') readonly @endif
           class="mt-1 block w-full rounded-md shadow-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border-zinc-300 dark:border-zinc-600 focus:border-indigo-500 focus:ring-indigo-500 @if(isset($mode) && $mode === 'edit') cursor-not-allowed @endif">
    @error('id')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="card_number" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Card Number</label>
    <input type="number"
           name="card_number"
           id="card_number"
           placeholder="Número de 6 dígitos"
           value="{{ old('card_number', $card->card_number ?? '') }}"
           required
           class="mt-1 block w-full rounded-md shadow-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border-zinc-300 dark:border-zinc-600 focus:border-indigo-500 focus:ring-indigo-500">
    @error('card_number')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="balance" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Balance</label>
    <input type="number"
           name="balance"
           id="balance"
           value="{{ old('balance', $card->balance ?? '0') }}"
           step="0.01"
           required
           class="mt-1 block w-full rounded-md shadow-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border-zinc-300 dark:border-zinc-600 focus:border-indigo-500 focus:ring-indigo-500">
     @error('balance')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
