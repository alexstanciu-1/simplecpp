<?php
declare(strict_types=1);
namespace native_type_import_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $mode = $fixture->member('mode')->integer();
            $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1; $policy->assignment = 1;
            if ($mode === 1) { $policy->copy = 0; }
            if ($mode === 2) { $policy->assignment = 0; }
            $none /** vector<\type_model\Lifecycle_Operation> */ = [];
            $life = new \type_model\Lifetime_Contract($policy,$none);
            $language = new \type_model\Named_Definition('Owned','provider_ns',\type_model\Representation::opaque(8,8),$life,null,'',false,false,false);
            if ($mode === 4) { $language = new \type_model\Named_Definition('Void','provider_ns',\type_model\Representation::void_type(),null,null,'',false,false,false); }
            $missing = ''; take_nullable($missing,\type_model\Generic_Contracts::missing($language,\type_model\GENERIC_COPYABLE_VALUE));
            $matches = $missing === $fixture->member('missing')->text();
            $kind = \load_runtime\RUNTIME_STORAGE_OPAQUE;
            if ($mode === 5) { $kind = \load_runtime\RUNTIME_STORAGE_ADDRESS; }
            $identity = 'foreign'; if ($mode === 6) { $identity = 'different'; }
            $type = new \load_runtime\Runtime_Type($identity,new \load_runtime\Runtime_Storage($kind,8,8),null,null,$language);
            if ($mode === 3) { $type = new \load_runtime\Runtime_Type($identity,new \load_runtime\Runtime_Storage($kind,8,8),null,null,null); }
            $owner = new \load_runtime\Runtime_Type_Import('accepted','foreign',$type,'target','layout');
            $accepted = true;
            try {
                $row = $fixture->member('row'); $measurement = \load_runtime\Package_Type_Import::measure($row);
                $result = \load_runtime\Native_Type_Import::definition($row,$measurement,$owner);
                if ($result !== $language) { $matches = false; }
            } catch (\RuntimeException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
