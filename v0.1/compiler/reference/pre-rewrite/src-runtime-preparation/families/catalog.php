<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation\native_type;
use runtime_preparation\Definitions;
use type_model as model;

/** Validate boundary data into fixed semantic records and separate native bindings. */
final class Catalog
{
    /** Parse only the bounded native vocabulary; no compiler phases or native generation run here. */
    public static function parse(array $data, string $provider): family_catalog
    {
        Definitions::fields($data, ['schema_version', 'types', 'families'], 'family catalog');
        if ($data['schema_version'] !== 1) {
            throw new \RuntimeException('Unsupported family catalog version');
        }
        $types = [];
        foreach (self::rows($data['types']) as $row)
        {
            Definitions::validate_type($row);
            $id = self::id($row['id'] ?? null);
            if (isset($types[$id])) {
                throw new \RuntimeException('Duplicate family type identity');
            }
            // These are the ordinary language primitive and isolated plain-record contracts.
            $baseline = ($row['kind'] === 'integer') || (($row['kind'] === 'value_record')
                && (($row['copy'] ?? null) === 'value') && (($row['cleanup'] ?? null) === 'none'));
            $types[$id] = new native_type(new model\provider_type_reference($provider, $id), $row, $baseline);
        }
        $families = [];
        foreach (self::rows($data['families']) as $row) {
            $family = self::family($row, $provider, $types);
            if (isset($families[$family->semantic->id])) {
                throw new \RuntimeException('Duplicate family identity');
            }
            $families[$family->semantic->id] = $family;
        }
        return new family_catalog($provider, $types, $families);
    }

    /** Build formal identities first, then validate operation signatures against those exact slots. */
    private static function family(array $row, string $provider, array $types): family_binding
    {
        Definitions::fields($row, ['id', 'cpp_name', 'header', 'parameters', 'lifecycle', 'operations'], 'family', ['language_type']);
        $id = self::id($row['id']);
        $owner = json_encode([$provider, $id], JSON_THROW_ON_ERROR);
        $parameters = [];
        $profiles = [];
        $names = [];
        foreach (self::rows($row['parameters']) as $parameter)
        {
            if (!is_array($parameter)) {
                throw new \RuntimeException('Family parameter requires an explicit default contract');
            }
            Definitions::fields($parameter, ['name', 'contract'], 'family parameter', ['source_profiles']);
            $allowed = self::rows($parameter['source_profiles'] ?? []);
            foreach ($allowed as $profile) {
                if ($profile !== \runtime_preparation\project\source_type::PROFILE) {
                    throw new \RuntimeException('Unsupported source adapter profile');
                }
            }
            $profiles[] = $allowed;
            $name = self::id($parameter['name']);
            if (($name === 'self') || isset($names[$name]) || ($parameter['contract'] !== 'copyable_value')) {
                throw new \RuntimeException('Unsupported or duplicate family parameter contract');
            }
            $names[$name] = new model\parameter_type_reference($owner, count($parameters));
            $parameters[] = new model\family_parameter($name);
        }
        if ($parameters === []) {
            throw new \RuntimeException('Family requires type parameters');
        }
        $names['self'] = new model\family_type_reference($owner, array_values($names));
        $operations = [];
        $bindings = [];
        foreach (self::rows($row['operations']) as $operation)
        {
            $binding = self::operation($operation, $names, $types);
            $key = $binding->semantic->id;
            if (isset($operations[$key])) {
                throw new \RuntimeException('Duplicate family operation');
            }
            $operations[$key] = $binding->semantic;
            $bindings[$key] = $binding;
        }
        Definitions::fields($row['lifecycle'], ['construct', 'destroy'], 'family lifecycle', ['copy_construct', 'move_construct', 'copy_assign']);
        foreach ($row['lifecycle'] as $role => $operation) {
            if (($bindings[$operation]->kind ?? null) !== $role) {
                throw new \RuntimeException('Missing or incompatible family lifecycle implementation');
            }
        }
        $semantic = new model\family_definition($provider, $id, $parameters, $operations, $row['lifecycle'], self::exposure($row['language_type'] ?? null));
        model\Family_Contracts::validate($semantic);
        return new family_binding($semantic, self::cpp($row['cpp_name']), self::header($row['header']), $bindings, $profiles);
    }

    /** Export only explicit language mappings; C++ spelling/width never supplies source identity.
     * @return array<string, model\named_type_reference> Exact provider/type keys. */
    public static function language_bindings(family_catalog $catalog): array
    {
        $bindings = [];
        foreach ($catalog->types as $type) {
            $name = self::exposure($type->definition['language_type'] ?? null);
            if ($name !== null) {
                $bindings[json_encode([$type->identity->provider, $type->identity->id], JSON_THROW_ON_ERROR)] = $name;
            }
        }
        return $bindings;
    }

    /** Source exposure is independent of native identities and implementation names. */
    private static function exposure(mixed $row): ?model\named_type_reference
    {
        if ($row === null) {
            return null;
        }
        if (!is_array($row)) {
            throw new \RuntimeException('Expected source exposure record');
        }
        Definitions::fields($row, ['name', 'namespace'], 'source exposure');
        if (!is_string($row['name']) || !is_string($row['namespace'])) {
            throw new \RuntimeException('Invalid source exposure');
        }
        return new model\named_type_reference($row['name'], $row['namespace']);
    }

    /** Parse declared type positions only; unrelated native spelling is never substituted. */
    private static function operation(array $row, array $names, array $types): operation_binding
    {
        Definitions::fields($row, ['id', 'kind', 'error_policy'], 'family operation',
            ['type', 'parameters', 'result_type', 'cpp_name', 'header', 'cpp_template_arguments', 'requires', 'effects', 'receiver', 'expose_as', 'source_payload_result']);
        $id = self::id($row['id']);
        $kind = $row['kind'];
        if (!in_array($kind, ['construct', 'destroy', 'copy_construct', 'move_construct', 'copy_assign', 'free_function'], true)
            || ($row['error_policy'] !== 'terminate')) {
            throw new \RuntimeException('Unsupported family operation or error boundary');
        }
        $object = isset($row['type']) ? self::reference($row['type'], $names, $types) : null;
        if (($kind !== 'free_function') && ($object !== $names['self'])) {
            throw new \RuntimeException('Lifecycle operation must target the family instance');
        }
        if (in_array($kind, ['destroy', 'copy_construct', 'move_construct', 'copy_assign'], true) && isset($row['parameters'])) {
            throw new \RuntimeException('Implicit lifecycle parameters cannot be overridden');
        }
        if (($kind === 'free_function') && ($object !== null)) {
            throw new \RuntimeException('Free-function binding has no implicit object type');
        }
        [$parameters, $adaptations] = self::parameters($row['parameters'] ?? [], $names, $types);

        // The result remains semantic; a formal result is unresolved until argument binding.
        $voids = array_filter($types, static fn($type) => $type->definition['kind'] === 'void');
        if (count($voids) !== 1) {
            throw new \RuntimeException('Family catalog requires one void type');
        }
        $void = array_values($voids)[0]->identity;
        $result = $kind === 'free_function' ? self::reference($row['result_type'] ?? '', $names, $types)
            : (in_array($kind, ['destroy', 'copy_assign'], true) ? $void : $object);
        $production = ($result === $void) ? model\result_production::none
            : (in_array($kind, ['construct', 'copy_construct', 'move_construct'], true) ? model\result_production::owned : model\result_production::value);
        if ($result instanceof model\parameter_type_reference) {
            $production = model\result_production::dependent_value;
        }
        elseif ($result instanceof model\family_type_reference) {
            $production = model\result_production::owned;
        }
        if ($kind === 'destroy') {
            $parameters = [new model\semantic_parameter($object, model\argument_passing::borrow_mutable)];
        }
        elseif (in_array($kind, ['copy_construct', 'move_construct'], true)) {
            $parameters = [new model\semantic_parameter($object, $kind === 'move_construct' ? model\argument_passing::borrow_mutable : model\argument_passing::borrow_const)];
        }
        elseif ($kind === 'copy_assign') {
            $parameters = [new model\semantic_parameter($object, model\argument_passing::borrow_mutable),
                new model\semantic_parameter($object, model\argument_passing::borrow_const)];
        }
        $requirements = self::requirements($row['requires'] ?? [], $names);

        // Receiver exposure and alias relationships never change the ordinary signature positions.
        $receiver = $row['receiver'] ?? null;
        if (($receiver !== null) && ((!is_int($receiver)) || !isset($parameters[$receiver])
            || ($parameters[$receiver]->type !== $names['self']) || !$parameters[$receiver]->passing->is_borrow())) {
            throw new \RuntimeException('Invalid family receiver');
        }
        $effects = self::effects($row['effects'] ?? [], $parameters, $names['self']);
        $semantic = new model\family_operation($id, new model\semantic_signature($parameters, new model\semantic_result($result, $production)),
            $requirements, $effects, $receiver, self::exposure($row['expose_as'] ?? null));
        $templates = array_map(static fn($ref) => self::reference($ref, $names, $types), self::rows($row['cpp_template_arguments'] ?? []));
        $source_result = $row['source_payload_result'] ?? null;
        if (($source_result !== null) && (($source_result !== 'copy_out') || !($result instanceof model\parameter_type_reference))) {
            throw new \RuntimeException('Source result crossing requires a formal owned copy-out');
        }
        return new operation_binding($semantic, $kind, $kind === 'free_function' ? self::cpp($row['cpp_name'] ?? '') : '',
            $kind === 'free_function' ? self::header($row['header'] ?? '') : '', $templates, $adaptations, $object, $source_result);
    }

    /** Parse semantic argument use separately from the C++ value adaptation. */
    private static function parameters(array $rows, array $names, array $types): array
    {
        $parameters = [];
        $adaptations = [];
        foreach (self::rows($rows) as $parameter)
        {
            $adaptation = [];
            if (is_array($parameter))
            {
                $passing = match ($parameter['passing'] ?? '') {
                    'const_address' => model\argument_passing::borrow_const,
                    'mutable_address' => model\argument_passing::borrow_mutable,
                    'direct' => model\argument_passing::value,
                    default => throw new \RuntimeException('Unsupported family parameter passing'),
                };
                if ($passing->is_borrow())
                {
                    Definitions::fields($parameter, ['type', 'passing', 'borrow_scope'], 'family borrow', ['source_payload']);
                    if (isset($parameter['source_payload']))
                    {
                        if (($parameter['source_payload'] !== 'copy_in') || ($passing !== model\argument_passing::borrow_const)) {
                            throw new \RuntimeException('Source parameter crossing requires const payload copy-in');
                        }
                        $adaptation['source_payload'] = 'copy_in';
                    }
                    if ($parameter['borrow_scope'] !== 'call') {
                        throw new \RuntimeException('Only call-scoped family borrowing is supported');
                    }
                }
                else {
                    Definitions::fields($parameter, ['type', 'passing', 'cpp_passing'], 'family value');
                    if ($parameter['cpp_passing'] !== 'const_reference') {
                        throw new \RuntimeException('Unsupported native value adaptation');
                    }
                    $adaptation = ['cpp_passing' => 'const_reference'];
                }
                $parameter = $parameter['type'];
            }
            else {
                $passing = model\argument_passing::value;
            }
            $parameters[] = new model\semantic_parameter(self::reference($parameter, $names, $types), $passing);
            $adaptations[] = $adaptation;
        }
        return [$parameters, $adaptations];
    }

    /** Only requirements guaranteed by the declared default contract are supported in this slice. */
    private static function requirements(array $rows, array $names): array
    {
        $requirements = [];
        foreach (self::rows($rows) as $requirement)
        {
            Definitions::fields($requirement, ['parameter', 'operation'], 'capability requirement');
            $formal = $names[$requirement['parameter']] ?? null;
            $role = model\lifecycle_operation_kind::tryFrom($requirement['operation']);
            if (!($formal instanceof model\parameter_type_reference)
                || ($role === null) || !model\generic_contract::copyable_value->permits($role)) {
                throw new \RuntimeException('Requirement is outside the default generic baseline');
            }
            $requirements[] = new model\capability_requirement($formal->slot, $role);
        }
        return $requirements;
    }

    /** Normalize only the two agreed element relationships, with parameter/type validity checked here. */
    private static function effects(array $rows, array $parameters, model\family_type_reference $self): array
    {
        $effects = [];
        foreach (self::rows($rows) as $row)
        {
            Definitions::fields($row, ['kind', 'receiver'], 'family effect', ['input']);
            $receiver = $row['receiver'];
            if (!is_int($receiver) || (($parameters[$receiver]->type ?? null) !== $self)
                || ($parameters[$receiver]->passing !== model\argument_passing::borrow_mutable)) {
                throw new \RuntimeException('Invalid effect receiver');
            }
            $input = null;
            if ($row['kind'] === 'safe_element_input')
            {
                $input = $row['input'] ?? null;
                if (!is_int($input) || !isset($parameters[$input]) || ($input === $receiver)
                    || ($parameters[$input]->passing !== model\argument_passing::borrow_const)
                    || !in_array($parameters[$input]->type, $self->arguments, true)) {
                    throw new \RuntimeException('Invalid safe element input');
                }
            }
            elseif (($row['kind'] !== 'invalidate_elements') || isset($row['input'])) {
                throw new \RuntimeException('Unsupported family effect');
            }
            $effects[] = new model\element_effect(model\element_effect_kind::from($row['kind']), $receiver, $input);
        }
        return $effects;
    }

    /** Parse one explicitly declared type position. */
    public static function reference(mixed $text, array $names, array $types): model\type_reference
    {
        if (!is_string($text)) {
            throw new \RuntimeException('Type reference must be a declared name or parameter');
        }
        return str_starts_with($text, '$') ? ($names[substr($text, 1)] ?? throw new \RuntimeException('Unknown formal type reference'))
            : ($types[$text]->identity ?? throw new \RuntimeException('Unknown named type reference'));
    }

    public static function rows(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new \RuntimeException('Expected ordered family rows');
        }
        return $value;
    }

    public static function id(mixed $value): string
    {
        Definitions::identifier($value, '/^[a-z][a-z0-9_.]*$/D', 'family identity');
        return $value;
    }

    public static function cpp(mixed $value): string
    {
        Definitions::identifier($value, '/^[A-Za-z_][A-Za-z0-9_]*(?:::[A-Za-z_][A-Za-z0-9_]*)*$/D', 'native binding');
        return $value;
    }

    public static function header(mixed $value): string
    {
        if (!is_string($value) || !preg_match('~^[A-Za-z0-9_./-]+$~D', $value) || str_contains($value, '..')) {
            throw new \RuntimeException('Invalid family header');
        }
        return $value;
    }
}
