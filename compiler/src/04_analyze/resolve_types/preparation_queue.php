<?php
declare(strict_types=1);

/*
 * Role: Own readiness for concrete application, record and member requests.
 * Used by: Concrete_Preparation
 * Call map: add()/wait_for(); take_ready(); complete(); publish()
 * Facts unblock indexed dependents; completed requests are never rescanned.
 */
namespace resolve_types;

final class Preparation_Queue
{
    /** @var array<string, preparation_request> */
    private array $requests = [];
    /** @var array<string, array<string, preparation_request>> */
    private array $ready = [];
    /** @var array<string, array<string, true>> */
    private array $waiting = [];
    /** @var array<string, array<string, true>> */
    private array $dependents = [];
    /** @var array<string, true> */
    private array $facts = [];

    /** Register a request exactly once; its prerequisites are concrete readiness facts.
     * @param list<string> $prerequisites */
    public function add(preparation_request $request, array $prerequisites = []): void
    {
        if (isset($this->requests[$request->key])) {
            throw new \LogicException('Repeated concrete preparation request');
        }
        $this->requests[$request->key] = $request;
        $this->wait_for($request, $prerequisites);
    }

    /** Retain only unavailable prerequisites, indexing their consumers without global rescans.
     * @param list<string> $prerequisites */
    public function wait_for(preparation_request $request, array $prerequisites): void
    {
        if (($this->requests[$request->key] ?? null) !== $request) {
            throw new \LogicException('Unknown concrete preparation request');
        }
        foreach ($prerequisites as $fact) {
            if (!isset($this->facts[$fact])) {
                $this->waiting[$request->key][$fact] = true;
                $this->dependents[$fact][$request->key] = true;
            }
        }
        if (!isset($this->waiting[$request->key])) {
            $this->ready[$request->kind->value][$request->key] = $request;
        }
    }

    /** Take one fixed task batch; newly ready requests wait for the next selection.
     * @return list<preparation_request> */
    public function take_ready(preparation_kind $kind): array
    {
        $batch = array_values($this->ready[$kind->value] ?? []);
        unset($this->ready[$kind->value]);
        return $batch;
    }

    public function complete(preparation_request $request): void
    {
        if (isset($this->waiting[$request->key]) || (($this->requests[$request->key] ?? null) !== $request)) {
            throw new \LogicException('Cannot complete an unknown or blocked preparation request');
        }
        unset($this->requests[$request->key]);
    }

    /** Publish a newly accepted fact and schedule only the requests that depended on it. */
    public function publish(string $fact): void
    {
        if (isset($this->facts[$fact])) {
            return;
        }
        $this->facts[$fact] = true;
        foreach ($this->dependents[$fact] ?? [] as $key => $_)
        {
            unset($this->waiting[$key][$fact]);
            if ($this->waiting[$key] === []) {
                unset($this->waiting[$key]);
                $request = $this->requests[$key];
                $this->ready[$request->kind->value][$key] = $request;
            }
        }
        unset($this->dependents[$fact]);
    }

    public function ready(): bool
    {
        return $this->ready !== [];
    }

    public function pending(): ?preparation_request
    {
        return $this->requests === [] ? null : $this->requests[array_key_first($this->requests)];
    }
}
