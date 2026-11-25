<?php
declare(strict_types=1);
namespace body_output_test;
final class Probe {
    private static function expect(bool $condition, string $label): void {
        if (!$condition) { throw new \LogicException($label); }
        echo json_quote($label), "\n";
    }
    public static function run(): void {
        $output = new \check_bodies\Body_Output(2);
        $projections /** vector<\check_bodies\Place_Projection> */ = [];
        $place = new \check_bodies\Place(1, $projections);
        $literal = new \check_bodies\Typed_Value(11, 7, \check_bodies\VALUE_INTEGER_LITERAL, '42');
        $first = $output->append_value($literal);
        $second = $output->append_place(12, 7, $place);
        $third = $output->append_place(13, 7, $place);
        Probe::expect(($first === 1) && ($second === 2) && ($third === 3), 'stable value IDs');
        $pending = $output->value_for($second);
        Probe::expect(($pending->pending === $place) && ($pending->completed === null), 'explicit pending place');
        $output->select_place_access(0, false);
        $output->select_place_access($first, true);
        Probe::expect($output->value_for($first)->completed === $literal, 'non-location selection is inert');
        $outer = $output->reserve_arguments(2);
        $inner = $output->reserve_arguments(1);
        Probe::expect(($outer === 0) && ($inner === 2) && ($output->reserve_arguments(0) === 3), 'nested argument ranges');
        $argument = new \check_bodies\Typed_Argument($first, 7);
        $output->complete_argument($inner, $argument);
        $nested = new \check_bodies\Typed_Call(20, 9, 0, $inner, 1);
        Probe::expect($output->append_call($nested) === 1, 'nested call ID');
        $output->complete_argument($outer + 1, $argument);
        $outer_call = new \check_bodies\Typed_Call(21, 10, $first, $outer, 2);
        Probe::expect($output->append_call($outer_call) === 2, 'parent call after nested call');
        $statement = new \check_bodies\Typed_Statement(22, \check_bodies\STATEMENT_EXPRESSION, $first, 0, 2, 1);
        $output->append_statement($statement);
        $output->complete_scope(2, new \check_bodies\Typed_Scope(0, 1));
        Probe::reject($output, 0, 'Checked body has unresolved location access');
        $output->select_place_access($second, true);
        $output->select_place_access($second, true);
        Probe::expect($pending->pending === $place, 'replacement preserves previous row view');
        $selected = $output->value_for($second)->completed;
        if ($selected === null) { throw new \LogicException('Selection lost value'); }
        Probe::expect(($selected->kind === \check_bodies\VALUE_LOCAL_BORROW) && ($selected->place() === $place), 'borrow preserves location identity');
        $output->select_place_access($third, false);
        Probe::reject($output, 0, 'Checked body has unfinished arguments');
        $output->complete_argument($outer, $argument);
        Probe::reject($output, 0, 'Checked body has unfinished scopes');
        $output->complete_scope(1, new \check_bodies\Typed_Scope(0, 1));
        $output->require_complete();
        $values = $output->completed_values();
        $arguments = $output->completed_arguments();
        $scopes = $output->completed_scopes();
        $calls = $output->completed_calls();
        $statements = $output->completed_statements();
        Probe::expect((q_count($values) === 3) && ($values[0] === $literal) && ($values[2]->kind === \check_bodies\VALUE_LOCAL_READ), 'completed values in ID order');
        Probe::expect((q_count($arguments) === 3) && ($arguments[0] === $argument) && ($arguments[2] === $argument), 'arguments in reserved order');
        Probe::expect((q_count($scopes) === 2) && ($scopes[0]->statement_count === 1), 'scopes in lexical ID order');
        Probe::expect(($calls[0] === $nested) && ($calls[1] === $outer_call) && ($output->call_for(1) === $nested), 'calls in evaluation order');
        Probe::expect(($statements[0] === $statement) && ($output->statement_count() === 1), 'statement handoff');
        $values[0] = $selected;
        $arguments[] = $argument;
        $calls[] = $nested;
        $statements[] = $statement;
        Probe::expect(($output->value_for(1)->completed === $literal) && ($output->argument_count() === 3) && ($output->call_count() === 2) && ($output->statement_count() === 1), 'handoff membership isolation');
        for ($mode = 1; $mode < 13; $mode++) {
            $message = '';
            if ($mode === 1) { $message = 'Location access was already selected by another consumer'; }
            if (($mode === 2) || ($mode === 3)) { $message = 'Unknown checking value'; }
            if (($mode === 4) || ($mode === 5)) { $message = 'Unknown argument slot'; }
            if ($mode === 6) { $message = 'Argument slot already completed'; }
            if (($mode === 7) || ($mode === 8)) { $message = 'Unknown scope slot'; }
            if ($mode === 9) { $message = 'Scope slot already completed'; }
            if (($mode === 10) || ($mode === 11)) { $message = 'Unknown checking call'; }
            if ($mode === 12) { $message = 'Invalid argument reservation'; }
            Probe::reject($output, $mode, $message);
        }
        $empty = new \check_bodies\Body_Output(0);
        $empty->require_complete();
        Probe::expect(q_count($empty->completed_values()) === 0, 'empty output is complete');
        $later = $output->append_place(30, 7, $place);
        Probe::reject($output, 0, 'Checked body has unresolved location access');
        Probe::expect(q_count($values) === 3, 'earlier snapshot excludes new rows');
        $output->select_place_access($later, false);
        $output->require_complete();
        Probe::expect($output->value_count() === 4, 'later completion preserves IDs');
    }
    private static function reject(\check_bodies\Body_Output $output, int $mode, string $expected): void {
        $message = '';
        try {
            if ($mode === 0) { $output->completed_values(); }
            if ($mode === 1) { $output->select_place_access(2, false); }
            if ($mode === 2) { $output->value_for(0); }
            if ($mode === 3) { $output->value_for(4); }
            if ($mode === 4) { $output->complete_argument(-1, new \check_bodies\Typed_Argument(1, 7)); }
            if ($mode === 5) { $output->complete_argument(3, new \check_bodies\Typed_Argument(1, 7)); }
            if ($mode === 6) { $output->complete_argument(0, new \check_bodies\Typed_Argument(1, 7)); }
            if ($mode === 7) { $output->complete_scope(0, new \check_bodies\Typed_Scope(0, 0)); }
            if ($mode === 8) { $output->complete_scope(3, new \check_bodies\Typed_Scope(0, 0)); }
            if ($mode === 9) { $output->complete_scope(1, new \check_bodies\Typed_Scope(0, 0)); }
            if ($mode === 10) { $output->call_for(0); }
            if ($mode === 11) { $output->call_for(3); }
            if ($mode === 12) { $output->reserve_arguments(-1); }
        } catch (\LogicException $error) { $message = $error->getMessage(); }
        catch (\OutOfBoundsException $error) { $message = $error->getMessage(); }
        Probe::expect($message === $expected, 'reject: ' . $expected);
    }
}
