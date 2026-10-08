<?php
namespace scpp\compiler;

/** Invocation-local handoff; the coordinator owns materialization, not analysis. */
final class callable_instance_request
{
	public function __construct(public collected_method $origin, public collected_record $called_type)
	{
	}
}
