<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{ast_call_expression, ast_field_expression, ast_lambda_expression, ast_name_expression, diagnostic, flavor, Parser,
	source_file, Source_Frontend, token_kind};

function check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Source_Frontend();
// Resume expression parsing at a known delimiter on the same cursor. Whole-file parse() is single-use.
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$prefix = match ($flavor) {
		flavor::cpp => 'auto outer = new Box(); auto bad = ',
		flavor::php => '$outer = new Box(); $bad = ',
		flavor::js => 'let outer = new Box(); let bad = ',
	};
	$param = static fn(string $name, string $type = 'int'): string => match ($flavor) {
		flavor::cpp => $type . ' ' . $name, flavor::php => $type . ' $' . $name, flavor::js => $name . ':' . $type,
	};
	$var = $flavor === flavor::php ? '$' : '';
	$probe = $flavor === flavor::js ? 'outer.field;inner.field;nextName.field;' : $var . 'outer->field;';
	$failure_line = '/* π */ ;';
	$access = $flavor === flavor::js ? '.' : '->';
	$valid_body = 'apply(fn(' . $param('nested') . '):int => ' . $var . 'nested, ' . $var . 'nextName' . $access . 'field)';
	$text = $prefix . 'fn(' . $param('inner') . "):int =>\n" . $failure_line . "\n" . $probe
		. 'fn(' . $param('nextName', 'Box') . '):int => ' . $valid_body . ';' . $probe;
	$source = new source_file('lambda.' . $flavor->source_extension(), $text, $flavor);
	$parser = new Parser($frontend->scan($source));
	try {
		$parser->parse();
		throw new RuntimeException('Expected a missing lambda body');
	} catch (diagnostic $failure) {
		$parser->cursor->take(token_kind::semicolon, 'failed body delimiter');
	}

	foreach (['failure', 'success'] as $exit) {
		if ($exit === 'success') {
			$lambda = $parser->value();
			check($lambda instanceof ast_lambda_expression && $lambda->body instanceof ast_call_expression,
				'Subsequent lambda parsed on same parser');
			[$nested, $next] = $lambda->body->arguments;
			check($nested instanceof ast_lambda_expression && $nested->body instanceof ast_name_expression
				&& $nested->body->name === 'nested' && $next instanceof ast_field_expression
				&& $next->receiver instanceof ast_name_expression && $next->receiver->name === 'nextName',
				'Nested lambda restored its enclosing lambda parameter');
			$parser->cursor->take(token_kind::semicolon, 'successful body delimiter');
		}
		$field = $parser->value();
		check($field instanceof ast_field_expression && $field->receiver instanceof ast_name_expression
			&& $field->receiver->name === 'outer' && $field->field === 'field', "Outer member lookup after $exit");
		$parser->cursor->take(token_kind::semicolon, ';');
		if ($flavor === flavor::js) {
			foreach (['inner', 'nextName'] as $name) {
				$path = $parser->value();
				check($path instanceof ast_name_expression && $path->name === $name . '\\field',
					"Leaked lambda parameter $name after $exit");
				$parser->cursor->take(token_kind::semicolon, ';');
			}
		} else {
			// These flavors spell member access explicitly; their expression shape alone cannot detect a leaked scope.
			$lexical = (new ReflectionProperty(Parser::class, 'lexical'))->getValue($parser);
			check((new ReflectionProperty(Parser::class, 'lambda_names'))->getValue($parser) === null
				&& $lexical->has_local('outer') && !$lexical->has_local('inner') && !$lexical->has_local('nextName'),
				"Outer scope after $exit in $flavor->name");
		}
	}
	check($parser->cursor->at_end() && $failure->source === $source
		&& $failure->offset === strpos($text, $failure_line) + strlen($failure_line) - 1
		&& str_starts_with($failure->getMessage(), $source->path . ':2:' . strlen($failure_line) . ':'),
		'Original diagnostic snapshot, byte offset and byte column after parser reuse');
}

// Nested callables need their own environment even when a later callback boundary rejects capture.
$text = '$factor=3; $f=fn(int $n):int => apply(fn(int $x):int => $factor+$x+$n+$factor);'
	. '$g=fn(int $factor):int => apply(fn():int => $factor);'
	. '$h=fn():int => apply(function () use ($factor):int { $inside=4; return $inside; });'
	. '$i=fn():int => apply(function ():int { return $factor; }); return 8;';
$source = new source_file('nested.phs', $text, flavor::php);
$statements = (new Parser($frontend->scan($source)))->parse()->main()->body()->statements;
foreach ([[1, ['factor'], ['factor', 'n']], [2, [], ['factor']], [3, ['factor'], ['factor']], [4, [], []]] as [$index, $outer, $inner]) {
	$arrow = $statements[$index]->initializer;
	$nested = $arrow->body->arguments[0];
	check(array_column($arrow->captures, 'short_name') === $outer
		&& array_column($nested->captures, 'short_name') === $inner, "Nested lexical capture sets at statement $index");
}
