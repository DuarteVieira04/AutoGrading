<?php

namespace Database\Seeders;

use App\Models\GradingProcess;
use Illuminate\Database\Seeder;

class GradingProcessSeeder extends Seeder
{
    public function run(): void
    {
        if (GradingProcess::query()->exists()) {
            return;
        }

        GradingProcess::query()->create([
            'name' => 'Projeto Laravel — componentes padrão',
            'description' => 'Substitui app/, routes/, resources/ do projeto de teste pelo conteúdo do ZIP e executa php artisan test --json (main.py).',
            'components' => ['app', 'routes', 'resources'],
            'is_active' => true,
        ]);
    }
}
