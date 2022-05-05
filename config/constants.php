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
        'ncrsor' => [
            'file_path' => 'organizations/:uid:/ncrsor'
        ],
        'projects' => [
            'logo_path' => 'organizations/:uid:/project/:project_uuid:/logo',
            'activity_document' => [
                'file_path' => 'organizations/:uid:/project/:project_uuid:/activity_document',
                'upload_image_max_size' => '10000' // 10 mb
            ],
            'ncrsor_request_document' => [
                'file_path' => 'organizations/:uid:/project/:project_uuid:/ncrsor_request_document',
                'upload_image_max_size' => '8000' // 8 mb
            ],
            'inspection' => [
                'document_path' => 'organizations/:uid:/project/inspection'
            ],
            'method_statements' => [
                'file_path' => 'organizations/:uid:/project/method_statements'
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
