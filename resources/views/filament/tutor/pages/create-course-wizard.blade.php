<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Progress Bar -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Step {{ $this->currentStep }} of {{ $this->totalSteps }}
                    </h3>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ round(($this->currentStep / $this->totalSteps) * 100) }}% Complete
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-300" 
                         style="width: {{ ($this->currentStep / $this->totalSteps) * 100 }}%"></div>
                </div>
            </div>
            
            <!-- Step Indicators -->
            <div class="flex justify-between mt-4">
                @foreach(range(1, $this->totalSteps) as $step)
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                            {{ $step <= $this->currentStep ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ $step }}
                        </div>
                        <span class="text-xs mt-1 text-center text-gray-600 dark:text-gray-400">
                            @if($step === 1) Course Info
                            @elseif($step === 2) Modules
                            @elseif($step === 3) Topics
                            @elseif($step === 4) Assignments
                            @elseif($step === 5) Quizzes
                            @elseif($step === 6) VR Content
                            @elseif($step === 7) DIY Projects
                            @elseif($step === 8) Resources
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Form -->
        <form wire:submit="submit">
            {{ $this->form }}

            <!-- Navigation Buttons -->
            <div class="flex justify-between mt-6">
                <div>
                    @if($this->currentStep > 1)
                        <x-filament::button 
                            type="button" 
                            wire:click="previousStep"
                            color="gray"
                            outlined>
                            <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
                            Previous
                        </x-filament::button>
                    @endif
                </div>
                
                <div class="flex gap-3">
                    @if($this->currentStep < $this->totalSteps)
                        <x-filament::button 
                            type="button" 
                            wire:click="nextStep"
                            color="primary">
                            Next
                            <x-heroicon-o-arrow-right class="w-4 h-4 ml-2" />
                        </x-filament::button>
                    @else
                        <x-filament::button 
                            type="submit"
                            color="success"
                            size="lg">
                            <x-heroicon-o-check-circle class="w-5 h-5 mr-2" />
                            Create Course
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>

