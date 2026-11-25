<?php
// Host-only ownership proof: after completion the queue retains neither request nor task.
$f = \construction_fixture\Fixture::prepare('scalar', 0);
$queue = new \resolve_types\Preparation_Queue();
$task = new \instantiate\Member_Task(\instantiate\Instance_Context::ordinary($f->input->owner), 1, 2);
$request = new \resolve_types\Preparation_Request('release', \resolve_types\PREPARATION_MEMBER, null, null, $task);
$request_weak = \WeakReference::create($request);
$task_weak = \WeakReference::create($task);
$queue->add($request, ['release-fact']);
$queue->publish('release-fact');
$queue->complete($request);
unset($request, $task);
if ($request_weak->get() !== null || $task_weak->get() !== null) {
    throw new \LogicException('Completed queue retained payload');
}
echo "true\n";
