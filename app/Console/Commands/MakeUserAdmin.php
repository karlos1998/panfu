<?php

namespace App\Console\Commands;

use App\Domain\Admin\Services\AdminRoleService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {user : ID, nazwa lub adres e-mail użytkownika}';

    protected $description = 'Nadaje użytkownikowi rolę administratora';

    public function handle(AdminRoleService $roles): int
    {
        try {
            $user = $roles->promote((string) $this->argument('user'));
        } catch (ModelNotFoundException) {
            $this->error('Nie znaleziono wskazanego użytkownika.');

            return self::FAILURE;
        }

        $this->info("{$user->name} ({$user->email}) ma teraz rolę administratora.");

        return self::SUCCESS;
    }
}
