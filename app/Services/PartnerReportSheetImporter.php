<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartnerReportSheetImporter
{
    public const PARTICIPANT_HEADERS = [
        'name',
        'email',
        'phone',
        'gender',
        'state',
        'lga',
        'occupation',
        'notes',
    ];

    public const LINK_HEADERS = [
        'title',
        'url',
        'type',
    ];

    /**
     * @return list<array{name:string,email:?string,phone:?string,gender:?string,state:?string,lga:?string,occupation:?string,notes:?string}>
     */
    public static function importParticipants(string $absolutePath): array
    {
        $rows = self::readRows($absolutePath);
        if ($rows === []) {
            return [];
        }

        $header = array_map(fn ($h) => self::normalizeHeader((string) $h), array_shift($rows));
        $index = self::headerIndexMap($header, self::PARTICIPANT_HEADERS);

        $participants = [];
        foreach ($rows as $row) {
            if (! is_array($row) || self::rowIsEmpty($row)) {
                continue;
            }

            $name = self::cell($row, $index, 'name');
            $email = self::cell($row, $index, 'email');
            if ($name === '' && $email === '') {
                continue;
            }

            $gender = strtolower(self::cell($row, $index, 'gender'));
            if ($gender !== '' && ! in_array($gender, ['male', 'female', 'other', 'prefer_not_to_say'], true)) {
                // Keep free-text gender if sheet uses different casing/labels.
                $gender = self::cell($row, $index, 'gender') ?: null;
            } else {
                $gender = $gender !== '' ? $gender : null;
            }

            $participants[] = [
                'name' => $name !== '' ? $name : ($email ?: 'Unknown'),
                'email' => $email !== '' ? $email : null,
                'phone' => self::nullableCell($row, $index, 'phone'),
                'gender' => $gender,
                'state' => self::nullableCell($row, $index, 'state'),
                'lga' => self::nullableCell($row, $index, 'lga'),
                'occupation' => self::nullableCell($row, $index, 'occupation'),
                'notes' => self::nullableCell($row, $index, 'notes'),
            ];
        }

        return $participants;
    }

    /**
     * @return array{google_docs: list<array{title:string,url:string}>, images: list<array{caption:string,url:string}>}
     */
    public static function importActivityLinks(string $absolutePath): array
    {
        $rows = self::readRows($absolutePath);
        if ($rows === []) {
            return ['google_docs' => [], 'images' => []];
        }

        $header = array_map(fn ($h) => self::normalizeHeader((string) $h), array_shift($rows));
        $index = self::headerIndexMap($header, self::LINK_HEADERS);

        $docs = [];
        $images = [];

        foreach ($rows as $row) {
            if (! is_array($row) || self::rowIsEmpty($row)) {
                continue;
            }

            $url = self::cell($row, $index, 'url');
            if ($url === '') {
                continue;
            }

            $title = self::cell($row, $index, 'title');
            $type = strtolower(self::cell($row, $index, 'type'));

            $isImage = str_contains($type, 'image')
                || str_contains($type, 'photo')
                || str_contains($type, 'picture')
                || preg_match('/\.(png|jpe?g|gif|webp|bmp)(\?|$)/i', $url);

            if ($isImage) {
                $images[] = [
                    'caption' => $title !== '' ? $title : 'Activity photo',
                    'url' => $url,
                ];
            } else {
                $docs[] = [
                    'title' => $title !== '' ? $title : 'Google Doc',
                    'url' => $url,
                ];
            }
        }

        return ['google_docs' => $docs, 'images' => $images];
    }

    public static function downloadParticipantTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Participants');
        $sheet->fromArray(self::PARTICIPANT_HEADERS, null, 'A1');
        $sheet->fromArray([
            ['Ada Okafor', 'ada@example.com', '08012345678', 'female', 'Lagos', 'Ikeja', 'Farmer', 'Registered via outreach'],
            ['Chidi Bello', 'chidi@example.com', '08087654321', 'male', 'Kano', 'Nassarawa', 'Trader', ''],
        ], null, 'A2');

        return self::streamXlsx($spreadsheet, 'partner-report-participants-template.xlsx');
    }

    public static function downloadLinksTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Activity Links');
        $sheet->fromArray(self::LINK_HEADERS, null, 'A1');
        $sheet->fromArray([
            ['June field visit notes', 'https://docs.google.com/document/d/example', 'google_doc'],
            ['Demo hub photo', 'https://example.com/photo.jpg', 'image'],
        ], null, 'A2');

        return self::streamXlsx($spreadsheet, 'partner-report-activity-links-template.xlsx');
    }

    /**
     * @return list<list<mixed>>
     */
    private static function readRows(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            throw new \InvalidArgumentException('Uploaded sheet could not be found.');
        }

        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }

    private static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-', '.'], '_', $header);

        return match ($header) {
            'full_name', 'participant', 'participant_name' => 'name',
            'e_mail', 'mail' => 'email',
            'mobile', 'telephone', 'tel' => 'phone',
            'sex' => 'gender',
            'location' => 'state',
            'local_government', 'local_govt' => 'lga',
            'job', 'work' => 'occupation',
            'note', 'comment', 'comments' => 'notes',
            'caption', 'label', 'document_title', 'link_title' => 'title',
            'link', 'href' => 'url',
            'kind', 'category' => 'type',
            default => $header,
        };
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $wanted
     * @return array<string, int>
     */
    private static function headerIndexMap(array $header, array $wanted): array
    {
        $map = [];
        foreach ($wanted as $key) {
            $pos = array_search($key, $header, true);
            if ($pos !== false) {
                $map[$key] = (int) $pos;
            }
        }

        return $map;
    }

    /**
     * @param  list<mixed>  $row
     * @param  array<string, int>  $index
     */
    private static function cell(array $row, array $index, string $key): string
    {
        if (! array_key_exists($key, $index)) {
            return '';
        }

        $value = $row[$index[$key]] ?? '';

        return trim((string) $value);
    }

    /**
     * @param  list<mixed>  $row
     * @param  array<string, int>  $index
     */
    private static function nullableCell(array $row, array $index, string $key): ?string
    {
        $value = self::cell($row, $index, $key);

        return $value !== '' ? $value : null;
    }

    /**
     * @param  list<mixed>  $row
     */
    private static function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) ($cell ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function streamXlsx(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
