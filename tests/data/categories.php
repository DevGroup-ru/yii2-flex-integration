<?php
$faker = Faker\Factory::create();
return [
    [
        'name' => 'First category',
    ],
    [
        'name' => $faker->name(),
    ],
    [
        'name' => $faker->name(),
    ],
];
