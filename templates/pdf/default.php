<?php
// Default PDF Template Configuration
return [
    'name' => 'Default',
    'description' => 'A clean and professional resume template',
    'settings' => [
        'margins' => [
            'left' => 15,
            'right' => 15,
            'top' => 15,
            'bottom' => 15
        ],
        'font' => [
            'family' => 'helvetica',
            'size' => 12,
            'header_size' => 16,
            'subheader_size' => 14
        ],
        'colors' => [
            'primary' => '#000000',
            'secondary' => '#666666',
            'accent' => '#2271b1'
        ],
        'sections' => [
            'header' => [
                'margin_bottom' => 20
            ],
            'profile' => [
                'margin_bottom' => 15
            ],
            'experience' => [
                'margin_bottom' => 15
            ],
            'education' => [
                'margin_bottom' => 15
            ],
            'skills' => [
                'margin_bottom' => 15
            ]
        ]
    ]
]; 