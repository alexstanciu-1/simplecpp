<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

use Scpp\S2S\Frontend\FrontendCallSurfaceInterface;

final class JssCallSurface implements FrontendCallSurfaceInterface
{
	/** @var array<string,array{target:string,return_type:string}> */
	private const HELPER_CALLS = [
		'fs.get' => ['target' => 'fs_get', 'return_type' => 'result<string>'],
		'fs.put' => ['target' => 'fs_put', 'return_type' => 'result<int>'],
		'fs.mkdir' => ['target' => 'fs_mkdir', 'return_type' => 'bool'],
		'fs.scan' => ['target' => 'fs_scan', 'return_type' => 'result<vector<string>>'],
		'fs.size' => ['target' => 'fs_size', 'return_type' => 'result<int>'],
		'fs.mtime' => ['target' => 'fs_mtime', 'return_type' => 'result<int>'],
		'fs.touch' => ['target' => 'fs_touch', 'return_type' => 'bool'],
		'fs.rmdir' => ['target' => 'fs_rmdir', 'return_type' => 'bool'],
		'fs.remove' => ['target' => 'fs_remove', 'return_type' => 'bool'],
		'fs.copy' => ['target' => 'fs_copy', 'return_type' => 'bool'],
		'fs.rename' => ['target' => 'fs_rename', 'return_type' => 'bool'],
		'fs.realpath' => ['target' => 'fs_realpath', 'return_type' => 'result<string>'],
		'fs.exists' => ['target' => 'fs_exists', 'return_type' => 'bool'],
		'fs.is_dir' => ['target' => 'fs_is_dir', 'return_type' => 'bool'],
		'fs.is_file' => ['target' => 'fs_is_file', 'return_type' => 'bool'],
		'fs.is_link' => ['target' => 'fs_is_link', 'return_type' => 'bool'],
		'fs.basename' => ['target' => 'fs_basename', 'return_type' => 'string'],
		'fs.dirname' => ['target' => 'fs_dirname', 'return_type' => 'string'],
		'io.open' => ['target' => 'io_open', 'return_type' => 'result_or_false<resource_handle>'],
		'io.seek' => ['target' => 'io_seek', 'return_type' => 'nullable<int>'],
		'io.tell' => ['target' => 'io_tell', 'return_type' => 'result_or_false<int>'],
		'io.read_line' => ['target' => 'io_read_line', 'return_type' => 'result_or_false<string>'],
		'io.read' => ['target' => 'io_read', 'return_type' => 'result_or_false<string>'],
		'io.write' => ['target' => 'io_write', 'return_type' => 'result_or_false<int>'],
		'io.rewind' => ['target' => 'io_rewind', 'return_type' => 'bool'],
		'io.flush' => ['target' => 'io_flush', 'return_type' => 'bool'],
		'io.close' => ['target' => 'io_close', 'return_type' => 'bool'],
		'io.eof' => ['target' => 'io_eof', 'return_type' => 'bool'],
		'json.decode' => ['target' => 'json_decode', 'return_type' => 'dynamic'],
		'json.encode' => ['target' => 'json_encode', 'return_type' => 'string'],
		'dt.parse' => ['target' => 'dt_parse', 'return_type' => 'result<int>'],
		'dt.format' => ['target' => 'dt_format', 'return_type' => 'string'],
		'dt.parse_iso_utc' => ['target' => 'dt_parse_iso_utc', 'return_type' => 'result<int>'],
		'dt.format_iso_utc' => ['target' => 'dt_format_iso_utc', 'return_type' => 'string'],
		'dt.format_now' => ['target' => 'dt_format_now', 'return_type' => 'string'],
	];

	public function resolveNormalizedCallTarget(array $chain): ?string
	{
		$key = strtolower(implode('.', $chain));
		return self::HELPER_CALLS[$key]['target'] ?? null;
	}

	public function resolveCallReturnType(array $chain): ?string
	{
		$key = strtolower(implode('.', $chain));
		return self::HELPER_CALLS[$key]['return_type'] ?? null;
	}
}
