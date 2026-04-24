<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Card;
use App\Enums\UserType;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    // ... (o método create continua igual)

    public function store(Request $request): RedirectResponse
    {
        // Validação dos dados de registo
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'gender' => ['required', 'in:M,F'],
            // Outros campos opcionais podem ser validados aqui
        ]);

        // Usar uma transação para garantir a consistência dos dados
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'gender' => $request->gender,
                'type' => UserType::PENDING_MEMBER, // Novo utilizador é 'pending_member'
            ]);

            // Cria o cartão virtual para o novo membro
            Card::create([
                'id' => $user->id, // O ID do cartão é o mesmo que o do utilizador
                'card_number' => random_int(100000, 999999), // Número de cartão único
                'balance' => 0.00,
            ]);

            event(new Registered($user)); // Envia email de confirmação
        });

        // Após o registo, o utilizador deve ser redirecionado para pagar a taxa de inscrição
        // A lógica de login automático pode ser removida ou ajustada aqui
        // Auth::login($user); <-- Remova ou comente esta linha

        // Redireciona para uma página que informa sobre o pagamento da taxa
        return redirect()->route('membership.payment.notice');
    }
}
