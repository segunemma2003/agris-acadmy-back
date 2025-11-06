<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email : The email of the user to make admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user an admin by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if ($user->role === 'admin') {
            $this->info("User '{$email}' is already an admin.");
            return 0;
        }

        $user->role = 'admin';
        $user->is_active = true;
        $user->save();

        $this->info("✅ User '{$email}' has been made an admin successfully!");
        $this->info("   They can now access the admin panel at /admin");

        return 0;
    }
}

