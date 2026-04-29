<?php

/**
 * Integração com main.py na pasta pai do projeto Laravel (ex.: projetInf/main.py).
 */
return [

    'enabled' => env('AUTOGRADING_ENABLED', true),

    /** Caminho absoluto para a pasta que contém main.py, base-project, testing-project */
    'project_root' => env('AUTOGRADING_PROJECT_ROOT') ?: dirname(base_path()),

    'python_binary' => env('AUTOGRADING_PYTHON', 'python3'),

    /** Timeout do processo em segundos */
    'timeout' => (int) env('AUTOGRADING_TIMEOUT', 600),

];
