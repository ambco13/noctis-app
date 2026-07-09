<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Images vehicules (admin) : disque persistant gratuit, sans carte
        // bancaire requise (contrairement a R2/Backblaze pour un bucket public).
        // Non utilise tant que CLOUDINARY_URL n'est pas defini -- voir
        // VehicleAdminController, qui retombe sur "public" (local) sinon.
        'cloudinary' => [
            'driver' => 'cloudinary',
            'key' => env('CLOUDINARY_KEY'),
            'secret' => env('CLOUDINARY_SECRET'),
            'cloud' => env('CLOUDINARY_CLOUD_NAME'),
            'url' => env('CLOUDINARY_URL'),
            'secure' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Disque des images vehicules (admin)
    |--------------------------------------------------------------------------
    |
    | Distinct du disque "default" (delibere) : le disque par defaut de
    | Laravel sert a d'autres usages internes et ne doit jamais dependre
    | d'identifiants cloud optionnels. "public" (local) tant que Cloudinary
    | n'est pas configure -- fonctionne toujours, juste non persistant sur
    | Render (plan gratuit, disque ephemere).
    |
    */

    'vehicle_images_disk' => env('CLOUDINARY_URL') ? 'cloudinary' : 'public',

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
