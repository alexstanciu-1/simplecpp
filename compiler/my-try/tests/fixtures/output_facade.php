<?php
final class Output_Facade_Proof
{
	public static function run(string $directory): void
	{
		$root = fs_output_root($directory);
		$bytes = 'abc' . string_byte_from_int(0) . 'def';
		$absent = fs_output_read($root, 'value.cpp');
		if ($absent !== null) { throw new \RuntimeException('Expected missing file'); }
		if (!fs_output_write($root, 'value.cpp', $bytes)) { throw new \RuntimeException('Missing changed result'); }
		if (fs_output_write($root, 'value.cpp', $bytes)) { throw new \RuntimeException('Missing no-op result'); }
		$observed = fs_output_read($root, 'value.cpp');
		if ($observed === null) { throw new \RuntimeException('Missing published bytes'); }
		if ($observed !== $bytes) { throw new \RuntimeException('Changed binary bytes'); }
		$rejected = false;
		try { fs_output_remove($root, 'value.cpp', 'wrong'); }
		catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \RuntimeException('Expected checked removal error'); }
		if (!fs_output_remove($root, 'value.cpp', $bytes)) { throw new \RuntimeException('Missing removal result'); }
		if (fs_output_remove($root, 'value.cpp', $bytes)) { throw new \RuntimeException('Missing absent result'); }
		echo content_sha256($bytes);
	}
}
