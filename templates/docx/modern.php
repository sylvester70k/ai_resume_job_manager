<?php
// Modern DOCX Template Configuration
return [
    'name' => 'Modern',
    'description' => 'A modern, clean resume template with accent colors',
    'settings' => [
        'margins' => [
            'left' => 1440,    // 1 inch
            'right' => 1440,
            'top' => 1440,
            'bottom' => 1440
        ],
        'font' => [
            'family' => 'Calibri',
            'size' => 11,
            'header_size' => 16,
            'subheader_size' => 14,
            'section_size' => 12
        ],
        'styles' => [
            'header' => [
                'bold' => true,
                'size' => 16,
                'color' => '2C3E50',
                'spacing' => 240,  // 12pt
                'underline' => 'single'
            ],
            'subheader' => [
                'bold' => true,
                'size' => 14,
                'color' => '3498DB',
                'spacing' => 120   // 6pt
            ],
            'normal' => [
                'size' => 11,
                'color' => '2C3E50',
                'spacing' => 120   // 6pt
            ],
            'date' => [
                'bold' => true,
                'size' => 11,
                'color' => '3498DB',
                'italic' => true
            ],
            'company' => [
                'bold' => true,
                'size' => 11,
                'color' => '2C3E50'
            ],
            'skill' => [
                'size' => 11,
                'color' => '2C3E50',
                'background' => 'ECF0F1'
            ]
        ],
        'sections' => [
            'header' => [
                'spacing' => 360,  // 18pt
                'border_bottom' => true,
                'border_color' => '3498DB',
                'border_width' => 2
            ],
            'profile' => [
                'spacing' => 240,  // 12pt
                'indent' => 720,   // 0.5 inch
                'line_spacing' => 1.5
            ],
            'experience' => [
                'spacing' => 240,  // 12pt
                'bullet_style' => 'circle',
                'indent' => 720    // 0.5 inch
            ],
            'education' => [
                'spacing' => 240,  // 12pt
                'bullet_style' => 'circle',
                'indent' => 720    // 0.5 inch
            ],
            'skills' => [
                'spacing' => 240,  // 12pt
                'columns' => 2,
                'column_spacing' => 720  // 0.5 inch
            ]
        ]
    ]
]; 