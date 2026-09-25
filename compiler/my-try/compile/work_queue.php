<?php
namespace scpp\compiler;

/** Compiler-owned work membership; the task executor owns bounded dispatch and joining. */
final class Source_Work_Queue
{
	private array $pending /** vector<source_work> */ = [];
	private bool $sealed = false;
	private \SplObjectStorage $sources /** hash<bool, shared<file>> */;

	public function __construct()
	{
		$sources /** hash<bool, shared<file>> */ = new \SplObjectStorage /** hash<bool, shared<file>> */();
		$this->sources = $sources;
	}

	/** Add an input snapshot before dispatch seals queue membership. */
	public function enqueue(file $source, ?token_list $tokens = null): void
	{
		if ($this->sealed) {
			throw new \LogicException('Work membership is sealed');
		}
		if (isset($this->sources[$source])) {
			throw new \LogicException('A source record cannot have concurrent work orders');
		}
		if ($tokens !== null) {
			$snapshot = object_cast($tokens, token_list::class);
			if ($snapshot->file !== $source) {
				throw new \LogicException('Token snapshot belongs to another source');
			}
		}
		$this->sources[$source] = true;
		$work = new source_work();
		$work->source = $source;
		$work->tokens = $tokens;
		$work->position = q_count($this->pending);
		$this->pending[] = $work;
	}

	public function items(): array /** vector<source_work> */
	{
		$this->sealed = true;
		return $this->pending;
	}

	/** Each dispatched item has a single worker; membership stays frozen during execution. */
	public function start(source_work $work): void
	{
		if (!$this->sealed) {
			throw new \LogicException('Seal work membership before dispatch');
		}
		$this->require_state($work, work_state::queued);
		$work->state = work_state::running;
	}

	public function complete(source_work $work): void
	{
		$this->require_state($work, work_state::running);
		$work->state = work_state::published;
	}

	public function fail(source_work $work): void
	{
		$this->require_state($work, work_state::running);
		$work->state = work_state::failed;
	}

	/** Reject foreign or repeated completion without changing queue state. */
	private function require_state(source_work $work, work_state $expected): void
	{
		if (($work->position < 0) || ($work->position >= q_count($this->pending))) {
			throw new \LogicException('Foreign work order');
		}
		if (($this->pending[$work->position] !== $work) || ($work->state !== $expected)) {
			throw new \LogicException('Unexpected work order state or ownership');
		}
	}

	/** Called only after the executor has joined all workers. */
	public function finished(): bool
	{
		foreach ($this->pending as $work) {
			if ($work->state !== work_state::published) {
				return false;
			}
		}
		return true;
	}
}
