<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->exclude('test')
    ->exclude('tests')
    ->in(__DIR__);

// https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/blob/master/doc/ruleSets
return new PhpCsFixer\Config()
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'array_syntax' => [ 'syntax' => 'short' ],
        'strict_comparison' => true,
        'strict_param' => true,
        'no_trailing_whitespace' => false,
        'no_trailing_whitespace_in_comment' => false,
        'braces' => false,
        'single_blank_line_at_eof' => false,
        'blank_line_after_namespace' => false,
    ])
    ->setFinder($finder);
