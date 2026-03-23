<?php
declare(strict_types=1);

namespace Scpp\S2S\IR;

/**
 * Normalized PHP-facing IR for one input file.
 *
 * At this stage the IR is intentionally small. It contains only the information
 * needed by the current sample-driven generator pass.
 */
final class PhpFile
{
	/**
	 * @param list<NamespaceBlock> $namespaces
	 * @param list<UseDecl> $rootUses
	 * @param list<ConstantDecl> $constants
	 * @param list<ClassDecl> $classes
	 * @param list<FunctionDecl> $functions
	 * @param list<Statement> $rootStatements
	 * @param array<string, string> $localTypeCommentsByKey
	 */
	public function __construct(
		public readonly string $path,
		public readonly array $namespaces,
		public readonly array $rootUses,
		public readonly array $constants,
		public readonly array $classes,
		public readonly array $functions,
		public readonly array $rootStatements,
		public readonly array $localTypeCommentsByKey,
	) {
	}
}
