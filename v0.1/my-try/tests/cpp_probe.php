<?php
namespace scpp\compiler;

/** Editable test transport. Boundaries survive instrumentation; the runner compiles separate units. */
final class CPP_Probe
{
	/** Textual assertions only; never used as a compilable translation unit. */
	public static function content(Storage $artifacts /** Storage<cpp_artifact> */): string
	{
		$text = '';
		foreach ($artifacts as $artifact) { $text .= $artifact->text; }
		return $text;
	}
	public static function text(Storage $artifacts /** Storage<cpp_artifact> */): string
	{
		if (q_count($artifacts) === 1) { return CPP_Output::single_unit($artifacts)->text; }
		$text = '';
		foreach ($artifacts as $artifact) {
			$text .= '// SCPP-ARTIFACT {"id":' . json_quote($artifact->identity)
				. ',"kind":' . json_quote($artifact->kind_name()) . ',"path":' . json_quote($artifact->file_name)
				. "}\n" . $artifact->text . "// SCPP-END-ARTIFACT\n";
		}
		return $text;
	}
}
