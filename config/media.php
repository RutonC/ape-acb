<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Biblioteca de média
    |--------------------------------------------------------------------------
    |
    | Os ficheiros carregados no painel ficam no disco abaixo (pasta "media")
    | e os registos na tabela "curator", herdada da versão anterior do painel.
    | As versões redimensionadas são servidas em /{glide_path}/... com URLs
    | assinados pelo token.
    |
    */

    'disk' => env('MEDIA_DISK', 'public'),

    'directory' => 'media',

    'glide_path' => 'img',

    'glide_token' => env('MEDIA_GLIDE_TOKEN', env('CURATOR_GLIDE_TOKEN')),

    // Extensões que o Glide sabe redimensionar; as restantes são servidas tal como estão.
    'resizable' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'bmp'],

];
