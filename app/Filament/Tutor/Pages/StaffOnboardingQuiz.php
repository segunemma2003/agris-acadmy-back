<?php

namespace App\Filament\Tutor\Pages;

use App\Models\StaffOnboardingQuizAttempt;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class StaffOnboardingQuiz extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static string $view = 'filament.tutor.pages.staff-onboarding-quiz';
    
    protected static ?string $title = 'Staff Onboarding Quiz';
    
    protected static ?string $navigationLabel = 'Onboarding Quiz';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public $attempt = null;
    public $showResults = false;
    public $bestScore = null;
    public $hasPassed = false;
    public $questions = [];

    public function mount(): void
    {
        $user = Auth::user();
        
        // Get best attempt
        $bestAttempt = StaffOnboardingQuizAttempt::where('user_id', $user->id)
            ->orderBy('percentage', 'desc')
            ->first();
            
        $this->bestScore = $bestAttempt ? $bestAttempt->percentage : null;
        $this->hasPassed = $bestAttempt ? $bestAttempt->is_passed : false;
        
        // Store questions for view access
        $this->questions = $this->getQuizQuestions();
        
        // Initialize form data with empty answers
        $this->form->fill(['answers' => []]);
    }

    protected function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        $questions = $this->getQuizQuestions();
        $schema = [];

        foreach ($questions as $index => $question) {
            $questionNumber = $index + 1;
            $schema[] = Section::make("Question {$questionNumber}")
                ->schema([
                    Radio::make("answers.{$question['id']}")
                        ->label($question['question'])
                        ->options($question['options'])
                        ->required()
                        ->inline()
                        ->columnSpanFull()
                ])
                ->description($question['explanation'] ?? null)
                ->collapsible()
                ->collapsed(false);
        }

        return $schema;
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        if (!isset($data['answers'])) {
            $data['answers'] = [];
        }
        $user = Auth::user();
        
        $questions = $this->getQuizQuestions();
        $userAnswers = $data['answers'] ?? [];
        
        $correctAnswers = 0;
        $totalQuestions = count($questions);
        
        foreach ($questions as $question) {
            $questionId = $question['id'];
            $userAnswer = $userAnswers[$questionId] ?? null;
            if ($userAnswer === $question['correct_answer']) {
                $correctAnswers++;
            }
        }
        
        $score = $correctAnswers;
        $percentage = ($correctAnswers / $totalQuestions) * 100;
        $isPassed = $percentage >= 70; // Passing score is 70%
        
        // Create attempt
        $attempt = StaffOnboardingQuizAttempt::create([
            'user_id' => $user->id,
            'answers' => $userAnswers,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'percentage' => round($percentage, 2),
            'is_passed' => $isPassed,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        
        $this->attempt = $attempt;
        $this->showResults = true;
        $bestAttempt = StaffOnboardingQuizAttempt::where('user_id', $user->id)
            ->orderBy('percentage', 'desc')
            ->first();
        $this->bestScore = $bestAttempt ? $bestAttempt->percentage : $attempt->percentage;
        $this->hasPassed = $attempt->is_passed;
        
        if ($isPassed) {
            Notification::make()
                ->title('Congratulations!')
                ->success()
                ->body("You passed the quiz with a score of {$attempt->percentage}%!")
                ->send();
        } else {
            Notification::make()
                ->title('Quiz Completed')
                ->warning()
                ->body("You scored {$attempt->percentage}%. You need 70% to pass. You can retake the quiz.")
                ->send();
        }
        
        // Reset form
        $this->form->fill(['answers' => []]);
    }

    public function retakeQuiz(): void
    {
        $this->showResults = false;
        $this->attempt = null;
        $this->form->fill(['answers' => []]);
    }

    public function getQuizQuestions(): array
    {
        return [
            [
                'id' => 1,
                'question' => 'What is Agrisiti\'s mission?',
                'options' => [
                    'A' => 'Provide free agricultural equipment to farmers',
                    'B' => 'Empower African youth with skills and opportunities in agribusiness, education, and food systems',
                    'C' => 'Fund large-scale commercial farms only',
                    'D' => 'Manage export of rice and aquaculture products',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - Agrisiti aims to empower African youth with skills and opportunities in agribusiness, education, and food systems.',
            ],
            [
                'id' => 2,
                'question' => 'Which value chain sectors does the program focus on?',
                'options' => [
                    'A' => 'Cocoa and cassava',
                    'B' => 'Rice and aquaculture',
                    'C' => 'Maize and poultry',
                    'D' => 'Livestock and fisheries',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - The program focuses on rice and aquaculture value chains.',
            ],
            [
                'id' => 3,
                'question' => 'How many states and participants are involved in the program?',
                'options' => [
                    'A' => '5 states, 2,500 participants',
                    'B' => '7 states, 3,500 participants',
                    'C' => '6 states, 3,000 participants',
                    'D' => '8 states, 4,000 participants',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - The program operates in 7 states with 3,500 participants.',
            ],
            [
                'id' => 4,
                'question' => 'What is the purpose of the 3-day farm immersion (Dignity-in-Labour)?',
                'options' => [
                    'A' => 'To relax participants and give them a break',
                    'B' => 'To observe professional farmers without participation',
                    'C' => 'To provide hands-on practical learning, understand farm operations, and inform business case development',
                    'D' => 'To conduct exams on theoretical agribusiness concepts',
                ],
                'correct_answer' => 'C',
                'explanation' => 'Correct Answer: C - The farm immersion provides hands-on practical learning and helps inform business case development.',
            ],
            [
                'id' => 5,
                'question' => 'What are the two final outcomes participants can choose at the end of the program?',
                'options' => [
                    'A' => 'Start a business OR become a government employee',
                    'B' => 'Launch a new agribusiness OR become job-ready for employment opportunities',
                    'C' => 'Receive a scholarship OR travel abroad',
                    'D' => 'Work on the farm permanently OR become an Agrisiti volunteer',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - Participants can either launch an agribusiness or become job-ready for employment.',
            ],
            [
                'id' => 6,
                'question' => 'Name two core values of the program.',
                'options' => [
                    'A' => 'Dignity of labour, teamwork & collaboration',
                    'B' => 'Speed, secrecy',
                    'C' => 'Profit maximization, competition',
                    'D' => 'Centralization, hierarchy',
                ],
                'correct_answer' => 'A',
                'explanation' => 'Correct Answer: A - The core values include dignity of labour and teamwork & collaboration.',
            ],
            [
                'id' => 7,
                'question' => 'Which phase includes mentorship?',
                'options' => [
                    'A' => 'Phase 1 — Orientation & Team Formation',
                    'B' => 'Phase 2b — Farm Immersion',
                    'C' => 'Phase 3 — Mentorship & Business Refinement',
                    'D' => 'Phase 4 — Business Showcase & Pitch',
                ],
                'correct_answer' => 'C',
                'explanation' => 'Correct Answer: C - Phase 3 is the Mentorship & Business Refinement phase.',
            ],
            [
                'id' => 8,
                'question' => 'Who does the State Program Facilitator report to?',
                'options' => [
                    'A' => 'Program Manager',
                    'B' => 'Mentor',
                    'C' => 'Volunteer Coordinator',
                    'D' => 'Youth Board Member',
                ],
                'correct_answer' => 'A',
                'explanation' => 'Correct Answer: A - State Program Facilitator reports to the Program Manager.',
            ],
            [
                'id' => 9,
                'question' => 'Which staff role is responsible for mobilizing participants and promoting the program?',
                'options' => [
                    'A' => 'Mentor',
                    'B' => 'Outreach & Communications Officer',
                    'C' => 'State Program Facilitator',
                    'D' => 'Intern',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - The Outreach & Communications Officer is responsible for mobilization and promotion.',
            ],
            [
                'id' => 10,
                'question' => 'What is the main responsibility of Mentors?',
                'options' => [
                    'A' => 'Organize physical training logistics',
                    'B' => 'Guide participants on business model development and career pathways',
                    'C' => 'Manage program budget',
                    'D' => 'Run social media campaigns',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - Mentors guide participants on business model development and career pathways.',
            ],
            [
                'id' => 11,
                'question' => 'How long is the virtual e-learning phase?',
                'options' => [
                    'A' => '6 weeks',
                    'B' => '8 weeks',
                    'C' => '12 weeks',
                    'D' => '4 weeks',
                ],
                'correct_answer' => 'C',
                'explanation' => 'Correct Answer: C - The virtual e-learning phase lasts 12 weeks.',
            ],
            [
                'id' => 12,
                'question' => 'How many members are in each project team?',
                'options' => [
                    'A' => '5–6',
                    'B' => '8–10',
                    'C' => '12–15',
                    'D' => '3–4',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - Each project team consists of 8–10 members.',
            ],
            [
                'id' => 13,
                'question' => 'What is the main outcome of Phase 4 — Business Showcase & Pitch?',
                'options' => [
                    'A' => 'Award scholarships for academic studies',
                    'B' => 'Provide capital, incubation support, and networking opportunities',
                    'C' => 'Conduct final exams on agribusiness theory',
                    'D' => 'Assign volunteers to new teams',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - Phase 4 provides capital, incubation support, and networking opportunities.',
            ],
            [
                'id' => 14,
                'question' => 'Which tool do staff primarily use to track participant engagement during e-learning?',
                'options' => [
                    'A' => 'WhatsApp',
                    'B' => 'Google Sheets',
                    'C' => 'LMS platform',
                    'D' => 'Email',
                ],
                'correct_answer' => 'C',
                'explanation' => 'Correct Answer: C - Staff primarily use the LMS platform to track participant engagement.',
            ],
            [
                'id' => 15,
                'question' => 'Which statement best describes Agrisiti\'s approach to inclusivity?',
                'options' => [
                    'A' => 'Focus only on high-performing participants',
                    'B' => 'Ensure participation of women, persons with disabilities, and marginalized youth',
                    'C' => 'Only provide online content for urban youth',
                    'D' => 'Select participants based solely on academic grades',
                ],
                'correct_answer' => 'B',
                'explanation' => 'Correct Answer: B - Agrisiti ensures participation of women, persons with disabilities, and marginalized youth.',
            ],
        ];
    }
}

