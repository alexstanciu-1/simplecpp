<?php
declare(strict_types=1);

namespace Scpp\S2S;

use Scpp\S2S\Builder\IrBuilder;
use Scpp\S2S\Emit\CppFile;
use Scpp\S2S\Generator\Generator;
use Scpp\S2S\Loader\InputLoader;
use Scpp\S2S\Metadata\TypeCommentExtractor;

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

	public function transpile(string $phpPath): CppFile
	{
		$input = $this->loader->load($phpPath);
		$typeComments = $this->typeComments->extract($input->tokens);
		$ir = $this->builder->build($input, $typeComments);
		return $this->generator->generate($ir);
	}
}
