<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Existing void return identity; no value literals or variable storage are admitted by preparation. */
final class type_void extends type_definition
{
	private static ?self $instance = null;

	private function __construct()
	{
		parent::__construct('void');
	}

	public function cpp_type(): string
	{
		return 'void';
	}

	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
