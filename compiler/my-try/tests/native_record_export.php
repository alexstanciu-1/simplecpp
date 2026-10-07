<?php
namespace scpp\compiler;

/** Host-only fixture export; normal tests keep their existing transport unchanged. */
function native_record_export(): string
{
	$manifest = getenv('SCPP_RECORD_EXPORT');
	if ($manifest !== false && $manifest !== '') {
		$sources = [];
		foreach (Model::sources() as $source) {
			$input = $source->file;
			$sources[$source->path] = $input->disk_source
				? file_get_contents($source->owning_module()->resolved_path . '/' . $source->path)
				: $input->content;
		}
		file_put_contents($manifest, json_encode(['sources' => $sources,
			'expected' => CPP_Output::encode(Model::$cpp_files)]) . "\n", FILE_APPEND);
	}
	return CPP_Probe::text(Model::$cpp_files);
}
