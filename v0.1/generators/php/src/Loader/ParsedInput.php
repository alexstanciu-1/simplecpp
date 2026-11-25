<?php
declare(strict_types=1);

namespace Scpp\S2S\Loader;

/**
 * Holds the raw PHP source together with the fixture-provided AST and tokens.
 */
final class ParsedInput
{
	/**
	 * @param array<int, mixed> $tokens
	 * @param list<array{
	 *   kind:string,
	 *   name:?string,
	 *   type:string,
	 *   line:int,
	 *   startOffset:int,
	 *   endOffset:int,
	 *   ownerName?:?string
	 * }> $annotations
	 */
	public function __construct(
		public readonly string $path,
		public readonly string $source,
		public readonly string $originalSource,
		public readonly array $tokens,
		public readonly mixed $ast,
		public readonly array $annotations = [],
	) {
		// Restore only scanner-marked constructor nodes, never arbitrary identifiers.
		$constructors = [];
		foreach ($annotations as $annotation) {
			if ($annotation['kind'] === 'collection_construction') $constructors[$annotation['name']] = $annotation['type'];
		}
		if ($constructors !== []) $this->restoreCollectionConstructors($ast, $constructors);
	}

	private function restoreCollectionConstructors(mixed $node, array $constructors): void
	{
		if (!is_object($node)) return;
		if (($node->kind ?? null) === \Scpp\S2S\Support\AstKind::NEW) {
			$class = $node->children['class'] ?? null;
			if (is_object($class) && ($class->kind ?? null) === \Scpp\S2S\Support\AstKind::NAME) {
				$name = $class->children['name'] ?? '';
				if (isset($constructors[$name])) $class->children['name'] = $constructors[$name];
			}
		}
		foreach (($node->children ?? []) as $child) $this->restoreCollectionConstructors($child, $constructors);
	}
}
