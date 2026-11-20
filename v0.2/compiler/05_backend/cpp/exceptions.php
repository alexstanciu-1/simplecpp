<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Projects the active runtime transport to one prepared source owner without copying the payload. */
final class Cpp_Exception_Catch
{
	/** Match the payload dynamically, retaining its control block only on success. */
	public static function request(type_exception $source, shared_record_type_definition $target): cpp_inline_adapter
	{
		$payload = $target->payload;
		$body = "\t" . $target->cpp_type() . " result;\n"
			. "\tauto* view = dynamic_cast<" . $payload->cpp_type() . "*>(source.view);\n"
			. "\tif (view) {\n\t\tsource.ptr->retain_strong();\n\t\tresult.ptr = source.ptr;\n\t\tresult.view = view;\n\t}\n"
			. "\treturn result;\n";
		return new cpp_inline_adapter('exception_catch', [new cpp_adapter_parameter($source, 'source', cpp_adapter_mode::const_reference)],
			$target, $body, true, body_types: [$payload], requires_rtti: true);
	}
}
