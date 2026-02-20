<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\EnrollmentCode;
use App\Models\Course;
use App\Mail\WelcomeStudentMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessStudentRegistrationWithCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // COMMENTED OUT: CSV email verification disabled
            // Instead, set default location to Lagos and send enrollment code
            // $csvPath = public_path('participant.csv');
            // 
            // // Check if CSV file exists
            // if (!file_exists($csvPath)) {
            //     Log::warning('participant.csv file not found, sending general welcome email', [
            //         'user_id' => $this->user->id,
            //         'email' => $this->user->email,
            //     ]);
            //     $this->sendGeneralWelcomeEmail();
            //     return;
            // }
            //
            // // Read CSV file
            // $csvData = $this->readCSV($csvPath);
            // 
            // // Find user's email in CSV (Column F, index 5)
            // $userData = $this->findUserInCSV($this->user->email, $csvData);
            // 
            // if ($userData) {
            //     // User found in CSV - update info and send enrollment code
            //     $this->updateUserFromCSV($userData);
            //     $this->sendWelcomeEmailWithEnrollmentCode();
            // } else {
            //     // User not found - send general welcome email
            //     $this->sendGeneralWelcomeEmail();
            // }

            // Set default location to Lagos
            $this->user->update(['location' => 'Lagos']);
            
            Log::info('Set default location to Lagos for user', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'location' => 'Lagos',
            ]);
            
            // Send welcome email with enrollment code
            $this->sendWelcomeEmailWithEnrollmentCode();
        } catch (\Exception $e) {
            Log::error('Failed to process student registration', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Fallback: send general welcome email even if processing fails
            try {
                $this->sendGeneralWelcomeEmail();
            } catch (\Exception $emailException) {
                Log::error('Failed to send fallback welcome email', [
                    'user_id' => $this->user->id,
                    'error' => $emailException->getMessage(),
                ]);
            }
        }
    }

    /**
     * Read CSV file and return data as array
     */
    private function readCSV(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception('Could not open CSV file');
        }

        // Skip header row
        fgetcsv($handle);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 7) { // Ensure we have at least 7 columns
                $data[] = $row;
            }
        }

        fclose($handle);
        return $data;
    }

    /**
     * Find user email in CSV data
     * Column F is index 5 (0-based)
     */
    private function findUserInCSV(string $email, array $csvData): ?array
    {
        $email = strtolower(trim($email));
        
        foreach ($csvData as $row) {
            if (count($row) >= 7) {
                $csvEmail = strtolower(trim($row[5] ?? '')); // Column F (index 5)
                
                if ($csvEmail === $email) {
                    return $row;
                }
            }
        }
        
        return null;
    }

    /**
     * Update user information from CSV data
     * Column C (index 2) = Gender
     * Column G (index 6) = State of Residence (Location)
     */
    private function updateUserFromCSV(array $csvRow): void
    {
        $updates = [];
        
        // Column C (index 2) = Gender
        if (isset($csvRow[2]) && !empty(trim($csvRow[2]))) {
            $updates['gender'] = trim($csvRow[2]);
        }
        
        // Column G (index 6) = State of Residence (Location)
        if (isset($csvRow[6]) && !empty(trim($csvRow[6]))) {
            $updates['location'] = trim($csvRow[6]);
        }
        
        if (!empty($updates)) {
            $this->user->update($updates);
            Log::info('Updated user from CSV', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'updates' => $updates,
            ]);
        }
    }

    /**
     * Send welcome email with enrollment code
     */
    private function sendWelcomeEmailWithEnrollmentCode(): void
    {
        try {
            // Get default course (first featured course, or first published course)
            $course = Course::where('is_published', true)
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$course) {
                $course = Course::where('is_published', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
            
            if (!$course) {
                Log::warning('No published course found for enrollment code generation', [
                    'user_id' => $this->user->id,
                ]);
                // Fallback to general welcome email
                $this->sendGeneralWelcomeEmail();
                return;
            }
            
            // Check if enrollment code already exists for this user and course
            $existingCode = EnrollmentCode::where('course_id', $course->id)
                ->where('email', $this->user->email)
                ->where('is_used', false)
                ->first();
            
            if ($existingCode) {
                $enrollmentCode = $existingCode;
            } else {
                // Create new enrollment code
                $enrollmentCode = EnrollmentCode::create([
                    'course_id' => $course->id,
                    'tutor_id' => $course->tutor_id, // Use course's tutor
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'code' => EnrollmentCode::generateCode(),
                    'is_used' => false,
                    'expires_at' => null, // No expiration
                ]);
            }
            
            // Send welcome email with enrollment code included
            Mail::to($this->user->email)->queue(new WelcomeStudentMail($this->user, $enrollmentCode, $course));
            
            Log::info('Sent welcome email and enrollment code to CSV participant', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'course_id' => $course->id,
                'enrollment_code_id' => $enrollmentCode->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email with enrollment code', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to general welcome email
            try {
                $this->sendGeneralWelcomeEmail();
            } catch (\Exception $emailException) {
                Log::error('Failed to send fallback welcome email', [
                    'user_id' => $this->user->id,
                    'error' => $emailException->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send general welcome email (for users not in CSV)
     */
    private function sendGeneralWelcomeEmail(): void
    {
        try {
            Mail::to($this->user->email)->queue(new WelcomeStudentMail($this->user));
            
            Log::info('Sent general welcome email', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send general welcome email', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessStudentRegistrationWithCSV job failed', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
