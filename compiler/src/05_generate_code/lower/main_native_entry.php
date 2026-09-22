<?php
declare(strict_types=1);

/*
 * Role: Native_Entry phase; one instance per update.
 * Used by: Compiler_Session / Phases
 * Call map (ordered lifecycle):
 *   init() -> [action] validate callable and native result width
 *   run() -> prepare_plan() [reuse or adapt integer result]
 *   finalize() -> [action] complete native_entry_plan
 * Output: result() returns native_entry_plan after finalize().
 */

namespace lower;

use prepare_backend\callable_binding;
use prepare_backend\integer_adaptation;

/** @compiler-api One phase over fixed inputs; init/run/finalize follow the shared Step contract. */
final class Native_Entry implements \compile\Step, \compile\Runnable_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private native_entry_plan $output;
    public const LINK_NAME = 'main';
    public const CALLING_CONVENTION = 'ccc';

    private native_entry_plan $candidate;

    public function __construct(
        private readonly callable_binding $entry,
        private readonly int $native_bits,
        private readonly ?native_entry_plan $previous
    )
    {
    }

    /** Validate a parameterless integer entry and the verified native result width. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            if ($this->entry->signature->count !== 0) {
                throw new \LogicException('Hosted entry must be parameterless');
            }
            $definition = $this->entry->return_definition;
            if (($this->native_bits < 1) || ($definition->representation->kind !== \type_model\representation_kind::integer)) {
                throw new \LogicException('Hosted entry requires an integer language result and a verified native integer width');
            }
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Prepare or reuse the native entry adaptation from fixed callable and target inputs. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try {
            $this->candidate = $this->prepare_plan();
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Expose the completed native entry plan. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        try {
            $this->output = $this->candidate;
            $this->state = \compile\step_status::finished;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    public function result(): native_entry_plan
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    public function status(): \compile\step_status
    {
        return $this->state;
    }

    public function supports_run(): bool
    {
        return true;
    }

    /** Choose the explicit integer status adaptation and reuse a plan only for the same binding and target width. */
    private function prepare_plan(): native_entry_plan
    {
        $definition = $this->entry->return_definition;
        $bits = $definition->representation->payload->bit_width;

        // Explicit status policy: retain low bits on narrowing; preserve the
        // source integer value on widening. This does not authorize source casts.
        $conversion = $bits === $this->native_bits ? integer_adaptation::identity
            : ($bits > $this->native_bits ? integer_adaptation::truncate
            : ($definition->signed ? integer_adaptation::sign_extend : integer_adaptation::zero_extend));
        if (($this->previous !== null) && ($this->previous->entry === $this->entry) && ($this->previous->native_bits === $this->native_bits)
            && ($this->previous->conversion === $conversion)) {
            return $this->previous;
        }
        return new native_entry_plan($this->entry, $this->native_bits, $conversion, self::LINK_NAME, self::CALLING_CONVENTION);
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Native_Entry::' . $operation . ' requires ' . $expected->name
                . '; current status is ' . $this->state->name);
        }
    }
}
