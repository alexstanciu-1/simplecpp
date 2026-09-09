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
	private readonly StructSyntaxRewriter $structSyntaxRewriter;
	private readonly UnionSyntaxRewriter $unionSyntaxRewriter;
	private readonly EnumBackingSyntaxRewriter $enumBackingSyntaxRewriter;
	private readonly AsyncSyntaxRewriter $asyncSyntaxRewriter;

	public function __construct(?TokenSiteScanner $scanner = null, ?AsyncSyntaxRewriter $asyncSyntaxRewriter = null, ?StructSyntaxRewriter $structSyntaxRewriter = null, ?EnumBackingSyntaxRewriter $enumBackingSyntaxRewriter = null, ?UnionSyntaxRewriter $unionSyntaxRewriter = null)
	{
		$this->scanner = $scanner ?? new TokenSiteScanner();
		$this->structSyntaxRewriter = $structSyntaxRewriter ?? new StructSyntaxRewriter();
		$this->unionSyntaxRewriter = $unionSyntaxRewriter ?? new UnionSyntaxRewriter();
		$this->enumBackingSyntaxRewriter = $enumBackingSyntaxRewriter ?? new EnumBackingSyntaxRewriter();
		$this->asyncSyntaxRewriter = $asyncSyntaxRewriter ?? new AsyncSyntaxRewriter();
	}

	public function rewrite(string $source): PreTokenizedInput
	{
		$source = $this->structSyntaxRewriter->rewrite($source);
		$source = $this->unionSyntaxRewriter->rewrite($source);
		$source = $this->enumBackingSyntaxRewriter->rewrite($source);
		$lexed = new LexedSource($source);
		$sites = $this->scanner->scan($lexed);

		$rewritten = '';
		$cursor = 0;
		$annotations = [];

		foreach ($sites as $site) {
			$rewritten .= substr($source, $cursor, $site['rewriteStart'] - $cursor);
			$rewritten .= $site['replacement'];
			$cursor = $site['rewriteEnd'];

			$annotation = [
				'kind' => $site['kind'],
				'name' => $site['name'],
				'type' => $site['type'],
				'line' => $site['line'],
				'startOffset' => $site['startOffset'],
				'endOffset' => $site['endOffset'],
			];
			if ((bool) ($site['isConst'] ?? false)) {
				$annotation['isConst'] = true;
			}
			$annotations[] = $annotation;
		}

		$rewritten .= substr($source, $cursor);
		$rewritten = $this->asyncSyntaxRewriter->rewrite($rewritten);

		return new PreTokenizedInput($rewritten, $annotations);
	}
}
