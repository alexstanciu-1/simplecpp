<?php
declare(strict_types=1);
namespace resolve_types;
/** Fact-indexed scheduling. Completed payloads and consumed edges are released. */
final class Preparation_Queue {
    private array $entries /** hash<Preparation_Entry> */ = [];
    private array $batches /** hash<Preparation_Batch, int> */ = [];
    private array $dependents /** hash<Preparation_Dependents> */ = [];
    private array $facts /** hash<bool> */ = [];

    public function add(Preparation_Request $request, array $prerequisites /** vector<string> */): void {
        if (isset($this->entries[$request->key])) { throw new \LogicException('Repeated concrete preparation request'); }
        $this->entries[$request->key] = new Preparation_Entry($request);
        $this->wait_for($request, $prerequisites);
    }
    private function entry(Preparation_Request $request): Preparation_Entry {
        if (!isset($this->entries[$request->key])) { throw new \LogicException('Unknown concrete preparation request'); }
        $entry = $this->entries[$request->key];
        if ($entry->request !== $request) { throw new \LogicException('Unknown concrete preparation request'); }
        return $entry;
    }
    private function schedule(Preparation_Request $request): void {
        if (!isset($this->batches[$request->kind])) { $this->batches[$request->kind] = new Preparation_Batch(); }
        $batch = $this->batches[$request->kind];
        $batch->requests[$request->key] = $request;
    }
    private function unschedule(Preparation_Request $request): void {
        if (isset($this->batches[$request->kind])) {
            $batch = $this->batches[$request->kind];
            unset($batch->requests[$request->key]);
            if (q_count($batch->requests) === 0) { unset($this->batches[$request->kind]); }
        }
    }
    public function wait_for(Preparation_Request $request, array $prerequisites /** vector<string> */): void {
        $entry = $this->entry($request);
        foreach ($prerequisites as $fact) {
            if (!isset($this->facts[$fact])) {
                $entry->waiting[$fact] = true;
                if (!isset($this->dependents[$fact])) { $this->dependents[$fact] = new Preparation_Dependents(); }
                $dependents = $this->dependents[$fact];
                $dependents->keys[$request->key] = true;
            }
        }
        if (q_count($entry->waiting) === 0) { $this->schedule($request); }
        else { $this->unschedule($request); }
    }
    public function take_ready(int $kind): array /** vector<Preparation_Request> */ {
        $result /** vector<Preparation_Request> */ = [];
        if (isset($this->batches[$kind])) {
            $batch = $this->batches[$kind];
            foreach ($batch->requests as $request) { $result[] = $request; }
            unset($this->batches[$kind]);
        }
        return $result;
    }
    public function complete(Preparation_Request $request): void {
        $entry = $this->entry($request);
        if (q_count($entry->waiting) !== 0) { throw new \LogicException('Cannot complete a blocked preparation request'); }
        $this->unschedule($request);
        unset($this->entries[$request->key]);
    }
    public function publish(string $fact): void {
        if (isset($this->facts[$fact])) { return; }
        $this->facts[$fact] = true;
        if (isset($this->dependents[$fact])) {
            $dependents = $this->dependents[$fact];
            foreach ($dependents->keys as $key => $present) {
                $entry = $this->entries[$key];
                unset($entry->waiting[$fact]);
                if (q_count($entry->waiting) === 0) { $this->schedule($entry->request); }
            }
            unset($this->dependents[$fact]);
        }
    }
    public function ready(): bool { return q_count($this->batches) !== 0; }
    public function pending(): ?Preparation_Request {
        foreach ($this->entries as $entry) { return $entry->request; }
        return null;
    }
}
