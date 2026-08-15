<?php

namespace App\Services;

use App\Models\CourseVrContent;
use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class VrSceneGeneratorService
{
    public const OBJECT_TYPES = [
        'weed',
        'leaves',
        'crop',
        'tree',
        'fish',
        'fish_pond',
        'water',
        'soil_bed',
        'fence',
        'barn',
        'tractor',
        'chicken',
        'goat',
    ];

    public function __construct(private VrStudioService $studio)
    {
    }

    /**
     * Ask Amazon Bedrock for a lesson scene; return sanitized copy + scene (does not persist).
     *
     * @return array{title: string, description: ?string, instructions: ?string, cta_label: string, scene: array}
     */
    public function generate(CourseVrContent $experience, string $prompt): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new RuntimeException('Prompt is required.');
        }

        $key = config('services.bedrock.key');
        $secret = config('services.bedrock.secret');
        $region = config('services.bedrock.region', 'us-east-1');
        $modelId = config('services.bedrock.model_id');

        if (! $key || ! $secret) {
            throw new RuntimeException('AI generation is not configured (missing AWS credentials for Bedrock).');
        }
        if (! $modelId) {
            throw new RuntimeException('AI generation is not configured (missing AWS_BEDROCK_MODEL_ID).');
        }

        $contextTitle = $experience->title ?: 'Untitled VR lesson';
        $courseTitle = $experience->course?->title ?? '';
        $userContent = "Course: {$courseTitle}\nExisting title: {$contextTitle}\n\nLesson brief:\n{$prompt}";

        try {
            $client = new BedrockRuntimeClient([
                'version' => 'latest',
                'region' => $region,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);

            $result = $client->converse([
                'modelId' => $modelId,
                'system' => [
                    ['text' => $this->systemPrompt()],
                ],
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['text' => $userContent],
                        ],
                    ],
                ],
                'inferenceConfig' => [
                    'maxTokens' => (int) config('services.bedrock.max_tokens', 4096),
                    'temperature' => 0.4,
                ],
            ]);
        } catch (AwsException $e) {
            Log::warning('VR scene generation Bedrock error', [
                'code' => $e->getAwsErrorCode(),
                'message' => $e->getAwsErrorMessage(),
            ]);
            $msg = (string) $e->getAwsErrorMessage();
            $hint = ($e->getAwsErrorCode() === 'AccessDeniedException' || str_contains($msg, "don't have access"))
                ? ' Enable model access for this Bedrock model in the AWS console and grant bedrock:InvokeModel to the IAM user.'
                : '';
            throw new RuntimeException('AI could not generate a scene right now.'.$hint);
        } catch (\Throwable $e) {
            Log::warning('VR scene generation Bedrock failure', ['message' => $e->getMessage()]);
            throw new RuntimeException('AI could not generate a scene right now. Try again in a moment.');
        }

        $text = $this->extractBedrockText($result->toArray());
        $decoded = $this->decodeJsonPayload($text);
        if (! is_array($decoded)) {
            throw new RuntimeException('AI returned an invalid scene. Please try a clearer prompt.');
        }

        return $this->sanitize($decoded, $experience);
    }

    protected function systemPrompt(): string
    {
        $types = implode(', ', self::OBJECT_TYPES);

        return <<<PROMPT
You are Agrisiti VR Studio's scene author for Nigerian agricultural training (WebXR / Three.js).
Return ONLY a single JSON object (no markdown fences, no commentary) with this shape:
{
  "title": "string",
  "description": "string",
  "instructions": "string — what the learner should do in VR",
  "cta_label": "string",
  "scene": {
    "version": 1,
    "engine": "three-webxr",
    "skyColor": "#hex",
    "panoramaUrl": null,
    "hotspots": [
      { "id": "uuid", "label": "short", "yaw": -180..180, "pitch": -80..80, "action": "info"|"link"|"complete", "payload": "string" }
    ],
    "objects": [
      { "id": "uuid", "type": "one of allowed types", "label": "string", "yaw": -180..180, "pitch": -80..80, "scale": 0.4..2.5 }
    ],
    "completion": { "requireAllHotspots": false, "buttonLabel": "Mark complete" }
  }
}

Rules:
- Allowed object types ONLY: {$types}
- Place 4–12 objects spread around the learner (vary yaw); put ground props at pitch about -12 to -25; sky accents higher.
- Include 3–6 educational hotspots with clear info payloads (action "info" unless a complete marker is useful).
- Do NOT invent photoreal panorama images. Set panoramaUrl to null unless the user's brief contains an https URL to an image — then copy that URL exactly.
- Prefer farm-realistic skyColor greens/blues (e.g. #0b3d2e, #1a4d6d).
- Content must fit smallholder / aquaculture / livestock lessons for Agrisiti Academy.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function extractBedrockText(array $result): string
    {
        $parts = $result['output']['message']['content'] ?? [];
        if (! is_array($parts)) {
            return '';
        }
        $chunks = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text'])) {
                $chunks[] = (string) $part['text'];
            }
        }

        return trim(implode("\n", $chunks));
    }

    protected function decodeJsonPayload(string $text): mixed
    {
        $text = trim($text);
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $text, $m)) {
            $text = trim($m[1]);
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        $slice = substr($text, $start, $end - $start + 1);

        return json_decode($slice, true);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{title: string, description: ?string, instructions: ?string, cta_label: string, scene: array}
     */
    protected function sanitize(array $raw, CourseVrContent $experience): array
    {
        $title = Str::limit(trim((string) ($raw['title'] ?? $experience->title ?: 'VR lesson')), 255, '');
        $description = isset($raw['description']) ? Str::limit(trim(strip_tags((string) $raw['description'])), 5000, '') : null;
        $instructions = isset($raw['instructions']) ? Str::limit(trim(strip_tags((string) $raw['instructions'])), 5000, '') : null;
        $cta = Str::limit(trim((string) ($raw['cta_label'] ?? 'Launch VR')), 100, '') ?: 'Launch VR';

        $sceneIn = is_array($raw['scene'] ?? null) ? $raw['scene'] : [];
        $default = $this->studio->defaultScene($experience);

        $panorama = $sceneIn['panoramaUrl'] ?? null;
        if (is_string($panorama) && $panorama !== '') {
            $panorama = filter_var($panorama, FILTER_VALIDATE_URL) ? $panorama : null;
        } else {
            $panorama = null;
        }

        $sky = (string) ($sceneIn['skyColor'] ?? $default['skyColor']);
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $sky)) {
            $sky = $default['skyColor'];
        }

        $hotspots = [];
        foreach (array_slice((array) ($sceneIn['hotspots'] ?? []), 0, 12) as $h) {
            if (! is_array($h)) {
                continue;
            }
            $action = (string) ($h['action'] ?? 'info');
            if (! in_array($action, ['info', 'link', 'complete'], true)) {
                $action = 'info';
            }
            $hotspots[] = [
                'id' => $this->uuidOrNew($h['id'] ?? null),
                'label' => Str::limit(trim((string) ($h['label'] ?? 'Point')), 80, '') ?: 'Point',
                'yaw' => $this->clampFloat($h['yaw'] ?? 0, -180, 180),
                'pitch' => $this->clampFloat($h['pitch'] ?? 0, -80, 80),
                'action' => $action,
                'payload' => Str::limit(trim((string) ($h['payload'] ?? '')), 2000, ''),
            ];
        }

        $objects = [];
        foreach (array_slice((array) ($sceneIn['objects'] ?? []), 0, 20) as $o) {
            if (! is_array($o)) {
                continue;
            }
            $type = (string) ($o['type'] ?? '');
            if (! in_array($type, self::OBJECT_TYPES, true)) {
                continue;
            }
            $objects[] = [
                'id' => $this->uuidOrNew($o['id'] ?? null),
                'type' => $type,
                'label' => Str::limit(trim((string) ($o['label'] ?? $type)), 80, '') ?: $type,
                'yaw' => $this->clampFloat($o['yaw'] ?? 0, -180, 180),
                'pitch' => $this->clampFloat($o['pitch'] ?? -15, -80, 80),
                'scale' => $this->clampFloat($o['scale'] ?? 1, 0.2, 5),
            ];
        }

        $completionIn = is_array($sceneIn['completion'] ?? null) ? $sceneIn['completion'] : [];

        return [
            'title' => $title ?: 'VR lesson',
            'description' => $description !== '' ? $description : null,
            'instructions' => $instructions !== '' ? $instructions : null,
            'cta_label' => $cta,
            'scene' => [
                'version' => 1,
                'engine' => 'three-webxr',
                'skyColor' => $sky,
                'panoramaUrl' => $panorama,
                'hotspots' => $hotspots,
                'objects' => $objects,
                'completion' => [
                    'requireAllHotspots' => (bool) ($completionIn['requireAllHotspots'] ?? false),
                    'buttonLabel' => Str::limit(trim((string) ($completionIn['buttonLabel'] ?? 'Mark complete')), 80, '') ?: 'Mark complete',
                ],
            ],
        ];
    }

    protected function uuidOrNew(mixed $value): string
    {
        $v = is_string($value) ? trim($value) : '';
        if ($v !== '' && Str::isUuid($v)) {
            return $v;
        }

        return (string) Str::uuid();
    }

    protected function clampFloat(mixed $value, float $min, float $max): float
    {
        $n = is_numeric($value) ? (float) $value : 0.0;

        return round(max($min, min($max, $n)), 1);
    }
}
