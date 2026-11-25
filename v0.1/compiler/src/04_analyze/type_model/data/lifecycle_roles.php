<?php
declare(strict_types=1);
namespace type_model;
const LIFECYCLE_NONE = 0;
const LIFECYCLE_DEFAULT = 1;
const LIFECYCLE_DESTROY = 2;
const LIFECYCLE_COPY = 3;
const LIFECYCLE_MOVE = 4;
const LIFECYCLE_ASSIGN = 5;

/** @scpp-struct */
final class Lifecycle_Order {
    public int $member_kind /** uint32 */ = 0;
    public bool $body_before_members = false;
    public bool $reverse_members = false;
}

final class Lifecycle_Roles {
    public static function require_role(int $kind): void {
        if (($kind < \type_model\LIFECYCLE_DEFAULT) || ($kind > \type_model\LIFECYCLE_ASSIGN)) { throw new \InvalidArgumentException('Unknown lifecycle role'); }
    }
    public static function name(int $kind): string {
        Lifecycle_Roles::require_role($kind);
        $name = 'default_construct';
        if ($kind === \type_model\LIFECYCLE_DESTROY) { $name = 'destroy'; }
        else if ($kind === \type_model\LIFECYCLE_COPY) { $name = 'copy_construct'; }
        else if ($kind === \type_model\LIFECYCLE_MOVE) { $name = 'move_construct'; }
        else if ($kind === \type_model\LIFECYCLE_ASSIGN) { $name = 'copy_assign'; }
        return $name;
    }
    public static function parse(string $name): int {
        $kind = 0;
        if ($name === 'default_construct') { $kind = \type_model\LIFECYCLE_DEFAULT; }
        else if ($name === 'destroy') { $kind = \type_model\LIFECYCLE_DESTROY; }
        else if ($name === 'copy_construct') { $kind = \type_model\LIFECYCLE_COPY; }
        else if ($name === 'move_construct') { $kind = \type_model\LIFECYCLE_MOVE; }
        else if ($name === 'copy_assign') { $kind = \type_model\LIFECYCLE_ASSIGN; }
        else { throw new \InvalidArgumentException('Unknown lifecycle role'); }
        return $kind;
    }
    public static function has_source(int $kind): bool {
        Lifecycle_Roles::require_role($kind);
        return ($kind === \type_model\LIFECYCLE_COPY) || ($kind === \type_model\LIFECYCLE_MOVE) || ($kind === \type_model\LIFECYCLE_ASSIGN);
    }
    public static function creates_destination(int $kind): bool {
        Lifecycle_Roles::require_role($kind);
        return ($kind === \type_model\LIFECYCLE_DEFAULT) || ($kind === \type_model\LIFECYCLE_COPY) || ($kind === \type_model\LIFECYCLE_MOVE);
    }
    public static function composition(int $kind, bool $custom_body): Lifecycle_Order {
        Lifecycle_Roles::require_role($kind);
        $out = new Lifecycle_Order(); $out->member_kind = $kind;
        if ($custom_body) {
            if ($kind === \type_model\LIFECYCLE_COPY) { $out->member_kind = \type_model\LIFECYCLE_DEFAULT; }
            if ($kind === \type_model\LIFECYCLE_ASSIGN) { $out->member_kind = \type_model\LIFECYCLE_NONE; }
        }
        if ($kind === \type_model\LIFECYCLE_DESTROY) { $out->body_before_members = true; $out->reverse_members = true; }
        return $out;
    }
}
