<?php

namespace App\Services;

use App\Models\CourseVrContent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VrStudioService
{
    public const HANDOFF_TTL_SECONDS = 300;

    public function studioBaseUrl(): string
    {
        return rtrim((string) config('services.vr_studio.url', 'http://localhost:5174'), '/');
    }

    public function playerUrlFor(CourseVrContent $content): string
    {
        $slug = $content->studio_slug ?: ('vr-'.$content->id);

        return $this->studioBaseUrl().'/play/'.$slug;
    }

    /**
     * Create a short-lived handoff token and Studio editor URL.
     * Authors: admin, tutor, facilitator.
     */
    public function createHandoffUrl(User $user, CourseVrContent $content): string
    {
        $token = Str::random(64);
        Cache::put($this->cacheKey($token), [
            'user_id' => $user->id,
            'vr_content_id' => $content->id,
            'course_id' => $content->course_id,
            'role' => $user->role,
        ], now()->addSeconds(self::HANDOFF_TTL_SECONDS));

        return $this->studioBaseUrl()
            .'/auth/handoff?token='.urlencode($token)
            .'&experience='.$content->id;
    }

    public function exchangeHandoff(string $token): ?array
    {
        $payload = Cache::pull($this->cacheKey($token));
        if (! is_array($payload) || empty($payload['user_id']) || empty($payload['vr_content_id'])) {
            return null;
        }

        $user = User::find($payload['user_id']);
        $content = CourseVrContent::with(['course:id,title', 'module:id,title'])->find($payload['vr_content_id']);

        if (! $user || ! $content || ! $this->userCanAuthor($user, $content)) {
            return null;
        }

        if (! $content->studio_slug) {
            $content->studio_slug = 'vr-'.$content->id.'-'.Str::lower(Str::random(8));
            $content->save();
        }

        $apiToken = $user->createToken('vr-studio', ['vr-studio'])->plainTextToken;

        return [
            'token' => $apiToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'experience' => $this->serializeExperience($content),
        ];
    }

    public function userCanAuthor(User $user, CourseVrContent $content): bool
    {
        if (! in_array($user->role, ['admin', 'tutor', 'facilitator'], true)) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'tutor') {
            $course = $content->relationLoaded('course') ? $content->course : $content->course()->first();

            return $course && $course->isAccessibleByTutor($user->id);
        }

        // Facilitator: course must be linked to learners in their location (same idea as Filament)
        if ($user->role === 'facilitator') {
            $location = $user->location;
            if (! $location) {
                return false;
            }

            return $content->course()
                ->whereHas('enrollments.user', fn ($q) => $q->whereRaw('LOWER(location) = LOWER(?)', [$location]))
                ->exists();
        }

        return false;
    }

    public function serializeExperience(CourseVrContent $content): array
    {
        $scene = $content->scene_json;
        if (is_string($scene)) {
            $scene = json_decode($scene, true);
        }

        return [
            'id' => $content->id,
            'course_id' => $content->course_id,
            'module_id' => $content->module_id,
            'course_title' => $content->course?->title,
            'module_title' => $content->module?->title,
            'title' => $content->title,
            'description' => $content->description,
            'instructions' => $content->instructions,
            'cta_label' => $content->cta_label ?: 'Launch VR',
            'vr_url' => $content->vr_url,
            'studio_slug' => $content->studio_slug,
            'studio_status' => $content->studio_status ?: 'draft',
            'scene' => $scene ?: $this->defaultScene($content),
            'is_active' => (bool) $content->is_active,
            'published_at' => optional($content->published_at)?->toIso8601String(),
            'player_url' => $content->studio_slug ? $this->playerUrlFor($content) : null,
        ];
    }

    public function defaultScene(CourseVrContent $content): array
    {
        return [
            'version' => 1,
            'engine' => 'three-webxr',
            'skyColor' => '#0b3d2e',
            'panoramaUrl' => null,
            'hotspots' => [],
            'objects' => [],
            'completion' => [
                'requireAllHotspots' => false,
                'buttonLabel' => 'Mark complete',
            ],
        ];
    }

    public function publish(CourseVrContent $content): CourseVrContent
    {
        if (! $content->studio_slug) {
            $content->studio_slug = 'vr-'.$content->id.'-'.Str::lower(Str::random(8));
        }

        $content->vr_url = $this->playerUrlFor($content);
        $content->studio_status = 'published';
        $content->published_at = now();
        $content->is_active = true;
        $content->save();

        return $content->fresh(['course', 'module']);
    }

    /**
     * Store a VR asset (e.g. 360 panorama). Prefers S3 when AWS_BUCKET is set; else public disk.
     *
     * @return array{url: string, path: string, disk: string}
     */
    public function storeAsset(CourseVrContent $content, \Illuminate\Http\UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $path = 'vr-studio/'.$content->id.'/'.Str::uuid().'.'.$ext;

        $useS3 = filled(config('filesystems.disks.s3.bucket'))
            && filled(config('filesystems.disks.s3.key'))
            && filled(config('filesystems.disks.s3.secret'));

        $disk = $useS3 ? 's3' : 'public';
        $stored = \Illuminate\Support\Facades\Storage::disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path),
            ['visibility' => 'public']
        );

        if (! $stored) {
            throw new \RuntimeException('Could not store VR asset.');
        }

        $url = \Illuminate\Support\Facades\Storage::disk($disk)->url($stored);

        return [
            'url' => $url,
            'path' => $stored,
            'disk' => $disk,
        ];
    }

    protected function cacheKey(string $token): string
    {
        return 'vr_studio_handoff:'.$token;
    }
}
