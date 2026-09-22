<?php
declare(strict_types=1);
namespace callable_import_test;
final class Probe {
    public static function run(string $text): void {
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $policy = new \type_model\Lifetime_Policy();
        $life = new \type_model\Lifetime_Contract($policy, $operations);
        $word = new \type_model\Named_Definition('Word', '', \type_model\Representation::integer(32), $life, true, '', false, false, true);
        $object = new \type_model\Named_Definition('Object', '', \type_model\Representation::opaque(8, 8), $life, null, '', false, false, false);
        $span = new \type_model\Named_Definition('Span', '', \type_model\Representation::byte_span(), $life, null, '', false, false, false);
        $void_type = new \type_model\Named_Definition('Void', '', \type_model\Representation::void_type(), null, null, '', false, false, false);
        $fields /** vector<\type_model\Field_Declaration> */ = [];
        $record = new \type_model\Record_Declaration('Row', '', $fields, true, 0, null, 0, 0, 0, 0);
        $types /** hash<\load_runtime\Runtime_Type> */ = [];
        $types['word'] = new \load_runtime\Runtime_Type('word', new \load_runtime\Runtime_Storage(0,4,4),32,true,$word);
        $types['object'] = new \load_runtime\Runtime_Type('object', new \load_runtime\Runtime_Storage(4,8,8),null,null,$object);
        $types['span'] = new \load_runtime\Runtime_Type('span', new \load_runtime\Runtime_Storage(2,16,8),null,null,$span);
        $types['void'] = new \load_runtime\Runtime_Type('void', new \load_runtime\Runtime_Storage(3,0,1),null,null,$void_type);
        $types['record'] = new \load_runtime\Runtime_Type('record', new \load_runtime\Runtime_Storage(5,8,8),null,null,null,$record);
        $types['hidden'] = new \load_runtime\Runtime_Type('hidden', new \load_runtime\Runtime_Storage(4,8,8),null,null,null);
        $other = new \type_model\Named_Definition('Other', '', \type_model\Representation::opaque(8,8), $life, null, '', false, false, false);
        $paths /** vector<vector<int>> */ = [];
        $owner = new \type_model\Named_Definition('Owner', '', \type_model\Representation::opaque(8,8), $life, null, '', false, false, false, new \type_model\Resource_Obligations(1,$paths));
        $types['other'] = new \load_runtime\Runtime_Type('other', new \load_runtime\Runtime_Storage(4,8,8),null,null,$other);
        $types['owner'] = new \load_runtime\Runtime_Type('owner', new \load_runtime\Runtime_Storage(4,8,8),null,null,$owner);
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $actual = '';
            $bindings /** hash<\type_model\Type_Reference> */ = [];
            $raw = $fixture->member('bindings');
            for ($slot = 0; $slot < $raw->size(); $slot++) {
                $id = $raw->key($slot); $item = $raw->member($id);
                $binding = \type_model\Type_Reference::named($item->member('name')->text(), $item->member('namespace')->text());
                if ($item->has('provided')) { $binding = \type_model\Type_Reference::provided('provider','id'); }
                $bindings[$id] = $binding;
            }
            $payloads /** vector<string> */ = []; $raw_payloads = $fixture->member('payloads');
            for ($slot = 0; $slot < $raw_payloads->size(); $slot++) { $payloads[] = $raw_payloads->at($slot)->text(); }
            try {
                $rows = \load_runtime\Package_Syntax::rows($fixture->member('rows'), 'operation');
                $accepted = \load_runtime\Callable_Import::callables($rows,$types,'provider',$bindings,$payloads);
                foreach ($accepted as $callable) {
                    $role = -1; $purpose = -1; $effect_kind = 0;
                    take_nullable($role,$callable->language_binding); take_nullable($purpose,$callable->conversion_purpose);
                    $effect = $callable->signature->allocation_effect;
                    if ($effect !== null) { $effect_kind = $effect->kind; }
                    $actual = $actual . $callable->id . '/' . $callable->name . '/' . $callable->namespace_name . '/' . $callable->signature->parameter_count() . '/' . $callable->abi->result_passing . '/' . $role . '/' . ($callable->default_literal ? '1' : '0') . '/' . $purpose . '/' . $effect_kind . ';';
                    if (($callable->provider !== 'provider') || ($callable->abi->link_name !== 'bridge_' . $callable->id)) { $actual = 'wrong identity'; }
                    $offset = $callable->abi->result_passing;
                    for ($slot = 0; $slot < $callable->abi->parameter_count(); $slot++) {
                        $indices = $callable->abi->parameter_indices($slot);
                        if ($indices[0] !== $offset) { $actual = 'wrong physical slot'; }
                        $offset = $offset + q_count($indices);
                    }
                }
            } catch (\RuntimeException $error) { $actual = 'error'; }
            catch (\InvalidArgumentException $error) { $actual = 'error'; }
            echo $actual === $fixture->member('want')->text() ? "true\n" : "false\n";
        }
    }
}
