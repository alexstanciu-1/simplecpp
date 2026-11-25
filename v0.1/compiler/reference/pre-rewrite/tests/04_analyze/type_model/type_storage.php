<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use type_model\implementation_binding;
use type_model\implementation_kind;
use type_model\operation_contract;
use type_model\floating_format;
use type_model\floating_representation;
use type_model\integer_representation;
use type_model\representation_kind;
use type_model\representation_record;
use type_model\type_member;
use type_model\Type_Store;
use type_model\type_context;

class Type_Storage_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function rejects(callable $operation, string $exception): void
    {
        try {
            $operation();
        }
        catch (Throwable $error) {
            self::check($error instanceof $exception, 'Unexpected failure: ' . $error);
            return;
        }
        throw new Exception('Expected rejection: ' . $exception);
    }
}

$types = new Type_Store(new type_context('fixture-config', 'fixture-providers', 'fixture-target'));

// Fixture definitions supply identities/widths. The compiler has no sample-name
// switch, implicit integer width or built-in provider catalog in this slice.
$signed = $types->declare_type('signed_word', 'fixture');
$unsigned = $types->declare_type('unsigned_word', 'fixture');
$integer = $types->intern_integer(32);
$types->set_representation($signed, $integer);
$types->set_representation($unsigned, $integer);
Type_Storage_Test::check(($signed !== $unsigned)
    && ($types->representation_for_type($signed) === $types->representation_for_type($unsigned)),
    'Distinct language identities must share equivalent representation without merging');
Type_Storage_Test::check(($types->find_type('signed_word', 'fixture') === $signed)
    && ($types->find_type('signed_word') === 0), 'Lookup must respect namespace');
$other_namespace = $types->declare_type('signed_word', 'other');
Type_Storage_Test::check($other_namespace !== $signed, 'Namespaces distinguish declarations');
$collision_a = $types->declare_type('c', 'ab');
$collision_b = $types->declare_type('bc', 'a');
Type_Storage_Test::check(($types->find_type('c', 'ab') === $collision_a)
    && ($types->find_type('bc', 'a') === $collision_b), 'Compound name keys cannot collide');

$void = $types->declare_type('nothing', 'fixture');
$types->set_representation($void, $types->intern_void());
Type_Storage_Test::check($types->representation_for_type($void)->payload === null,
    'Void has no scalar payload or invented zero-bit storage');
Type_Storage_Test::rejects(static fn() => $types->intern_integer(0), InvalidArgumentException::class);

$binary16 = $types->intern_float(floating_format::ieee_binary16);
$bfloat16 = $types->intern_float(floating_format::bfloat16);
Type_Storage_Test::check(($binary16 !== $bfloat16)
    && ($types->representation_by_id($binary16)->payload->bit_width() === 16)
    && ($types->representation_by_id($bfloat16)->payload->bit_width() === 16),
    'Floating-point identity requires format, not width alone');

// One type graph combines declaration-before-definition, recursive pointers,
// arrays, field lists and a signature. All edges reference the same type dataset.
$node = $types->declare_type('node', 'fixture');
$node_pointer = $types->declare_type('node_pointer', 'fixture');
$types->set_representation($node_pointer, $types->intern_pointer($node));
$words = $types->declare_type('words', 'fixture');
$types->set_representation($words, $types->intern_array($signed, 4));
$fields = [new type_member($node_pointer, 'next'), new type_member($words, 'values')];
$node_shape = $types->intern_structure($fields);
$types->set_representation($node, $node_shape);
$signature = $types->intern_signature($node_pointer, [$node_pointer, $unsigned]);
$signature_type = $types->declare_type('visit_signature', 'fixture');
$types->set_representation($signature_type, $signature);
$shape = $types->representation_for_type($node)->payload;
$call = $types->representation_by_id($signature)->payload;
Type_Storage_Test::check(($shape->count === 2) && ($call->count === 2)
    && ($types->member_at($shape->first) === $fields[0])
    && ($types->member_at($shape->first + 1)->type_id === $words)
    && ($call->return_type === $node_pointer)
    && ($types->member_at($call->first)->type_id === $node_pointer)
    && ($types->member_at($call->first + 1)->type_id === $unsigned),
    'Fields and parameters must use ordered flat ranges with a separate return type');
Type_Storage_Test::check(($types->representation_for_type($node_pointer)->payload->element_type === $node)
    && ($types->representation_for_type($words)->payload->count === 4),
    'Recursive references and array count retain real type relations');
Type_Storage_Test::check(($types->intern_array($signed, 0) !== $types->intern_array($signed, 4))
    && ($types->intern_pointer($node, 1) !== $types->intern_pointer($node)),
    'Array count and pointer address space distinguish representations');

$before = $types->to_json();
Type_Storage_Test::check(($types->intern_structure([new type_member($node_pointer, 'next'), new type_member($words, 'values')]) === $node_shape)
    && ($types->intern_signature($node_pointer, [$node_pointer, $unsigned]) === $signature)
    && ($types->intern_integer(32) === $integer)
    && ($types->to_json() === $before), 'Interning equivalent shapes must not append member or representation rows');
Type_Storage_Test::check(($types->intern_structure(array_reverse($fields)) !== $node_shape)
    && ($types->intern_signature($node_pointer, [$unsigned, $node_pointer]) !== $signature)
    && ($types->intern_signature($void, [$node_pointer, $unsigned]) !== $signature),
    'Field/parameter order and return type affect representation identity');

// Empty lists remain legitimate shapes, separate from void and no signature.
$empty = $types->representation_by_id($types->intern_structure([]))->payload;
$no_arguments = $types->representation_by_id($types->intern_signature($void, []))->payload;
Type_Storage_Test::check(($empty->count === 0) && ($no_arguments->count === 0)
    && ($no_arguments->return_type === $void), 'Empty aggregates and parameter lists retain their kind');

// A coordinator can update a separate candidate without copying unchanged rows
// or exposing writes to workers still holding the previous store.
$before = $types->to_json();
$candidate = clone $types;
$candidate->set_representation($unsigned, $candidate->intern_integer(64));
$candidate->declare_type('added', 'fixture');
Type_Storage_Test::check(($types->to_json() === $before)
    && ($candidate->type_by_id($signed) === $types->type_by_id($signed))
    && ($candidate->type_by_id($unsigned) !== $types->type_by_id($unsigned))
    && ($candidate->representation_for_type($unsigned)->payload->bit_width === 64)
    && ($types->representation_for_type($unsigned)->payload->bit_width === 32)
    && ($types->find_type('added', 'fixture') === 0),
    'Candidate writes replace only changed rows and preserve accepted indexes/data');
$unchanged_row = $candidate->type_by_id($signed);
$candidate->set_representation($signed, $integer);
Type_Storage_Test::check($candidate->type_by_id($signed) === $unchanged_row, 'Equal definitions retain their row');
Type_Storage_Test::rejects(static function () use ($shape): void {
        $shape->count = 9;
    }, Error::class);

// Incomplete/invalid references fail explicitly. Storage does not yet prove
// layout legality (e.g. recursive by-value records) or backend support.
Type_Storage_Test::rejects(static fn() => $types->representation_for_type($other_namespace), LogicException::class);
foreach ([0, -1, 999999] as $id) {
    Type_Storage_Test::rejects(static fn() => $types->type_by_id($id), OutOfBoundsException::class);
    Type_Storage_Test::rejects(static fn() => $types->representation_by_id($id), OutOfBoundsException::class);
}
foreach ([
        static fn() => $types->intern_structure([new type_member($signed, 'same'), new type_member($unsigned, 'same')]),
        static fn() => $types->intern_structure([new type_member($signed)]),
        static fn() => $types->intern_structure([1 => new type_member($signed, 'x')]),
        static fn() => $types->intern_signature($void, ['1']),
        static fn() => $types->intern_array($signed, -1),
        static fn() => $types->intern_pointer($signed, -1),
        static fn() => $types->declare_type('signed_word', 'fixture'),
        static fn() => new representation_record(representation_kind::integer, new floating_representation(floating_format::ieee_binary32)),
    ] as $invalid) {
    $before = $types->to_json();
    Type_Storage_Test::rejects($invalid, InvalidArgumentException::class);
    Type_Storage_Test::check($types->to_json() === $before, 'Invalid additions must leave no partial rows');
}
$before = $types->to_json();
Type_Storage_Test::rejects(static fn() => $types->intern_structure([
            new type_member($signed, 'valid'), new type_member(999999, 'dangling'),
        ]), OutOfBoundsException::class);
Type_Storage_Test::check($types->to_json() === $before, 'A dangling later field must not partially append members');

// These are descriptor fixtures, not advertised or executable capabilities.
$signed_action = new operation_contract('divide', [$signed, $signed], $signed,
    new implementation_binding(implementation_kind::native_operation, 'fixture', 'signed_divide'));
$unsigned_action = new operation_contract('divide', [$unsigned, $unsigned], $unsigned,
    new implementation_binding(implementation_kind::callable, 'fixture', 'unsigned_divide'));
Type_Storage_Test::check(($signed_action->operand_types !== $unsigned_action->operand_types)
    && ($signed_action->implementation !== $unsigned_action->implementation),
    'Same representation must not imply identical operations or implementation bindings');
Type_Storage_Test::rejects(static fn() => new operation_contract('divide', [0], $signed,
        $signed_action->implementation), InvalidArgumentException::class);
Type_Storage_Test::rejects(static fn() => new implementation_binding(implementation_kind::callable, '', 'entry'),
    InvalidArgumentException::class);
$export = json_decode($types->to_json(), true, 512, JSON_THROW_ON_ERROR);
Type_Storage_Test::check(($export['types'][$signed - 1]['representation_id'] === $integer)
    && ($export['representations'][$integer - 1]['payload']['bit_width'] === 32)
    && ($export['members'][$shape->first]['name'] === 'next'), 'Debug export exposes shared rows and ranges');

echo "type storage ok: distinct identities, shared representations, flat fields/parameters, composed recursive graph, immutable candidates, action descriptors and invalid inputs; storage boundary verified\n";
