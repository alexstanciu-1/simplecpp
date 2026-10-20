<?php
final class Coalesce_Probe
{
	public static int $keys = 0;
	public static int $fallbacks = 0;
	public static function key(string $name): string { self::$keys++; return $name; }
	public static function fallback(): string { self::$fallbacks++; return 'fallback'; }
	public static function fail_key(): string { throw new \RuntimeException('key error'); }
	public static function fail_fallback(): string { throw new \RuntimeException('fallback error'); }
	public static function method(): string
	{
		$values /** hash<string> */ = [];
		return $values['missing'] ?? 'method';
	}
	public static function other_scope(): string
	{
		$values /** vector<string> */ = ['vector'];
		return $values[0] ?? 'unused';
	}
}
$values /** hash<string> */ = [];
$values['full'] = 'value';
$values['empty'] = '';
$values['zero'] = '0';
echo $values[Coalesce_Probe::key('missing')] ?? Coalesce_Probe::fallback(), "\n";
echo $values[Coalesce_Probe::key('full')] ?? Coalesce_Probe::fail_fallback(), "\n";
echo '[', $values[Coalesce_Probe::key('empty')] ?? Coalesce_Probe::fallback(), "]\n";
echo $values[Coalesce_Probe::key('zero')] ?? Coalesce_Probe::fallback(), "\n";
echo Coalesce_Probe::$keys, ':', Coalesce_Probe::$fallbacks, ':', q_count($values), "\n";
$owned = $values['full'] ?? 'unused';
$values['full'] = 'changed';
echo $owned, ':', $values['full'], "\n";
echo $values['missing'] ?? $values['also_missing'] ?? ('last' . '-fallback'), "\n";
try { echo $values[Coalesce_Probe::fail_key()] ?? Coalesce_Probe::fallback(); }
catch (\RuntimeException $error) { echo $error->getMessage(), "\n"; }
try { echo $values['missing'] ?? Coalesce_Probe::fail_fallback(); }
catch (\RuntimeException $error) { echo $error->getMessage(), "\n"; }
echo Coalesce_Probe::$fallbacks, ':', q_count($values), "\n";
echo Coalesce_Probe::method(), ':', Coalesce_Probe::other_scope(), "\n";
$maybe /** nullable<string> */ = null;
echo $maybe ?? 'nullable', "\n";
$maybe = '';
echo '[', $maybe ?? 'unused', "]\n";
