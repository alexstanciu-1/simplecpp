<?php
declare(strict_types=1);

/*
 * Role: Translate prepared representations into LLVM spelling.
 * Used by: Backend probes and LLVM emission
 * Call map:
 *   LLVM_Types::parameters(); storage(); scalar()
 *     -> [action] format supported backend primitives
 */

namespace prepare_backend;

/**
 * @compiler-api Shared LLVM syntax helpers for backend preparation and emission.
 * Stateless/read-only; accepts resolved representation or target facts. This is
 * intentional backend coupling, not source name lookup or language compatibility.
 * All returned text is newly produced; no store, cache or input is mutated.
 */
class LLVM_Types
{
    /** @compiler-internal Physical address count for implemented implicit lifecycle roles; all return void. */
    public static function lifecycle_arity(\type_model\lifecycle_operation_kind $kind): int
    {
        return $kind->has_source() ? 2 : 1;
    }

    /**
     * @compiler-api Translate supported scalar/void shape to its LLVM type spelling.
     * @throws \LogicException Unsupported representation; no fallback type.
     */
    public static function scalar(\type_model\representation_record $shape): string
    {
        return match ($shape->kind)
        {
            \type_model\representation_kind::void_type => 'void',
            \type_model\representation_kind::integer => 'i' . $shape->payload->bit_width,
            \type_model\representation_kind::floating_point => match ($shape->payload->format) {
                \type_model\floating_format::ieee_binary16 => 'half',
                \type_model\floating_format::bfloat16 => 'bfloat',
                \type_model\floating_format::ieee_binary32 => 'float',
                \type_model\floating_format::ieee_binary64 => 'double',
                \type_model\floating_format::ieee_binary128 => 'fp128',
            },
            default => throw new \LogicException('Unsupported LLVM scalar representation'),
        };
    }

    /**
     * @compiler-api LLVM spelling for scalar or opaque inline storage; excludes void.
     * @throws \LogicException Unsupported or valueless storage representation.
     */
    public static function storage(\type_model\representation_record $shape): string
    {
        if ($shape->kind === \type_model\representation_kind::opaque_inline) {
            return '[' . $shape->payload->size_bytes . ' x i8]';
        }
        $type = self::scalar($shape);
        if ($type === 'void') {
            throw new \LogicException('Void has no LLVM storage');
        }
        return $type;
    }

    /** Spell compound storage from canonical constituent types; LLVM owns target padding and stride. */
    public static function compound(Layout_Input $types, int $id): string
    {
        $shape = $types->representation_for_type($id);
        if ($shape->kind === \type_model\representation_kind::fixed_array) {
            return '[' . $shape->payload->count . ' x ' . self::compound($types, $shape->payload->element_type) . ']';
        }
        if ($shape->kind === \type_model\representation_kind::structure)
        {
            $fields = [];
            for ($index = 0; $index < $shape->payload->count; ++$index) {
                $fields[] = self::compound($types, $types->field_for($id, $index)->type_id);
            }
            return '{ ' . implode(', ', $fields) . ' }';
        }
        return self::storage($shape);
    }

    /**
     * @compiler-api Read the stack address space from the prepared target layout.
     * LLVM DataLayout's A entry owns this fact; omission means address space zero.
     */
    public static function alloca_address_space(backend_configuration $configuration): int
    {
        foreach (explode('-', $configuration->data_layout) as $entry) {
            if (preg_match('/^A([0-9]+)$/D', $entry, $match) === 1) {
                return (int)$match[1];
            }
        }
        return 0;
    }

    /** @compiler-api Shared LLVM integer conversion spelling for body instructions and native-entry adaptation. */
    public static function integer_conversion(integer_adaptation $operation, int $source_bits, int $destination_bits, string $operand): string
    {
        $valid = match ($operation) {
            integer_adaptation::sign_extend, integer_adaptation::zero_extend => $source_bits < $destination_bits,
            integer_adaptation::truncate => $source_bits > $destination_bits,
            integer_adaptation::identity => false,
        };
        if ((!$valid) || ($source_bits <= 0) || ($destination_bits <= 0)) {
            throw new \LogicException('Invalid LLVM integer conversion widths');
        }
        return $operation->value . ' i' . $source_bits . ' ' . $operand . ' to i' . $destination_bits;
    }

    /** @compiler-internal Normalize a semantic binding once into its shared physical ABI target. */
    public static function call_target(callable_binding $binding): abi_target
    {
        $parameters = $binding->result_passing === \type_model\result_passing::caller_storage ? [new abi_parameter('ptr')] : [];
        foreach ($binding->parameters as $parameter)
        {
            if ($parameter->span !== null) {
                $parameters[] = new abi_parameter('ptr');
                $parameters[] = new abi_parameter('i' . $parameter->span->length->bits, $parameter->span->length->extension);
            }
            else {
                $parameters[] = new abi_parameter(self::parameter_type($parameter), $parameter->extension);
            }
        }
        return new abi_target($binding->link_name, $binding->calling_convention, self::return_type($binding),
            $parameters, $binding->return_extension);
    }

    /** @compiler-api Parameter spellings shared by definitions and cross-file declarations. */
    public static function parameters(callable_binding $binding, bool $named): string
    {
        $parts = [];
        foreach ($binding->abi->parameters as $index => $parameter) {
            $parts[] = $parameter->type . self::attribute($parameter->extension) . ($named ? ' %p' . ($index + 1) : '');
        }
        return implode(', ', $parts);
    }

    /** @compiler-api ABI parameter spelling; the semantic object type is retained in its binding. */
    public static function parameter_type(callable_parameter $parameter): string
    {
        return $parameter->passing->is_borrow()
            ? 'ptr' : self::scalar($parameter->definition->representation);
    }

    /** @compiler-api ABI result spelling; construction writes the hidden destination parameter. */
    public static function return_type(callable_binding $binding): string
    {
        return $binding->result_passing === \type_model\result_passing::caller_storage
            ? 'void' : self::scalar($binding->return_definition->representation);
    }

    /** @compiler-api Prepared integer ABI extension; empty for ordinary language calls. */
    public static function attribute(\type_model\abi_extension $extension): string
    {
        return $extension === \type_model\abi_extension::none ? '' : ' ' . $extension->value;
    }

    /** @compiler-api Produce a quoted LLVM string/identifier with escaped bytes. */
    public static function quote(string $text): string
    {
        return '"' . preg_replace_callback('/[^\x20-\x21\x23-\x5b\x5d-\x7e]/',
            static fn($match) => '\\' . strtoupper(bin2hex($match[0])), $text) . '"';
    }
}
