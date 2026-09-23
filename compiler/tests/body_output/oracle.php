<?php
declare(strict_types=1);
// Host-only adaptation comparison of the real retained access-selection trait.
$root = dirname(__DIR__, 3);
$original = ($argv[1] ?? '') === 'original';
if ($original) {
    require $root . '/compiler/reference/pre-rewrite/src/04_analyze/check_bodies/data/structures.php';
    require $root . '/compiler/reference/pre-rewrite/src/04_analyze/check_bodies/handlers/places.php';
    final class OriginalAccess {
        use \check_bodies\Place_Checking;
        public array $values = [];
        public int $pending_place_count = 0;
        public function select(int $id, bool $borrow): void { $this->select_place_access($id, $borrow); }
    }
} else {
    require $root . '/tools/php_portability/runtime/bootstrap.php';
    foreach (['places', 'values', 'statements', 'output'] as $file) {
        require $root . '/compiler/src/04_analyze/check_bodies/data/' . $file . '.php';
    }
}
foreach (['pending', 'local_read', 'local_borrow', 'integer_literal'] as $kind) {
    foreach ([[false], [true], [false, false], [true, true], [false, true], [true, false]] as $sequence) {
        $location = $original ? new \check_bodies\place(1) : new \check_bodies\Place(1, []);
        if ($original) {
            $buffer = new OriginalAccess();
            $buffer->values[] = $kind === 'pending' ? new \check_bodies\pending_place_value(12, 7, $location)
                : new \check_bodies\typed_value(12, 7, \check_bodies\value_kind::from($kind), $kind === 'integer_literal' ? '42' : $location);
            $buffer->pending_place_count = $kind === 'pending' ? 1 : 0;
        } else {
            $buffer = new \check_bodies\Body_Output(0);
            if ($kind === 'pending') { $buffer->append_place(12, 7, $location); }
            else {
                $tag = ['local_read' => 6, 'local_borrow' => 7, 'integer_literal' => 1][$kind];
                $buffer->append_value(new \check_bodies\Typed_Value(12, 7, $tag, $tag === 1 ? '42' : '', 0, $tag === 1 ? null : $location));
            }
        }
        $events = [];
        foreach ($sequence as $borrow) {
            try {
                if ($original) {
                    $buffer->select(1, $borrow);
                    $value = $buffer->values[0];
                    $events[] = [$value->kind->value, $value->source_node_id, $value->type_id, $buffer->pending_place_count];
                } else {
                    $buffer->select_place_access(1, $borrow);
                    $value = $buffer->value_for(1)->completed;
                    $events[] = [[1=>'integer_literal', 6=>'local_read', 7=>'local_borrow'][$value->kind], $value->source_node_id, $value->type_id, 0];
                }
            } catch (LogicException $error) { $events[] = $error->getMessage(); }
        }
        echo json_encode($events, JSON_THROW_ON_ERROR), "\n";
    }
}
