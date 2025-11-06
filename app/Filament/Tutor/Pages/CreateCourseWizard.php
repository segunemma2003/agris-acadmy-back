<?php

namespace App\Filament\Tutor\Pages;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseDiyContent;
use App\Models\CourseResource;
use App\Models\CourseVrContent;
use App\Models\Module;
use App\Models\ModuleTest;
use App\Models\TestQuestion;
use App\Models\Topic;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCourseWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.tutor.pages.create-course-wizard';

    protected static ?string $title = 'Create Course - Step by Step';

    protected static ?string $navigationLabel = 'Create Course (Wizard)';

    protected static ?string $navigationGroup = 'Course Management';

    public ?array $data = [];

    public int $currentStep = 1;

    public int $totalSteps = 8;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Step 1: Course Basic Information
                Section::make('Step 1: Course Basic Information')
                    ->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->options(Category::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Course::class, 'slug', ignoreRecord: true),
                        Textarea::make('short_description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->image()
                            ->directory('courses')
                            ->columnSpanFull(),
                        Repeater::make('what_you_will_learn')
                            ->label('What You Will Learn')
                            ->schema([
                                TextInput::make('item')
                                    ->required(),
                            ])
                            ->defaultItems(3)
                            ->columnSpanFull(),
                        Repeater::make('what_you_will_get')
                            ->label('What You Will Get')
                            ->schema([
                                TextInput::make('item')
                                    ->required(),
                            ])
                            ->defaultItems(2)
                            ->columnSpanFull(),
                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->default(0),
                        Select::make('level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->default('beginner'),
                        TextInput::make('language')
                            ->default('English')
                            ->maxLength(255),
                        TextInput::make('price')
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),
                        Toggle::make('is_free')
                            ->label('Free Course'),
                        TagsInput::make('tags')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn () => $this->currentStep === 1),

                // Step 2: Modules
                Section::make('Step 2: Course Modules')
                    ->description('Add modules to organize your course content')
                    ->schema([
                        Repeater::make('modules')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->defaultItems(1)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->currentStep === 2),

                // Step 3: Topics
                Section::make('Step 3: Topics for Each Module')
                    ->description('Add topics to your modules')
                    ->schema([
                        Repeater::make('topics')
                            ->schema([
                                Select::make('module_index')
                                    ->label('Module')
                                    ->options(fn () => $this->getModuleOptions())
                                    ->required(),
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                TextInput::make('video_url')
                                    ->label('Video URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('transcript')
                                    ->label('Video Transcript')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                RichEditor::make('write_up')
                                    ->label('Content/Write-up')
                                    ->columnSpanFull(),
                                Select::make('content_type')
                                    ->options([
                                        'video' => 'Video',
                                        'text' => 'Text',
                                        'mixed' => 'Mixed',
                                    ])
                                    ->default('video'),
                                TextInput::make('duration_minutes')
                                    ->label('Duration (minutes)')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_free')
                                    ->label('Free Preview'),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->defaultItems(1)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->currentStep === 3),

                // Step 4: Assignments
                Section::make('Step 4: Assignments')
                    ->description('Create assignments for your course')
                    ->schema([
                        Repeater::make('assignments')
                            ->schema([
                                Select::make('module_index')
                                    ->label('Module (Optional)')
                                    ->options(fn () => $this->getModuleOptions())
                                    ->nullable(),
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                RichEditor::make('instructions')
                                    ->label('Instructions')
                                    ->columnSpanFull(),
                                TextInput::make('max_score')
                                    ->label('Maximum Score')
                                    ->numeric()
                                    ->default(100)
                                    ->required(),
                                DateTimePicker::make('due_date')
                                    ->label('Due Date')
                                    ->native(false),
                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->currentStep === 4),

                // Step 5: Quizzes/Tests
                Section::make('Step 5: Module Quizzes/Tests')
                    ->description('Create CBT tests for your modules')
                    ->schema([
                        Repeater::make('tests')
                            ->schema([
                                Select::make('module_index')
                                    ->label('Module')
                                    ->options(fn () => $this->getModuleOptions())
                                    ->required(),
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                TextInput::make('passing_score')
                                    ->label('Passing Score (%)')
                                    ->numeric()
                                    ->default(70)
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100),
                                TextInput::make('time_limit_minutes')
                                    ->label('Time Limit (minutes)')
                                    ->numeric()
                                    ->default(60),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                Repeater::make('questions')
                                    ->label('Test Questions')
                                    ->schema([
                                        TextInput::make('question')
                                            ->required()
                                            ->columnSpanFull(),
                                        Select::make('question_type')
                                            ->options([
                                                'multiple_choice' => 'Multiple Choice',
                                                'true_false' => 'True/False',
                                                'short_answer' => 'Short Answer',
                                            ])
                                            ->default('multiple_choice')
                                            ->required(),
                                        Repeater::make('options')
                                            ->label('Answer Options')
                                            ->schema([
                                                TextInput::make('option')
                                                    ->required(),
                                                Toggle::make('is_correct')
                                                    ->label('Correct Answer'),
                                            ])
                                            ->defaultItems(4)
                                            ->visible(fn ($get) => in_array($get('../../question_type'), ['multiple_choice', 'true_false'])),
                                        TextInput::make('correct_answer')
                                            ->label('Correct Answer')
                                            ->visible(fn ($get) => $get('question_type') === 'short_answer'),
                                        TextInput::make('points')
                                            ->label('Points')
                                            ->numeric()
                                            ->default(1)
                                            ->required(),
                                        TextInput::make('sort_order')
                                            ->label('Order')
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->defaultItems(1)
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->currentStep === 5),

                // Step 6: VR Content
                Section::make('Step 6: VR Learning Content')
                    ->description('Add VR experiences to your course')
                    ->schema([
                        Repeater::make('vr_content')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                TextInput::make('vr_url')
                                    ->label('VR URL')
                                    ->url()
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                FileUpload::make('thumbnail')
                                    ->image()
                                    ->directory('vr-content')
                                    ->columnSpanFull(),
                                TextInput::make('duration_minutes')
                                    ->label('Duration (minutes)')
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->currentStep === 6),

                // Step 7: DIY Content
                Section::make('Step 7: DIY Projects & Instructions')
                    ->description('Add hands-on DIY projects and instructions')
                    ->schema([
                        Repeater::make('diy_content')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                RichEditor::make('instructions')
                                    ->label('Instructions')
                                    ->required()
                                    ->columnSpanFull(),
                                Repeater::make('materials_needed')
                                    ->label('Materials Needed')
                                    ->schema([
                                        TextInput::make('material')
                                            ->required(),
                                    ])
                                    ->defaultItems(3)
                                    ->columnSpanFull(),
                                TextInput::make('video_url')
                                    ->label('Instructional Video URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                FileUpload::make('image')
                                    ->image()
                                    ->directory('diy-content')
                                    ->columnSpanFull(),
                                TextInput::make('estimated_time_minutes')
                                    ->label('Estimated Time (minutes)')
                                    ->numeric()
                                    ->default(60),
                                Select::make('difficulty_level')
                                    ->options([
                                        'easy' => 'Easy',
                                        'medium' => 'Medium',
                                        'hard' => 'Hard',
                                    ])
                                    ->default('medium'),
                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->currentStep === 7),

                // Step 8: Course Resources & Projects
                Section::make('Step 8: Course Resources & Projects')
                    ->description('Add downloadable resources and project files')
                    ->schema([
                        Repeater::make('resources')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Select::make('resource_type')
                                    ->options([
                                        'document' => 'Document',
                                        'project' => 'Project',
                                        'template' => 'Template',
                                        'other' => 'Other',
                                    ])
                                    ->default('document')
                                    ->required(),
                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                    ->directory('course-resources')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxSize(10240), // 10MB
                                TextInput::make('sort_order')
                                    ->label('Order')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->currentStep === 8),
            ])
            ->statePath('data');
    }

    protected function getModuleOptions(): array
    {
        if (!isset($this->data['modules'])) {
            return [];
        }

        $options = [];
        foreach ($this->data['modules'] as $index => $module) {
            if (isset($module['title'])) {
                $options[$index] = $module['title'];
            }
        }
        return $options;
    }

    public function nextStep(): void
    {
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            DB::beginTransaction();

            // Create Course
            $course = Course::create([
                'category_id' => $data['category_id'],
                'tutor_id' => auth()->id(),
                'title' => $data['title'],
                'slug' => $data['slug'],
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'],
                'what_you_will_learn' => $data['what_you_will_learn'] ?? [],
                'what_you_will_get' => $data['what_you_will_get'] ?? [],
                'image' => $data['image'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? 0,
                'level' => $data['level'] ?? 'beginner',
                'language' => $data['language'] ?? 'English',
                'price' => $data['price'] ?? 0,
                'is_free' => $data['is_free'] ?? false,
                'tags' => $data['tags'] ?? [],
                'is_published' => false,
            ]);

            // Create Modules
            if (isset($data['modules'])) {
                foreach ($data['modules'] as $moduleData) {
                    Module::create([
                        'course_id' => $course->id,
                        'title' => $moduleData['title'],
                        'description' => $moduleData['description'] ?? null,
                        'sort_order' => $moduleData['sort_order'] ?? 0,
                        'is_active' => $moduleData['is_active'] ?? true,
                    ]);
                }
            }

            // Create Topics
            if (isset($data['topics'])) {
                $modules = $course->modules()->get();
                foreach ($data['topics'] as $topicData) {
                    $moduleIndex = $topicData['module_index'] ?? 0;
                    $module = $modules->get($moduleIndex);
                    if ($module) {
                        Topic::create([
                            'module_id' => $module->id,
                            'title' => $topicData['title'],
                            'description' => $topicData['description'] ?? null,
                            'video_url' => $topicData['video_url'] ?? null,
                            'transcript' => $topicData['transcript'] ?? null,
                            'write_up' => $topicData['write_up'] ?? null,
                            'content_type' => $topicData['content_type'] ?? 'video',
                            'duration_minutes' => $topicData['duration_minutes'] ?? 0,
                            'sort_order' => $topicData['sort_order'] ?? 0,
                            'is_free' => $topicData['is_free'] ?? false,
                            'is_active' => $topicData['is_active'] ?? true,
                        ]);
                    }
                }
            }

            // Create Assignments
            if (isset($data['assignments'])) {
                $modules = $course->modules()->get();
                foreach ($data['assignments'] as $assignmentData) {
                    $moduleId = null;
                    if (isset($assignmentData['module_index'])) {
                        $module = $modules->get($assignmentData['module_index']);
                        $moduleId = $module?->id;
                    }
                    Assignment::create([
                        'course_id' => $course->id,
                        'module_id' => $moduleId,
                        'tutor_id' => auth()->id(),
                        'title' => $assignmentData['title'],
                        'description' => $assignmentData['description'] ?? null,
                        'instructions' => $assignmentData['instructions'] ?? null,
                        'max_score' => $assignmentData['max_score'] ?? 100,
                        'due_date' => $assignmentData['due_date'] ?? null,
                        'sort_order' => $assignmentData['sort_order'] ?? 0,
                        'is_active' => $assignmentData['is_active'] ?? true,
                    ]);
                }
            }

            // Create Tests
            if (isset($data['tests'])) {
                $modules = $course->modules()->get();
                foreach ($data['tests'] as $testData) {
                    $moduleIndex = $testData['module_index'] ?? 0;
                    $module = $modules->get($moduleIndex);
                    if ($module) {
                        $test = ModuleTest::create([
                            'module_id' => $module->id,
                            'course_id' => $course->id,
                            'title' => $testData['title'],
                            'description' => $testData['description'] ?? null,
                            'passing_score' => $testData['passing_score'] ?? 70,
                            'time_limit_minutes' => $testData['time_limit_minutes'] ?? 60,
                            'is_active' => $testData['is_active'] ?? true,
                        ]);

                        // Create Test Questions
                        if (isset($testData['questions'])) {
                            foreach ($testData['questions'] as $questionData) {
                                $question = TestQuestion::create([
                                    'module_test_id' => $test->id,
                                    'question' => $questionData['question'],
                                    'question_type' => $questionData['question_type'] ?? 'multiple_choice',
                                    'correct_answer' => $questionData['correct_answer'] ?? null,
                                    'points' => $questionData['points'] ?? 1,
                                    'sort_order' => $questionData['sort_order'] ?? 0,
                                ]);

                                // Create options for multiple choice
                                if (isset($questionData['options']) && in_array($questionData['question_type'], ['multiple_choice', 'true_false'])) {
                                    $options = [];
                                    foreach ($questionData['options'] as $optionData) {
                                        $options[] = [
                                            'option' => $optionData['option'] ?? '',
                                            'is_correct' => $optionData['is_correct'] ?? false,
                                        ];
                                    }
                                    $question->update(['options' => $options]);
                                }
                                
                                // Set correct answer for short answer
                                if ($questionData['question_type'] === 'short_answer' && isset($questionData['correct_answer'])) {
                                    $question->update(['correct_answer' => $questionData['correct_answer']]);
                                }
                            }
                        }
                    }
                }
            }

            // Create VR Content
            if (isset($data['vr_content'])) {
                foreach ($data['vr_content'] as $vrData) {
                    CourseVrContent::create([
                        'course_id' => $course->id,
                        'title' => $vrData['title'],
                        'description' => $vrData['description'] ?? null,
                        'vr_url' => $vrData['vr_url'],
                        'thumbnail' => $vrData['thumbnail'] ?? null,
                        'duration_minutes' => $vrData['duration_minutes'] ?? 0,
                        'sort_order' => $vrData['sort_order'] ?? 0,
                        'is_active' => $vrData['is_active'] ?? true,
                    ]);
                }
            }

            // Create DIY Content
            if (isset($data['diy_content'])) {
                foreach ($data['diy_content'] as $diyData) {
                    CourseDiyContent::create([
                        'course_id' => $course->id,
                        'title' => $diyData['title'],
                        'description' => $diyData['description'] ?? null,
                        'instructions' => $diyData['instructions'],
                        'materials_needed' => $diyData['materials_needed'] ?? [],
                        'video_url' => $diyData['video_url'] ?? null,
                        'image' => $diyData['image'] ?? null,
                        'estimated_time_minutes' => $diyData['estimated_time_minutes'] ?? 60,
                        'difficulty_level' => $diyData['difficulty_level'] ?? 'medium',
                        'sort_order' => $diyData['sort_order'] ?? 0,
                        'is_active' => $diyData['is_active'] ?? true,
                    ]);
                }
            }

            // Create Resources
            if (isset($data['resources'])) {
                foreach ($data['resources'] as $resourceData) {
                    $filePath = is_array($resourceData['file_path']) ? $resourceData['file_path'][0] : $resourceData['file_path'];
                    $fileSize = 0;
                    if ($filePath) {
                        $fullPath = storage_path('app/public/' . $filePath);
                        if (file_exists($fullPath)) {
                            $fileSize = filesize($fullPath);
                        }
                    }
                    
                    CourseResource::create([
                        'course_id' => $course->id,
                        'title' => $resourceData['title'],
                        'description' => $resourceData['description'] ?? null,
                        'resource_type' => $resourceData['resource_type'] ?? 'document',
                        'file_path' => $filePath,
                        'file_name' => basename($filePath),
                        'file_type' => pathinfo($filePath, PATHINFO_EXTENSION),
                        'file_size' => $fileSize,
                        'sort_order' => $resourceData['sort_order'] ?? 0,
                        'is_active' => $resourceData['is_active'] ?? true,
                    ]);
                }
            }

            DB::commit();

            Notification::make()
                ->title('Course created successfully!')
                ->success()
                ->send();

            return redirect(\App\Filament\Tutor\Resources\CourseResource::getUrl('edit', ['record' => $course->id]));
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Error creating course')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

