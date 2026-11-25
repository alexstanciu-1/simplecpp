<?php
namespace scpp\compiler;

/** Parked host-only LLVM state and orchestration; excluded from the C++ bootstrap. */
final class LLVM_Prototype
{
	public static Storage $files /** Storage<llvm_module> */;
	public static function reset(): void
	{
		self::$files = new Storage /** Storage<llvm_module> */();
	}
	public static function emit(): void
	{
		self::reset();
		$policy = new llvm_policy();
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::collected_files() as $source) {
			if ($source->source_file()->changes !== \scpp\compiler\SYNC_DELETED) {
				$sources->append($source);
			}
		}
		$prepared_files = (new LLVM_Preparation())->prepare_program($sources, $policy);
		self::$files = (new LLVM_Generator())->generate($prepared_files, $policy);
	}
}
