<?php

// app/Policies/UserPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Enums\UserType;

class UserPolicy
{
    /**
     * Permite a um administrador ver qualquer perfil
     */
    public function viewAny(User $user): bool
    {
        return $user->type === UserType::BOARD;
    }

    /**
     * Define quem pode atualizar um perfil.
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        // Membros (regulares e do conselho) podem atualizar o seu próprio perfil
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Membros do conselho podem atualizar perfis de funcionários
        if ($currentUser->type === UserType::BOARD && $targetUser->type === UserType::EMPLOYEE) {
            return true;
        }

        return false;
    }

    /**
     * Define quem pode bloquear/desbloquear um utilizador. Apenas membros do conselho.
     */
    public function block(User $currentUser, User $targetUser): bool
    {
        return $currentUser->type === UserType::BOARD && $currentUser->id !== $targetUser->id;
    }

    /**
     * Define quem pode cancelar uma afiliação (soft delete). Apenas membros do conselho.
     */
    public function delete(User $currentUser, User $targetUser): bool
    {
        // Membros do conselho podem apagar outros membros, mas não a si mesmos
        return $currentUser->type === UserType::BOARD && $currentUser->id !== $targetUser->id;
    }

    /**
     * Define quem pode alterar funções (promover/despromover). Apenas membros do conselho.
     */
    public function changeRole(User $currentUser): bool
    {
        return $currentUser->type === UserType::BOARD;
    }
}
