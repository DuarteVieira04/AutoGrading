<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardFormRequest;
use App\Models\Card;
use App\Models\Operation;
use App\Services\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use App\Models\Order;
use App\Models\ItemsOrder;
use App\Models\SettingsShippingCost;

class CardController extends Controller
{
    //======================================================================
    //== MÉTODOS PARA O MEMBRO
    //======================================================================

    public function showMyCard()
    {
        $user = Auth::user();
        $card = $user->card;
        if (!$card) {
            return redirect('/')->with('error', 'Cartão não encontrado.');
        }

        $operations = Operation::where('card_id', $card->id)
                                ->with('order')
                                ->orderBy('date', 'desc')
                                ->orderBy('created_at', 'desc')
                                ->get();

        // Aponta para a view 'cards.show', que agora sabe lidar com os dois casos
        return view('cards.show', compact('user', 'card', 'operations'));
    }

    public function topUp(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:5|max:1000',
            'payment_type' => ['required', Rule::in(['Visa', 'PayPal', 'MB WAY'])],
            'payment_reference_visa_card' => 'required_if:payment_type,Visa|nullable|digits:16',
            'payment_reference_visa_cvc' => 'required_if:payment_type,Visa|nullable|digits:3',
            'payment_reference_paypal' => 'required_if:payment_type,PayPal|nullable|email',
            'payment_reference_mbway' => 'required_if:payment_type,MB WAY|nullable|digits:9|regex:/^9/',
        ]);

        $amount = (float) $validated['amount'];
        $paymentType = $validated['payment_type'];
        $paymentSuccessful = false;
        $paymentReference = '';

        switch ($paymentType) {
            case 'Visa':
                $paymentReference = $validated['payment_reference_visa_card'];
                $paymentSuccessful = Payment::payWithVisa($paymentReference, $validated['payment_reference_visa_cvc']);
                break;
            case 'PayPal':
                $paymentReference = $validated['payment_reference_paypal'];
                $paymentSuccessful = Payment::payWithPayPal($paymentReference);
                break;
            case 'MB WAY':
                $paymentReference = $validated['payment_reference_mbway'];
                $paymentSuccessful = Payment::payWithMBway($paymentReference);
                break;
        }

        if (!$paymentSuccessful) {
            return back()->with('error', 'Pagamento falhou. Verifique os dados.');
        }

        try {
            DB::transaction(function () use ($amount, $paymentType, $paymentReference) {
                $card = Auth::user()->card;
                $card->balance += $amount;
                $card->save();

                Operation::create([
                    'card_id' => $card->id,
                    'type' => 'credit',
                    'value' => $amount,
                    'date' => now(),
                    'credit_type' => 'payment',
                    'payment_type' => $paymentType,
                    'payment_reference' => $paymentReference,
                ]);
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao atualizar o saldo.');
        }

        return redirect()->route('card.my.show')->with('success', 'Cartão carregado com sucesso!');
    }

    //======================================================================
    //== MÉTODOS PARA O ADMINISTRADOR
    //======================================================================

    public function index(Request $request): View
    {
        // 1. Inicia a "montagem" da consulta (query)
        $query = Card::query()->with('user');

        // 2. Aplica os filtros com a lógica corrigida e completa

        // Filtro por ID (EXATO)
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        // Filtro por Número de Cartão (EXATO)
        if ($request->filled('card_number')) {
            // Alterado de 'like' para uma correspondência exata
            $query->where('card_number', $request->card_number);
        }

        // Filtro por Saldo Mínimo (maior ou igual a)
        if ($request->filled('balance')) {
            $query->where('balance', '>=', $request->balance);
        }

        // Filtro opcional para pesquisar pelo nome do utilizador
        // Para usar este, precisaria de adicionar um campo name="user_search" no seu formulário de filtro
        if ($request->filled('user_search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user_search . '%');
            });
        }

        // 3. Executa a consulta final com paginação e ordenação
        $cards = $query->latest('id')->paginate(20)->appends($request->query());

        // 4. Retorna a view, passando os cartões e os valores dos filtros para os campos manterem os valores
        return view('cards.index', compact('cards'))->with($request->all());
    }

    public function create()
    {
        return view('cards.create');
    }

    public function store(CardFormRequest $request)
    {
        $newCard = Card::create($request->validated());
        return redirect()->route('admin.cards.index')->with('success', 'Cartão criado com sucesso.');
    }

    public function show(Card $card)
    {
        // Aponta para a view 'cards.show'. A view decide o que mostrar.
        return view('cards.show', compact('card'));
    }

    public function edit(Card $card)
    {
        return view('cards.edit', compact('card'));
    }

    public function update(CardFormRequest $request, Card $card)
    {
        $card->update($request->validated());
        return redirect()->route('admin.cards.index')->with('success', 'Cartão atualizado com sucesso.');
    }

    public function destroy(Card $card)
    {
        $card->delete();
        return redirect()->route('admin.cards.index')->with('success', 'Cartão eliminado com sucesso.');
    }
}
