<?php
declare(strict_types=1);
namespace type_model;

/** One primitive constituent or shared imported/composed operation; never an executed action. */
final class Lifecycle_Member {
    public function __construct(public readonly int $type_id, public readonly int $index,
        public readonly ?Lifecycle_Operation $operation, public readonly int $kind) {
        Lifecycle_Roles::require_role($kind);
        if (($type_id < 1) || ($index < 0)) { throw new \InvalidArgumentException('Invalid lifecycle member identity'); }
        if ($operation !== null) {
            if ($operation->kind !== $kind) { throw new \InvalidArgumentException('Constituent lifecycle role differs from its implementation'); }
        }
    }
}

/** Tagged imported identity or compiler-composed plan. No host/native execution is performed. */
final class Lifecycle_Operation {
    private array $members /** vector<Lifecycle_Member> */ = [];
    // Full constructor validates the tagged record; named factories keep producer calls readable.
    public function __construct(public readonly bool $imported, public readonly int $kind,
        public readonly string $link_name, public readonly string $calling_convention,
        public readonly string $provider, public readonly string $provider_id,
        public readonly int $type_id, array $members /** vector<Lifecycle_Member> */,
        public readonly int $repeat, public readonly int $body_symbol_id) {
        Lifecycle_Roles::require_role($kind);
        if (($link_name === '') || ($calling_convention === '')) { throw new \InvalidArgumentException('Lifecycle operation requires linkage identity'); }
        if ($imported) {
            if (($provider === '') || ($provider_id === '') || ($type_id !== 0) || (q_count($members) !== 0)
                || ($repeat !== 0) || ($body_symbol_id !== 0)) { throw new \InvalidArgumentException('Invalid imported lifecycle payload'); }
        } else {
            if (($provider !== '') || ($provider_id !== '') || ($type_id < 1) || ($repeat < 0) || ($body_symbol_id < 0)) { throw new \InvalidArgumentException('Invalid source lifecycle payload'); }
            $order = Lifecycle_Roles::composition($kind, $body_symbol_id !== 0);
            if ((int)$order->member_kind === \type_model\LIFECYCLE_NONE) {
                if (q_count($members) !== 0) { throw new \InvalidArgumentException('Custom assignment owns its field updates'); }
            }
            foreach ($members as $member) {
                $allowed = $member->kind === (int)$order->member_kind;
                if ($kind === \type_model\LIFECYCLE_MOVE) {
                    if ($member->kind === \type_model\LIFECYCLE_COPY) { $allowed = true; }
                }
                if (!$allowed) { throw new \InvalidArgumentException('Constituent lifecycle role differs from its complete operation'); }
                $this->members[] = $member;
            }
        }
    }
    public static function runtime(string $provider, string $id, string $link, string $convention, int $kind): Lifecycle_Operation {
        $none /** vector<Lifecycle_Member> */ = [];
        return new Lifecycle_Operation(true, $kind, $link, $convention, $provider, $id, 0, $none, 0, 0);
    }
    public static function source(int $type_id, string $link, int $kind, array $members /** vector<Lifecycle_Member> */,
        int $repeat, string $convention, int $body_symbol_id): Lifecycle_Operation {
        return new Lifecycle_Operation(false, $kind, $link, $convention, '', '', $type_id, $members, $repeat, $body_symbol_id);
    }
    public function order(): Lifecycle_Order {
        if ($this->imported) { throw new \LogicException('Imported lifecycle operation has no source composition order'); }
        return Lifecycle_Roles::composition($this->kind, $this->body_symbol_id !== 0);
    }
    public function member_count(): int { return q_count($this->members); }
    public function member_at(int $index): Lifecycle_Member {
        if (($index < 0) || ($index >= q_count($this->members))) { throw new \InvalidArgumentException('Invalid lifecycle member index'); }
        return $this->members[$index];
    }
}
