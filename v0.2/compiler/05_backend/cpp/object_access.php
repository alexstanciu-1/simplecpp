<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Concrete shared-object cast/test patterns; admission is already settled and ownership stays with the existing control block. */
final class Cpp_Object_Access
{
	/** Casts transfer an owning parameter; tests only inspect a handle whose argument lifetime covers the call. */
	public static function request(shared_record_type_definition $source, shared_record_type_definition $target, bool $test, bool $dynamic, bool $borrow = false): cpp_inline_adapter
	{
		$pointer = $dynamic ? 'dynamic_cast<' . $target->payload->cpp_type() . '*>(source.view)' : 'source.view';
		$body = "\t" . $target->payload->cpp_type() . '* view = ' . $pointer . ";\n";
		if ($borrow) $body .= "\treturn view;\n";
		elseif ($test) $body .= "\treturn view != nullptr;\n";
		else {
			$body .= "\tif (view == nullptr) ::scpp::throw_invalid_object_cast();\n"
				. "\t" . $target->cpp_type() . " result;\n\tresult.ptr = source.ptr;\n\tresult.view = view;\n"
				. "\tsource.ptr = nullptr;\n\tsource.view = nullptr;\n\treturn result;\n";
		}
		return new cpp_inline_adapter($test ? 'shared_type_test' : 'shared_checked_cast',
			[new cpp_adapter_parameter($source, 'source', $test ? cpp_adapter_mode::const_reference : cpp_adapter_mode::value)], $borrow ? $target->payload : ($test ? type_bool::instance() : $target),
			$body, $test, headers: ['scpp/owner_access.hpp'], body_types: [$target], requires_rtti: $dynamic, pointer_result: $borrow);
	}
}
