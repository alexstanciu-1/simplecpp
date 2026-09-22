<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use compile\Phases;
use compile\Update_Context;
use check_bodies\Body_Set;

class local_body_inputs
{
    public function __construct(
        public readonly \compile\Input_Snapshot $inputs,
        public readonly \collect_symbols\Symbol_Store $symbols,
        public readonly \resolve_symbols\Resolution_Set $names,
        public readonly \resolve_types\Type_Resolution $types,
    )
    {
    }
}

class Body_Test_Stages
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

    /** Return the expected failure so callers can also inspect source anchors. */
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

    /** Prepare real frontend, binding and type stages against a retained baseline. */
    public static function prepare(\compile\Compile_Result $baseline, ?local_body_inputs $previous = null): local_body_inputs
    {
        $inputs = clone ($previous?->inputs ?? $baseline->inputs);
        $old_symbols = $previous?->symbols ?? $baseline->symbols->current;
        $old_names = $previous?->names ?? $baseline->resolutions;
        $old_types = $previous?->types ?? $baseline->types;
        $update = new Update_Context();
        $sources = \Step_Test::run(new \read_sources\Source_Discovery($inputs->manifest, $inputs->sources));
        $lexical = Phases::run_tokenization($sources, $inputs->tokens, $update);
        $inputs->sources = $lexical->sources;
        $inputs->tokens = $lexical->tokens;
        $inputs->frontends = Phases::run_parsing($inputs->sources, $inputs->tokens, $inputs->frontends, $update);
        $symbols = \Step_Test::run(new \collect_symbols\Declaration_Collector($old_symbols, $inputs->sources, $inputs->frontends, false))->current;
        $names = Phases::run_symbols($symbols, $old_names, $update, $old_types->catalog);
        $entry = \Step_Test::run(new \resolve_types\Entry_Resolver($inputs->sources, $symbols, $old_types->catalog));
        $types = \Step_Test::run(new \resolve_types\Type_Resolver($symbols, $old_types->catalog, clone $old_types->types, $old_types, $update->full_rebuild, $entry, $names));
        return new local_body_inputs($inputs, $symbols, $names, $types);
    }

    public static function bodies(local_body_inputs $input, ?Body_Set $previous = null, bool $full = false): Body_Set
    {
        $update = new Update_Context();
        $update->full_rebuild = $full;
        return Phases::run_bodies($input->symbols, $input->names, $input->types, $previous ?? new Body_Set(), $update);
    }
}
