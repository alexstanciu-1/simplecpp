<?php
namespace scpp\compiler;

enum await_operation { case suspend; case synchronous_wait; }

final class prepared_await extends prepared_expression
{
	public canonical_type_use $task_type;
	public await_operation $operation = await_operation::suspend;
}

final class prepared_async_call extends prepared_compiler_call
{
	public ?prepared_await $awaited = null;
	public function __construct(private compiler_function_kind $operation) {}
	public function kind(): compiler_function_kind { return $this->operation; }
}
