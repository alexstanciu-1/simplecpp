<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Loads module.json graphs, e.g. dependencies.runtime.modules=["json"], before any source frontend runs. */
final class Module_Loader
{
	private array $documents /** array<string,\stdClass>; canonical manifest path -> validated document */ = [];
	private array $paths /** array<string,string>; unique module name -> canonical manifest */ = [];
	private array $definitions /** array<string,array{root:string,type:string,sources:array<string,flavor>,dependencies:list<string>,manifest:string,artifacts:list<string>,entry?:string}> */ = [];
	private string $runtime;
	private string $entry_manifest;
	private array $dependency_roots /** array<string,bool> */ = [];
	private string|false $output_directory = false;

	public function __construct(?string $runtime = null)
	{
		$this->runtime = $runtime ?? dirname(__DIR__, 2) . '/runtime';
	}

	/** Each invocation reloads configuration and discovery; generated inventories are never input caches. */
	public function load(string $path, ?string $output_directory = null): compilation_session
	{
		$this->output_directory = $output_directory === null ? false : realpath($output_directory);
		$this->documents = [];
		$this->paths = [];
		$this->definitions = [];
		$manifest = $this->manifest_path($path);
		$this->entry_manifest = $manifest;
		$this->dependency_roots = [];
		$entry = $this->read($manifest);
		$runtime = $this->manifest_path($this->runtime);
		if ($this->read($runtime) !== 'runtime') {
			throw new \RuntimeException('Built-in runtime manifest must be named runtime');
		}
		$this->activate($manifest, $runtime);
		$this->definitions[$entry]['dependencies'][] = 'runtime';
		foreach ($this->definitions as $name => $input) {
			if ($name !== $entry) {
				$this->dependency_roots[$input['root']] = true;
			}
		}
		$definition = &$this->definitions[$entry];
		$definition['sources'] = $this->sources($definition['root'], $definition['type'], $this->documents[$manifest]);
		if (!isset($definition['entry']))
		{
			$matches = array_filter(array_keys($definition['sources']),
				static fn(string $source): bool => (dirname($source) === '.') &&
					(pathinfo($source, PATHINFO_FILENAME) === 'main'));
			if (count($matches) !== 1) {
				throw new \RuntimeException('Entry requires exactly one root-level main source: ' . $entry);
			}
			$definition['entry'] = array_values($matches)[0];
		}
		$session = Program_Loader::modules($this->definitions, $entry, true, array_keys($this->definitions));
		$this->write_inventory($session);
		return $session;
	}

	/** Resolve a folder or explicit JSON manifest; canonical paths deduplicate repeated activation. */
	private function manifest_path(string $path): string
	{
		if (is_dir($path)) {
			$path = rtrim($path, '/') . '/module.json';
		}
		$resolved = realpath($path);
		if (($resolved === false) || !is_file($resolved)) {
			throw new \RuntimeException('Missing module manifest: ' . $path);
		}
		return $resolved;
	}

	/** Validate object boundaries strictly so misspelled configuration never silently changes discovery. */
	private function fields(mixed $value, array $allowed /** list<string> */, string $where): \stdClass
	{
		if (!$value instanceof \stdClass) {
			throw new \RuntimeException('Expected an object: ' . $where);
		}
		foreach (get_object_vars($value) as $key => $_) {
			if (!in_array($key, $allowed, true)) {
				throw new \RuntimeException('Unsupported field ' . $key . ' in ' . $where);
			}
		}
		return $value;
	}

	/** Lists contain only nonempty names or paths; options/config are deliberately unsupported. */
	private function strings(mixed $value, string $where): array /** list<string> */
	{
		if (!is_array($value) || !array_is_list($value)) {
			throw new \RuntimeException('Expected a string list: ' . $where);
		}
		foreach ($value as $item) {
			if (!is_string($item) || ($item === '')) {
				throw new \RuntimeException('Expected nonempty string: ' . $where);
			}
		}
		return $value;
	}

	/** Read a manifest once and register its name before descending into dependencies. */
	private function read(string $path): string
	{
		if (isset($this->documents[$path])) {
			return $this->documents[$path]->name;
		}
		$text = file_get_contents($path);
		if ($text === false) {
			throw new \RuntimeException('Cannot read manifest: ' . $path);
		}
		try {
			$document = json_decode($text, false, 512, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $error) {
			throw new \RuntimeException('Invalid module JSON: ' . $path . ': ' . $error->getMessage());
		}
		$document = $this->fields($document,
			['name', 'type', 'entry', 'sources', 'dependencies', 'modules', 'defaults', 'artifacts'], $path);
		$root = dirname($path);
		$name = property_exists($document, 'name') ? $document->name : basename($root);
		$type = property_exists($document, 'type') ? $document->type : 'default';
		if (!is_string($name) || (preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $name) !== 1)) {
			throw new \RuntimeException('Invalid module name: ' . $path);
		}
		if (isset($this->paths[$name]) && ($this->paths[$name] !== $path)) {
			throw new \RuntimeException('Conflicting module name: ' . $name);
		}
		if (!in_array($type, ['default', 'c++'], true)) {
			throw new \RuntimeException('Invalid module type: ' . $path);
		}
		$document->name = $name;
		foreach (['dependencies', 'modules', 'defaults'] as $field) {
			if (!property_exists($document, $field)) {
				$document->$field = $field === 'defaults' ? [] : new \stdClass();
			}
		}
		if (!$document->dependencies instanceof \stdClass || !$document->modules instanceof \stdClass) {
			throw new \RuntimeException('dependencies and modules must be objects: ' . $path);
		}
		$this->strings($document->defaults, 'defaults');
		foreach ($document->modules as $alias => $location) {
			if (!is_string($location) || ($location === '')) {
				throw new \RuntimeException('Invalid submodule path: ' . $alias);
			}
		}
		$sources = [];
		$artifacts = array_map(fn(string $artifact): string => $this->relative($root, $artifact),
			$this->strings($document->artifacts ?? [], 'artifacts'));
		$definition = ['manifest' => $path, 'root' => $root, 'type' => $type, 'sources' => $sources, 'dependencies' => [], 'artifacts' => $artifacts];
		if (($path === $this->entry_manifest) && property_exists($document, 'entry')) {
			if (!is_string($document->entry)) {
				throw new \RuntimeException('entry must be a relative source path');
			}
			$definition['entry'] = $document->entry;
		}
		$this->documents[$path] = $document;
		$this->paths[$name] = $path;
		$this->definitions[$name] = $definition;
		return $name;
	}

	/** Expand activation independently of dependency edges; the common graph loader checks actual cycles. */
	private function activate(string $entry, string $runtime): void
	{
		$queue = [[$entry, []], [$runtime, []]];
		$expanded = [];
		$activated = [];
		for ($index = 0; $index < count($queue); ++$index)
		{
			[$path, $requested] = $queue[$index];
			$name = $this->read($path);
			$document = $this->documents[$path];
			if (!isset($expanded[$path]))
			{
				$expanded[$path] = true;
				$requested = array_merge($requested, $document->defaults);
				foreach ($document->dependencies as $dependency => $options)
				{
					$options = $this->fields($options, ['path', 'modules'], 'dependency ' . $dependency);
					if (property_exists($options, 'path'))
					{
						if (!is_string($options->path) || ($options->path === '')) {
							throw new \RuntimeException('Invalid dependency path: ' . $dependency);
						}
						$location = $this->relative(dirname($path), $options->path);
					}
					else {
						$location = $dependency === 'runtime' ? $this->runtime
							: throw new \RuntimeException('Dependency requires path: ' . $dependency);
					}
					$target = $this->manifest_path($location);
					if ($this->read($target) !== $dependency) {
						throw new \RuntimeException('Dependency name does not match manifest: ' . $dependency);
					}
					$this->definitions[$name]['dependencies'][] = $dependency;
					$modules = property_exists($options, 'modules') ? $options->modules : [];
					$queue[] = [$target, $this->strings($modules, 'modules')];
				}
			}
			foreach (array_unique($requested) as $alias)
			{
				if (!isset($document->modules->$alias)) {
					throw new \RuntimeException('Unknown submodule: ' . $name . '::' . $alias);
				}
				$key = $path . '::' . $alias;
				if (!isset($activated[$key])) {
					$activated[$key] = true;
					$queue[] = [$this->manifest_path($this->relative(dirname($path), $document->modules->$alias)), []];
				}
			}
		}
	}

	private function relative(string $root, string $path): string
	{
		return str_starts_with($path, '/') ? $path : $root . '/' . $path;
	}

	/** Explicit lists replace discovery; explicit flavor maps remain available for nonstandard extensions. */
	private function sources(string $root, string $type, \stdClass $document): array /** array<string,flavor> */
	{
		$selection = property_exists($document, 'sources') ? $document->sources : $this->discover($root, $type);
		$result = [];
		if ($selection instanceof \stdClass)
		{
			foreach ($selection as $path => $label) {
				if (!is_string($label) || (($flavor = flavor::tryFrom($label)) === null)
					|| (($type === 'c++') && ($flavor !== flavor::cpp))) {
					throw new \RuntimeException('Invalid source flavor: ' . $path);
				}
				$result[$path] = $flavor;
			}
		}
		else
		{
			foreach ($this->strings($selection, 'sources') as $path) {
				if (isset($result[$path])) {
					throw new \RuntimeException('Duplicate source: ' . $path);
				}
				$result[$path] = $this->source_flavor($path, $type)
					?? throw new \RuntimeException('Unsupported source extension: ' . $path);
			}
		}
		ksort($result, SORT_STRING);
		return $result;
	}

	private function source_flavor(string $path, string $type): ?flavor
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return $type === 'c++' ? (in_array($extension, ['cpp', 'cc', 'cxx'], true) ? flavor::cpp : null)
			: match ($extension) { 'cpp' => flavor::cpp, 'php' => flavor::php, 'js' => flavor::js, default => null };
	}

	/** Stable recursive discovery skips nested modules, symlinks, hidden directories and generated/build trees. */
	private function discover(string $root, string $type, string $relative = ''): array /** list<string> */
	{
		$entries = scandir($root . '/' . $relative);
		if ($entries === false) {
			throw new \RuntimeException('Cannot discover sources: ' . $root . '/' . $relative);
		}
		$result = [];
		foreach ($entries as $entry)
		{
			if (str_starts_with($entry, '.')) {
				continue;
			}
			$path = $relative === '' ? $entry : $relative . '/' . $entry;
			$absolute = $root . '/' . $path;
			if (is_link($absolute)) {
				continue;
			}
			if (is_dir($absolute)) {
				if ((realpath($absolute) === $this->output_directory) || isset($this->dependency_roots[realpath($absolute)])) {
					continue;
				}
				if (!in_array(strtolower($entry), ['build', 'generated', 'vendor', 'node_modules'], true)
					&& !is_file($absolute . '/module.json')) {
					$result = array_merge($result, $this->discover($root, $type, $path));
				}
			}
			elseif (is_file($absolute) && ($this->source_flavor($path, $type) !== null)) {
				$result[] = $path;
			}
		}
		return $result;
	}

	/** Publish only the application inventory after graph loading; leave identical output untouched. */
	private function write_inventory(compilation_session $session): void
	{
		$module = $session->entry;
		$root = $module->root;
		$protected = array_fill_keys(array_keys($this->documents), true);
		$sources = [];
		foreach ($module->sources as $unit) {
			$protected[realpath($root . '/' . $unit->path)] = true;
			$sources[$unit->path] = $unit->flavor->value;
		}
		$modules = [$module->name => (object) $sources];
		$path = $root . '/module.sources.json';
		if (!is_link($path) && isset($protected[realpath($path)])) {
			throw new \RuntimeException('Inventory collides with an input: ' . $path);
		}
		if (is_link($path)) {
			throw new \RuntimeException('Inventory must not be a symlink: ' . $path);
		}
		ksort($modules, SORT_STRING);
		$text = json_encode(['generated' => true, 'modules' => $modules], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
		if (is_file($path) && (file_get_contents($path) === $text)) {
			return;
		}
		$temp = tempnam($root, '.module-sources-');
		if ($temp === false) {
			throw new \RuntimeException('Cannot create inventory: ' . $path);
		}
		try {
			if ((file_put_contents($temp, $text) !== strlen($text)) || !rename($temp, $path)) {
				throw new \RuntimeException('Cannot publish inventory: ' . $path);
			}
		}
		finally {
			if (is_file($temp)) {
				unlink($temp);
			}
		}
	}
}
