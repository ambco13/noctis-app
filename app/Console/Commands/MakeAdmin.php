<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeAdmin extends Command
{
    protected $signature = 'noctis:make-admin {email} {--password= : Mot de passe (généré si omis)}';

    protected $description = 'Crée ou promeut un compte administrateur du back-office';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $password = $this->option('password');

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->forceFill(['is_admin' => true, 'account_status' => 'active']);
            if ($password) {
                $user->forceFill(['password' => Hash::make($password)]);
            }
            $user->save();
            $this->info("Compte {$email} promu administrateur.");
        } else {
            $password = $password ?: Str::random(16);
            User::create([
                'name' => $email,
                'email' => $email,
                'password' => Hash::make($password),
            ])->forceFill(['is_admin' => true, 'account_status' => 'active', 'email_verified_at' => now()])->save();
            $this->info("Administrateur {$email} créé.");
            $this->warn("Mot de passe : {$password}");
        }

        return self::SUCCESS;
    }
}
