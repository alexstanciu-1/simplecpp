<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use resolve_symbols\Symbol_Resolver as Resolver;
use resolve_symbols\Resolution_Set;
use resolve_symbols\Symbol_Resolution;
use resolve_symbols\local_access;
use collect_symbols\symbol_kind;
use compile\Phases;
use compile\Update_Context;

class Local_Resolution_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    public static function rejects(callable $action, string $reason): Throwable
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $reason), $error->getMessage());
            return $error;
        }
        throw new Exception('Expected rejection: ' . $reason);
    }

    public static function project(\compile\Compile_Result $previous): \collect_symbols\Symbol_Store
    {
        $inputs = $previous->inputs;
        $update = new Update_Context();
        $sources = \Step_Test::run(new \read_sources\Source_Discovery($inputs->manifest, $inputs->sources));
        $lexical = Phases::run_tokenization($sources, $inputs->tokens, $update);
        $parsed = Phases::run_parsing($lexical->sources, $lexical->tokens, $inputs->frontends, $update);
        return \Step_Test::run(new \collect_symbols\Declaration_Collector($previous->symbols->current, $lexical->sources, $parsed, false))->current;
    }
}
