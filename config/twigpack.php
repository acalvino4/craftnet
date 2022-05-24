<?php

return [
    '*' => [
        'useDevServer' => false,
        'manifest' => [
            'legacy' => 'manifest.json',
            'modern' => 'manifest.json',
        ],
        'server' => [
            'manifestPath' => getenv('TWIGPACK_SERVER_MANIFEST_PATH'),
            'publicPath' => getenv('TWIGPACK_SERVER_PUBLIC_PATH'),
        ],
        'devServer' => [
            'manifestPath' => getenv('TWIGPACK_DEV_SERVER_MANIFEST_PATH'),
            'publicPath' => getenv('TWIGPACK_DEV_SERVER_PUBLIC_PATH'),
        ],
    ],
    'dev' => [
        'useDevServer' => true,
    ],
];
