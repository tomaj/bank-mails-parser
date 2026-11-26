<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withSkip([
        // "@return $this" documents the fluent contract of the interfaces.
        RemoveUselessReturnTagRector::class,
        // Promoting $money and $transactionDate would move them behind the other public
        // properties, which changes get_object_vars(), json_encode() and var_export() output.
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/MailContent.php',
        ],
        // The OpenPGP library's exception has the PGP passphrase in its trace, so it is deliberately not kept as previous.
        ThrowWithPreviousExceptionRector::class => [
            __DIR__ . '/src/Parser/TatraBanka/TatraBankaMailDecryptor.php',
        ],
    ]);
