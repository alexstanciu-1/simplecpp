<?php
declare(strict_types=1);

/*
 * Role: Own per-function text, operands and emission counters.
 * Used by: LLVM_Emitter::run()
 * Call map:
 *   Emission_Worker::emit()
 *     -> emit_instruction() [each instruction]; emit_terminator() [each block]
 */

namespace emit_llvm;

use prepare_backend\LLVM_Types;
use lower\Lowered_Body;

/** @compiler-internal One fixed lowered callable, private operands/text and no tool or session writes. */
class Emission_Worker
{
    use Instruction_Emission;
    use Location_Emission;
    use Call_Emission;
    use Storage_Emission;
    use Terminator_Emission;

    private string $ir = '';
    private string $constants = '';
    private string $return_type;
    private string $pointer;

    /** @var array<int, string|array{string, int}> Lowered value ID to its defined LLVM operand. */
    private array $operands = [];

    /** @var array<string, \prepare_backend\abi_target> Distinct referenced callable contracts. */
    private array $references = [];
    private int $next_parameter = 1;
    private int $next_argument = 0;
    private int $next_address = 0;
    /** Begin/end must pair inside one block; the selected element operation runs between them. */
    private ?\lower\storage_transition $storage_transition = null;
    /** @var array<int, array{int, string}> Prepared address ID -> pointee type and pointer operand. */
    private array $addresses = [];

    public function __construct(private readonly Lowered_Body $body)
    {
    }

    /** @compiler-internal Emit once for this callable; external callers use LLVM_Emitter::run(). */
    public function emit(): Emitted_Function
    {
        $body = $this->body;
        if (($body->entry_block_id !== 1) || ($body->blocks === [])) {
            throw new \LogicException('LLVM emission requires a first entry block');
        }
        $binding = $body->binding;
        $this->return_type = LLVM_Types::return_type($binding);
        $this->ir = 'define ' . $binding->linkage . ' ' . $binding->calling_convention . ' ' . $this->return_type
            . ' @' . LLVM_Types::quote($binding->link_name) . '(' . LLVM_Types::parameters($binding, true) . ") #0 {\n";
        $address_space = LLVM_Types::alloca_address_space($binding->configuration);
        $this->pointer = 'ptr addrspace(' . $address_space . ')';

        // Allocate local storage before emitting the prepared operations for this block.
        foreach ($body->blocks as $block_index => $block)
        {
            $this->ir .= 'b' . ($block_index + 1) . ":\n";
            foreach (($block_index === 0 ? $body->slots : []) as $slot_index => $slot)
            {
                if (($slot->incoming_parameter !== 0) || ($slot->incoming_result)) {
                    $this->slot_operand($slot_index + 1);
                    continue;
                }
                $shape = $body->definition_for($slot->type_id)->representation;
                $type = $this->storage_type($slot->type_id);
                $alignment = $shape->kind === \type_model\representation_kind::opaque_inline
                    ? ', align ' . $shape->payload->alignment_bytes
                    : (isset($body->backend_context()->layouts[$slot->type_id]) ? ', align ' . $body->backend_context()->layouts[$slot->type_id]->alignment : '');
                $this->ir .= '  %s' . ($slot_index + 1) . ' = alloca ' . $type . $alignment . ', addrspace(' . $address_space . ")\n";
            }
            if (($block->instruction_start < 0) || ($block->instruction_count < 0)
                || (($block->instruction_start + $block->instruction_count) > count($body->instructions))) {
                throw new \LogicException('Invalid LLVM block instruction range');
            }

            // Track each lowered value's LLVM operand as instructions define and consume it.
            for ($instruction_index = $block->instruction_start; $instruction_index < ($block->instruction_start + $block->instruction_count); ++$instruction_index) {
                $this->emit_instruction($body->instructions[$instruction_index]);
            }

            if ($this->storage_transition !== null) {
                throw new \LogicException('Unfinished storage transition at block exit');
            }

            // Emit the block ending already selected by lowering.
            $this->emit_terminator($block->terminator, $block_index + 1);
        }
        if (($this->next_parameter !== (count($binding->parameters) + 1)) || ($this->next_argument !== count($body->arguments))) {
            throw new \LogicException('Incomplete LLVM parameter or argument coverage');
        }
        return new Emitted_Function($body, $this->constants . $this->ir . "}\n", $this->references);
    }

}
