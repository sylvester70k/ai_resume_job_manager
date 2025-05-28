<?php
// Default DOCX Template Configuration
return [
    'name' => 'Default',
    'description' => 'A clean and professional resume template',
    'settings' => [
        'margins' => [
            'left' => 1440, // 1 inch in twips
            'right' => 1440,
            'top' => 1440,
            'bottom' => 1440
        ],
        'font' => [
            'family' => 'Calibri',
            'size' => 11,
            'header_size' => 14,
            'subheader_size' => 12
        ],
        'styles' => [
            'header' => [
                'bold' => true,
                'size' => 14,
                'color' => '000000'
            ],
            'subheader' => [
                'bold' => true,
                'size' => 12,
                'color' => '666666'
            ],
            'normal' => [
                'size' => 11,
                'color' => '000000'
            ]
        ],
        'sections' => [
            'header' => [
                'spacing' => 240 // 12pt in twips
            ],
            'profile' => [
                'spacing' => 240
            ],
            'experience' => [
                'spacing' => 240
            ],
            'education' => [
                'spacing' => 240
            ],
            'skills' => [
                'spacing' => 240
            ]
        ]
    ]
]; 