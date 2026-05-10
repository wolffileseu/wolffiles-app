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
        // PM profile: very restrictive. Used for private messages.
        // No images (attachments handled separately), no tables, no divs/spans,
        // no inline CSS. Links forced to noopener+noreferrer+target=_blank.
        'pm' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'p,br,strong,em,b,i,u,code,pre,blockquote,ul,ol,li,a[href|title|rel|target]',
            'CSS.AllowedProperties' => '',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.Linkify' => false,
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
            'URI.DisableExternalResources' => true,
            'Attr.AllowedFrameTargets' => ['_blank'],
            'HTML.TargetBlank' => true,
            'HTML.Nofollow' => true,
            'HTML.TargetNoreferrer' => true,
            'HTML.TargetNoopener' => true,
        ],
    ],
];
