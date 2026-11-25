<?php
declare(strict_types=1);

namespace scpp\portability;

require_once __DIR__ . '/exception_policy.php';

/** Fixed host-facade type bindings, not symbol resolution or inferred ancestry. */
final class Type_Names {
    public static function name(string $name): string {
        if (strcasecmp($name, '\\scpp\\Process_Handle') === 0) {
            return '\\process_handle';
        }
        return Exception_Policy::name($name);
    }
}
