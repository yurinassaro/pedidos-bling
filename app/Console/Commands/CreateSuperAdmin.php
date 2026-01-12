<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-super-admin
                            {email : O email do super admin}
                            {--name= : O nome do usuário (opcional)}
                            {--password= : A senha (opcional, será solicitada se não informada)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria um novo usuário Super Admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->option('name') ?? $this->ask('Digite o nome do usuário');
        $password = $this->option('password') ?? $this->secret('Digite a senha');

        // Validar dados
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Digite um email válido.',
            'email.unique' => 'Este email já está em uso.',
            'name.required' => 'O nome é obrigatório.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        // Criar usuário
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->info("Super Admin criado com sucesso!");
        $this->table(
            ['ID', 'Nome', 'Email', 'Role'],
            [[$user->id, $user->name, $user->email, $user->role]]
        );

        return Command::SUCCESS;
    }
}
