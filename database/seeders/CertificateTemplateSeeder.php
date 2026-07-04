<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (CertificateTemplate::where('name', 'Course Completion Certificate')->exists()) {
            return;
        }

        $sourcePath = public_path('cert_temp.pdf');

        if (!file_exists($sourcePath)) {
            return;
        }

        $storedPath = 'certificate-templates/cert_temp.pdf';
        Storage::disk('public')->put($storedPath, file_get_contents($sourcePath));

        CertificateTemplate::create([
            'name' => 'Course Completion Certificate',
            'file_path' => $storedPath,
            'is_default' => true,
            'name_y_percent' => 50,
            'font_size' => 28,
            'font_color' => '#141414',
            'font_family' => 'Helvetica',
            'font_style' => 'B',
        ]);
    }
}
