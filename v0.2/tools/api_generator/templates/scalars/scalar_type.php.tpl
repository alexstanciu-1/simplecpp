<?php

declare(strict_types=1);
namespace scpp\compiler;

// Generated from runtime metadata and validated target facts. Do not edit.
/** Canonical @name@ definition; literal facts belong to source AST nodes, not this shared identity. */
final class type_@class@ extends type_definition@index_interface@
{
@limits@	/** STATIC PROPERTIES */
	private static ?self $instance = null;

	/** CONSTRUCTOR */
	private function __construct()
	{
@constructor_checks@		parent::__construct('@name@');
	}

	/** METHODS */
	public function accepts_name(string $name): bool
	{
		return @accept_names@;
	}

	public function cpp_type(): string
	{
		return @representation@;
	}

@literal_methods@
@conversion_methods@
	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
