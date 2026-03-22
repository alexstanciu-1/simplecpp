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
	public function __construct(
		private readonly InputLoader $loader = new InputLoader(),
		private readonly TypeCommentExtractor $typeComments = new TypeCommentExtractor(),
		private readonly IrBuilder $builder = new IrBuilder(),
		private readonly Generator $generator = new Generator(),
	) {
	}

	public function transpile(string $phpPath): CppFile
	{
		$input = $this->loader->load($phpPath);
		$typeComments = $this->typeComments->extract($input->tokens);
		$ir = $this->builder->build($input, $typeComments);
		return $this->generator->generate($ir);
	}
}
