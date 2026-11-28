
/** Common semantic query for native and nominal text conversion, independent of consumer flavor. */
final class Stringable_Contract
{
    public const NAME = @name@;
    public const METHOD = @method@;
    public const SOURCE = @source@;

    /** Return the native binding or nominal interface signature. Ordinary call preparation selects the override. */
    public static function target(type_definition $type, scope $symbols, bool $fallback = false): runtime_function|ast_function_declaration|null
    {
        $interface = $symbols->stringable;
        if ($type instanceof shared_record_type_definition && $type->required && $interface !== null
            && $type->payload->derives_from($interface->definition)) {
            return $interface->methods[self::METHOD];
        }
        return $type->native_string_formatter($fallback);
    }
}
