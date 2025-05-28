<?php
// Professional DOCX Template Configuration
return [
    'name' => 'Professional',
    'description' => 'A traditional, business-focused resume template',
    'settings' => [
        'margins' => [
            'left' => 1440,    // 1 inch
            'right' => 1440,
            'top' => 1440,
            'bottom' => 1440
        ],
        'font' => [
            'family' => 'Times New Roman',
            'size' => 12,
            'header_size' => 14,
            'subheader_size' => 13,
            'section_size' => 12
        ],
        'styles' => [
            'header' => [
                'bold' => true,
                'size' => 14,
                'color' => '000000',
                'spacing' => 240,  // 12pt
                'border_bottom' => true,
                'border_color' => '1A5276',
                'border_width' => 2
            ],
            'subheader' => [
                'bold' => true,
                'size' => 13,
                'color' => '1A5276',
                'spacing' => 120   // 6pt
            ],
            'normal' => [
                'size' => 12,
                'color' => '000000',
                'spacing' => 120   // 6pt
            ],
            'date' => [
                'bold' => true,
                'size' => 12,
                'color' => '000000'
            ],
            'company' => [
                'italic' => true,
                'size' => 12,
                'color' => '000000'
            ],
            'skill' => [
                'size' => 12,
                'color' => '000000'
            ]
        ],
        'sections' => [
            'header' => [
                'spacing' => 360,  // 18pt
                'alignment' => 'center'
            ],
            'profile' => [
                'spacing' => 240,  // 12pt
                'indent' => 720,   // 0.5 inch
                'line_spacing' => 1.5
            ],
            'experience' => [
                'spacing' => 240,  // 12pt
                'bullet_style' => 'square',
                'indent' => 720    // 0.5 inch
            ],
            'education' => [
                'spacing' => 240,  // 12pt
                'bullet_style' => 'square',
                'indent' => 720    // 0.5 inch
            ],
            'skills' => [
                'spacing' => 240,  // 12pt
                'columns' => 3,
                'column_spacing' => 720,  // 0.5 inch
                'skill_separator' => '•'
            ]
        ]
    ]
]; 