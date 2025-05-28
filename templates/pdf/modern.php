<?php
// Modern PDF Template Configuration
return [
    'name' => 'Modern',
    'description' => 'A modern, clean resume template with accent colors',
    'settings' => [
        'margins' => [
            'left' => 20,
            'right' => 20,
            'top' => 20,
            'bottom' => 20
        ],
        'font' => [
            'family' => 'helvetica',
            'size' => 11,
            'header_size' => 18,
            'subheader_size' => 14,
            'section_size' => 13
        ],
        'colors' => [
            'primary' => '#2C3E50',    // Dark blue-gray
            'secondary' => '#7F8C8D',  // Medium gray
            'accent' => '#3498DB',     // Bright blue
            'text' => '#2C3E50',       // Dark blue-gray
            'light' => '#ECF0F1'       // Light gray
        ],
        'sections' => [
            'header' => [
                'margin_bottom' => 25,
                'background' => '#3498DB',
                'text_color' => '#FFFFFF'
            ],
            'profile' => [
                'margin_bottom' => 20,
                'border_left' => true,
                'border_color' => '#3498DB',
                'padding_left' => 10
            ],
            'experience' => [
                'margin_bottom' => 20,
                'bullet_style' => 'circle',
                'date_color' => '#3498DB'
            ],
            'education' => [
                'margin_bottom' => 20,
                'bullet_style' => 'circle',
                'date_color' => '#3498DB'
            ],
            'skills' => [
                'margin_bottom' => 20,
                'columns' => 2,
                'skill_background' => '#ECF0F1',
                'skill_text' => '#2C3E50'
            ]
        ]
    ]
]; 