<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/submodules.php';

/** Resolved type information consumed by recipes; implementations need not expose their source model. */
interface ConcreteType {
    public function identity(): string;
    public function cppType(string $namespace): string;
    /** @return list<string> Public declaration dependencies; publication owns include placement. */
    public function headers(): array;
    /** Null means not established by the provider; generated native assertions remain required. */
    public function supports(string $requirement): ?bool;
}

/** The native void result has a spelling and no storage or lifetime capabilities. */
final class VoidConcreteType implements ConcreteType {
    public function identity(): string { return 'void'; }
    public function cppType(string $namespace): string { return 'void'; }
    public function headers(): array { return []; }
    public function supports(string $requirement): ?bool { return false; }
}

/** Nonthrowing key operations; equality must imply equal hashes and remain stable while stored. */
interface ConcreteHashKey {
    public function hashExpression(string $key): ?string;
    public function equalityExpression(string $left, string $right): ?string;
}

/** Adapter over validated runtime publication facts, without retaining or copying the entire plan. */
final readonly class RuntimeConcreteType implements ConcreteType, ConcreteHashKey {
    private string $identity;
    private string $symbol;
    private string $header;
    private ?string $lifecycle;
    private ?array $keyContract;

    public function __construct(array $plan, string $identity) {
        $identity = object($plan['aliases'])[$identity] ?? $identity;
        $export = typeExport($plan['submodules'], $identity);
        $provider = $export['provider'];
        requireThat(in_array($provider, $plan['submodules']['published'], true), 'Concrete type provider is not published: ' . $identity);
        $lifecycle = null;
        foreach (array_merge($plan['types'], $plan['values']) as $type) {
            if ($type['name'] === $identity) { $lifecycle = 'native_value'; break; }
        }
        if (($plan['string']['name'] ?? null) === $identity) $lifecycle = $plan['string']['lifecycle'];
        foreach (array_merge($plan['vectors'] ?? [], $plan['hashes']['specializations'] ?? []) as $type) {
            if ($type['name'] === $identity) $lifecycle = $type['lifecycle'] ?? null;
        }
        $this->keyContract = $plan['hashes']['key_contracts'][$identity] ?? null;
        $this->identity = $identity;
        $this->symbol = $export['symbol'];
        $this->header = providerHeader($plan['submodules'], $provider);
        $this->lifecycle = $lifecycle;
    }

    public function hashExpression(string $key): ?string {
        return $this->keyContract === null ? null : strtr($this->keyContract['hash'], ['@key@' => $key]);
    }
    public function equalityExpression(string $left, string $right): ?string {
        return $this->keyContract === null ? null : strtr($this->keyContract['equal'], ['@left@' => $left, '@right@' => $right]);
    }
    public function identity(): string { return $this->identity; }
    public function cppType(string $namespace): string {
        $prefix = $namespace . '::';
        return $namespace !== '' && str_starts_with($this->symbol, $prefix)
            ? substr($this->symbol, strlen($prefix)) : '::' . $this->symbol;
    }
    public function headers(): array { return [$this->header]; }
    public function supports(string $requirement): ?bool {
        if (!in_array($requirement, ['copy_constructible', 'nothrow_move_constructible', 'nothrow_move_assignable', 'nothrow_destructible'], true)) return null;
        return in_array($this->lifecycle, ['native_value', 'owned_copy_noexcept_move'], true) ? true : null;
    }
}
