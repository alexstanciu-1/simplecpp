<?php
declare(strict_types=1);

namespace Scpp\S2S;

use Scpp\S2S\Builder\IrBuilder;
use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\Generator\Generator;
use Scpp\S2S\Loader\InputLoader;
use Scpp\S2S\Metadata\TypeCommentExtractor;
use Scpp\S2S\Support\InputException;

/**
 * Coordinates the generator pipeline for one file.
 */
final class Transpiler
{
	/**
	 * Stores collaborators and default state for this phase object.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	public function __construct(
		private readonly InputLoader $loader = new InputLoader(),
		private readonly TypeCommentExtractor $typeComments = new TypeCommentExtractor(),
		private readonly IrBuilder $builder = new IrBuilder(),
		private readonly Generator $generator = new Generator(),
	) {
	}

	/**
	 * Runs the full S2S pipeline for one exported PHP fixture and returns the generated C++ plus diagnostics.
	 *
	 * Relationship to specs:
	 * - preserves the subset and lowering rules documented for the prototype
	 * - keeps the implementation explicit so mismatches with exporter shapes are easier to audit
	 */
	public function transpile(string $phpPath, bool $save_ast_to_json = false): CppFile
	{
		$source = file_get_contents($phpPath);
		if ($source === false) {
			throw new InputException('Failed to read PHP input: ' . $phpPath);
		}

		$this->assertNoSimpleReferenceRebinding($source);

		$input = $this->loader->load($phpPath, $source, $save_ast_to_json);

		$typeComments = $this->typeComments->extract($input->tokens);
		$ir = $this->builder->build($input, $typeComments);
		return $this->generator->generate($ir);
	}

	private function assertNoSimpleReferenceRebinding(string $sourcePhp): void
	{
		$tokens = token_get_all($sourcePhp);
		$bindings = [];
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++) {
			$token = $tokens[$i];
			if (!is_array($token) || $token[0] !== T_VARIABLE) {
				continue;
			}

			$next = $this->nextMeaningfulToken($tokens, $i + 1);
			if ($next !== '=') {
				continue;
			}

			$afterAssignIndex = $this->nextMeaningfulTokenIndex($tokens, $i + 1);
			if ($afterAssignIndex === null) {
				continue;
			}
			$ampIndex = $this->nextMeaningfulTokenIndex($tokens, $afterAssignIndex + 1);
			if ($ampIndex === null || !$this->isReferenceAmpersandToken($tokens[$ampIndex])) {
				continue;
			}

			$name = substr($token[1], 1);
			$bindings[$name] = ($bindings[$name] ?? 0) + 1;
			if ($bindings[$name] > 1) {
				throw new InputException('Reference rebinding is not supported for $' . $name . '.');
			}
		}
	}


	private function isReferenceAmpersandToken(array|string $token): bool
	{
		if ($token === '&') {
			return true;
		}

		if (!is_array($token)) {
			return false;
		}

		return in_array($token[0], [T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG, T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG], true);
	}

	private function nextMeaningfulToken(array $tokens, int $start): array|string|null
	{
		$index = $this->nextMeaningfulTokenIndex($tokens, $start);
		if ($index === null) {
			return null;
		}

		return $tokens[$index];
	}

	private function nextMeaningfulTokenIndex(array $tokens, int $start): ?int
	{
		$count = count($tokens);
		for ($i = $start; $i < $count; $i++) {
			$token = $tokens[$i];
			if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			}

			return $i;
		}

		return null;
	}
}
