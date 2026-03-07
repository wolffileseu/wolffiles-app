<?php

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,

    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h2,h3,h4,p,br,b,strong,i,em,u,a[href|title|target],ul,ol,li,pre,code,blockquote,hr,img[src|alt|width|height|class],table,thead,tbody,tr,th,td,div[class],span[class|style],sub,sup',
            'CSS.AllowedProperties' => 'font-weight,font-style,text-decoration,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
        ],
    ],
];
