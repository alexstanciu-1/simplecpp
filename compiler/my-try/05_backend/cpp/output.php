<?php

/* Output transport consumes final artifacts, never syntax or preparation workers. */
namespace scpp\compiler;

final class CPP_Output
{
	public static function artifact(Storage $artifacts /** Storage<cpp_artifact> */, string $identity): cpp_artifact
	{
		foreach ($artifacts as $artifact) {
			if ($artifact->identity === $identity) { return $artifact; }
		}
		throw new \LogicException('Missing C++ artifact: ' . $identity);
	}
	/** Versioned transport for CLI/native consumers; emit every artifact, not index zero. */
	public static function encode(Storage $artifacts /** Storage<cpp_artifact> */): string
	{
		$identities /** hash<bool> */ = [];
		$paths /** hash<bool> */ = [];
		$rows = '';
		$separator = '';
		foreach ($artifacts as $artifact)
		{
			if (isset($identities[$artifact->identity]) || isset($paths[$artifact->file_name])) {
				throw new \LogicException('Duplicate C++ artifact identity or path');
			}
			$identities[$artifact->identity] = true;
			$paths[$artifact->file_name] = true;
			$rows .= $separator . '{"id":' . json_quote($artifact->identity)
				. ',"kind":' . json_quote($artifact->kind_name())
				. ',"path":' . json_quote($artifact->file_name)
				. ',"text":' . json_quote($artifact->text)
				. ',"includes":' . CPP_Artifacts::includes_json($artifact) . '}';
			$separator = ',';
		}
		return '{"format":"scpp-cpp-artifacts-v1","artifacts":[' . $rows . "]}\n";
	}

	/** Explicit adapter for existing single-unit source probes, never a flattening renderer. */
	public static function single_unit(Storage $artifacts /** Storage<cpp_artifact> */): cpp_artifact
	{
		if (q_count($artifacts) !== 1) {
			throw new \LogicException('Single-unit inspection requires exactly one C++ artifact');
		}
		$artifact = $artifacts[0];
		if ($artifact->kind() !== cpp_artifact_kind::translation_unit) {
			throw new \LogicException('Single-unit inspection requires a C++ translation unit');
		}
		return $artifact;
	}
}
