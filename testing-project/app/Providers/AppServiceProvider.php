<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Course;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Partilha o conteúdo do carrinho com todas as views
        View::composer('*', function ($view) {
            $cart = session()->get('cart', []);
            $cartCount = count($cart);
            $view->with('cartCount', $cartCount);
        });
    }

    protected $policies = [
    User::class => UserPolicy::class,
    ];
}
