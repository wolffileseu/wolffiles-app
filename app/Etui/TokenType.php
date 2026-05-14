<?php

declare(strict_types=1);

namespace App\Etui;

enum TokenType: string
{
    case LBRACE = 'LBRACE';
    case RBRACE = 'RBRACE';
    case LPAREN = 'LPAREN';
    case RPAREN = 'RPAREN';
    case COMMA = 'COMMA';
    case PLUS = 'PLUS';
    case MINUS = 'MINUS';
    case STAR = 'STAR';
    case SLASH = 'SLASH';
    case STRING = 'STRING';
    case NUMBER = 'NUMBER';
    case IDENT = 'IDENT';
    case EOF = 'EOF';
}
