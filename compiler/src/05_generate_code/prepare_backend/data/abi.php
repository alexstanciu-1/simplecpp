<?php
declare(strict_types=1);
namespace prepare_backend;

/** Physical LLVM spelling, separate from source type meaning. */
final class Abi_Parameter {
    public function __construct(public readonly string $type, public readonly int $extension = 0) {
        \type_model\Callable_Modes::extension_name($extension);
    }
}
final class Abi_Target {
    public function __construct(public readonly string $link_name, public readonly string $calling_convention,
        public readonly string $return_type, public readonly array $parameters /** vector<Abi_Parameter> */,
        public readonly int $return_extension = 0, public readonly ?\type_model\Lifecycle_Operation $lifecycle_operation = null) {
        \type_model\Callable_Modes::extension_name($return_extension);
    }
}
const INTEGER_ADAPT_IDENTITY = 0;
const INTEGER_ADAPT_TRUNCATE = 1;
const INTEGER_ADAPT_SIGN_EXTEND = 2;
const INTEGER_ADAPT_ZERO_EXTEND = 3;
final class Integer_Adaptations {
    public static function name(int $kind): string {
        $name = '';
        if ($kind === 0) { $name = 'identity'; }
        elseif ($kind === 1) { $name = 'trunc'; }
        elseif ($kind === 2) { $name = 'sext'; }
        elseif ($kind === 3) { $name = 'zext'; }
        else { throw new \InvalidArgumentException('Invalid integer adaptation'); }
        return $name;
    }
}
