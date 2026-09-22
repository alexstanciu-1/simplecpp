<?php
declare(strict_types=1);
namespace resolve_types;

/** Complete tagged identity encoding; only factories construct valid keys. No local type IDs or digests. */
final class Export_Type_Identity {
    private string $encoded = '';
    private bool $source_value = false;
    public function key(): string {
        if ($this->encoded === '') { throw new \LogicException('Export identity requires a tagged factory'); }
        return $this->encoded;
    }
    public function is_source(): bool {
        if ($this->encoded === '') { throw new \LogicException('Export identity requires a tagged factory'); }
        return $this->source_value;
    }
    private static function make(string $encoded, bool $source): Export_Type_Identity {
        $out = new Export_Type_Identity(); $out->encoded = $encoded; $out->source_value = $source; return $out;
    }
    /** Match the retained JSON_UNESCAPED_SLASHES key protocol using the checked string encoder. */
    public static function quote(string $text): string {
        $quoted = json_quote($text); $out = ''; $size = string_byte_len($quoted);
        for ($index = 0; $index < $size; $index++) {
            if (string_byte_at($quoted,$index) === 92) {
                if ($index + 1 < $size) {
                    if (string_byte_at($quoted,$index + 1) === 47) { $out = $out . '/'; $index = $index + 1; continue; }
                }
            }
            $out = $out . string_byte_slice($quoted,$index,1);
        }
        return $out;
    }
    private static function arguments(array $arguments /** vector<Export_Argument> */): string {
        $out = '['; $separator = '';
        foreach ($arguments as $argument) { $out = $out . $separator . $argument->key; $separator = ','; }
        return $out . ']';
    }
    public static function language(string $provider, string $namespace_name, string $name): Export_Type_Identity {
        return Export_Type_Identity::make('["language",' . Export_Type_Identity::quote($provider) . ',' . Export_Type_Identity::quote($namespace_name) . ',' . Export_Type_Identity::quote($name) . ']',false);
    }
    public static function provided(string $provider, string $id): Export_Type_Identity {
        return Export_Type_Identity::make('["provider",' . Export_Type_Identity::quote($provider) . ',' . Export_Type_Identity::quote($id) . ']',false);
    }
    public static function family(string $provider, string $id, array $arguments /** vector<Export_Argument> */): Export_Type_Identity {
        return Export_Type_Identity::make('["family",' . Export_Type_Identity::quote($provider) . ',' . Export_Type_Identity::quote($id) . ',' . Export_Type_Identity::arguments($arguments) . ']',false);
    }
    public static function source(string $project, string $module, string $namespace_name, string $name, array $arguments /** vector<Export_Argument> */): Export_Type_Identity {
        return Export_Type_Identity::make('["source",' . Export_Type_Identity::quote($project) . ',[' . Export_Type_Identity::quote($module) . ',' . Export_Type_Identity::quote($namespace_name) . ',' . Export_Type_Identity::quote($name) . '],' . Export_Type_Identity::arguments($arguments) . ']',true);
    }
    public static function fixed_array(Export_Type_Identity $element, int $count): Export_Type_Identity {
        if ($count < 0) { throw new \InvalidArgumentException('Export array count must be nonnegative'); }
        return Export_Type_Identity::make('["array",' . $element->key() . ',' . Export_Type_Identity::quote("" . $count) . ']',false);
    }
}

/** Type and constant arguments retain their declared type and their position in a specialization. */
final class Export_Argument {
    public readonly string $key;
    public function __construct(Export_Type_Identity $type, ?string $value) {
        $text = '';
        if (take_nullable($text,$value)) {
            Export_Argument::require_integer($text);
            $this->key = '["constant",' . $type->key() . ',' . Export_Type_Identity::quote($text) . ']';
        } else { $this->key = '["type",' . $type->key() . ']'; }
    }
    private static function require_integer(string $text): void {
        if ($text === '0') { return; }
        $size = string_byte_len($text); $start = 0;
        if (string_byte_at($text,0) === 45) { $start = 1; }
        if ($start >= $size) { throw new \LogicException('Export constant must be a normalized integer literal'); }
        $first = string_byte_at($text,$start);
        if (($first < 49) || ($first > 57)) { throw new \LogicException('Export constant must be a normalized integer literal'); }
        for ($index = $start + 1; $index < $size; $index++) {
            $byte = string_byte_at($text,$index);
            if (($byte < 48) || ($byte > 57)) { throw new \LogicException('Export constant must be a normalized integer literal'); }
        }
    }
}
