<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

use Scpp\S2S\Stan\StanSemanticPass;

final class JssTranspiler
{
	public function __construct(
		private readonly JssTokenizer $tokenizer = new JssTokenizer(),
		private readonly JssParser $parser = new JssParser(),
		private readonly JssSemanticValidator $validator = new JssSemanticValidator(),
		private readonly JssEmitter $emitter = new JssEmitter(),
	) {
	}

	public function transpileToPhs(string $source): string
	{
		$program = $this->parse($source);
		return $this->emitter->emit($program);
	}

	public function transpileToPhsWithStanClassifications(string $source, string $path = 'main.jss'): string
	{
		$program = $this->parse($source);
		$summary = (new JssSummaryExtractor())->summarize($program, $path);
		$semanticResult = (new StanSemanticPass())->analyze([$path => $summary], dirname($path) ?: '.');
		return $this->emitter->emit($program, $semanticResult['frontend_classifications'] ?? [], true);
	}

	/** @param array<string,array<string,mixed>> $frontendClassifications */
	public function transpileToPhsWithProvidedClassifications(string $source, string $path, array $frontendClassifications): string
	{
		$program = $this->parse($source);
		return $this->emitter->emit($program, $frontendClassifications, true);
	}

	private function parse(string $source): JssNode
	{
		$tokens = $this->tokenizer->tokenize($source);
		$program = $this->parser->parse($tokens);
		$this->validator->validate($program);
		return $program;
	}
}
