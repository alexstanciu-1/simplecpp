<?php

declare(strict_types=1);

use scpp\compiler\{application_vector_type, type_int64};
use Scpp\Api\ConcreteType;
use function Scpp\Api\concreteApplication;
use function Scpp\Api\concreteSupport;

require_once __DIR__ . '/../../boot.php';

// Publish the configured recipe before reading an application. These two formal
// slots are supplied by preparation when the native renderer runs.
/** Formal type slot compiled into the native vector recipe before application preparation. */
final class vector_element_slot implements ConcreteType
{
	public function identity(): string
	{
		return 'formal_element';
	}

	public function cppType(string $namespace): string
	{
		return '@ELEMENT@';
	}

	/** @return string[] */
	public function headers(): array
	{
		return [];
	}

	public function supports(string $requirement): ?bool
	{
		return null;
	}
}
$profile = new application_vector_type(type_int64::instance());
$values = ['n' => '@VECTOR@', 'inline' => 'inline ', 'access_name' => 'access_@VECTOR@',
	'access' => 'detail::access_@VECTOR@'] + $profile->recipe_arguments();
$recipe = $profile::concrete_recipe();
$body = concreteApplication($recipe, ['e' => new vector_element_slot()], $values, '');
$literal = static fn(string $text): string => json_encode($text, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo 'function vector_body(string $vector, string $element): string {', "\n";
echo '    $body = "";', "\n";
foreach (preg_split('/(@ELEMENT@|@VECTOR@)/', $body, flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $part) {
	$value = match ($part) {
		'@ELEMENT@' => '$element',
		'@VECTOR@' => '$vector',
		default => $literal($part),
	};
	echo '    $body .= ', $value, ";\n";
}
echo '    return $body;', "\n}\n";
$support = ($values['mt_segments'] === '1' ? "namespace scpp::mt { struct job; }\n" : '') . concreteSupport($recipe);
echo 'function vector_support(): string { return ', $literal($support), "; }\n";
