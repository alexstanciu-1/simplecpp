<?php
declare(strict_types=1);

namespace Scpp\S2S\Loader;

use Scpp\S2S\PreTokenizer\PreTokenizer;
use Scpp\S2S\Support\InputException;

/**
 * Loads a PHP++ input file together with its sidecar JSON fixture.
 *
 * The generator starts in fixture-driven mode on purpose:
 * - deterministic development
 * - no dependency on the php-ast extension during early implementation
 * - easier debugging against known sample data
 */
final class InputLoader
{
	public function __construct(
		private readonly PreTokenizer $preTokenizer = new PreTokenizer(),
	) {
	}

	/**
	 * Loads exported AST and token data for one PHP++ source file and validates the expected JSON sidecar shape.
	 *
	 * Accepted JSON sidecars:
	 * - legacy wrapper object with {ast, tokens}
	 * - raw AST JSON emitted directly from ext-ast/json_encode($ast)
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	public function load(string $path, ?string $code = null, bool $save_ast_to_json = false): ParsedInput
	{
		if ($code === null) {
			$code = file_get_contents($path);
		}
		if ($code === false) {
			throw new InputException('Failed to read PHP input: ' . $path);
		}

		$preTokenized = $this->preTokenizer->rewrite($code);
		$parseCode = $preTokenized->source;
		$json_file = $path . ".json";
		
		if (extension_loaded('ast')) {
			$version = max(\ast\get_supported_versions()); # \ast\get_latest_version();
			$ast = \ast\parse_code($parseCode, $version);
			
			if ($save_ast_to_json) {
				file_put_contents($json_file, json_encode($ast));
			}

			return new ParsedInput($path, $parseCode, $code, \token_get_all($parseCode), $ast, $preTokenized->annotations);
		}
		
		if (!is_file($json_file)) {
			throw new InputException('No AST source [file] available (ext-ast missing and no JSON provided)');
		}
		
		$jsonSource = file_get_contents($json_file);

		if ($jsonSource === false) {
			throw new InputException('No AST source [content] available (ext-ast missing and no JSON provided)');
		}

		$data = json_decode($jsonSource, false, flags: JSON_THROW_ON_ERROR);

		if (is_object($data) && property_exists($data, 'ast')) {
			$ast = $this->normalizeDecodedAstShape($data->ast);
			$tokens = \token_get_all($parseCode);

			return new ParsedInput($path, $parseCode, $code, $tokens, $ast, $preTokenized->annotations);
		}

		$ast = $this->normalizeDecodedAstShape($data);
		return new ParsedInput($path, $parseCode, $code, \token_get_all($parseCode), $ast, $preTokenized->annotations);
	}

	/**
	 * Normalizes decoded fixture data into the same node/children shape returned by ext-ast:
	 * - nodes stay as objects
	 * - lists stay as arrays
	 * - node->children becomes an array
	 */
	private function normalizeDecodedAstShape(mixed $value): mixed
	{
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = $this->normalizeDecodedAstShape($item);
			}
			return $value;
		}

		if (!is_object($value)) {
			return $value;
		}

		foreach (get_object_vars($value) as $key => $item) {
			$value->$key = $this->normalizeDecodedAstShape($item);
		}

		if (property_exists($value, 'children') && is_object($value->children)) {
			$value->children = (array) $value->children;
			foreach ($value->children as $key => $item) {
				$value->children[$key] = $this->normalizeDecodedAstShape($item);
			}
		}

		return $value;
	}
}
