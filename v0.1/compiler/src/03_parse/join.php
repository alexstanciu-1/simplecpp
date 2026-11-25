<?php
declare(strict_types=1);
namespace parse;

/** One coordinator for fixed input/plan snapshots; workers never access this accumulator. */
final class Frontend_Join {
    private bool $prepared = false;
    private array $by_path /** hash<int> */ = [];
    private array $selected /** hash<bool,int> */ = [];
    private array $accepted /** hash<Parse_Result,int> */ = [];
    public function __construct(private Parser_Plan $plan) {}

    private function prepare(): void {
        if ($this->prepared) { return; }
        $paths = Parser_Selection::index($this->plan->current);
        $selected /** hash<bool,int> */ = [];
        $covered /** hash<bool,int> */ = [];
        $size = q_count($this->plan->current->buffers);
        foreach ($this->plan->tasks as $position) {
            if (($position < 0) || ($position >= $size) || isset($covered[$position])) { throw new \LogicException('Invalid or duplicate parser task'); }
            $selected[$position] = true;
            $covered[$position] = true;
        }
        foreach ($this->plan->retained as $position => $result) {
            if (($position < 0) || ($position >= $size) || isset($covered[$position])) { throw new \LogicException('Invalid retained parser position'); }
            if ($result->tokens !== $this->plan->current->buffers[$position]) { throw new \LogicException('Stale retained frontend'); }
            Frontend_Set::require_file($result);
            $covered[$position] = true;
        }
        if (q_count($covered) !== $size) { throw new \LogicException('Incomplete parser plan'); }
        $this->by_path = $paths;
        $this->selected = $selected;
        $this->prepared = true;
    }
    /** Validate a complete segment before adopting any of its results. */
    public function merge(array $results /** vector<Parse_Result> */, int $index, int $count): void {
        $this->prepare();
        $size = q_count($results);
        if (($index < 0) || ($count < 0) || ($index > $size)) { throw new \LogicException('Invalid frontend result segment'); }
        if ($count > $size - $index) { throw new \LogicException('Invalid frontend result segment'); }
        $segment /** hash<Parse_Result,int> */ = [];
        for ($offset /** int */ = $index; $offset < $index + $count; ++$offset) {
            $result = $results[$offset];
            $path = $result->tokens->source->path;
            if (!isset($this->by_path[$path])) { throw new \LogicException('Removed or unexpected frontend path'); }
            $position = $this->by_path[$path];
            if (!isset($this->selected[$position])) { throw new \LogicException('Unselected frontend result'); }
            if (isset($this->accepted[$position]) || isset($segment[$position])) { throw new \LogicException('Duplicate frontend result'); }
            if ($result->tokens !== $this->plan->current->buffers[$position]) { throw new \LogicException('Stale frontend token snapshot'); }
            Frontend_Set::require_file($result);
            $segment[$position] = $result;
        }
        foreach ($segment as $position => $result) { $this->accepted[$position] = $result; }
    }
    public function finish(): Frontend_Set {
        $this->prepare();
        if (q_count($this->accepted) !== q_count($this->selected)) { throw new \LogicException('Incomplete frontend task batch'); }
        $output = new Frontend_Set();
        $output->entry_index = $this->plan->current->entry_index;
        for ($position /** int */ = 0; $position < q_count($this->plan->current->buffers); ++$position) {
            if (isset($this->accepted[$position])) { $output->add($this->accepted[$position]); }
            else { $output->add($this->plan->retained[$position]); }
        }
        return $output;
    }
    public function join(array $results /** vector<Parse_Result> */): Frontend_Set {
        $this->merge($results, 0, q_count($results));
        return $this->finish();
    }
}
