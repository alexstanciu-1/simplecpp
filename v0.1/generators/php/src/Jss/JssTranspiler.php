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
		$program = $this->parse($source, true);
		$summary = (new JssSummaryExtractor())->summarize($program, $path);
		$semanticResult = (new StanSemanticPass())->analyze([$path => $summary], dirname($path) ?: '.');
		$classifications = $semanticResult['frontend_classifications'] ?? [];
		$this->throwTakeContractDiagnostics($classifications);
		return $this->emitter->emit($program, $classifications, true);
	}

	/** @param array<string,array<string,mixed>> $frontendClassifications */
	public function transpileToPhsWithProvidedClassifications(string $source, string $path, array $frontendClassifications): string
	{
		$program = $this->parse($source, true);
		$this->throwTakeContractDiagnostics($frontendClassifications);
		return $this->emitter->emit($program, $frontendClassifications, true);
	}

	private function parse(string $source, bool $deferTakeContracts = false): JssNode
	{
		$tokens = $this->tokenizer->tokenize($source);
		$program = $this->parser->parse($tokens);
		$this->validator->validate($program, [], ['defer_take_contracts' => $deferTakeContracts]);
		return $program;
	}

	/** @param array<string,array<string,mixed>> $frontendClassifications */
	private function throwTakeContractDiagnostics(array $frontendClassifications): void
	{
		foreach ($frontendClassifications as $classification) {
			if (!is_array($classification) || (string) ($classification['request_kind'] ?? '') !== 'take_contract') {
				continue;
			}
			$diagnostics = is_array($classification['diagnostics'] ?? null) ? $classification['diagnostics'] : [];
			if ($diagnostics === []) {
				continue;
			}
			$message = is_array($diagnostics[0] ?? null) && is_string($diagnostics[0]['message'] ?? null)
				? (string) $diagnostics[0]['message']
				: 'JSS `take(...)` contract could not be validated by STAN.';
			$line = (int) ($classification['line'] ?? 0);
			$column = (int) ($classification['column'] ?? 0);
			$path = (string) ($classification['path'] ?? '');
			if ($line > 0 && $column > 0) {
				$location = $path !== '' ? $path . ':' . $line . ':' . $column : $line . ':' . $column;
				$message .= ' at ' . $location . '.';
			}
			throw new \RuntimeException($message);
		}
	}
}
