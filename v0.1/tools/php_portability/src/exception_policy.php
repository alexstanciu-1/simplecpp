<?php
declare(strict_types=1);

namespace scpp\portability;

/** Fixed root PHP exception spellings; never infer a user class's ancestry. */
final class Exception_Policy {
    public const TYPES = [
        'Throwable' => ['exception', null],
        'Exception' => ['exception', null],
        'LogicException' => ['logic_exception', 'exception'],
        'InvalidArgumentException' => ['invalid_argument_exception', 'logic_exception'],
        'RuntimeException' => ['runtime_exception', 'exception'],
        'OutOfBoundsException' => ['out_of_bounds_exception', 'runtime_exception'],
        'RangeException' => ['range_exception', 'runtime_exception'],
        'JsonException' => ['json_exception', 'exception'],
        'OverflowException' => ['overflow_exception', 'runtime_exception'],
    ];

    public static function name(string $name): string {
        foreach (self::TYPES as $php => [$native]) {
            if (strcasecmp($name, '\\' . $php) === 0) {
                return '\\scpp_portability_' . $native;
            }
        }
        return $name;
    }

    public static function constructionName(string $name): string {
        foreach (self::TYPES as $php => $_) {
            if (strcasecmp($name, $php) === 0) {
                throw new \RuntimeException('Exception construction requires an explicitly qualified type');
            }
        }
        if (strcasecmp($name, '\\Throwable') === 0) { throw new \RuntimeException('Throwable is not constructible'); }
        return self::name($name);
    }

    public static function kind(string $name): ?int {
        $native = self::name($name);
        if ($native === $name) { return null; }
        return self::kinds()[substr($native, strlen('\\scpp_portability_'))];
    }

    private static function kinds(): array {
        $result = [];
        foreach (self::TYPES as [$name]) {
            if (!isset($result[$name])) { $result[$name] = 1 << count($result); }
        }
        return $result;
    }

    private static function mask(string $name): int {
        $parents = [];
        foreach (self::TYPES as [$native, $parent]) { $parents[$native] = $parent; }
        $mask = 0;
        while ($name !== null) {
            $mask |= self::kinds()[$name];
            $name = $parents[$name];
        }
        return $mask;
    }

    /** Native counterpart only. PHP execution keeps the real built-in exceptions. */
    public static function nativeSource(): string {
        $source = <<<'PHS'
// Owned by the PHP portability framework. Regenerate with install_native_runtime.php.
// Global implementation names avoid a v0.1.76 namespaced runtime-base lookup gap.
class scpp_portability_exception extends Exception {
    protected int $kind_mask = 1;
    public function matches(int $kind): bool { return ($this->kind_mask & $kind) !== 0; }
    protected string $detail = "";
    protected int $error_code = 0;
    protected ?scpp_portability_exception $cause = null;
    public function __construct(string $message = "", int $code = 0, ?scpp_portability_exception $previous = null) {
        $this->detail = $message;
        $this->error_code = $code;
        $this->cause = $previous;
    }
    public function getMessage(): string { return $this->detail; }
    public function getCode(): int { return $this->error_code; }
    public function getPrevious(): ?scpp_portability_exception { return $this->cause; }
}

PHS;
        foreach (self::TYPES as [$name, $parent]) {
            if ($parent === null) { continue; }
            $source .= 'class scpp_portability_' . $name . ' extends scpp_portability_' . $parent . " {\n"
                . '    public function __construct(string $message = "", int $code = 0, ?scpp_portability_exception $previous = null) {' . "\n"
                . '        $this->kind_mask = ' . self::mask($name) . ';' . "\n"
                . '        $this->detail = $message; $this->error_code = $code; $this->cause = $previous;' . "\n    }\n}\n";
        }
        $source .= <<<'PHS'
function scpp_portability_same_exception(scpp_portability_exception $left, scpp_portability_exception $right): bool {
    return $left === $right;
}
PHS;
        return $source;
    }
}
