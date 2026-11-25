<?php
declare(strict_types=1);
namespace resolve_types;
const PREPARATION_APPLICATION = 1;
const PREPARATION_RECORD = 2;
const PREPARATION_MEMBER = 3;

/** Exactly one concrete task; queue membership is separate from payload ownership. */
final class Preparation_Request {
    public function __construct(public readonly string $key, public readonly int $kind,
        private readonly ?\instantiate\Application_Task $application_task = null,
        private readonly ?Record_Task $record_task = null,
        private readonly ?\instantiate\Member_Task $member_task = null) {
        $count = 0;
        if ($application_task !== null) { $count++; }
        if ($record_task !== null) { $count++; }
        if ($member_task !== null) { $count++; }
        if ($count !== 1) { throw new \LogicException('Preparation request requires one task'); }
        $valid = false;
        if ($kind === \resolve_types\PREPARATION_APPLICATION) { $valid = $application_task !== null; }
        if ($kind === \resolve_types\PREPARATION_RECORD) { $valid = $record_task !== null; }
        if ($kind === \resolve_types\PREPARATION_MEMBER) { $valid = $member_task !== null; }
        if (!$valid) { throw new \LogicException('Preparation kind does not match its task'); }
    }
    public function application(): \instantiate\Application_Task {
        $task = $this->application_task;
        if ($task === null) { throw new \LogicException('Not an application request'); }
        return $task;
    }
    public function record(): Record_Task {
        $task = $this->record_task;
        if ($task === null) { throw new \LogicException('Not a record request'); }
        return $task;
    }
    public function member(): \instantiate\Member_Task {
        $task = $this->member_task;
        if ($task === null) { throw new \LogicException('Not a member request'); }
        return $task;
    }
}
/** Mutable queue-owned indexes, never retained compiler results. */
final class Preparation_Entry {
    public array $waiting /** hash<bool> */ = [];
    public function __construct(public readonly Preparation_Request $request) {}
}
final class Preparation_Dependents {
    public array $keys /** hash<bool> */ = [];
}
final class Preparation_Batch {
    public array $requests /** hash<Preparation_Request> */ = [];
}
