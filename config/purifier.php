<?php

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,

    'settings' => [
        // Custom HTML5 elements (Purifier kennt diese nicht von Haus aus)
        // Format: [name, content_set, content_model, attr_collections, [attributes]]
        'custom_elements' => [
            ['figure',     'Block',  'Flow',    'Common', []],
            ['figcaption', 'Inline', 'Flow',    'Common', []],
            ['mark',       'Inline', 'Inline',  'Common', []],
            ['time',       'Inline', 'Inline',  'Common', ['datetime' => 'Text']],
        ],
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.DefinitionID' => 'wolffiles-default',
            'HTML.DefinitionRev' => 1,
            'HTML.Allowed' => 'h1[id|class],h2[id|class],h3[id|class],h4[id|class],h5[id|class],h6[id|class],p[class],br,b,strong,i,em,u,s,del,ins,mark,a[href|title|target|class|rel],ul[class],ol[class|start],li[class|value],dl[class],dt[class],dd[class],pre[class],code[class],blockquote[class|cite],hr,img[src|alt|width|height|class],figure[class],figcaption[class],table[class],thead,tbody,tfoot,tr[class],th[class|colspan|rowspan|scope],td[class|colspan|rowspan],div[class|id],span[class|style|id],sub,sup,abbr[title],kbd,samp,var,small,cite,q[cite],time[datetime]',
            'Attr.AllowedClasses' => null,
            'Attr.EnableID' => true,
            'Attr.IDPrefix' => '',
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
            'HTML.DefinitionID' => 'wolffiles-pm',
            'HTML.DefinitionRev' => 1,
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
