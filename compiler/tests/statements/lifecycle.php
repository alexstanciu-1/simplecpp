<?php
declare(strict_types=1);
// Host-only ownership and continuation-lifetime assertions supplement portable outcomes.
require dirname(__DIR__, 2) . '/bootstrap.php';
function check(bool $ok, string $why): void { if (!$ok) { throw new LogicException($why); } }
$source = new \read_sources\Source_Buffer();
$source->path = 'lifecycle.phs';
$source->content = str_repeat('$v int = f(1);', 2000);
$tokens = \tokenize\File_Tokenizer::tokenize($source);
$before = serialize($tokens);
$state = new \parse\Parse_Result($tokens, new \parse\Syntax_Arena());
$parser = new \parse\File_Parser($state);
$result = (new ReflectionMethod($parser, 'run'))->invoke($parser, true, false);
check($result->valid, 'Long sequential parse succeeds');
check((new ReflectionProperty($parser, 'depth'))->getValue($parser) === 0, 'Every expression releases its root continuation');
check(count((new ReflectionProperty($parser, 'frames'))->getValue($parser)) === 2, 'Sequential expressions reuse bounded continuation storage');
check(serialize($tokens) === $before && $result->tokens === $tokens, 'Parser retains exact unchanged token/source snapshot');
$old = serialize($result);
$again = \parse\File_Parser::parse($tokens);
check(serialize($again) === $old, 'Fresh parsing is deterministic');
check($again->tree !== $result->tree, 'Each result owns its mutable arena');
$broken = new \read_sources\Source_Buffer();
$broken->content = 'function good(): int {} return 1; function broken(): int {';
$failure = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($broken));
check(!$failure->valid && $failure->root === 0 && $failure->entry === 0 && $failure->definitions === [] && $failure->tree->size() === 0, 'Failure discards partial declarations and executable syntax');
check(serialize($result) === $old, 'Later failure leaves retained successful result unchanged');
check(serialize(\parse\File_Parser::parse($tokens)) === $old, 'Repair uses the same fresh parsing boundary');
echo "Parser lifecycle: 9 assertions passed\n";
