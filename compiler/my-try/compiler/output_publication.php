<?php
namespace scpp\compiler;

/** Invocation-local coordinator consumer; the model and backend retain no filesystem worker. */
final class Output_Publication
{
	private string $root;
	private \scpp\Lock_Reservation $reservation;
	private Keyed_Storage $owned /** Keyed_Storage<output_file_ownership> */;
	private bool $attempted = false;

	/** Mark the attempted revision pending before parsing; errors cannot expose an old ready build. */
	public function __construct(string $directory)
	{
		$this->root = fs_output_root($directory);
		$this->owned = new Keyed_Storage /** Keyed_Storage<output_file_ownership> */();
		$this->reservation = new \scpp\Lock_Reservation();
		fs_output_read($this->root, '.scpp-output.lock');
		if (!$this->reservation->acquire($this->root . '/.scpp-output.lock', false)) {
			throw new \RuntimeException('Output publication is busy');
		}
		try
		{
			$status = fs_output_read($this->root, '.scpp-output.status');
			$manifest = fs_output_read($this->root, '.scpp-output.manifest');
			if ($status === null) {
				if ($manifest !== null) {
					throw new \RuntimeException('Output metadata has no publication owner');
				}
			}
			else {
				if (string_byte_starts_with($status, 'scpp-output-v1:ready:')) {
					if ($manifest === null) {
						throw new \RuntimeException('Ready output manifest is missing');
					}
					if ($status !== 'scpp-output-v1:ready:' . content_sha256($manifest) . "\n") {
						throw new \RuntimeException('Ready output manifest was modified');
					}
					$this->load_inventory($manifest, false);
				}
				else {
					$journal = self::pending_inventory($status);
					$this->load_inventory($journal, true);
				}
			}
			$initial = '{"format":"scpp-output-journal-v1","files":[';
			$separator = '';
			$owned /** Keyed_Storage<output_file_ownership> */ = $this->owned;
			foreach ($owned as $path => $authorization) {
				$initial .= $separator . self::inventory_entry($path, $authorization->before, $authorization->after);
				$separator = ',';
			}
			$this->mark_pending($initial . "]}\n");
		}
		catch (\Throwable $error) {
			$this->reservation->release();
			throw $error;
		}
	}

	/** Preflight all ownership before writes; journal first, manifest last, readiness after success. */
	public function publish(Storage $artifacts /** Storage<cpp_artifact> */, runtime_cpp_build_spec $runtime): void
	{
		if (!$this->reservation->active() || $this->attempted) {
			throw new \LogicException('Publication requires an active unfinished reservation');
		}
		$this->attempted = true;
		$next /** Keyed_Storage<cpp_artifact> */ = new Keyed_Storage();
		$identities /** hash<bool> */ = [];
		$hashes /** hash<string> */ = [];
		$manifest = '{"format":"scpp-output-manifest-v1","files":[';
		$separator = '';
		$units = 0;
		foreach ($artifacts as $artifact)
		{
			$path = $artifact->file_name;
			self::require_artifact_path($path);
			if (isset($identities[$artifact->identity]) || isset($next[$path])) {
				throw new \RuntimeException('Duplicate publication artifact');
			}
			$identities[$artifact->identity] = true;
			$next->add($path, $artifact);
			$digest = content_sha256($artifact->text);
			$hashes[$path] = $digest;
			$manifest .= $separator . '{"path":' . json_quote($path) . ',"sha256":' . json_quote($digest)
				. ',"id":' . json_quote($artifact->identity) . ',"kind":' . json_quote($artifact->kind_name()) . '}';
			$separator = ',';
			if ($artifact->kind() === cpp_artifact_kind::translation_unit) {
				$units++;
			}
		}
		if ($units === 0) {
			throw new \RuntimeException('Publication requires executable translation units');
		}
		$manifest .= '],"runtime_build":' . Runtime_Build_Contract::encode($runtime) . "}\n";
		$current /** hash<string> */ = [];
		$owned /** Keyed_Storage<output_file_ownership> */ = $this->owned;
		foreach ($owned as $path => $authorization)
		{
			$bytes = fs_output_read($this->root, $path);
			if ($bytes !== null) {
				$digest = content_sha256($bytes);
				if (($digest !== $authorization->before) && ($digest !== $authorization->after)) {
					throw new \RuntimeException('Refusing to replace edited output: ' . $path);
				}
				$current[$path] = $digest;
			}
		}
		foreach ($next as $path => $artifact) {
			if (!isset($owned[$path])) {
				if (fs_output_read($this->root, $path) !== null) {
					throw new \RuntimeException('Refusing to adopt unowned output: ' . $path);
				}
				$owned->add($path, new output_file_ownership());
			}
		}
		$journal = '{"format":"scpp-output-journal-v1","files":[';
		$separator = '';
		foreach ($owned as $path => $authorization) {
			$journal .= $separator . self::inventory_entry($path, $current[$path] ?? '', $hashes[$path] ?? '');
			$separator = ',';
		}
		$journal .= "]}\n";
		$this->mark_pending($journal);
		foreach ($next as $path => $artifact) {
			fs_output_write($this->root, $path, $artifact->text);
		}
		foreach ($owned as $path => $authorization) {
			if (!isset($next[$path])) {
				$bytes = fs_output_read($this->root, $path);
				if ($bytes !== null) {
					fs_output_remove($this->root, $path, $bytes);
				}
			}
		}
		fs_output_write($this->root, '.scpp-output.manifest', $manifest);
		fs_output_write($this->root, '.scpp-output.status', 'scpp-output-v1:ready:' . content_sha256($manifest) . "\n");
	}

	public function close(): void
	{
		$this->reservation->release();
	}

	/** Readers must hold a shared reservation for the entire build, not just this check. */
	public static function require_ready(string $root): string
	{
		$status = fs_output_read($root, '.scpp-output.status');
		$manifest = fs_output_read($root, '.scpp-output.manifest');
		if (($status === null) || ($manifest === null)) {
			throw new \RuntimeException('Output has no ready revision');
		}
		if ($status !== 'scpp-output-v1:ready:' . content_sha256($manifest) . "\n") {
			throw new \RuntimeException('Output revision is pending or failed');
		}
		return $manifest;
	}

	/** Reconcile a previous successful set or the two allowed states of an interrupted write. */
	private function load_inventory(string $bytes, bool $journal): void
	{
		$document = json_read($bytes);
		$expected = $journal ? 'scpp-output-journal-v1' : 'scpp-output-manifest-v1';
		if ($document->member('format')->text() !== $expected) {
			throw new \RuntimeException('Unsupported output inventory');
		}
		$rows = $document->member('files');
		if ($rows->kind() !== 'array') {
			throw new \RuntimeException('Output inventory files must be an array');
		}
		$seen /** hash<bool> */ = [];
		$owned /** Keyed_Storage<output_file_ownership> */ = $this->owned;
		for ($index = 0; $index < $rows->size(); $index++)
		{
			$row = $rows->at($index);
			$path = $row->member('path')->text();
			self::require_artifact_path($path);
			if (isset($seen[$path])) {
				throw new \RuntimeException('Duplicate inventory path');
			}
			$seen[$path] = true;
			$record = new output_file_ownership();
			if ($journal) {
				$record->before = $row->member('before')->text();
				$record->after = $row->member('after')->text();
			}
			else {
				$record->before = $row->member('sha256')->text();
			}
			self::require_digest($record->before, $journal);
			self::require_digest($record->after, true);
			$owned[$path] = $record;
		}
	}

	/** Keep generated artifacts away from the adapter's private control files. */
	private static function require_artifact_path(string $path): void
	{
		if ($path === '') {
			throw new \RuntimeException('Empty artifact path');
		}
		$part = '';
		for ($index = 0; $index < string_byte_len($path); $index++)
		{
			$byte = string_byte_at($path, $index);
			if ($byte === 47) {
				self::require_component($part);
				$part = '';
			}
			else {
				if (!(($byte >= 48 && $byte <= 57) || ($byte >= 65 && $byte <= 90)
					|| ($byte >= 97 && $byte <= 122) || ($byte === 45) || ($byte === 95) || ($byte === 46))) {
					throw new \RuntimeException('Invalid artifact path character');
				}
				$part .= string_byte_from_int($byte);
			}
		}
		self::require_component($part);
		if (!string_byte_ends_with($path, '.cpp') && !string_byte_ends_with($path, '.hpp')) {
			throw new \RuntimeException('Invalid artifact suffix');
		}
	}

	private static function require_component(string $part): void
	{
		if (string_byte_starts_with($part, '.') || ($part === '')) {
			throw new \RuntimeException('Reserved or empty output component');
		}
	}

	private static function inventory_entry(string $path, string $before, string $after): string
	{
		return '{"path":' . json_quote($path) . ',"before":' . json_quote($before)
			. ',"after":' . json_quote($after) . '}';
	}

	/** Journal and its authorization marker are one atomic file, never two dependent renames. */
	private function mark_pending(string $journal): void
	{
		fs_output_write($this->root, '.scpp-output.status', 'scpp-output-v1:pending:'
			. content_sha256($journal) . "\n" . $journal);
	}

	private static function pending_inventory(string $status): string
	{
		if (!string_byte_starts_with($status, 'scpp-output-v1:pending:') || (string_byte_len($status) < 88)) {
			throw new \RuntimeException('Unrecognized output ownership status');
		}
		$journal = string_byte_slice($status, 88, string_byte_len($status) - 88);
		if ($status !== 'scpp-output-v1:pending:' . content_sha256($journal) . "\n" . $journal) {
			throw new \RuntimeException('Pending output inventory was modified');
		}
		return $journal;
	}

	private static function require_digest(string $digest, bool $allow_empty): void
	{
		if ($allow_empty && ($digest === '')) {
			return;
		}
		if (string_byte_len($digest) !== 64) {
			throw new \RuntimeException('Invalid output digest');
		}
		for ($index = 0; $index < 64; $index++) {
			$byte = string_byte_at($digest, $index);
			if (!(($byte >= 48 && $byte <= 57) || ($byte >= 97 && $byte <= 102))) {
				throw new \RuntimeException('Invalid output digest');
			}
		}
	}
}
