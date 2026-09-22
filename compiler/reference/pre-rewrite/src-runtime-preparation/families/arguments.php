<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation\native_type;
use runtime_preparation\Definitions;

/** Native type recipes cross package boundaries; package-local row names never establish identity. */
final class Arguments
{
    /** Normalize both catalog and prepared arguments, allocating local row names once per exact import. */
    public static function resolve(family_catalog $catalog, array $values): array
    {
        $used = array_fill_keys(array_keys($catalog->types), true);
        $imports = [];
        $arguments = [];
        $next = 0;
        foreach ($values as $value)
        {
            $type = is_string($value) ? ($catalog->types[$value] ?? null) : $value;
            if (!($type instanceof native_type)) {
                throw new \RuntimeException('Unknown concrete type argument');
            }
            if (isset($type->definition['native_import']) || isset($type->definition['source_payload']))
            {
                Definitions::validate_type($type->definition);
                if (isset($type->definition['source_payload'])) {
                    \runtime_preparation\project\Adapter::argument($type);
                }
                if (isset($type->definition['native_import'])
                    && ($type->definition['native_import'] !== ['provider' => $type->identity->provider, 'id' => $type->identity->id])) {
                    throw new \RuntimeException('Native argument identity mismatch');
                }
                $key = json_encode($type->identity, JSON_THROW_ON_ERROR);
                if (!isset($imports[$key]))
                {
                    do {
                        $id = 'argument' . $next++;
                    } while (isset($used[$id]));
                    $used[$id] = true;
                    $row = $type->definition;
                    $row['id'] = $id;
                    $imports[$key] = new native_type($type->identity, $row, $type->baseline,
                        $type->headers, $type->declarations, $type->contract, $type->context, $type->sources);
                }
                else
                {
                    $row = $type->definition;
                    $row['id'] = $imports[$key]->definition['id'];
                    $repeated = new native_type($type->identity, $row, $type->baseline,
                        $type->headers, $type->declarations, $type->contract, $type->context, $type->sources);
                    if ($imports[$key] != $repeated) {
                        throw new \RuntimeException('Conflicting native argument contracts');
                    }
                }
                $type = $imports[$key];
            }
            elseif (($catalog->types[$type->identity->id] ?? null) !== $type) {
                throw new \RuntimeException('Type arguments require exact catalog identities');
            }
            if (!$type->baseline) {
                throw new \RuntimeException('Type argument does not satisfy copyable_value: ' . $type->identity->id);
            }
            $arguments[] = $type;
        }
        return $arguments;
    }

    /** Merge native declaration dependencies in prerequisite order, rejecting conflicting alias definitions. */
    public static function declarations(array $arguments): array
    {
        $declarations = [];
        foreach ($arguments as $argument)
        {
            foreach ($argument->declarations as $name => $text) {
                if (isset($declarations[$name]) && ($declarations[$name] !== $text)) {
                    throw new \RuntimeException('Conflicting native type declaration');
                }
                $declarations[$name] = $text;
            }
        }
        return $declarations;
    }

}
