<?php

/**
 * Integração com main.py na pasta pai do projeto Laravel (ex.: projetInf/main.py).
 * Por submissão: storage/app/autograding/submission-{id}/ (submission.zip, result.json, report.xml).
 *
 * Peso e finalidade **por pasta de testes** definem-se em ficheiros `autograding.json`
 * dentro de cada pasta sob base-project (ex.: base-project/tests/tests1/autograding.json).
 * Campos: weight (inteiro, pontos máximos da pasta), visibility (student|teacher|both), purpose (formative|summative).
 * Nota final = Σ (taxa_% da pasta × weight / 100). Ex.: weight 30 e 50% na pasta → 15 pontos.
 * A classificação final agregada é sempre visível ao aluno (própria submissão) e ao docente do processo.
 *
 * Opcional: peso global do processo na disciplina/UC — apenas aqui ou via .env.
 */
return [

    'enabled' => env('AUTOGRADING_ENABLED', true),

    /** Caminho absoluto para a pasta que contém main.py, base-project, testing-project */
    'project_root' => env('AUTOGRADING_PROJECT_ROOT') ?: dirname(base_path()),

    'python_binary' => env('AUTOGRADING_PYTHON', 'python3'),

    /** Timeout do processo em segundos */
    'timeout' => (int) env('AUTOGRADING_TIMEOUT', 600),

    /*
    |--------------------------------------------------------------------------
    | Peso opcional do processo (âmbito global)
    |--------------------------------------------------------------------------
    |
    | Percentagem ou fração na avaliação da disciplina, se usares esse modelo.
    | Pesos por pasta de testes ficam em cada autograding.json na pasta de testes.
    |
    */
    'process_weight_percent' => env('AUTOGRADING_PROCESS_WEIGHT_PERCENT') !== null
        ? (float) env('AUTOGRADING_PROCESS_WEIGHT_PERCENT')
        : null,

    /*
    |--------------------------------------------------------------------------
    | Fila de correção
    |--------------------------------------------------------------------------
    |
    | QUEUE_CONNECTION=database (ou redis) + `php artisan queue:work`
    | AUTOGRADING_RUN_SYNC=true força correção síncrona (útil em testes).
    |
    */
    'run_sync' => filter_var(env('AUTOGRADING_RUN_SYNC', false), FILTER_VALIDATE_BOOL),

    'queue' => env('AUTOGRADING_QUEUE', 'default'),

    'notify_on_complete' => filter_var(env('AUTOGRADING_NOTIFY_ON_COMPLETE', true), FILTER_VALIDATE_BOOL),

];
