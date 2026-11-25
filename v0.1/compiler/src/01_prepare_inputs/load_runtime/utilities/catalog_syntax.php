<?php
declare(strict_types=1);
namespace load_runtime;

/** Strict version-1 scalar catalog schema, over immutable JSON views. */
final class Catalog_Syntax {
    private static function fields(\scpp\Json_View $node, array $required /** vector<string> */, array $optional /** vector<string> */): void {
        if ($node->kind() !== 'object') { throw new \InvalidArgumentException('Expected type catalog object'); }
        $allowed /** hash<bool> */ = [];
        foreach ($required as $name) {
            if (!$node->has($name)) { throw new \InvalidArgumentException('Missing catalog field: ' . $name); }
            $allowed[$name] = true;
        }
        foreach ($optional as $name) { $allowed[$name] = true; }
        for ($i /** int */ = 0; $i < $node->size(); ++$i) {
            $key = $node->key($i);
            if (!isset($allowed[$key])) { throw new \InvalidArgumentException('Unknown catalog field: ' . $key); }
        }
    }
    private static function text(\scpp\Json_View $node): string {
        if ($node->kind() !== 'string') { throw new \InvalidArgumentException('Expected catalog string'); }
        return $node->text();
    }
    private static function integer(\scpp\Json_View $node): int {
        $value = 0;
        try { $value = $node->integer(); } catch (\RuntimeException $error) { throw new \InvalidArgumentException('Expected catalog integer'); }
        return $value;
    }
    private static function boolean(\scpp\Json_View $node): bool {
        if ($node->kind() !== 'boolean') { throw new \InvalidArgumentException('Expected catalog boolean'); }
        return $node->boolean();
    }
    private static function lifetime(\scpp\Json_View $node, bool $zero): ?\type_model\Lifetime_Contract {
        if ($node->kind() === 'null') { return null; }
        $required /** vector<string> */ = ['copy','cleanup']; $none /** vector<string> */ = [];
        Catalog_Syntax::fields($node,$required,$none);
        $copy = \type_model\Lifetime_Policies::decode('copy',Catalog_Syntax::text($node->member('copy')));
        $cleanup = \type_model\Lifetime_Policies::decode('cleanup',Catalog_Syntax::text($node->member('cleanup')));
        if (($copy === \type_model\COPY_CONSTRUCT) || ($cleanup !== \type_model\CLEANUP_NONE)) { throw new \InvalidArgumentException('Scalar catalog cannot bind executable lifecycle operations'); }
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = $copy; $policy->cleanup = $cleanup;
        if ($zero) { $policy->construction = \type_model\CONSTRUCTION_ZERO; }
        if ($copy === \type_model\COPY_VALUE) { $policy->assignment = \type_model\ASSIGNMENT_VALUE; $policy->expiring = \type_model\EXPIRING_VALUE; }
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Lifetime_Contract($policy,$operations);
    }
    private static function definition(\scpp\Json_View $node): \type_model\Named_Definition {
        $common /** vector<string> */ = ['name','namespace','kind','lifetime'];
        $possible /** vector<string> */ = ['bit_width','signed','integer_family','addition','comparison','struct_field','format'];
        Catalog_Syntax::fields($node,$common,$possible);
        $name = Catalog_Syntax::text($node->member('name')); $namespace_name = Catalog_Syntax::text($node->member('namespace'));
        $kind = Catalog_Syntax::text($node->member('kind'));
        $shape = \type_model\Representation::void_type();
        $signed_value /** nullable<bool> */ = null;
        $family = ''; $addition = false; $comparison = false; $field = false;
        $optional /** vector<string> */ = [];
        if ($kind === 'integer') {
            $common[] = 'bit_width'; $common[] = 'signed';
            $optional = ['integer_family','addition','comparison','struct_field'];
            Catalog_Syntax::fields($node,$common,$optional);
            $shape = \type_model\Representation::integer(Catalog_Syntax::integer($node->member('bit_width')));
            $signed_value = Catalog_Syntax::boolean($node->member('signed'));
            if ($node->has('integer_family')) {
                $family = Catalog_Syntax::text($node->member('integer_family'));
                if ($family === '') { throw new \InvalidArgumentException('Integer family must be nonempty'); }
            }
            if ($node->has('addition')) {
                if (Catalog_Syntax::text($node->member('addition')) !== 'wrapping') { throw new \InvalidArgumentException('Unsupported integer addition'); }
                $addition = true;
            }
            if ($node->has('comparison')) {
                if (Catalog_Syntax::text($node->member('comparison')) !== 'ordered') { throw new \InvalidArgumentException('Unsupported integer comparison'); }
                $comparison = true;
            }
            if ($node->has('struct_field')) { $field = Catalog_Syntax::boolean($node->member('struct_field')); }
        } else if ($kind === 'floating_point') {
            $common[] = 'format'; Catalog_Syntax::fields($node,$common,$optional);
            $shape = \type_model\Representation::floating(Catalog_Syntax::text($node->member('format')));
        } else if ($kind === 'void') { Catalog_Syntax::fields($node,$common,$optional); }
        else { throw new \InvalidArgumentException('Unsupported named type representation'); }
        return new \type_model\Named_Definition($name,$namespace_name,$shape,Catalog_Syntax::lifetime($node->member('lifetime'),$kind === 'integer'),$signed_value,$family,$addition,$comparison,$field);
    }
    private static function binding(array $definitions /** vector<\type_model\Named_Definition> */, \scpp\Json_View $node): \type_model\Named_Definition {
        $fields /** vector<string> */ = ['name','namespace']; $none /** vector<string> */ = [];
        Catalog_Syntax::fields($node,$fields,$none);
        $name = Catalog_Syntax::text($node->member('name')); $namespace_name = Catalog_Syntax::text($node->member('namespace'));
        $index = -1;
        for ($i /** int */ = 0; $i < q_count($definitions); ++$i) {
            $definition = $definitions[$i];
            if (($definition->name === $name) && ($definition->namespace_name === $namespace_name)) {
                if ($definition->representation->kind() === \type_model\REPRESENTATION_INTEGER) { $index = $i; }
            }
        }
        if ($index < 0) { throw new \InvalidArgumentException('Catalog default must refer to a defined integer'); }
        return $definitions[$index];
    }
    private static function boolean_binding(array $definitions /** vector<\type_model\Named_Definition> */, \scpp\Json_View $literal): ?\type_model\Named_Definition {
        if (!$literal->has('boolean')) { return null; }
        return Catalog_Syntax::binding($definitions,$literal->member('boolean'));
    }
    public static function parse(string $content): \type_model\Type_Catalog {
        $root = json_read($content);
        $fields /** vector<string> */ = ['schema_version','provider','representation_scope','types','literal_types','entry_return_type'];
        $none /** vector<string> */ = [];
        Catalog_Syntax::fields($root,$fields,$none);
        if (Catalog_Syntax::integer($root->member('schema_version')) !== 1) { throw new \InvalidArgumentException('Expected version-1 type catalog'); }
        $provider = Catalog_Syntax::text($root->member('provider'));
        $scope = Catalog_Syntax::text($root->member('representation_scope'));
        $types = $root->member('types');
        if ($types->kind() !== 'array') { throw new \InvalidArgumentException('Catalog types must be a list'); }
        $definitions /** vector<\type_model\Named_Definition> */ = [];
        for ($i /** int */ = 0; $i < $types->size(); ++$i) { $definitions[] = Catalog_Syntax::definition($types->at($i)); }
        $literal = $root->member('literal_types');
        $required /** vector<string> */ = ['integer']; $optional /** vector<string> */ = ['boolean'];
        Catalog_Syntax::fields($literal,$required,$optional);
        $integer = Catalog_Syntax::binding($definitions,$literal->member('integer'));
        $entry = Catalog_Syntax::binding($definitions,$root->member('entry_return_type'));
        // Exact bytes are a complete content identity; no unproved hash approximation.
        return new \type_model\Type_Catalog($provider,'catalog-v1:' . $content,$scope,$definitions,$integer,$entry,Catalog_Syntax::boolean_binding($definitions,$literal));
    }
}
