<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Partner organisations learners pick from on the participant form
 * ("organisation you work with"). Each gets an approved organisation account.
 *
 * Safe to run on every deploy: matched by organisation name (and the login by
 * email), so nothing is duplicated, and an existing account's password or
 * details are never overwritten.
 */
class OrganisationSeeder extends Seeder
{
    /** Default password for the seeded logins; change it after first sign-in. */
    private const PASSWORD = 'Nigeria@60';

    /** name => [login email, type, state] */
    public const ORGANISATIONS = [
        'Kano Agricultural Supply Company Limited - KASCO' => ['kasco@org.agrisiti.com', 'agro_enterprise', 'Kano'],
        'The Agro-Climatic Resilience in Semi-Arid Landscapes (ACReSAL) - Kano' => ['acresal.kano@org.agrisiti.com', 'government', 'Kano'],
        'Nigeria Climate Adaptation Erosion and Watershed - Kano' => ['erosion-watershed.kano@org.agrisiti.com', 'government', 'Kano'],
        'Kano State Agricultural and Rural Development Authority (KNARDA)' => ['knarda@org.agrisiti.com', 'government', 'Kano'],
        'Anadariya Farms Kano' => ['anadariya@org.agrisiti.com', 'farm', 'Kano'],
        'Kwanar Dawaki rice Processing and marketing Association' => ['kwanar-dawaki-rice@org.agrisiti.com', 'agro_enterprise', 'Kano'],
        'Darussalam Global Farms Limited' => ['darussalam@org.agrisiti.com', 'farm', 'Kano'],
        'ITF Minna' => ['itf.minna@org.agrisiti.com', 'institution', 'Niger'],
        'Niger Foods Minna' => ['nigerfoods.minna@org.agrisiti.com', 'agro_enterprise', 'Niger'],
    ];

    public function run(): void
    {
        $created = 0;
        foreach (self::ORGANISATIONS as $name => [$email, $type, $state]) {
            if (Organisation::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                continue; // already there (seeded before, or registered by the organisation itself)
            }

            DB::transaction(function () use ($name, $email, $type, $state, &$created) {
                $user = User::firstOrCreate(['email' => $email], [
                    'name' => $name,
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'organisation',
                    'is_active' => true,
                ]);
                Organisation::create([
                    'user_id' => $user->id,
                    'name' => $name,
                    'organisation_type' => $type,
                    'country' => 'Nigeria',
                    'state' => $state,
                    'is_approved' => true,
                    'approved_at' => now(),
                ]);
                $created++;
            });
        }

        $this->command?->info("Organisations: {$created} added, " . (count(self::ORGANISATIONS) - $created) . ' already present.');
    }
}
