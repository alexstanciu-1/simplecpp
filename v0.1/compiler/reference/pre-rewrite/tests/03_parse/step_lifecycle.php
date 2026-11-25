<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

use compile\step_status;
use parse\Frontend_Set;
use parse\Parser;
use parse\File_Parser;
use read_sources\Source_Set;
use tokenize\Token_Set;

class Parser_Test
{
    public static function inputs(array $texts): array
    {
        $sources = new Source_Set();
        $sources->folders = [new \read_sources\source_folder()];
        $buffers = [];
        foreach ($texts as $index => $text)
        {
            $file = new \read_sources\source_file();
            $file->id = $index + 1;
            $file->full_path = '/step-test/' . $file->id . '.phs';
            $file->buffer = new \read_sources\Source_Buffer($file->id, $file->full_path, 0, $text);
            $sources->files[] = $file;
            $buffers[] = \tokenize\File_Tokenizer::tokenize($file->buffer);
        }
        $sources->refresh_indexes();
        return [$sources, new Token_Set($buffers)];
    }

    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function rejects(callable $action, string $type, string $message): void
    {
        try {
            $action();
        }
        catch (\Throwable $error) {
            self::check(($error instanceof $type) && str_contains($error->getMessage(), $message),
                'Unexpected rejection: ' . $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $message);
    }

    public static function checkpoint(Parser $step, step_status $state, array $allowed): void
    {
        self::check(($step->status() === $state) && $step->supports_run(), 'Status and capability remain queryable');
        foreach (['init', 'run', 'finalize', 'result', 'store'] as $method)
        {
            if (in_array($method, $allowed, true)) {
                continue;
            }
            self::rejects(static fn() => $step->$method(), \LogicException::class, 'Parser::' . $method . ' requires');
            self::check($step->status() === $state, 'Caller misuse must not change state');
        }
    }

    public static function complete(Parser $step): Frontend_Set
    {
        $step->init();
        $step->run();
        $step->finalize();
        return $step->result();
    }
}

[$sources, $tokens] = Parser_Test::inputs(['function answer(): int { return 42; }', 'answer();']);
$previous = new Frontend_Set();
$before = serialize([$sources, $tokens, $previous]);
$step = new Parser($sources, $tokens, $previous, false);
Parser_Test::check(($step instanceof \compile\Step) && ($step instanceof \compile\Runnable_Step)
    && ($step instanceof \compile\Store_Providing_Step), 'Step exposes its real capabilities');
Parser_Test::checkpoint($step, step_status::created, ['init']);
$step->init();
Parser_Test::checkpoint($step, step_status::ready, ['run']);
$step->run();
Parser_Test::checkpoint($step, step_status::processed, ['finalize']);
$step->finalize();
Parser_Test::checkpoint($step, step_status::finished, ['result', 'store']);
$result = $step->result();
Parser_Test::check(($result instanceof \compile\Step_Result) && ($result instanceof \compile\Step_Store)
    && ($step->store() === $result) && ($step->result() === $result), 'Typed result/store share one completed output');
foreach ($sources->files as $file) {
    Parser_Test::check($result->for_file($file->id)->to_json() === (new File_Parser($tokens->for_file($file->id)))->parse()->to_json(),
        'Lifecycle preserves ordinary worker output');
}
Parser_Test::check(serialize([$sources, $tokens, $previous]) === $before, 'Lifecycle leaves all input snapshots unchanged');

$retained_before = serialize($result);
$reused = Parser_Test::complete(new Parser($sources, $tokens, $result, false));
$forced = Parser_Test::complete(new Parser($sources, $tokens, $result, true));
foreach ($sources->files as $file) {
    Parser_Test::check(($reused->for_file($file->id) === $result->for_file($file->id))
        && ($forced->for_file($file->id) !== $result->for_file($file->id)), 'Empty selection reuses; full rebuild executes workers');
}
Parser_Test::check(($forced->to_json() === $result->to_json()) && (serialize($result) === $retained_before),
    'Forced parse preserves exports and retained output');

// A removed file still disappears when no remaining file requires parsing.
$removed = clone $sources;
$removed->files[1] = clone $sources->files[1];
$removed->files[1]->change_state = \read_sources\file_change::deleted;
$removed->refresh_indexes();
$current = Parser_Test::complete(new Parser($removed, $tokens, $result, false));
Parser_Test::check(($current->for_file(1) === $result->for_file(1)) && ($current->for_file(2) === null),
    'Zero-work finalization removes deleted contributions');
$empty = Parser_Test::complete(new Parser(new Source_Set(), new Token_Set(), new Frontend_Set(), false));
Parser_Test::check($empty->to_json() === '[]', 'An empty project completes the same lifecycle');

// Construction does not inspect even missing inputs; init owns that failure.
$invalid = new Parser($sources, new Token_Set(), $result, false);
Parser_Test::checkpoint($invalid, step_status::created, ['init']);
Parser_Test::rejects(static fn() => $invalid->init(), \Exception::class, 'Missing or stale tokens');
Parser_Test::checkpoint($invalid, step_status::failed, []);

// Failure after an earlier task succeeded must expose neither partial output nor a store.
[$bad_sources, $bad_tokens] = Parser_Test::inputs(['return 42;', 'return 9']);
$bad = new Parser($bad_sources, $bad_tokens, $result, true);
$bad->init();
Parser_Test::rejects(static fn() => $bad->run(), \diagnostics\Source_Error::class, "Expected ';'");
Parser_Test::checkpoint($bad, step_status::failed, []);
Parser_Test::check(serialize($result) === $retained_before, 'A failed update preserves retained frontends');

// Deliberately violate the fixed-input contract to exercise finalization failure.
[$changed_sources, $changed_tokens] = Parser_Test::inputs(['return 1;']);
$changed = new Parser($changed_sources, $changed_tokens, new Frontend_Set(), false);
$changed->init();
$changed->run();
$changed_sources->files[0]->buffer = new \read_sources\Source_Buffer(1, '/step-test/1.phs', 0, 'return 2;');
Parser_Test::rejects(static fn() => $changed->finalize(), \Exception::class, 'stale');
Parser_Test::checkpoint($changed, step_status::failed, []);

echo "parsing step lifecycle ok: transitions, typed outputs, failures, purity, reuse, full rebuild and removal\n";
