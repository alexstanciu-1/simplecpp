<?php
declare(strict_types=1);
namespace resolve_types;
/** Selection consumes prior publication; annotation workers do not depend on snapshots. */
final class Signature_Selection {
    public static function select(\collect_symbols\Symbol_Store $symbols, \instantiate\Instance_View $instances,
        Entry_Contract $entry, \type_model\Type_Store $types, Signature_Set $previous,
        array $prepared /** hash<\type_model\Runtime_Callable,int> */, bool $full): array /** vector<Callable_Input> */ {
        $tasks /** vector<Callable_Input> */ = [];
        foreach (Callable_Inputs::all($symbols,$instances) as $input) {
            if (!Signature_Resolver::participates($input,$entry)) { continue; }
            if ($full) { $tasks[] = $input; }
            else if (!Signature_Validity::is_current($previous,$types,$input,$prepared)) { $tasks[] = $input; }
        }
        return $tasks;
    }
}
