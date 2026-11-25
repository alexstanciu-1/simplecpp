<?php
declare(strict_types=1);
namespace runtime_preparation\project;

use runtime_preparation as native;

/** Generate a real C++ payload object whose special members call complete compiler operations. */
final class Adapter
{
    /** Reject incomplete profiles and unsupported physical/effect contracts before generating native code. */
    public static function validate(source_type $source): void
    {
        $roles = ['default_construct', 'copy_construct', 'move_construct', 'copy_assign', 'move_assign', 'destroy'];
        if (($source->profile !== source_type::PROFILE) || ($source->project === '') || ($source->key === '') || ($source->size < 1) || ($source->alignment < 1)
            || (($source->alignment & ($source->alignment - 1)) !== 0) || (($source->size % $source->alignment) !== 0)
            || (array_keys($source->states) !== $roles) || (array_keys($source->operations) !== $roles)) {
            throw new \RuntimeException('Invalid source payload contract');
        }
        foreach ($source->states as $role => $state)
        {
            $operation = $source->operations[$role];
            if (!in_array($state, ['available', 'forbidden', 'unsupported'], true)
                || (($state === 'available') !== ($operation !== null))) {
                throw new \RuntimeException('Incomplete source payload capability');
            }
            if ($operation === null) {
                continue;
            }
            if (in_array($role, ['move_construct', 'move_assign'], true)) {
                throw new \RuntimeException('Source payload movement is not implemented');
            }
            $count = in_array($role, ['default_construct', 'destroy'], true) ? 1 : 2;
            $expected = ['calling_convention' => 'ccc', 'return_type' => 'void',
                'parameters' => array_fill(0, $count, ['type' => 'ptr', 'extension' => '']), 'return_extension' => ''];
            if (($operation->role !== $role) || ($operation->abi !== $expected)
                || (($operation->semantics['failure'] ?? null) !== 'terminate')
                || (($operation->semantics['unwind'] ?? null) !== 'none')
                || (($operation->semantics['payload_escape'] ?? null) !== 'call_scoped')
                || !preg_match('/^scpp_source_[A-Za-z0-9_]+$/D', $operation->symbol)) {
                throw new \RuntimeException('Unsupported source import ABI or effects');
            }
        }
    }

    /** Rejoin a source argument recipe with its exact portable owner before package-local row allocation. */
    public static function argument(native\native_type $argument): void
    {
        $source = $argument->sources[$argument->definition['source_payload']] ?? null;
        if ($source === null) {
            throw new \RuntimeException('Source argument has no payload contract');
        }
        self::validate($source);
        $name = self::name($source);
        $baseline = ($source->states['copy_construct'] === 'available') && ($source->states['copy_assign'] === 'available')
            && ($source->states['destroy'] === 'available');
        if (($argument->identity->provider !== native\Symbols::name(['source', $source->project]))
            || ($argument->identity->id !== $source->key) || ($argument->baseline !== $baseline)
            || ($argument->definition['cpp_name'] !== $name) || (($argument->declarations[$name] ?? null) !== self::declaration($source))
            || ($argument->contract !== json_encode($source, JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('Source argument identity, declaration or contract mismatch');
        }
    }

    public static function name(source_type $source): string
    {
        return native\Symbols::name(['source_adapter', source_type::PROFILE, $source->key]);
    }

    /** No source fields or source algorithm appear here: only aligned payload and forwarding special members. */
    public static function declaration(source_type $source): string
    {
        self::validate($source);
        $name = self::name($source);
        $text = '';
        foreach ($source->operations as $operation)
        {
            if ($operation !== null) {
                $parameters = count($operation->abi['parameters']) === 1 ? 'void *' : 'void *, const void *';
                $text .= 'extern "C" void ' . $operation->symbol . '(' . $parameters . ") noexcept;\n";
            }
        }
        $text .= 'struct alignas(' . $source->alignment . ') ' . $name . " {\n"
            . '    unsigned char payload[' . $source->size . "];\n"
            . "    struct copy_payload_tag {};\n";
        $bodies = [
            'default_construct' => [$name . '()', 'payload'],
            'copy_construct' => [$name . '(const ' . $name . ' &source)', 'payload, source.payload'],
            'copy_assign' => [$name . ' &operator=(const ' . $name . ' &source)', 'payload, source.payload'],
            'destroy' => ['~' . $name . '()', 'payload'],
        ];
        foreach ($bodies as $role => [$declaration, $arguments])
        {
            $operation = $source->operations[$role];
            $text .= '    ' . $declaration . ' noexcept';
            $text .= $operation === null ? " = delete;\n" : ' { ' . $operation->symbol . '(' . $arguments . ');'
                . ($role === 'copy_assign' ? ' return *this;' : '') . " }\n";
        }
        $text .= '    ' . $name . '(' . $name . " &&) = delete;\n"
            . '    ' . $name . ' &operator=(' . $name . " &&) = delete;\n";
        $copy = $source->operations['copy_construct'];
        $text .= '    ' . $name . '(copy_payload_tag, const void *source) noexcept'
            . ($copy === null ? " = delete;\n" : ' { ' . $copy->symbol . "(payload, source); }\n");
        $text .= "};\nstatic_assert(sizeof(" . $name . ') == ' . $source->size . ");\n"
            . 'static_assert(alignof(' . $name . ') == ' . $source->alignment . ");\n"
            . 'static_assert(sizeof(' . $name . '[2]) == ' . (2 * $source->size) . ");\n"
            . 'static_assert(offsetof(' . $name . ", payload) == 0);\n";
        return $text;
    }
    /** Begin a real adapter lifetime from borrowed source payload; never reinterpret the source as an adapter. */
    public static function copy_in(source_type $source, string $input, string $local): string
    {
        if (($source->operations['copy_construct'] ?? null) === null) {
            throw new \RuntimeException('Source payload cannot be copied');
        }
        $cpp = self::name($source);
        return '        ' . $cpp . ' ' . $local . '(typename ' . $cpp . '::copy_payload_tag{}, ' . $input . ");\n";
    }

    /** Copy a returned adapter into source caller storage; its temporary then runs its native destructor. */
    public static function copy_out(source_type $source, string $call): string
    {
        $copy = $source->operations['copy_construct'] ?? throw new \RuntimeException('Source result cannot be copied');
        return '        auto native_result = ' . $call . ";\n"
            . '        ' . $copy->symbol . '(result, native_result.payload);';
    }
}
