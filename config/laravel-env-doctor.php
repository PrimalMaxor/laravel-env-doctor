<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Environment File Paths
    |--------------------------------------------------------------------------
    |
    | These are the default paths that the package will use when comparing
    | environment files. You can override these in your .env file or
    | when calling the command.
    |
    */
    'defaults' => [
        'example_file' => '.env.example',
        'env_file' => '.env',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Patterns to Include
    |--------------------------------------------------------------------------
    |
    | When using the --all option, these patterns will be used to find
    | environment files to compare.
    |
    */
    'file_patterns' => [
        '*.env',
        '*.env.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Directories to Exclude
    |--------------------------------------------------------------------------
    |
    | These directories will be excluded when searching for environment files
    | to compare.
    |
    */
    'exclude_directories' => [
        'vendor',
        'node_modules',
        '.git',
        'storage',
        'bootstrap/cache',
    ],
]; 