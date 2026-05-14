<?php

declare(strict_types=1);

namespace App\Etui\Profiles;

/**
 * Vanilla ET:Main profile — the 8 stock function-like macros every .menu
 * file in ETMain relies on, hardcoded with simplified bodies.
 *
 * Phase 1 simplifications versus the original menumacros.h:
 *
 *  - $evalfloat/$evalint wrappers around coordinate parameters are dropped.
 *    Body tokens land in the AST as raw arithmetic (NUMBER, +, -, NUMBER);
 *    the Phase 4 renderer / a future evaluator folds them with the
 *    profile's known constants (WINDOW_WIDTH = 640, etc.). With $eval
 *    inline here, the MacroExpander would emit `$evalfloat(10)` tokens
 *    that no downstream stage knows how to fold.
 *
 *  - C-preprocessor `##` token-paste IS preserved — the MacroExpander
 *    handles it post-substitution so each call still gets a unique item
 *    name (`"bttn"##BUTTON_TEXT` → `"bttnQuit"` for caller-text "Quit").
 *
 *  - Game engine constants like WINDOW_STYLE_FILLED, ITEM_TYPE_BUTTON,
 *    UI_FONT_COURBD_30 stay as IDENT tokens in the AST. The renderer
 *    resolves them against its enum table at draw time.
 *
 * Phase 3 plan: `php artisan etui:dump-macros <mod.pk3>` will generate
 * full-fidelity profiles (with $eval, full styling) for user-uploaded
 * mod headers.
 */
class EtMainProfile extends ModProfile
{
    public function slug(): string
    {
        return 'etmain';
    }

    public function includeSearchPaths(): array
    {
        return ['_includes/'];
    }

    public function flagProperties(): array
    {
        return ['popup', 'decoration', 'autowrapped', 'forceshader', 'wrapped', 'fullscreen'];
    }

    public function macros(): array
    {
        return [
            'WINDOW_FUI' => new MacroDefinition(
                'WINDOW_FUI',
                ['WINDOW_TEXT', 'GRADIENT_START_OFFSET'],
                $this->windowFuiBody(),
            ),
            'WINDOW_INGAME' => new MacroDefinition(
                'WINDOW_INGAME',
                ['WINDOW_TEXT', 'GRADIENT_START_OFFSET'],
                $this->windowIngameBody(),
            ),
            'SUBWINDOW' => new MacroDefinition(
                'SUBWINDOW',
                ['SUBWINDOW_X', 'SUBWINDOW_Y', 'SUBWINDOW_W', 'SUBWINDOW_H', 'SUBWINDOW_TEXT'],
                $this->subwindowBody(),
            ),
            'SUBWINDOWBLACK' => new MacroDefinition(
                'SUBWINDOWBLACK',
                ['SUBWINDOWBLACK_X', 'SUBWINDOWBLACK_Y', 'SUBWINDOWBLACK_W', 'SUBWINDOWBLACK_H', 'SUBWINDOWBLACK_TEXT'],
                $this->subwindowBlackBody(),
            ),
            'BUTTON' => new MacroDefinition(
                'BUTTON',
                ['BUTTON_X', 'BUTTON_Y', 'BUTTON_W', 'BUTTON_H', 'BUTTON_TEXT', 'BUTTON_TEXT_SCALE', 'BUTTON_TEXT_ALIGN_Y', 'BUTTON_ACTION'],
                $this->buttonBody(),
            ),
            'BUTTONEXT' => new MacroDefinition(
                'BUTTONEXT',
                ['BUTTONEXT_X', 'BUTTONEXT_Y', 'BUTTONEXT_W', 'BUTTONEXT_H', 'BUTTONEXT_TEXT', 'BUTTONEXT_TEXT_SCALE', 'BUTTONEXT_TEXT_ALIGN_Y', 'BUTTONEXT_ACTION', 'BUTTONEXT_EXT'],
                $this->buttonExtBody(),
            ),
            'LABEL' => new MacroDefinition(
                'LABEL',
                ['LABEL_X', 'LABEL_Y', 'LABEL_W', 'LABEL_H', 'LABEL_TEXT', 'LABEL_TEXT_SCALE', 'LABEL_TEXT_ALIGN', 'LABEL_TEXT_ALIGN_X', 'LABEL_TEXT_ALIGN_Y'],
                $this->labelBody(),
            ),
            'LABELWHITE' => new MacroDefinition(
                'LABELWHITE',
                ['LABELWHITE_X', 'LABELWHITE_Y', 'LABELWHITE_W', 'LABELWHITE_H', 'LABELWHITE_TEXT', 'LABELWHITE_TEXT_SCALE', 'LABELWHITE_TEXT_ALIGN', 'LABELWHITE_TEXT_ALIGN_X', 'LABELWHITE_TEXT_ALIGN_Y'],
                $this->labelWhiteBody(),
            ),
        ];
    }

    private function windowFuiBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "window"
                group GROUP_NAME
                rect 0 0 WINDOW_WIDTH WINDOW_HEIGHT
                style WINDOW_STYLE_FILLED
                backcolor 0 0 0 .2
                border WINDOW_BORDER_FULL
                bordercolor .5 .5 .5 .5
                visible 1
                decoration
            }
            itemDef {
                name "titlebar"
                group GROUP_NAME
                rect 2 2 GRADIENT_START_OFFSET 24
                style WINDOW_STYLE_FILLED
                backcolor .16 .2 .17 .8
                visible 1
                decoration
            }
            itemDef {
                name "titlebargradient"
                group GROUP_NAME
                rect GRADIENT_START_OFFSET+2 2 WINDOW_WIDTH-GRADIENT_START_OFFSET-4 24
                style WINDOW_STYLE_GRADIENT
                backcolor .16 .2 .17 .8
                visible 1
                decoration
            }
            itemDef {
                name "windowtitle"
                group GROUP_NAME
                rect 2 2 WINDOW_WIDTH-4 24
                text WINDOW_TEXT
                textfont UI_FONT_ARIBLK_27
                textscale .4
                textalignx 3
                textaligny 20
                forecolor .6 .6 .6 1
                visible 1
                decoration
            }
            BODY;
    }

    private function windowIngameBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "window"
                group GROUP_NAME
                rect 0 0 WINDOW_WIDTH WINDOW_HEIGHT
                style WINDOW_STYLE_FILLED
                backcolor 0 0 0 .6
                border WINDOW_BORDER_FULL
                bordercolor .5 .5 .5 .5
                visible 1
                decoration
            }
            itemDef {
                name "titlebar"
                group GROUP_NAME
                rect 2 2 GRADIENT_START_OFFSET 24
                style WINDOW_STYLE_FILLED
                backcolor .16 .2 .17 .8
                visible 1
                decoration
            }
            itemDef {
                name "titlebargradient"
                group GROUP_NAME
                rect GRADIENT_START_OFFSET+2 2 WINDOW_WIDTH-GRADIENT_START_OFFSET-4 24
                style WINDOW_STYLE_GRADIENT
                backcolor .16 .2 .17 .8
                visible 1
                decoration
            }
            itemDef {
                name "windowtitle"
                group GROUP_NAME
                rect 2 2 WINDOW_WIDTH-4 24
                text WINDOW_TEXT
                textfont UI_FONT_ARIBLK_27
                textscale .4
                textalignx 3
                textaligny 20
                forecolor .6 .6 .6 1
                visible 1
                decoration
            }
            BODY;
    }

    private function subwindowBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "subwindow"##SUBWINDOW_TEXT
                group GROUP_NAME
                rect SUBWINDOW_X SUBWINDOW_Y SUBWINDOW_W SUBWINDOW_H
                style WINDOW_STYLE_FILLED
                backcolor 0 0 0 .2
                border WINDOW_BORDER_FULL
                bordercolor .5 .5 .5 .5
                visible 1
                decoration
            }
            itemDef {
                name "subwindowtitle"##SUBWINDOW_TEXT
                group GROUP_NAME
                rect SUBWINDOW_X+2 SUBWINDOW_Y+2 SUBWINDOW_W-4 12
                text SUBWINDOW_TEXT
                textfont UI_FONT_ARIBLK_16
                textscale .19
                textalignx 3
                textaligny 10
                style WINDOW_STYLE_FILLED
                backcolor .16 .2 .17 .8
                forecolor .6 .6 .6 1
                visible 1
                decoration
            }
            BODY;
    }

    private function subwindowBlackBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "subwindowblack"##SUBWINDOWBLACK_TEXT
                group GROUP_NAME
                rect SUBWINDOWBLACK_X SUBWINDOWBLACK_Y SUBWINDOWBLACK_W SUBWINDOWBLACK_H
                style WINDOW_STYLE_FILLED
                backcolor 0 0 0 .85
                border WINDOW_BORDER_FULL
                bordercolor .5 .5 .5 .5
                visible 1
                decoration
            }
            itemDef {
                name "subwindowblacktitle"##SUBWINDOWBLACK_TEXT
                group GROUP_NAME
                rect SUBWINDOWBLACK_X+2 SUBWINDOWBLACK_Y+2 SUBWINDOWBLACK_W-4 12
                text SUBWINDOWBLACK_TEXT
                textfont UI_FONT_ARIBLK_16
                textscale .19
                textalignx 3
                textaligny 10
                style WINDOW_STYLE_FILLED
                backcolor .16 .2 .17 .8
                forecolor .6 .6 .6 1
                visible 1
                decoration
            }
            BODY;
    }

    private function buttonBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "bttn"##BUTTON_TEXT
                group GROUP_NAME
                rect BUTTON_X BUTTON_Y BUTTON_W BUTTON_H
                type ITEM_TYPE_BUTTON
                text BUTTON_TEXT
                textfont UI_FONT_COURBD_30
                textscale BUTTON_TEXT_SCALE
                textalign ITEM_ALIGN_CENTER
                textaligny BUTTON_TEXT_ALIGN_Y
                style WINDOW_STYLE_FILLED
                backcolor .3 .3 .3 .4
                forecolor .6 .6 .6 1
                border WINDOW_BORDER_FULL
                bordercolor .1 .1 .1 .5
                visible 1
                mouseEnter {
                    setitemcolor "bttn"##BUTTON_TEXT forecolor .9 .9 .9 1 ;
                    setitemcolor "bttn"##BUTTON_TEXT backcolor .5 .5 .5 .4
                }
                mouseExit {
                    setitemcolor "bttn"##BUTTON_TEXT forecolor .6 .6 .6 1 ;
                    setitemcolor "bttn"##BUTTON_TEXT backcolor .3 .3 .3 .4
                }
                action {
                    setitemcolor "bttn"##BUTTON_TEXT forecolor .6 .6 .6 1 ;
                    setitemcolor "bttn"##BUTTON_TEXT backcolor .3 .3 .3 .4 ;
                    play "sound/menu/select.wav" ;
                    BUTTON_ACTION
                }
            }
            BODY;
    }

    private function buttonExtBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "bttnext"##BUTTONEXT_TEXT
                group GROUP_NAME
                rect BUTTONEXT_X BUTTONEXT_Y BUTTONEXT_W BUTTONEXT_H
                type ITEM_TYPE_BUTTON
                text BUTTONEXT_TEXT
                textfont UI_FONT_COURBD_30
                textscale BUTTONEXT_TEXT_SCALE
                textalign ITEM_ALIGN_CENTER
                textaligny BUTTONEXT_TEXT_ALIGN_Y
                style WINDOW_STYLE_FILLED
                backcolor .3 .3 .3 .4
                forecolor .6 .6 .6 1
                border WINDOW_BORDER_FULL
                bordercolor .1 .1 .1 .5
                visible 1
                mouseEnter {
                    setitemcolor "bttnext"##BUTTONEXT_TEXT forecolor .9 .9 .9 1 ;
                    setitemcolor "bttnext"##BUTTONEXT_TEXT backcolor .5 .5 .5 .4
                }
                mouseExit {
                    setitemcolor "bttnext"##BUTTONEXT_TEXT forecolor .6 .6 .6 1 ;
                    setitemcolor "bttnext"##BUTTONEXT_TEXT backcolor .3 .3 .3 .4
                }
                action {
                    setitemcolor "bttnext"##BUTTONEXT_TEXT forecolor .6 .6 .6 1 ;
                    setitemcolor "bttnext"##BUTTONEXT_TEXT backcolor .3 .3 .3 .4 ;
                    play "sound/menu/select.wav" ;
                    BUTTONEXT_ACTION
                }
                BUTTONEXT_EXT
            }
            BODY;
    }

    private function labelBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "label"##LABEL_TEXT
                group GROUP_NAME
                rect LABEL_X LABEL_Y LABEL_W LABEL_H
                type ITEM_TYPE_TEXT
                text LABEL_TEXT
                textfont UI_FONT_COURBD_21
                textstyle ITEM_TEXTSTYLE_SHADOWED
                textscale LABEL_TEXT_SCALE
                textalign LABEL_TEXT_ALIGN
                textalignx LABEL_TEXT_ALIGN_X
                textaligny LABEL_TEXT_ALIGN_Y
                forecolor .6 .6 .6 1
                visible 1
                decoration
                autowrapped
            }
            BODY;
    }

    private function labelWhiteBody(): string
    {
        return <<<'BODY'
            itemDef {
                name "labelwhite"##LABELWHITE_TEXT
                group GROUP_NAME
                rect LABELWHITE_X LABELWHITE_Y LABELWHITE_W LABELWHITE_H
                type ITEM_TYPE_TEXT
                text LABELWHITE_TEXT
                textfont UI_FONT_COURBD_21
                textstyle ITEM_TEXTSTYLE_SHADOWED
                textscale LABELWHITE_TEXT_SCALE
                textalign LABELWHITE_TEXT_ALIGN
                textalignx LABELWHITE_TEXT_ALIGN_X
                textaligny LABELWHITE_TEXT_ALIGN_Y
                forecolor 1 1 1 1
                visible 1
                decoration
                autowrapped
            }
            BODY;
    }
}
