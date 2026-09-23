<?php
declare(strict_types=1);
namespace preparation_queue_test;
final class Probe {
    public static function require_true(bool $value): void {
        if (!$value) { throw new \LogicException('Queue proof failed'); }
    }
    public static function run(string $unused): void {
        $f = \construction_fixture\Fixture::prepare('scalar', 0);
        $context = \instantiate\Instance_Context::ordinary($f->input->owner);
        $task = new \instantiate\Member_Task($context, 1, 2);
        $a = new \resolve_types\Preparation_Request('a', \resolve_types\PREPARATION_MEMBER, null, null, $task);
        $b = new \resolve_types\Preparation_Request('b', \resolve_types\PREPARATION_MEMBER, null, null, $task);
        $none /** vector<string> */ = [];
        $both /** vector<string> */ = ['x', 'y', 'x'];
        $x /** vector<string> */ = ['x'];
        $queue = new \resolve_types\Preparation_Queue();
        \preparation_queue_test\Probe::require_true(!$queue->ready());
        \preparation_queue_test\Probe::require_true($queue->pending() === null);
        $queue->add($a, $both); $queue->add($b, $x);
        \preparation_queue_test\Probe::require_true(!$queue->ready());
        $queue->publish('x');
        $batch = $queue->take_ready(\resolve_types\PREPARATION_MEMBER);
        \preparation_queue_test\Probe::require_true(q_count($batch) === 1);
        \preparation_queue_test\Probe::require_true($batch[0] === $b);
        \preparation_queue_test\Probe::require_true(!$queue->ready());
        $queue->publish('y');
        \preparation_queue_test\Probe::require_true(q_count($batch) === 1);
        $queue->complete($b);
        $next = $queue->take_ready(\resolve_types\PREPARATION_MEMBER);
        \preparation_queue_test\Probe::require_true(q_count($next) === 1);
        \preparation_queue_test\Probe::require_true($next[0] === $a);
        $queue->complete($a);
        $queue->publish('x'); $queue->publish('y');
        \preparation_queue_test\Probe::require_true($queue->pending() === null);
        \preparation_queue_test\Probe::require_true(!$queue->ready());
        $queue->add($a, $both);
        \preparation_queue_test\Probe::require_true($queue->ready());
        $queue->complete($a);
        \preparation_queue_test\Probe::require_true(!$queue->ready());
        $queue->add($a, $none);
        $later /** vector<string> */ = ['later'];
        $queue->wait_for($a, $later);
        \preparation_queue_test\Probe::require_true(!$queue->ready());
        $failed = false;
        try { $queue->complete($a); } catch (\LogicException $e) { $failed = true; }
        \preparation_queue_test\Probe::require_true($failed);
        $failed = false;
        try { $queue->add($a, $none); } catch (\LogicException $e) { $failed = true; }
        \preparation_queue_test\Probe::require_true($failed);
        $fake = new \resolve_types\Preparation_Request('a', \resolve_types\PREPARATION_MEMBER, null, null, $task);
        $failed = false;
        try { $queue->wait_for($fake, $none); } catch (\LogicException $e) { $failed = true; }
        \preparation_queue_test\Probe::require_true($failed);
        $queue->publish('later');
        $last = $queue->take_ready(\resolve_types\PREPARATION_MEMBER);
        \preparation_queue_test\Probe::require_true(q_count($last) === 1);
        \preparation_queue_test\Probe::require_true($last[0] === $a);
        \preparation_queue_test\Probe::require_true($a->member() === $task);
        $failed = false;
        try { $a->record(); } catch (\LogicException $e) { $failed = true; }
        \preparation_queue_test\Probe::require_true($failed);
        $queue->complete($a);
        $failed = false;
        try { $queue->complete($a); } catch (\LogicException $e) { $failed = true; }
        \preparation_queue_test\Probe::require_true($failed);
        $record_owner = $f->symbols->symbol_by_id($f->symbols->find_symbol('Point', \collect_symbols\SYMBOL_STRUCT, 0, ''));
        $record_task = new \resolve_types\Record_Task($f->reader, $f->symbols, \instantiate\Instance_Context::ordinary($record_owner));
        $record = new \resolve_types\Preparation_Request('record', \resolve_types\PREPARATION_RECORD, null, $record_task);
        $application_task = new \instantiate\Application_Task($context, new \resolve_symbols\Template_Application_Binding(1, $record_owner));
        $application = new \resolve_types\Preparation_Request('app', \resolve_types\PREPARATION_APPLICATION, $application_task);
        $queue->add($record, $none); $queue->add($application, $none); $queue->add($b, $none);
        $records = $queue->take_ready(\resolve_types\PREPARATION_RECORD);
        $applications = $queue->take_ready(\resolve_types\PREPARATION_APPLICATION);
        $members = $queue->take_ready(\resolve_types\PREPARATION_MEMBER);
        \preparation_queue_test\Probe::require_true(q_count($records) === 1);
        \preparation_queue_test\Probe::require_true(q_count($applications) === 1);
        \preparation_queue_test\Probe::require_true(q_count($members) === 1);
        \preparation_queue_test\Probe::require_true($records[0]->record() === $record_task);
        \preparation_queue_test\Probe::require_true($applications[0]->application() === $application_task);
        \preparation_queue_test\Probe::require_true($queue->pending() === $record);
        $queue->complete($record); $queue->complete($application); $queue->complete($b);
        $failed = false;
        try { $invalid = new \resolve_types\Preparation_Request('bad', \resolve_types\PREPARATION_RECORD, null, null, $task); }
        catch (\LogicException $e) { $failed = true; }
        \preparation_queue_test\Probe::require_true($failed);
        // A blocked cycle remains pending without manufacturing ready work.
        $cycle_a /** vector<string> */ = ['cycle-b'];
        $cycle_b /** vector<string> */ = ['cycle-a'];
        $queue->add($a, $cycle_a); $queue->add($b, $cycle_b);
        \preparation_queue_test\Probe::require_true(!$queue->ready());
        \preparation_queue_test\Probe::require_true($queue->pending() === $a);
        echo "true\n";
    }
}
