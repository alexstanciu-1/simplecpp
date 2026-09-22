<?php
declare(strict_types=1);
namespace prepare_backend;

/** Fixed source export identity and private-result validation. Capture/preparation/join remain separate work. */
final class Source_Export_Preparation {
    private static function encode(string $text): string {
        $out = ''; $hex = '0123456789ABCDEF';
        for ($index = 0; $index < string_byte_len($text); $index++) {
            $byte = string_byte_at($text,$index);
            $plain = false;
            if (($byte >= 48) && ($byte < 58)) { $plain = true; }
            if (($byte >= 65) && ($byte < 91)) { $plain = true; }
            if (($byte >= 97) && ($byte < 123)) { $plain = true; }
            if ($plain) {
                $out .= string_byte_from_int($byte);
            } elseif ($byte === 95) { $out .= '__'; }
            else {
                $high = (int)($byte / 16); $low = $byte - $high * 16;
                $out .= '_x' . string_byte_slice($hex,$high,1) . string_byte_slice($hex,$low,1) . '_';
            }
        }
        return $out;
    }
    public static function symbol(\resolve_types\Export_Type_Identity $identity, int $role): string {
        $kind = Source_Export_Roles::implemented_kind($role);
        if ($kind === 0) { throw new \LogicException('Unsupported export role has no link symbol'); }
        $signature = \type_model\Lifecycle_Roles::has_source($kind) ? 'ccc:void(ptr,ptr)' : 'ccc:void(ptr)';
        return 'scpp_source_' . Source_Export_Preparation::encode(Source_Type_Export::profile()) . '_X_'
            . Source_Export_Preparation::encode($identity->key()) . '_X_' . Source_Export_Preparation::encode(Source_Export_Roles::name($role))
            . '_X_' . Source_Export_Preparation::encode($signature);
    }
    public static function validate(Source_Type_Export $result): void {
        $task = $result->task; $roles = Source_Export_Roles::all();
        if ((q_count($task->capabilities) !== q_count($roles)) || (q_count($result->operations) !== q_count($roles))) {
            throw new \LogicException('Incomplete source export capability table');
        }
        foreach ($roles as $role) {
            $name = Source_Export_Roles::name($role);
            if (!isset($task->capabilities[$name])) { throw new \LogicException('Incomplete source export capability table'); }
            if (!isset($result->operations[$name])) { throw new \LogicException('Incomplete source export operation table'); }
            $capability = $task->capabilities[$name]; $row = $result->operations[$name];
            if (($row->capability !== $capability) || ($capability->role !== $role)) { throw new \LogicException('Stale source export capability association'); }
            if ($capability->state !== \prepare_backend\EXPORT_AVAILABLE) {
                if (($row->implementation !== null) || ($row->import !== null)) { throw new \LogicException('Unavailable source capability acquired an ABI'); }
                continue;
            }
            $implementation = $row->implementation; $import = $row->import;
            if ($implementation === null) { throw new \LogicException('Missing source implementation'); }
            if ($import === null) { throw new \LogicException('Missing source import'); }
            if (($implementation->lifecycle_operation !== $capability->operation) || !Callable_Contract::lifecycle_matches($implementation,$task->layout->configuration)
                || ($import->link_name !== Source_Export_Preparation::symbol($task->identity,$role)) || ($import->calling_convention !== 'ccc')
                || ($import->return_type !== 'void') || ($import->return_extension !== 0) || ($import->lifecycle_operation !== null)) {
                throw new \LogicException('Stale source export ABI association');
            }
            if (q_count($import->parameters) !== q_count($implementation->parameters)) { throw new \LogicException('Source import parameter count mismatch'); }
            foreach ($implementation->parameters as $index => $parameter) {
                $other = $import->parameters[$index];
                if (($parameter->type !== $other->type) || ($parameter->extension !== $other->extension)) { throw new \LogicException('Source import parameter mismatch'); }
            }
        }
    }
}
