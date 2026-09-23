<?php
declare(strict_types=1);
namespace resolve_types;
/** Concrete storage identity and eligibility. Callable signatures belong to later signature preparation. */
final class Storage_Definitions {
    public static function name(\type_model\Storage_Family $family, \type_model\Named_Definition $element): string {
        return '[' . json_quote($family->provider) . ',' . json_quote($family->id) . ',' . json_quote($element->namespace_name) . ',' . json_quote($element->name) . ']';
    }
    public static function eligible(\type_model\Named_Definition $element): bool {
        $ownership=$element->ownership;if($ownership!==null){if($ownership->has_owners()){return false;}}
        $lifetime=$element->lifetime;if($lifetime===null){return false;}$policy=$lifetime->policy();
        if(((int)$policy->copy!==\type_model\COPY_VALUE)&&((int)$policy->copy!==\type_model\COPY_CONSTRUCT)){return false;}
        if(((int)$policy->cleanup!==\type_model\CLEANUP_NONE)&&((int)$policy->cleanup!==\type_model\CLEANUP_DESTROY)){return false;}
        $kind=$element->representation->kind();
        return ($kind===\type_model\REPRESENTATION_INTEGER)||($kind===\type_model\REPRESENTATION_STRUCTURE)||($kind===\type_model\REPRESENTATION_OPAQUE);
    }
    /** Writes only the accepting phase's private canonical candidate. */
    public static function materialize(\type_model\Storage_Family $family, \type_model\Named_Definition $element,
        \type_model\Type_Store $types): \type_model\Named_Definition {
        $name=Storage_Definitions::name($family,$element);$namespace_name=string_byte_from_int(0).'element_storage';
        $known=$types->find_type($name,$namespace_name);
        if($known!==0){
            $definition=$types->definition_for_type($known);$storage=$definition->element_storage;
            if($storage===null){throw new \LogicException('Stale concrete storage definition');}
            if(($storage->family!==$family)||($storage->element!==$element)){throw new \LogicException('Stale concrete storage definition');}
            return $definition;
        }
        if(!Storage_Definitions::eligible($element)){throw new \LogicException('Unvalidated storage element reached type acceptance');}
        $element_id=Type_Cache::materialize($types,$element);$descriptor=$family->descriptor;
        $paths /** vector<vector<int>> */ = [];
        $ownership=new \type_model\Resource_Obligations(\type_model\RESOURCE_ALLOCATION,$paths);
        $definition=new \type_model\Named_Definition($name,$namespace_name,$descriptor->representation,$descriptor->lifetime,null,'',false,false,$descriptor->struct_field,$ownership,null,new \type_model\Element_Storage($family,$element_id,$element));
        Type_Cache::materialize($types,$definition);return $definition;
    }
}
