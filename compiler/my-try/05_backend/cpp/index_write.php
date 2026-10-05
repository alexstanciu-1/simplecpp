<?php

/* Sequenced wrapper mutation; no interior reference survives key/RHS evaluation. */
namespace scpp\compiler;

final class CPP_Index_Write
{
	/** The same lowering serves every registered family; runtime overloads own storage details. */
	public static function generate(assignment_expression_node $node, prepared_index_assignment $facts,
		cpp_generation_context $context, bool $value_required = true): string
	{
		$target = object_cast($node->target, index_node::class);
		$emitter = new CPP_Syntax($context);
		$context->headers['scpp/index_write.hpp'] = true;
		$id = $context->next_temporary;
		$context->next_temporary++;
		$receiver = 'write_receiver_' . $id;
		$key = 'write_key_' . $id;
		$value = 'write_value_' . $id;
		$type = CPP_Declarations::type($facts->type, $context);
		$text = '{ auto& ' . $receiver . ' = ' . $emitter->expression($target->base) . '; ';
		$helper = 'scpp::index_append';
		$arguments = $receiver . ', ';
		if ($facts->decision->operation === operator_operation::index_store)
		{
			$key_node /** expression_node */ = $target->index;
			$conversion = $facts->decision->operands[1];
			$text .= CPP_Declarations::type($conversion->target_type, $context) . ' ' . $key . ' = '
				. CPP_Declarations::conversion($emitter->expression($key_node), $conversion, $context) . '; ';
			$text .= 'scpp::require_write_key(' . $key . '); ';
			$helper = 'scpp::index_store';
			$arguments .= $key . ', ';
		}
		elseif ($facts->decision->operation !== operator_operation::index_append) {
			throw new \LogicException('Unsupported prepared indexed write');
		}
		$text .= $type . ' ' . $value . ' = '
			. CPP_Declarations::conversion($emitter->expression($node->value), $facts->value_conversion, $context) . '; ';
		$text .= $helper . '(' . $arguments . $value . '); ';
		if (!$value_required) {
			return $text . '}';
		}
		return '([&]() -> ' . $type . ' ' . $text . 'return ' . $value . '; }())';
	}
}
