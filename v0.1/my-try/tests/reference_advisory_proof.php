<?php
namespace scpp\compiler;

/** Portable retained-diagnostic proof, executed by both PHP and the native compiler. */
final class Reference_Advisory_Proof
{
	public static function run(): string
	{
		Compiler_Lifecycle::reset();
		$owner = new module('/advisories', '/advisories', '/advisories');
		$input_file = new file(); $input_file->path = 'main.phs';
		$original = 'function &identity(int &$value): int { return $value; } echo 1;';
		$input_file->content = $original;
		$record = Source_Registry::add($owner, $input_file);
		$modules /** Keyed_Storage<module> */ = Model::$modules;
		$modules->add($owner->name, $owner);
		$compiler = new Compiler(); $compiler->exec_cpp();
		$first = self::check($compiler, 1, 1);
		$unchanged /** vector<string> */ = [];
		$compiler->update_cpp($unchanged);
		if (self::check($compiler, 1, 1) !== $first) { throw new \LogicException('No-op advisory changed'); }
		$changes /** vector<string> */ = ['/advisories/main.phs'];
		$record->file->content = "\n\n" . $original; $compiler->update_cpp($changes);
		$moved = self::check($compiler, 1, 3);
		$record->file->content = 'echo 1;'; $compiler->update_cpp($changes); self::check($compiler, 0, 0);
		$record->file->content = 'function &bad():int { $value=1; return $value; }';
		$failed = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $expected) { $failed = true; }
		if (!$failed) { throw new \LogicException('Invalid return accepted'); }
		self::check($compiler, 0, 0);
		$record->file->content = $original; $compiler->update_cpp($changes);
		if (self::check($compiler, 1, 1) !== $first) { throw new \LogicException('Repaired advisory changed'); }
		Compiler_Lifecycle::reset_preparation(); $compiler->prepare(true); $compiler->cpp();
		if (self::check($compiler, 1, 1) !== $first) { throw new \LogicException('Reset advisory changed'); }
		return $first . $moved . "Reference advisory parity passed\n";
	}

	private static function check(Compiler $compiler, int $count, int $line): string
	{
		$records /** Storage<compiler_diagnostic> */ = $compiler->diagnostics();
		if ($records->count() !== $count) { throw new \LogicException('Unexpected advisory count'); }
		foreach ($records as $record) {
			if (($record->code !== 'REF001') || ($record->severity !== 'warning') || ($record->line !== $line)) {
				throw new \LogicException('Incorrect advisory record: ' . Diagnostic_Reporting::text($records));
			}
		}
		return Diagnostic_Reporting::text($records);
	}
}
