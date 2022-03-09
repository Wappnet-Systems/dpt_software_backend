<?php
return [
    'default_per_page_limit' => 10,
    'default_orderby' => 'desc',
    'upload_image_types' => 'jpeg,jpg,bmp,png',
    'upload_image_max_size' => '8000', // 8 mb
    'upload_video_max_size' => '10000', // 10 mb
    'users' => [
        'image_path' => 'users/:uid:/profile'
    ],
    'organizations' => [
        'logo_path' => 'organizations/:uid:/logo',
        'projects' => [
            'logo_path' => 'organizations/:uid:/project/:project_uuid:/logo',
            'ifc_drawings' => [
                'file_path' => 'organizations/:uid:/project/:project_uuid:/ifc-drawing',
                'upload_image_max_size' => '10000' // 10 mb
            ]
        ]
    ],
    'format_files' => [
        'material_file' => [
            'path' => 'format-file',
            'name' => 'materials.csv'
        ]
    ]
];
