<?php
namespace scpp\compiler;

/** Output spelling is backend-owned; identities never use object IDs or source positions. */
final class CPP_Artifacts
{
	/** Materialize one assembled group through the same described-artifact protocol. */
	public static function implementation(cpp_implementation_group $group): cpp_artifact
	{
		return self::described($group->identity, cpp_artifact_kind::translation_unit, $group->file_name,
			self::preamble($group->headers, $group->forward_declarations, '') . $group->text, $group->headers);
	}

	/** Preserve the same include set that produced the artifact preamble. */
	public static function described(string $identity, cpp_artifact_kind $kind, string $path,
		string $text, array $headers /** hash<bool> */): cpp_artifact
	{
		$artifact = new cpp_artifact($identity, $kind, $path, $text);
		foreach ($headers as $header => $used) {
			if ($header !== $path) { $artifact->includes[] = $header; }
		}
		$artifact->includes_complete = true;
		return $artifact;
	}

	public static function includes_json(cpp_artifact $artifact): string
	{
		if (!$artifact->includes_complete) { return 'null'; }
		$text = '[';
		$separator = '';
		foreach ($artifact->includes as $header) {
			$text .= $separator . json_quote($header);
			$separator = ',';
		}
		return $text . ']';
	}

	public static function component(string $name): string
	{
		$hex = '0123456789abcdef';
		$result = 'n';
		for ($index = 0; $index < string_byte_len($name); $index++) {
			$byte = string_byte_at($name, $index);
			$result .= string_byte_slice($hex, (int)($byte / 16), 1) . string_byte_slice($hex, $byte % 16, 1);
		}
		return $result;
	}

	public static function callable_header(string $name): string
	{
		return 'callables/' . self::component($name) . '.hpp';
	}

	public static function type_header(string $name): string
	{
		return 'types/' . self::component($name) . '.hpp';
	}

	public static function unit_path(collected_file $source): string
	{
		return 'units/' . self::component(Source_Registry::collection_module($source)) . '/'
			. self::component($source->source_file()->path) . '.cpp';
	}

	public static function includes(array $headers /** hash<bool> */, string $self_header): string
	{
		$text = '';
		foreach ($headers as $header => $used) {
			if ($header !== $self_header) { $text .= '#include "' . $header . '"' . "\n"; }
		}
		return $text;
	}

	/** Complete includes dominate name-only requirements within the same artifact. */
	public static function preamble(array $headers /** hash<bool> */,
		array $declarations /** hash<bool> */, string $self_header): string
	{
		$text = self::includes($headers, $self_header);
		foreach ($declarations as $name => $used)
		{
			$path = self::type_header($name);
			if (($path !== $self_header) && !isset($headers[$path])) {
				$text .= 'struct ' . CPP_Generator::source_name('record', $name) . ";\n";
			}
		}
		return $text;
	}
}
