<?php
namespace scpp\compiler;

/** Canonical source namespace; path ancestry is not lexical user-symbol fallback. */
final class namespace_identity
{
	public function __construct(private string $qualified_path, private scope $members) {}

	public function path(): string
	{
		return $this->qualified_path;
	}

	public function published_scope(): scope
	{
		return $this->members;
	}

	public function member_name(string $local_name): string
	{
		return $this->qualified_path === '' ? $local_name : $this->qualified_path . '\\' . $local_name;
	}
}

/** Invocation-local result of synchronized namespace access. */
final class namespace_registration_result
{
	public ?namespace_identity $identity = null;
}

/** Model-owned namespace identities; parallel parser callers hold the publication lock.
 * Preparation body batches synchronize lookup-only interning after the whole parse join.
 */
final class Namespace_Registry
{
	private bool $parallel = false;
	public function parallel_access(bool $enabled): void { $this->parallel = $enabled; }

	private Keyed_Storage $identities /** Keyed_Storage<namespace_identity> */;
	private scope $language;

	public function __construct(scope $language)
	{
		$this->language = $language;
		$this->identities = new Keyed_Storage /** Keyed_Storage<namespace_identity> */();
	}

	/** Intern canonical paths only; syntax and relative-name resolution belong to their workers. */
	public function register(string $path): namespace_identity
	{
		if (!$this->parallel) { return $this->register_serial($path); }
		$registry = $this;
		$result = new namespace_registration_result();
		task_synchronize(function () use ($registry, $path, $result): void {
			$result->identity = $registry->register_serial($path);
		});
		return $result->identity;
	}

	private function register_serial(string $path): namespace_identity
	{
		$identities /** Keyed_Storage<namespace_identity> */ = $this->identities;
		if (isset($identities[$path])) {
			return $identities[$path];
		}
		if ($path !== '')
		{
			$segment = '';
			for ($index = 0; $index <= string_byte_len($path); $index++)
			{
				$byte = $index === string_byte_len($path) ? 92 : string_byte_at($path, $index);
				if ($byte === 92) {
					if (!Source_Text::identifier($segment)) {
						throw new \InvalidArgumentException('Invalid canonical namespace path: ' . $path);
					}
					$segment = '';
				}
				else {
					$segment .= string_byte_from_int($byte);
				}
			}
		}
		$members = new scope();
		$members->set_parent($this->language);
		$identity = new namespace_identity($path, $members);
		$members->set_namespace($identity);
		$identities->add($path, $identity);
		return $identity;
	}

	public function find(string $path): ?namespace_identity
	{
		if (!$this->parallel) { return $this->find_serial($path); }
		$registry = $this;
		$result = new namespace_registration_result();
		task_synchronize(function () use ($registry, $path, $result): void {
			$result->identity = $registry->find_serial($path);
		});
		return $result->identity;
	}

	private function find_serial(string $path): ?namespace_identity
	{
		$identities /** Keyed_Storage<namespace_identity> */ = $this->identities;
		if (isset($identities[$path])) { return $identities[$path]; }
		return null;
	}
}
