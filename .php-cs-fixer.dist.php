<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__);

// https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/blob/master/doc/ruleSets
return new PhpCsFixer\Config()
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true, // inclut déjà @Symfony:risky
        '@PHP8x5Migration' => true,
        '@PHP8x5Migration:risky' => true,
        '@PHPUnit11x0Migration:risky' => true,
    ])
    ->setFinder($finder);
