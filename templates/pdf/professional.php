<?php
// Professional PDF Template Configuration
return [
    'name' => 'Professional',
    'description' => 'A traditional, business-focused resume template',
    'settings' => [
        'margins' => [
            'left' => 25,
            'right' => 25,
            'top' => 25,
            'bottom' => 25
        ],
        'font' => [
            'family' => 'times',
            'size' => 12,
            'header_size' => 16,
            'subheader_size' => 14,
            'section_size' => 13
        ],
        'colors' => [
            'primary' => '#000000',    // Black
            'secondary' => '#333333',  // Dark gray
            'accent' => '#1A5276',     // Navy blue
            'text' => '#000000',       // Black
            'light' => '#F5F5F5'       // Light gray
        ],
        'sections' => [
            'header' => [
                'margin_bottom' => 30,
                'border_bottom' => true,
                'border_color' => '#1A5276',
                'border_width' => 2
            ],
            'profile' => [
                'margin_bottom' => 25,
                'indent' => 20,
                'line_height' => 1.5
            ],
            'experience' => [
                'margin_bottom' => 25,
                'bullet_style' => 'square',
                'date_style' => 'bold',
                'company_style' => 'italic'
            ],
            'education' => [
                'margin_bottom' => 25,
                'bullet_style' => 'square',
                'date_style' => 'bold',
                'institution_style' => 'italic'
            ],
            'skills' => [
                'margin_bottom' => 25,
                'columns' => 3,
                'skill_separator' => '•',
                'skill_spacing' => 10
            ]
        ]
    ]
]; 