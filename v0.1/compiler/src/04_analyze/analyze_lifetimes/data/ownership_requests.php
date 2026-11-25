<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Selected subject and required keys; executable tasks receive accepted summaries later. */
final class Ownership_Request {
    private array $keys /** vector<string> */ = [];
    public function __construct(public readonly string $key, public readonly ?\check_bodies\Checked_Body $body,
        public readonly ?Ownership_Lifecycle $lifecycle, array $dependencies /** vector<string> */) {
        if (($key === '') || (($body === null) === ($lifecycle === null))) { throw new \InvalidArgumentException('Ownership request requires exactly one subject'); }
        $seen /** hash<bool> */ = [];
        foreach ($dependencies as $dependency) {
            if ($dependency === '') { throw new \InvalidArgumentException('Empty ownership dependency'); }
            if (!isset($seen[$dependency])) { $seen[$dependency] = true; $this->keys[] = $dependency; }
        }
    }
    public function dependencies(): array /** vector<string> */ { return $this->keys; }
}
/** Readiness is mutable private scheduling state, separate from the selected subject. */
final class Ownership_Queue_Node {
    public int $remaining = 0;
    public array $users /** vector<string> */ = [];
    public function __construct(public readonly Ownership_Request $request) { $this->remaining = q_count($request->dependencies()); }
}
final class Ownership_Reuse {
    public static function subject(Ownership_Request $request, Ownership_Task $task): bool {
        $body = $task->body();
        if ($request->body !== null) { return $request->body === $body; }
        if ($body !== null) { return false; }
        $left = $request->lifecycle; $right = $task->lifecycle();
        if ($left === null) { return false; } if ($right === null) { return false; }
        if (($left->definition !== $right->definition) || ($left->type_id !== $right->type_id) || ($left->kind !== $right->kind) || ($left->body_id !== $right->body_id)) { return false; }
        $children = $left->children(); $other = $right->children();
        if (q_count($children) !== q_count($other)) { return false; }
        for ($i = 0; $i < q_count($children); $i++) {
            if (($children[$i]->ordinal !== $other[$i]->ordinal) || ($children[$i]->dependency !== $other[$i]->dependency)) { return false; }
        }
        return true;
    }
    public static function dependencies(Ownership_Task $task, array $inputs /** hash<Ownership_Summary> */): bool {
        $previous = $task->dependencies(); if (q_count($previous) !== q_count($inputs)) { return false; }
        foreach ($inputs as $key => $summary) {
            if (!isset($previous[$key])) { return false; }
            if ($previous[$key] !== $summary) { return false; }
        }
        return true;
    }
}
