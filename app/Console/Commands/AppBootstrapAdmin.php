<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class AppBootstrapAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bootstrap-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an admin user interactively.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = text(
            label: 'What is the admin user\'s name?',
            placeholder: 'E.g. John Doe',
            required: true,
        );

        $email = text(
            label: 'What is the admin user\'s email address?',
            placeholder: 'E.g. john.doe@example.com',
            required: true,
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The email address must be valid.',
                User::where('email', $value)->exists() => 'The email address is already registered.',
                default => null
            },
        );

        $password = password(
            label: 'Choose a password for the admin user.',
            placeholder: 'Min 8 characters',
            required: true,
            validate: fn (string $value) => match (true) {
                (new Password(8))->rules([])->passes('password', $value) => null,
                default => 'The password must be at least 8 characters.'
            },
        );

        $confirmPassword = password(
            label: 'Confirm the password.',
            required: true,
            validate: fn (string $value) => match (true) {
                $value !== $password => 'The passwords do not match.',
                default => null
            },
        );

        if (! confirm('Create this admin user?')) {
            $this->info('Admin user creation cancelled.');

            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('admin');

        $this->info('Admin user created successfully!');
    }
}
