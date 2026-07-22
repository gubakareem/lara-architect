<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * How one node depends on another. RegexExtractor emits a subset today;
 * AstExtractor (v2) can fill the rest without changing consumers.
 */
enum EdgeType: string
{
    case Import = 'import';
    case Extends = 'extends';
    case Implements = 'implements';
    case UsesTrait = 'uses-trait';
    case Attribute = 'attribute';
    case StaticCall = 'static-call';
    case Instantiates = 'new';
    case FunctionCall = 'function-call';
    case MethodCall = 'method-call';
}
