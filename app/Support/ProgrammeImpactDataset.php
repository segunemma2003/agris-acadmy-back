<?php

namespace App\Support;

/**
 * Custom programme impact figures for partner dashboard reports (Feb–May 2026).
 * Tabs and statistical tables are defined here so the frontend can render them from the API.
 * No personal names are included — only programme statistics and aggregates.
 */
class ProgrammeImpactDataset
{
    public static function title(): string
    {
        return 'Programme Impact Report — Feb to May 2026';
    }

    public static function summary(): string
    {
        return 'The Dignity in Labour programme, launched in April 2026, took the Demo Hub model further: participants did not simply observe, they contributed. Placed as active workers inside agribusiness settings, they handled rice processing, managed aquaculture feed cycles, and operated packaging lines alongside experienced practitioners. This report consolidates programme-wide results across registration, LMS learning, Demo Hub visits, competition architecture, and seed grant disbursement.';
    }

    /**
     * Headline KPIs used for report stats tiles / charts.
     *
     * @return list<array{key:string,label:string,value:float|int,unit:string}>
     */
    public static function glanceStats(): array
    {
        return [
            ['key' => 'total_applications', 'label' => 'Total applications', 'value' => 6664, 'unit' => 'count'],
            ['key' => 'participants_selected', 'label' => 'Participants selected', 'value' => 3591, 'unit' => 'count'],
            ['key' => 'lms_enrolled', 'label' => 'LMS enrolled', 'value' => 2690, 'unit' => 'count'],
            ['key' => 'active_learners', 'label' => 'Active learners', 'value' => 2539, 'unit' => 'count'],
            ['key' => 'modules_delivered', 'label' => 'Modules delivered', 'value' => 11, 'unit' => 'count'],
            ['key' => 'demo_hub_visits', 'label' => 'Demo hub visits', 'value' => 117, 'unit' => 'count'],
            ['key' => 'pitch_entries', 'label' => 'Pitch entries', 'value' => 155, 'unit' => 'count'],
            ['key' => 'grant_winners', 'label' => 'Seed grant winners', 'value' => 25, 'unit' => 'count'],
            ['key' => 'seed_grants_disbursed', 'label' => 'Seed grants disbursed (NGN)', 'value' => 4600000, 'unit' => 'ngn'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function tabs(): array
    {
        return [
            [
                'key' => 'glance',
                'label' => 'At a glance',
                'title' => '2. Programme at a Glance',
                'narrative' => 'Nine headline results spanning applications through seed grant disbursement across seven states.',
                'blocks' => [
                    [
                        'type' => 'stat_grid',
                        'title' => 'Programme at a Glance',
                        'items' => [
                            ['label' => 'Total applications', 'value' => 6664, 'unit' => 'count', 'hint' => 'Reviewed & scored against a 50-point inclusive framework'],
                            ['label' => 'Participants selected', 'value' => 3591, 'unit' => 'count', 'hint' => '61% female; 161 PWDs enrolled'],
                            ['label' => 'LMS enrolled', 'value' => 2690, 'unit' => 'count', 'hint' => 'Final figure at programme close'],
                            ['label' => 'Active learners', 'value' => 2539, 'unit' => 'count', 'hint' => '94.4% of enrolled – programme-wide'],
                            ['label' => 'Modules delivered', 'value' => 11, 'unit' => 'count', 'hint' => 'February to April 2026; all via Agrisiti LMS'],
                            ['label' => 'Demo hub visits', 'value' => 117, 'unit' => 'count', 'hint' => 'Rice processing, aquaculture, agritech sites'],
                            ['label' => 'Pitch entries', 'value' => 155, 'unit' => 'count', 'hint' => 'Business Track teams; all 7 states represented'],
                            ['label' => 'Seed grant winners', 'value' => 25, 'unit' => 'count', 'hint' => 'All 7 states represented'],
                            ['label' => 'Seed grants disbursed', 'value' => 4600000, 'unit' => 'ngn', 'hint' => 'Across 25 winning agribusiness teams'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'key_results',
                'label' => 'Key results',
                'title' => 'Key Results',
                'narrative' => 'Programme-wide achievements with notes for partners.',
                'blocks' => [
                    [
                        'type' => 'key_results',
                        'title' => 'Key Results',
                        'columns' => ['Key Result', 'Achievement', 'Notes'],
                        'rows' => [
                            ['States covered', '7', 'Ogun, Niger, Bayelsa, Kano, Rivers, Cross River, Enugu'],
                            ['Total applications received', '6,664', 'Reviewed & scored against 50-point inclusive framework'],
                            ['Participants selected', '3,591', '61% female; 161 PWDs enrolled'],
                            ['LMS enrolled learners', '2,690', 'Final figure at programme close'],
                            ['Active learners at close', '2,539', '94.4% of enrolled – programme-wide'],
                            ['Learning groups formed', '319', 'Across all 7 states by April 2026'],
                            ['Modules delivered', '11', 'February to April 2026; all via Agrisiti LMS'],
                            ['Demo hub visits conducted', '117', 'Rice processing, aquaculture, agritech sites'],
                            ['Dignity in Labour sessions', '15', 'Niger State (12) and Rivers State (3) in April 2026'],
                            ['Elevator pitch entries', '155', 'Business Track teams; all 7 states represented'],
                            ['Teams in semi-finals', '82', '11–12 May 2026; 9 expert judges across 3 panels'],
                            ['Teams in finals', '54', '36 virtual (15 May); 18 live in Kano (16 May)'],
                            ['Seed grant winners', '25 teams', 'All 7 states represented'],
                            ['Total seed grants disbursed', 'NGN 4,600,000', 'Across 25 winning agribusiness teams'],
                            ['Female representation (selected)', '61%', 'Up from 38.9% at registration – deliberate design outcome'],
                            ['PWDs enrolled', '161', 'Inclusive enrolment across the programme'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'registration',
                'label' => 'Registration',
                'title' => '4.1 Registration and Screening',
                'narrative' => 'State-level registration and screening profile used for selection design.',
                'blocks' => [
                    [
                        'type' => 'table',
                        'title' => 'Registration and Screening by State',
                        'columns' => ['State', 'Registrations', 'Female', 'IDP', 'PWD', '% Screened'],
                        'rows' => [
                            ['Kano', '1,930', '794', '26', '162', '66.3%'],
                            ['Niger', '1,028', '327', '2', '20', '68.4%'],
                            ['Ogun', '551', '237', '4', '8', '77.9%'],
                            ['Rivers', '284', '111', '8', '11', '73.2%'],
                            ['Cross River', '295', '98', '10', '13', '0%'],
                            ['Enugu', '118', '52', '1', '1', '0%'],
                            ['Bayelsa', '199', '94', '1', '10', '35.2%'],
                            ['TOTAL', '4,405', '1,713', '52', '225', '61.1%'],
                        ],
                        'chart' => [
                            'title' => 'Registrations by state',
                            'items' => [
                                ['label' => 'Kano', 'value' => 1930],
                                ['label' => 'Niger', 'value' => 1028],
                                ['label' => 'Ogun', 'value' => 551],
                                ['label' => 'Cross River', 'value' => 295],
                                ['label' => 'Rivers', 'value' => 284],
                                ['label' => 'Bayelsa', 'value' => 199],
                                ['label' => 'Enugu', 'value' => 118],
                            ],
                        ],
                    ],
                    [
                        'type' => 'stat_grid',
                        'title' => 'Inclusion snapshot (registration table)',
                        'items' => [
                            ['label' => 'IDP / refugee participants', 'value' => 52, 'unit' => 'count', 'hint' => 'Confirmed across screening states'],
                            ['label' => 'PWD at registration', 'value' => 225, 'unit' => 'count', 'hint' => 'From registration & screening totals'],
                            ['label' => 'Female at registration', 'value' => 1713, 'unit' => 'count', 'hint' => '38.9% of 4,405 registrations'],
                            ['label' => 'Overall screened', 'value' => 61.1, 'unit' => 'percentage', 'hint' => 'Programme screening coverage'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'learning',
                'label' => 'Learning',
                'title' => '5. Learning Progression & LMS Engagement',
                'narrative' => 'Monthly learning progression and LMS engagement by state through programme close.',
                'blocks' => [
                    [
                        'type' => 'table',
                        'title' => '5.1 Monthly Learning Progression',
                        'columns' => ['Month', 'Modules', 'Key learning activities', 'Enrolled / Active'],
                        'rows' => [
                            ['February 2026', 'Modules 1–4', 'LMS launch; team formation; peer assignments; first Demo Hub visit (Ogun, 21 Feb); live Q&A via Zoom; leaderboard badges', '1,198 / 1,173'],
                            ['March 2026', 'Modules 5–8', 'Live masterclasses; capstone presentations; PWD drone workshop (29 Mar, Kano); 11 Demo Hub visits across 6 states', '1,623 / 1,512'],
                            ['April 2026', 'Modules 9–11', 'Live Finance Clinic; Virtual Hangout; CV Clinic; Track Selection Survey; 155 pitch entries; mentorship launched', '2,690 / 2,539'],
                        ],
                    ],
                    [
                        'type' => 'table',
                        'title' => '5.3 LMS Engagement by State',
                        'columns' => ['State', 'Feb enrolled', 'Feb active %', 'Mar enrolled', 'Mar active %', 'Apr active', 'Final active'],
                        'rows' => [
                            ['Kano', '369', '78.3%', '580', '90.2%', '856', '856'],
                            ['Niger', '261', '69.4%', '261', '100%', '568', '568'],
                            ['Ogun', '253', '100%', '253', '90.9%', '519', '519'],
                            ['Rivers', '148', '100%', '148', '100%', '200', '200'],
                            ['Cross River', '180', '77.8%', '164', '85.4%', '164', '164'],
                            ['Enugu', '61', '100%', '98', '100%', '127', '127'],
                            ['Bayelsa', '119', '86.6%', '112', '94.1%', '105', '105'],
                            ['TOTAL', '1,391', '—', '1,616', '—', '2,539', '2,539'],
                        ],
                        'chart' => [
                            'title' => 'Final active learners by state',
                            'items' => [
                                ['label' => 'Kano', 'value' => 856],
                                ['label' => 'Niger', 'value' => 568],
                                ['label' => 'Ogun', 'value' => 519],
                                ['label' => 'Rivers', 'value' => 200],
                                ['label' => 'Cross River', 'value' => 164],
                                ['label' => 'Enugu', 'value' => 127],
                                ['label' => 'Bayelsa', 'value' => 105],
                            ],
                        ],
                    ],
                    [
                        'type' => 'stat_grid',
                        'title' => 'Learning outcomes',
                        'items' => [
                            ['label' => 'LMS enrolled', 'value' => 2690, 'unit' => 'count'],
                            ['label' => 'Active at close', 'value' => 2539, 'unit' => 'count'],
                            ['label' => 'Active rate', 'value' => 94.4, 'unit' => 'percentage'],
                            ['label' => 'Modules delivered', 'value' => 11, 'unit' => 'count'],
                            ['label' => 'Learning groups', 'value' => 319, 'unit' => 'count'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'demo_hubs',
                'label' => 'Demo hubs',
                'title' => 'Demo Hub Visits',
                'narrative' => 'Field exposure across rice processing, aquaculture and agritech demonstration sites.',
                'blocks' => [
                    [
                        'type' => 'table',
                        'title' => 'Demo hub visits by state',
                        'columns' => ['Month', 'State', 'Focus', 'Hubs visited'],
                        'rows' => [
                            ['Feb–Mar 2026', 'Ogun', 'Rice processing; drone/agritech demonstration', '17'],
                            ['Mar 2026', 'Niger', 'Agribusiness operations; commercial rice processing at scale', '16'],
                            ['Mar 2026', 'Cross River', 'Rice milling & processing; aquaculture; mixed value chain', '30'],
                            ['Mar–Apr 2026', 'Bayelsa', 'Mixed value chain; aquaculture enterprise', '3'],
                            ['Mar–Apr 2026', 'Kano', 'Aerial mapping & drone operations; live aquaculture', '25'],
                            ['Mar 2026', 'Enugu', 'Aquaculture processing; rice value chain processing', '4'],
                            ['Apr 2026', 'Rivers', 'Rice & aquaculture, multiple sessions', '22'],
                            ['TOTAL', '—', 'Programme-wide Demo Hub visits', '117'],
                        ],
                        'chart' => [
                            'title' => 'Demo hub visits by state',
                            'items' => [
                                ['label' => 'Cross River', 'value' => 30],
                                ['label' => 'Kano', 'value' => 25],
                                ['label' => 'Rivers', 'value' => 22],
                                ['label' => 'Ogun', 'value' => 17],
                                ['label' => 'Niger', 'value' => 16],
                                ['label' => 'Enugu', 'value' => 4],
                                ['label' => 'Bayelsa', 'value' => 3],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'dignity',
                'label' => 'Dignity in Labour',
                'title' => 'Dignity in Labour',
                'narrative' => 'The Dignity in Labour programme, launched in April 2026, took the Demo Hub model further: participants did not simply observe, they contributed. Placed as active workers inside agribusiness settings, they handled rice processing, managed aquaculture feed cycles, and operated packaging lines alongside experienced practitioners.',
                'blocks' => [
                    [
                        'type' => 'table',
                        'title' => 'Dignity in Labour sessions',
                        'columns' => ['State', 'Sessions', 'Enterprises / sites', 'Key activity'],
                        'rows' => [
                            ['Niger', '12', 'Technology Incubation Centre (rice processing & packaging); Alpha Green (fish production)', 'Hands-on sessions over 3 consecutive days; female participants predominantly represented'],
                            ['Rivers', '3', 'ARAC Demonstration Farm, Umuechi', 'Repeated structured exposure to established agribusiness operations; confidence and competence building'],
                            ['TOTAL', '15', '—', 'Niger (12) + Rivers (3) in April 2026'],
                        ],
                    ],
                    [
                        'type' => 'stat_grid',
                        'title' => 'Dignity in Labour snapshot',
                        'items' => [
                            ['label' => 'Sessions delivered', 'value' => 15, 'unit' => 'count'],
                            ['label' => 'States engaged', 'value' => 2, 'unit' => 'count'],
                            ['label' => 'Niger sessions', 'value' => 12, 'unit' => 'count'],
                            ['label' => 'Rivers sessions', 'value' => 3, 'unit' => 'count'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'competition',
                'label' => 'Competition',
                'title' => '8. Competition Architecture & Numbers',
                'narrative' => 'Business Track competition funnel from elevator pitch entries through live finals and seed grant awards.',
                'blocks' => [
                    [
                        'type' => 'table',
                        'title' => '8.1 Competition Architecture',
                        'columns' => ['Stage', 'Date(s)', 'Format', 'Scale'],
                        'rows' => [
                            ['Elevator pitch entry round', 'April 2026', 'Written submission reviewed by the programme team', '155 entries from all 7 states; 53.5% qualification rate'],
                            ['Semi-finals', '11–12 May 2026', 'Live on Zoom; 3 simultaneous breakout rooms; Panels A, B & C', '82 teams; 9 expert judges (3 per panel)'],
                            ['Virtual final', 'Friday, 15 May 2026', 'Live Zoom pitch event; 6 states represented', '36 finalist teams pitching virtually'],
                            ['Live final – Kano', 'Saturday, 16 May 2026', 'In-person ceremony with full event programme', '18 finalist teams; live audience in Kano State'],
                        ],
                    ],
                    [
                        'type' => 'stat_grid',
                        'title' => '8.2 The Numbers Behind the Competition',
                        'items' => [
                            ['label' => 'Elevator pitch entries', 'value' => 155, 'unit' => 'count'],
                            ['label' => 'Semi-final teams', 'value' => 82, 'unit' => 'count'],
                            ['label' => 'Finalist teams', 'value' => 54, 'unit' => 'count'],
                            ['label' => 'Expert judges', 'value' => 13, 'unit' => 'count'],
                            ['label' => 'Winning teams', 'value' => 25, 'unit' => 'count'],
                            ['label' => 'Seed grants disbursed', 'value' => 4600000, 'unit' => 'ngn'],
                        ],
                        'chart' => [
                            'title' => 'Competition funnel',
                            'items' => [
                                ['label' => 'Pitch entries', 'value' => 155],
                                ['label' => 'Semi-finals', 'value' => 82],
                                ['label' => 'Finals', 'value' => 54],
                                ['label' => 'Winners', 'value' => 25],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'grants',
                'label' => 'Seed grants',
                'title' => '8.4 Seed Grant Summary by State',
                'narrative' => 'Seed grant disbursement across winning agribusiness teams in seven states. Individual recipient names are omitted; figures are programme aggregates.',
                'blocks' => [
                    [
                        'type' => 'table',
                        'title' => 'Winning topics (focus areas)',
                        'columns' => ['Topic / focus area', 'Winning teams', 'Share of winners'],
                        'rows' => [
                            ['Aquaculture & fish value chain', '8', '32%'],
                            ['Rice production & processing', '7', '28%'],
                            ['Integrated agribusiness', '5', '20%'],
                            ['Agri-nutrition & value addition', '2', '8%'],
                            ['Mixed farming / agribusiness', '2', '8%'],
                            ['Women-led agribusiness', '1', '4%'],
                            ['TOTAL', '25', '100%'],
                        ],
                        'chart' => [
                            'title' => 'Winning topics (teams)',
                            'items' => [
                                ['label' => 'Aquaculture & fish', 'value' => 8],
                                ['label' => 'Rice production', 'value' => 7],
                                ['label' => 'Integrated agribusiness', 'value' => 5],
                                ['label' => 'Agri-nutrition', 'value' => 2],
                                ['label' => 'Mixed farming', 'value' => 2],
                                ['label' => 'Women-led', 'value' => 1],
                            ],
                        ],
                    ],
                    [
                        'type' => 'table',
                        'title' => 'Seed grant disbursed per state (NGN)',
                        'columns' => ['State', 'Winners', 'Disbursed (NGN)'],
                        'rows' => [
                            ['Kano', '6', '1,000,000'],
                            ['Niger', '5', '900,000'],
                            ['Enugu', '3', '600,000'],
                            ['Rivers', '3', '600,000'],
                            ['Bayelsa', '3', '600,000'],
                            ['Ogun', '3', '600,000'],
                            ['Cross River', '2', '300,000'],
                            ['TOTAL', '25', '4,600,000'],
                        ],
                        'chart' => [
                            'title' => 'Disbursed per state (NGN)',
                            'items' => [
                                ['label' => 'Kano', 'value' => 1000000],
                                ['label' => 'Niger', 'value' => 900000],
                                ['label' => 'Enugu', 'value' => 600000],
                                ['label' => 'Rivers', 'value' => 600000],
                                ['label' => 'Bayelsa', 'value' => 600000],
                                ['label' => 'Ogun', 'value' => 600000],
                                ['label' => 'Cross River', 'value' => 300000],
                            ],
                        ],
                    ],
                    [
                        'type' => 'stat_grid',
                        'title' => 'Grant impact',
                        'items' => [
                            ['label' => 'Winning teams', 'value' => 25, 'unit' => 'count'],
                            ['label' => 'Total disbursed', 'value' => 4600000, 'unit' => 'ngn'],
                            ['label' => 'Average grant / team', 'value' => 184000, 'unit' => 'ngn'],
                            ['label' => 'States represented', 'value' => 7, 'unit' => 'count'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'inclusion',
                'label' => 'Gender & inclusion',
                'title' => 'Gender, location & inclusion',
                'narrative' => 'Female representation rose from 38.9% at registration to 61% among selected participants. Screening and inclusion totals below are from the registration table (4,405 screened cohort) and programme close figures.',
                'blocks' => [
                    [
                        'type' => 'stat_grid',
                        'title' => 'Gender & inclusion KPIs',
                        'items' => [
                            ['label' => 'Female (registration)', 'value' => 1713, 'unit' => 'count', 'hint' => '38.9% of 4,405 registrations'],
                            ['label' => 'Male / other (registration)', 'value' => 2692, 'unit' => 'count', 'hint' => '4,405 − 1,713'],
                            ['label' => 'Female share (registration)', 'value' => 38.9, 'unit' => 'percentage'],
                            ['label' => 'Female share (selected)', 'value' => 61.0, 'unit' => 'percentage', 'hint' => '≈ 2,191 of 3,591 selected'],
                            ['label' => 'PWD at registration', 'value' => 225, 'unit' => 'count'],
                            ['label' => 'PWDs enrolled (programme)', 'value' => 161, 'unit' => 'count'],
                            ['label' => 'IDP / refugee participants', 'value' => 52, 'unit' => 'count'],
                            ['label' => 'States covered', 'value' => 7, 'unit' => 'count'],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Gender at registration',
                        'items' => [
                            ['label' => 'Female', 'value' => 1713],
                            ['label' => 'Male / other', 'value' => 2692],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Gender among selected (61% female)',
                        'items' => [
                            ['label' => 'Female', 'value' => 2191],
                            ['label' => 'Male / other', 'value' => 1400],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Female registrants by state',
                        'items' => [
                            ['label' => 'Kano', 'value' => 794],
                            ['label' => 'Niger', 'value' => 327],
                            ['label' => 'Ogun', 'value' => 237],
                            ['label' => 'Rivers', 'value' => 111],
                            ['label' => 'Cross River', 'value' => 98],
                            ['label' => 'Bayelsa', 'value' => 94],
                            ['label' => 'Enugu', 'value' => 52],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'PWD at registration by state',
                        'items' => [
                            ['label' => 'Kano', 'value' => 162],
                            ['label' => 'Niger', 'value' => 20],
                            ['label' => 'Cross River', 'value' => 13],
                            ['label' => 'Rivers', 'value' => 11],
                            ['label' => 'Bayelsa', 'value' => 10],
                            ['label' => 'Ogun', 'value' => 8],
                            ['label' => 'Enugu', 'value' => 1],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'IDP participants by state',
                        'items' => [
                            ['label' => 'Kano', 'value' => 26],
                            ['label' => 'Cross River', 'value' => 10],
                            ['label' => 'Rivers', 'value' => 8],
                            ['label' => 'Ogun', 'value' => 4],
                            ['label' => 'Niger', 'value' => 2],
                            ['label' => 'Enugu', 'value' => 1],
                            ['label' => 'Bayelsa', 'value' => 1],
                        ],
                    ],
                    [
                        'type' => 'table',
                        'title' => 'Registration gender & inclusion by state',
                        'columns' => ['State', 'Registrations', 'Female', 'Female %', 'IDP', 'PWD'],
                        'rows' => [
                            ['Kano', '1,930', '794', '41.1%', '26', '162'],
                            ['Niger', '1,028', '327', '31.8%', '2', '20'],
                            ['Ogun', '551', '237', '43.0%', '4', '8'],
                            ['Rivers', '284', '111', '39.1%', '8', '11'],
                            ['Cross River', '295', '98', '33.2%', '10', '13'],
                            ['Enugu', '118', '52', '44.1%', '1', '1'],
                            ['Bayelsa', '199', '94', '47.2%', '1', '10'],
                            ['TOTAL', '4,405', '1,713', '38.9%', '52', '225'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'analysis',
                'label' => 'Analysis',
                'title' => 'Statistical analysis',
                'narrative' => 'Conversion rates, state distribution, gender shift, and competition funnel derived from programme figures.',
                'blocks' => [
                    [
                        'type' => 'stat_grid',
                        'title' => 'Conversion & retention',
                        'items' => [
                            ['label' => 'Application → selected', 'value' => 53.9, 'unit' => 'percentage', 'hint' => '3,591 / 6,664'],
                            ['label' => 'Selected → LMS enrolled', 'value' => 74.9, 'unit' => 'percentage', 'hint' => '2,690 / 3,591'],
                            ['label' => 'LMS → active close', 'value' => 94.4, 'unit' => 'percentage', 'hint' => '2,539 / 2,690'],
                            ['label' => 'Pitch → semi-final', 'value' => 52.9, 'unit' => 'percentage', 'hint' => '82 / 155'],
                            ['label' => 'Semi-final → final', 'value' => 65.9, 'unit' => 'percentage', 'hint' => '54 / 82'],
                            ['label' => 'Final → winner', 'value' => 46.3, 'unit' => 'percentage', 'hint' => '25 / 54'],
                            ['label' => 'Female lift (reg → selected)', 'value' => 22.1, 'unit' => 'percentage', 'hint' => '61.0% − 38.9%'],
                            ['label' => 'Avg grant / winning team', 'value' => 184000, 'unit' => 'ngn', 'hint' => '4,600,000 / 25'],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Participant funnel',
                        'items' => [
                            ['label' => 'Applications', 'value' => 6664],
                            ['label' => 'Selected', 'value' => 3591],
                            ['label' => 'LMS enrolled', 'value' => 2690],
                            ['label' => 'Active learners', 'value' => 2539],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Competition funnel',
                        'items' => [
                            ['label' => 'Pitch entries', 'value' => 155],
                            ['label' => 'Semi-finals', 'value' => 82],
                            ['label' => 'Finals', 'value' => 54],
                            ['label' => 'Winners', 'value' => 25],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Registrations by state',
                        'items' => [
                            ['label' => 'Kano', 'value' => 1930],
                            ['label' => 'Niger', 'value' => 1028],
                            ['label' => 'Ogun', 'value' => 551],
                            ['label' => 'Cross River', 'value' => 295],
                            ['label' => 'Rivers', 'value' => 284],
                            ['label' => 'Bayelsa', 'value' => 199],
                            ['label' => 'Enugu', 'value' => 118],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Active learners by state',
                        'items' => [
                            ['label' => 'Kano', 'value' => 856],
                            ['label' => 'Niger', 'value' => 568],
                            ['label' => 'Ogun', 'value' => 519],
                            ['label' => 'Rivers', 'value' => 200],
                            ['label' => 'Cross River', 'value' => 164],
                            ['label' => 'Enugu', 'value' => 127],
                            ['label' => 'Bayelsa', 'value' => 105],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Demo hub visits by state',
                        'items' => [
                            ['label' => 'Cross River', 'value' => 30],
                            ['label' => 'Kano', 'value' => 25],
                            ['label' => 'Rivers', 'value' => 22],
                            ['label' => 'Ogun', 'value' => 17],
                            ['label' => 'Niger', 'value' => 16],
                            ['label' => 'Enugu', 'value' => 4],
                            ['label' => 'Bayelsa', 'value' => 3],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Seed grant winners by state',
                        'items' => [
                            ['label' => 'Kano', 'value' => 6],
                            ['label' => 'Niger', 'value' => 5],
                            ['label' => 'Enugu', 'value' => 3],
                            ['label' => 'Rivers', 'value' => 3],
                            ['label' => 'Bayelsa', 'value' => 3],
                            ['label' => 'Ogun', 'value' => 3],
                            ['label' => 'Cross River', 'value' => 2],
                        ],
                    ],
                    [
                        'type' => 'chart',
                        'title' => 'Gender at registration vs selected',
                        'items' => [
                            ['label' => 'Female % registration', 'value' => 38.9],
                            ['label' => 'Female % selected', 'value' => 61.0],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Chart breakdowns attached to reports section + each report payload.
     *
     * @return list<array{title:string,items:list<array{label:string,value:float|int}>}>
     */
    public static function programmeBreakdowns(): array
    {
        return [
            [
                'title' => 'Participant funnel',
                'items' => [
                    ['label' => 'Applications', 'value' => 6664],
                    ['label' => 'Selected', 'value' => 3591],
                    ['label' => 'LMS enrolled', 'value' => 2690],
                    ['label' => 'Active learners', 'value' => 2539],
                ],
            ],
            [
                'title' => 'Competition funnel',
                'items' => [
                    ['label' => 'Pitch entries', 'value' => 155],
                    ['label' => 'Semi-finals', 'value' => 82],
                    ['label' => 'Finals', 'value' => 54],
                    ['label' => 'Winners', 'value' => 25],
                ],
            ],
            [
                'title' => 'Registrations by state',
                'items' => [
                    ['label' => 'Kano', 'value' => 1930],
                    ['label' => 'Niger', 'value' => 1028],
                    ['label' => 'Ogun', 'value' => 551],
                    ['label' => 'Cross River', 'value' => 295],
                    ['label' => 'Rivers', 'value' => 284],
                    ['label' => 'Bayelsa', 'value' => 199],
                    ['label' => 'Enugu', 'value' => 118],
                ],
            ],
            [
                'title' => 'Female registrants by state',
                'items' => [
                    ['label' => 'Kano', 'value' => 794],
                    ['label' => 'Niger', 'value' => 327],
                    ['label' => 'Ogun', 'value' => 237],
                    ['label' => 'Rivers', 'value' => 111],
                    ['label' => 'Cross River', 'value' => 98],
                    ['label' => 'Bayelsa', 'value' => 94],
                    ['label' => 'Enugu', 'value' => 52],
                ],
            ],
            [
                'title' => 'Gender at registration',
                'items' => [
                    ['label' => 'Female', 'value' => 1713],
                    ['label' => 'Male / other', 'value' => 2692],
                ],
            ],
            [
                'title' => 'Gender among selected (61% female)',
                'items' => [
                    ['label' => 'Female', 'value' => 2191],
                    ['label' => 'Male / other', 'value' => 1400],
                ],
            ],
            [
                'title' => 'Active learners by state',
                'items' => [
                    ['label' => 'Kano', 'value' => 856],
                    ['label' => 'Niger', 'value' => 568],
                    ['label' => 'Ogun', 'value' => 519],
                    ['label' => 'Rivers', 'value' => 200],
                    ['label' => 'Cross River', 'value' => 164],
                    ['label' => 'Enugu', 'value' => 127],
                    ['label' => 'Bayelsa', 'value' => 105],
                ],
            ],
            [
                'title' => 'Demo hub visits by state',
                'items' => [
                    ['label' => 'Cross River', 'value' => 30],
                    ['label' => 'Kano', 'value' => 25],
                    ['label' => 'Rivers', 'value' => 22],
                    ['label' => 'Ogun', 'value' => 17],
                    ['label' => 'Niger', 'value' => 16],
                    ['label' => 'Enugu', 'value' => 4],
                    ['label' => 'Bayelsa', 'value' => 3],
                ],
            ],
            [
                'title' => 'Seed grant winners by state',
                'items' => [
                    ['label' => 'Kano', 'value' => 6],
                    ['label' => 'Niger', 'value' => 5],
                    ['label' => 'Enugu', 'value' => 3],
                    ['label' => 'Rivers', 'value' => 3],
                    ['label' => 'Bayelsa', 'value' => 3],
                    ['label' => 'Ogun', 'value' => 3],
                    ['label' => 'Cross River', 'value' => 2],
                ],
            ],
            [
                'title' => 'Winning topics (focus areas)',
                'items' => [
                    ['label' => 'Aquaculture & fish', 'value' => 8],
                    ['label' => 'Rice production', 'value' => 7],
                    ['label' => 'Integrated agribusiness', 'value' => 5],
                    ['label' => 'Agri-nutrition', 'value' => 2],
                    ['label' => 'Mixed farming', 'value' => 2],
                    ['label' => 'Women-led', 'value' => 1],
                ],
            ],
            [
                'title' => 'Seed grants disbursed per state (NGN)',
                'items' => [
                    ['label' => 'Kano', 'value' => 1000000],
                    ['label' => 'Niger', 'value' => 900000],
                    ['label' => 'Enugu', 'value' => 600000],
                    ['label' => 'Rivers', 'value' => 600000],
                    ['label' => 'Bayelsa', 'value' => 600000],
                    ['label' => 'Ogun', 'value' => 600000],
                    ['label' => 'Cross River', 'value' => 300000],
                ],
            ],
            [
                'title' => 'PWD at registration by state',
                'items' => [
                    ['label' => 'Kano', 'value' => 162],
                    ['label' => 'Niger', 'value' => 20],
                    ['label' => 'Cross River', 'value' => 13],
                    ['label' => 'Rivers', 'value' => 11],
                    ['label' => 'Bayelsa', 'value' => 10],
                    ['label' => 'Ogun', 'value' => 8],
                    ['label' => 'Enugu', 'value' => 1],
                ],
            ],
            [
                'title' => 'IDP participants by state',
                'items' => [
                    ['label' => 'Kano', 'value' => 26],
                    ['label' => 'Cross River', 'value' => 10],
                    ['label' => 'Rivers', 'value' => 8],
                    ['label' => 'Ogun', 'value' => 4],
                    ['label' => 'Niger', 'value' => 2],
                    ['label' => 'Enugu', 'value' => 1],
                    ['label' => 'Bayelsa', 'value' => 1],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label:string,value:int,key:string}>
     */
    public static function genderFilters(): array
    {
        return [
            ['key' => 'female', 'label' => 'Female', 'value' => 2191],
            ['key' => 'male', 'label' => 'Male / other', 'value' => 1400],
        ];
    }

    /**
     * Location filters use final active learners by state.
     *
     * @return list<array{label:string,value:int,key:string}>
     */
    public static function locationFilters(): array
    {
        return [
            ['key' => 'kano', 'label' => 'Kano', 'value' => 856],
            ['key' => 'niger', 'label' => 'Niger', 'value' => 568],
            ['key' => 'ogun', 'label' => 'Ogun', 'value' => 519],
            ['key' => 'rivers', 'label' => 'Rivers', 'value' => 200],
            ['key' => 'cross river', 'label' => 'Cross River', 'value' => 164],
            ['key' => 'enugu', 'label' => 'Enugu', 'value' => 127],
            ['key' => 'bayelsa', 'label' => 'Bayelsa', 'value' => 105],
        ];
    }

    /**
     * Full partner-dashboard report payload (synthetic published report).
     *
     * @return array<string, mixed>
     */
    public static function toPartnerReportPayload(int $id = 1): array
    {
        $stats = self::glanceStats();

        return [
            'id' => $id,
            'title' => self::title(),
            'period_type' => 'custom',
            'period_label' => 'Programme cycle',
            'period_start' => '2026-02-01',
            'period_end' => '2026-05-31',
            'summary' => self::summary(),
            'published_at' => '2026-05-31T12:00:00+00:00',
            'created_at' => '2026-05-31T12:00:00+00:00',
            'stats' => $stats,
            'breakdowns' => self::programmeBreakdowns(),
            'chart' => [
                'title' => 'Programme outcomes',
                'unit' => 'count',
                'points' => [
                    ['label' => 'Applications', 'value' => 6664],
                    ['label' => 'Selected', 'value' => 3591],
                    ['label' => 'LMS enrolled', 'value' => 2690],
                    ['label' => 'Active', 'value' => 2539],
                    ['label' => 'Demo hubs', 'value' => 117],
                    ['label' => 'Pitch entries', 'value' => 155],
                    ['label' => 'Grant winners', 'value' => 25],
                ],
            ],
            'gender_filters' => self::genderFilters(),
            'location_filters' => self::locationFilters(),
            'participants_registered' => [],
            'participants_selected' => [],
            'participants_enrolled' => [],
            'google_doc_links' => [],
            'image_links' => [],
            'tabs' => self::tabs(),
        ];
    }

    /**
     * Replace outcome figures / tabs / charts with programme impact data,
     * while keeping uploaded name lists, docs and images from an existing report.
     *
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    public static function overlayOn(array $existing): array
    {
        $impact = self::toPartnerReportPayload((int) ($existing['id'] ?? -1));

        // Keep report identity / period labels from the sent report when present.
        if (! empty($existing['id']) && (int) $existing['id'] > 0) {
            $impact['id'] = (int) $existing['id'];
        }
        if (! empty($existing['title'])) {
            $impact['title'] = (string) $existing['title'];
        }
        if (! empty($existing['period_type'])) {
            $impact['period_type'] = (string) $existing['period_type'];
            $impact['period_label'] = (string) ($existing['period_label'] ?? ucfirst((string) $existing['period_type']));
        }
        if (array_key_exists('period_start', $existing)) {
            $impact['period_start'] = $existing['period_start'];
        }
        if (array_key_exists('period_end', $existing)) {
            $impact['period_end'] = $existing['period_end'];
        }
        if (! empty($existing['published_at'])) {
            $impact['published_at'] = $existing['published_at'];
        }
        if (! empty($existing['created_at'])) {
            $impact['created_at'] = $existing['created_at'];
        }
        // Keep the report write-up from admin when present; otherwise use programme narrative.
        if (! empty($existing['summary'])) {
            $impact['summary'] = $existing['summary'];
        }

        // Preserve name listings and media — never wipe Excel / manual participant rows.
        $impact['participants_registered'] = is_array($existing['participants_registered'] ?? null)
            ? $existing['participants_registered']
            : [];
        $impact['participants_selected'] = is_array($existing['participants_selected'] ?? null)
            ? $existing['participants_selected']
            : [];
        $impact['participants_enrolled'] = is_array($existing['participants_enrolled'] ?? null)
            ? $existing['participants_enrolled']
            : [];
        $impact['google_doc_links'] = is_array($existing['google_doc_links'] ?? null)
            ? $existing['google_doc_links']
            : [];
        $impact['image_links'] = is_array($existing['image_links'] ?? null)
            ? $existing['image_links']
            : [];

        return $impact;
    }

    /**
     * Full programme KPI set for a dashboard section (replaces live DB tiles).
     *
     * @return list<array{key:string,label:string,value:float|int,unit:string}>
     */
    public static function sectionStats(?string $sectionKey): array
    {
        $core = self::glanceStats();

        $extra = match ($sectionKey) {
            'platform_overview', 'learners', 'demographics', 'geography' => [
                ['key' => 'female_selected_pct', 'label' => 'Female (selected)', 'value' => 61, 'unit' => 'percentage'],
                ['key' => 'pwds_enrolled', 'label' => 'PWDs enrolled', 'value' => 161, 'unit' => 'count'],
                ['key' => 'idp_participants', 'label' => 'IDP / refugee participants', 'value' => 52, 'unit' => 'count'],
                ['key' => 'states_covered', 'label' => 'States covered', 'value' => 7, 'unit' => 'count'],
                ['key' => 'learning_groups', 'label' => 'Learning groups', 'value' => 319, 'unit' => 'count'],
            ],
            'courses', 'course_performance', 'engagement' => [
                ['key' => 'learning_groups', 'label' => 'Learning groups', 'value' => 319, 'unit' => 'count'],
                ['key' => 'active_rate', 'label' => 'Active rate (LMS)', 'value' => 94.4, 'unit' => 'percentage'],
                ['key' => 'states_covered', 'label' => 'States covered', 'value' => 7, 'unit' => 'count'],
            ],
            'enrollments' => [
                ['key' => 'active_rate', 'label' => 'Active rate (LMS)', 'value' => 94.4, 'unit' => 'percentage'],
                ['key' => 'learning_groups', 'label' => 'Learning groups', 'value' => 319, 'unit' => 'count'],
                ['key' => 'female_selected_pct', 'label' => 'Female (selected)', 'value' => 61, 'unit' => 'percentage'],
            ],
            'apprenticeships' => [
                ['key' => 'semi_finals', 'label' => 'Semi-final teams', 'value' => 82, 'unit' => 'count'],
                ['key' => 'finalist_teams', 'label' => 'Finalist teams', 'value' => 54, 'unit' => 'count'],
                ['key' => 'dignity_sessions', 'label' => 'Dignity in Labour sessions', 'value' => 15, 'unit' => 'count'],
                ['key' => 'states_covered', 'label' => 'States covered', 'value' => 7, 'unit' => 'count'],
                ['key' => 'avg_grant', 'label' => 'Avg grant / team (NGN)', 'value' => 184000, 'unit' => 'ngn'],
            ],
            'certificates' => [
                ['key' => 'modules_delivered', 'label' => 'Modules delivered', 'value' => 11, 'unit' => 'count'],
                ['key' => 'grant_winners', 'label' => 'Seed grant winners', 'value' => 25, 'unit' => 'count'],
                ['key' => 'active_learners', 'label' => 'Active learners', 'value' => 2539, 'unit' => 'count'],
            ],
            default => [
                ['key' => 'states_covered', 'label' => 'States covered', 'value' => 7, 'unit' => 'count'],
                ['key' => 'female_selected_pct', 'label' => 'Female (selected)', 'value' => 61, 'unit' => 'percentage'],
            ],
        };

        // Deduplicate by key (glance first, then section extras).
        $byKey = [];
        foreach (array_merge($core, $extra) as $stat) {
            $byKey[$stat['key']] = $stat;
        }

        return array_values($byKey);
    }

    /**
     * Replace section KPI tiles with programme figures; keep catalogs / name lists.
     * Injects gender/location filters, state & grant breakdowns, and marks programme_impact.
     *
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    public static function applyFigures(array $section, ?string $sectionKey = null): array
    {
        $section['programme_impact'] = true;
        $section['stats'] = self::sectionStats($sectionKey);
        $section['highlight_stats'] = array_slice(self::glanceStats(), 0, 6);
        $section['gender_filters'] = self::genderFilters();
        $section['location_filters'] = self::locationFilters();

        $breakdowns = self::programmeBreakdowns();

        $preferred = match ($sectionKey) {
            'demographics' => [
                'Gender at registration',
                'Gender among selected (61% female)',
                'Female registrants by state',
                'PWD at registration by state',
                'IDP participants by state',
                'Registrations by state',
            ],
            'geography' => [
                'Registrations by state',
                'Active learners by state',
                'Demo hub visits by state',
                'Seed grant winners by state',
                'Seed grants disbursed per state (NGN)',
                'Female registrants by state',
            ],
            'learners' => [
                'Participant funnel',
                'Gender among selected (61% female)',
                'Active learners by state',
                'Registrations by state',
                'Female registrants by state',
            ],
            'courses', 'course_performance', 'engagement' => [
                'Participant funnel',
                'Active learners by state',
                'Competition funnel',
                'Winning topics (focus areas)',
            ],
            'enrollments' => [
                'Participant funnel',
                'Active learners by state',
                'Gender among selected (61% female)',
                'Registrations by state',
            ],
            'apprenticeships' => [
                'Competition funnel',
                'Winning topics (focus areas)',
                'Seed grant winners by state',
                'Seed grants disbursed per state (NGN)',
                'Demo hub visits by state',
            ],
            'certificates' => [
                'Participant funnel',
                'Winning topics (focus areas)',
                'Seed grant winners by state',
            ],
            default => [
                'Participant funnel',
                'Competition funnel',
                'Gender at registration',
                'Gender among selected (61% female)',
                'Registrations by state',
                'Active learners by state',
                'Demo hub visits by state',
                'Winning topics (focus areas)',
                'Seed grants disbursed per state (NGN)',
                'Seed grant winners by state',
            ],
        };

        $section['breakdowns'] = collect($breakdowns)
            ->filter(fn ($b) => in_array($b['title'], $preferred, true))
            ->values()
            ->all();

        // Programme funnel trend for chart sections.
        $section['trend'] = [
            'title' => 'Programme funnel',
            'unit' => 'count',
            'points' => [
                ['label' => 'Applications', 'value' => 6664],
                ['label' => 'Selected', 'value' => 3591],
                ['label' => 'LMS enrolled', 'value' => 2690],
                ['label' => 'Active', 'value' => 2539],
                ['label' => 'Pitch entries', 'value' => 155],
                ['label' => 'Winners', 'value' => 25],
            ],
        ];

        return $section;
    }
}
