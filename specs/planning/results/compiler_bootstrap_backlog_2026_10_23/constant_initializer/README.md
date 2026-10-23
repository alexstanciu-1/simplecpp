# Constant initializer staged storage in authored PHP
Doc Status: planning

Base 3b9fa0a66 plus candidate hashes. User approved explicit nullable backing storage
and checked required accessors, preserving collection order and retained identity.
No compiler semantics, converter, runtime, or collection protocol changes.

constant_declaration_node owns private initializer_value, initially null. The checked
require_initializer accessor throws LogicException before returning an absent value;
set_initializer accepts a required expression_node. Parser updates only its existing
final retain/replace step after expression and semicolon parsing. No reset before
replacement. Constant preparation, maintenance and child iteration use the required
accessor; unrelated optional field/variable initializers keep their original contract.

## Proofs

Actual source_constants test checks early required reads and child inspection fail,
unchanged parsing retains syntax/work, malformed replacement preserves old initializer
and occurrence, and repair restores output. A malformed new constant is already
collected before expression failure; repair reuses that exact occurrence and syntax
node. Existing member-owner and declaration-model tests pass. Host tasks 0.37–1.55 s.

Native fixture extracts production backing field, getter/setter, children/maintain
methods and constant_children_iterator into dependency doubles. PHP and v0.1,
v0.2 ordinary and concrete agree on early-read/traversal failure, input identity,
mutation/replacement, failed RHS preserving old state, child identity and exhaustion.
Native task times 20.85/23.08/20.51 s. This proves the accessor protocol and actual
constant-specific traversal, not a complete native compiler rebuild. First fixture
attempt lacked its return interface; second used an unsupported free-function form.
Corrected fixture only (interface and static failure-source method). Shared runner
used CLI fallback because FPM socket startup was unavailable.

Existing Simple C++ nullable storage, required argument/return checking and inherited
methods suffice. No staged-field converter extension or new nullability semantics.

## Bootstrap

76.06 s wall / 354792 KiB peak RSS (346.48 MiB) / 324 MiB PHP heap.
Removes required initializer diagnostic, adds none. First remaining preparation
failure is required type_syntax initialization; left unchanged. Total task 81.04 s
includes conversion/setup. This remains an unsuccessful whole-bootstrap preparation
attempt, distinct from passing focused native accessor proofs.
