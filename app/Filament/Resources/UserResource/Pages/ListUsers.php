<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Support\ParticipantProfile;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every registered account, enrolled in a course or not. Tabs split them the
 * way the team works: learners who have / haven't started a course, profiles
 * still missing answers, staff, organisations and partners.
 */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $learners = fn (Builder $query) => $query->where('role', 'student');
        $count = fn (callable $scope) => $scope(User::query())->count();

        $enrolled = fn (Builder $query) => $learners($query)->whereHas('enrollments');
        $notEnrolled = fn (Builder $query) => $learners($query)->whereDoesntHave('enrollments');
        $incomplete = fn (Builder $query) => $learners($query)->whereNot(fn (Builder $w) => ParticipantProfile::scopeCvReady($w));
        $staff = fn (Builder $query) => $query->where(fn (Builder $w) => $w->whereIn('role', ['admin', 'tutor', 'facilitator'])->orWhere('is_staff', true));
        $orgs = fn (Builder $query) => $query->where('role', 'organisation');
        $partners = fn (Builder $query) => $query->whereIn('role', ['partner', 'tagdev']);

        return [
            'all' => Tab::make('All users')->badge(User::count()),
            'learners' => Tab::make('Learners')->badge($count($learners))->modifyQueryUsing($learners),
            'enrolled' => Tab::make('Enrolled in a course')->badge($count($enrolled))->badgeColor('success')->modifyQueryUsing($enrolled),
            'not_enrolled' => Tab::make('Not enrolled yet')->badge($count($notEnrolled))->badgeColor('warning')->modifyQueryUsing($notEnrolled),
            'incomplete' => Tab::make('Profile incomplete')->badge($count($incomplete))->badgeColor('danger')->modifyQueryUsing($incomplete),
            'staff' => Tab::make('Staff')->badge($count($staff))->modifyQueryUsing($staff),
            'organisations' => Tab::make('Organisations')->badge($count($orgs))->modifyQueryUsing($orgs),
            'partners' => Tab::make('Partners')->badge($count($partners))->modifyQueryUsing($partners),
        ];
    }
}
