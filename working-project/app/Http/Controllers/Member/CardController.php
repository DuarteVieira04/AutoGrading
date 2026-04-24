<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{

    public function show()
    {
        $user = Auth::user();

        $card = $user->card()->with('operations')->firstOrFail();

        return view('member.card.show', compact('card'));
    }
}
