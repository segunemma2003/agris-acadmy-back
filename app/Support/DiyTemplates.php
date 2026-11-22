<?php

namespace App\Support;

/**
 * Ready-made DIY practicals tutors can start from and change. Each matches a
 * VR Studio simulation, so learners practise on the simulation first and then
 * do the real thing, recording what they observe.
 */
class DiyTemplates
{
    public static function all(): array
    {
        return [
            'brood_chicks' => [
                'name' => 'Brood day-old chicks for a week',
                'title' => 'Brood day-old chicks for a week',
                'description' => 'Keep 10–20 day-old chicks warm, fed and watered for 7 days and record how they do every morning.',
                'materials_needed' => ['10–20 day-old chicks', 'A brooder box or corner with a draught guard', 'Heat source (bulb, charcoal pot or gas brooder)', 'Thermometer', 'Chick feeder and drinker', 'Chick starter feed', 'Wood shavings'],
                'difficulty_level' => 'beginner',
                'estimated_time_minutes' => 30,
                'entries_required' => 7,
                'entry_label' => 'Day',
                'steps' => [
                    ['title' => 'Prepare the brooder the day before', 'body' => 'Spread 5–8 cm of dry wood shavings, put up a draught guard and switch on the heat so the brooder is warm before the chicks arrive.'],
                    ['title' => 'Check the temperature at chick height', 'body' => 'Hang the thermometer at chick height under the heat. Aim for about 32–33 °C in the first week.'],
                    ['title' => 'Put out feed and water', 'body' => 'Fill the drinker with clean, cool water and the feeder with chick starter. Dip a few beaks in the water so the chicks find it.'],
                    ['title' => 'Release the chicks and watch them', 'body' => 'Huddled under the heat = cold; pressed against the walls = too hot; spread evenly = just right. Adjust the heat.'],
                    ['title' => 'Every morning: check, clean and record', 'body' => 'Refill feed and water, remove wet litter, count the chicks and fill in today\'s observation.'],
                ],
                'observation_fields' => [
                    ['label' => 'Temperature under the heat', 'type' => 'number', 'unit' => '°C', 'required' => true],
                    ['label' => 'How are the chicks spread?', 'type' => 'choice', 'options' => ['Huddled under the heat', 'Spread evenly', 'Against the walls, away from the heat'], 'required' => true],
                    ['label' => 'Chicks alive', 'type' => 'number', 'unit' => 'chicks', 'required' => true],
                    ['label' => 'Feed and water refilled', 'type' => 'check'],
                    ['label' => 'Photo of the chicks', 'type' => 'photo'],
                    ['label' => 'What did you notice?', 'type' => 'text'],
                ],
            ],
            'pond_water' => [
                'name' => 'Test your fish pond water for 5 days',
                'title' => 'Test your fish pond water for 5 days',
                'description' => 'Measure pH, temperature and water colour at the same time each day, and note how the fish behave.',
                'materials_needed' => ['pH test strips or a pH meter', 'Thermometer', 'A clear glass or Secchi disk', 'Notebook'],
                'difficulty_level' => 'beginner',
                'estimated_time_minutes' => 20,
                'entries_required' => 5,
                'entry_label' => 'Day',
                'steps' => [
                    ['title' => 'Choose a fixed time', 'body' => 'Test at the same time each day (early morning is best — oxygen is lowest then).'],
                    ['title' => 'Measure the pH', 'body' => 'Dip a strip or the meter about 20 cm below the surface and read it after the time on the packet.'],
                    ['title' => 'Measure the temperature', 'body' => 'Hold the thermometer in the water for a minute before reading.'],
                    ['title' => 'Watch the fish', 'body' => 'Are they gasping at the surface, eating well, or hiding at the bottom?'],
                ],
                'observation_fields' => [
                    ['label' => 'pH', 'type' => 'number', 'unit' => 'pH', 'required' => true],
                    ['label' => 'Water temperature', 'type' => 'number', 'unit' => '°C', 'required' => true],
                    ['label' => 'Water colour', 'type' => 'choice', 'options' => ['Clear', 'Light green', 'Green', 'Thick dark green', 'Brown / muddy']],
                    ['label' => 'Fish gasping at the surface', 'type' => 'check'],
                    ['label' => 'Photo of the pond', 'type' => 'photo'],
                    ['label' => 'Notes', 'type' => 'text'],
                ],
            ],
            'seedling_nursery' => [
                'name' => 'Raise seedlings in a nursery tray',
                'title' => 'Raise seedlings in a nursery tray',
                'description' => 'Sow vegetable seeds in a tray, water and shade them, and record germination and growth for 2 weeks.',
                'materials_needed' => ['Seedling tray or cut plastic bottles', 'Seeds (pepper, tomato or onion)', 'Topsoil and compost', 'Watering can', 'Shade (net or palm fronds)'],
                'difficulty_level' => 'beginner',
                'estimated_time_minutes' => 15,
                'entries_required' => 7,
                'entry_label' => 'Check',
                'steps' => [
                    ['title' => 'Mix the soil', 'body' => 'Mix 2 parts topsoil with 1 part well-rotted compost and fill the tray.'],
                    ['title' => 'Sow the seeds', 'body' => 'Sow 1–2 seeds per cell, about 1 cm deep, and press the soil gently.'],
                    ['title' => 'Water and shade', 'body' => 'Water lightly morning and evening and keep the tray in light shade.'],
                    ['title' => 'Record every other day', 'body' => 'Count the seeds that have come up and measure the tallest seedling.'],
                ],
                'observation_fields' => [
                    ['label' => 'Seedlings up', 'type' => 'number', 'unit' => 'seedlings', 'required' => true],
                    ['label' => 'Tallest seedling', 'type' => 'number', 'unit' => 'cm'],
                    ['label' => 'Leaves look healthy (green, not yellow)', 'type' => 'check'],
                    ['label' => 'Photo of the tray', 'type' => 'photo'],
                    ['label' => 'Notes', 'type' => 'text'],
                ],
            ],
        ];
    }

    public static function options(): array
    {
        return array_map(fn ($t) => $t['name'], self::all());
    }
}
