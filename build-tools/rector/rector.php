<?php declare(strict_types=1);

/**
 * Rector
 *
 * https://getrector.com/documentation/
 */

use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;

$workspace_root = dirname(__DIR__, 2);

return RectorConfig::configure()
    ->withPaths([
        $workspace_root . '/bin',
        $workspace_root . '/examples',
        $workspace_root . '/src',
        $workspace_root . '/tests'
    ])
    ->withSkipPath($workspace_root . '/vendor')
    ->withParallel(120, 8, 10)
    ->withCache($workspace_root . '/build/cache/rector')
    ->withSets(
        [
            # === Current Refactoring Run ===
            SetList::STRICT_BOOLEANS,
            SetList::DEAD_CODE,
            SetList::PRIVATIZATION,
            SetList::EARLY_RETURN,
            SetList::TYPE_DECLARATION,
            SetList::CODE_QUALITY,
            #SetList::CODING_STYLE

            # === Passed Refactoring Runs ===

            # Level Up To X
            # -------------------------------
            LevelSetList::UP_TO_PHP_84,

            # PHP
            # -------------------------------
            #SetList::PHP_84
            #SetList::PHP_83
            #SetList::PHP_82
            #SetList::PHP_81
            #SetList::PHP_80
            #SetList::PHP_74
            #SetList::PHP_73
            #SetList::PHP_72
            #SetList::PHP_71
            #SetList::PHP_70
            #SetList::PHP_56
            #SetList::PHP_55
            #SetList::PHP_54

            # Additional Sets
            # -------------------------------
            #SetList::STRICT_BOOLEANS,
            #SetList::DEAD_CODE,
            #SetList::PRIVATIZATION,
            #SetList::EARLY_RETURN,
            #SetList::TYPE_DECLARATION
        ]
    )
    ->withSkip([
        SimplifyUselessVariableRector::class,
        RemoveUnusedVariableAssignRector::class => [
            $workspace_root . '/src/CSQuery/ServerProtocols/CounterStrike16.php',
        ]
    ])
    ->withPHPStanConfigs([dirname(__DIR__) . '/phpstan/phpstan.neon.dist'])
    ;
