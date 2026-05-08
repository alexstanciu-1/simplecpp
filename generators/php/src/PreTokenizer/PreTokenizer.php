<?php
declare(strict_types=1);

namespace Scpp\S2S\PreTokenizer;

/**
 * Applies scanner-owned typed-slot rewrites to produce PHP-compatible source
 * plus separate annotation memory.
 */
final class PreTokenizer
{
	private readonly TokenSiteScanner $scanner;

	public function __construct(?TokenSiteScanner $scanner = null)
	{
		$this->scanner = $scanner ?? new TokenSiteScanner();
	}

	public function rewrite(string $source): PreTokenizedInput
	{
		$lexed = new LexedSource($source);
		$sites = $this->scanner->scan($lexed);

		$rewritten = '';
		$cursor = 0;
		$annotations = [];

		foreach ($sites as $site) {
			$rewritten .= substr($source, $cursor, $site['rewriteStart'] - $cursor);
			$rewritten .= $site['replacement'];
			$cursor = $site['rewriteEnd'];

			$annotations[] = [
				'kind' => $site['kind'],
				'name' => $site['name'],
				'type' => $site['type'],
				'line' => $site['line'],
				'startOffset' => $site['startOffset'],
				'endOffset' => $site['endOffset'],
			];
		}

		$rewritten .= substr($source, $cursor);

		return new PreTokenizedInput($rewritten, $annotations);
	}
}
