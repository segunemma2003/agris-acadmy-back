<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserTutor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-tutor {email : The email of the user to make tutor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user a tutor by email';

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

        if ($user->role === 'tutor') {
            $this->info("User '{$email}' is already a tutor.");
            return 0;
        }

        $user->role = 'tutor';
        $user->is_active = true;
        $user->save();

        $this->info("✅ User '{$email}' has been made a tutor successfully!");
        $this->info("   They can now access the tutor panel at /tutor");

        return 0;
    }
}

