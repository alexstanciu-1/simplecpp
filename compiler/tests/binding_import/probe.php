<?php
declare(strict_types=1);
namespace binding_import_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index);
            $source = \type_model\Type_Reference::named($fixture->member('source')->text(), $fixture->member('source_ns')->text());
            $target = \type_model\Type_Reference::named($fixture->member('target')->text(), $fixture->member('target_ns')->text());
            $parameters /** vector<\type_model\Semantic_Parameter> */ = [];
            for ($slot = 0; $slot < $fixture->member('count')->integer(); $slot++) { $parameters[] = new \type_model\Semantic_Parameter($source, $fixture->member('passing')->integer()); }
            $signature = new \type_model\Semantic_Signature($parameters, new \type_model\Semantic_Result($target, $fixture->member('production')->integer()));
            $actual = 'none';
            try {
                if ($fixture->member('mode')->integer() === 0) {
                    $binding = \load_runtime\Binding_Import::call_language_binding($fixture->member('row'), $signature);
                    $tag = -1;
                    if (take_nullable($tag, $binding->binding)) { $actual = \type_model\Callable_Modes::binding_name($tag); }
                    $actual = $actual . ($binding->default_literal ? ':true' : ':false');
                } else {
                    $conversion = \load_runtime\Binding_Import::call_conversion($fixture->member('row'), $signature);
                    $tag = -1;
                    if (take_nullable($tag, $conversion)) { $actual = \type_model\Callable_Modes::conversion_name($tag); }
                }
            } catch (\RuntimeException $error) { $actual = 'error'; }
            echo $actual === $fixture->member('want')->text() ? "true\n" : "false\n";
        }
        for ($bad_side = 0; $bad_side < 2; $bad_side++) {
            $source = \type_model\Type_Reference::named('Source', '');
            $target = \type_model\Type_Reference::named('Target', '');
            if ($bad_side === 0) { $source = \type_model\Type_Reference::provided('provider', 'id'); }
            else { $target = \type_model\Type_Reference::parameter('owner', 0); }
            $parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($source, 0)];
            $signature = new \type_model\Semantic_Signature($parameters, new \type_model\Semantic_Result($target, 1));
            $rejected = false;
            try { $conversion = \load_runtime\Binding_Import::call_conversion(json_read('{"kind":"free_function","conversion_purpose":"text"}'), $signature); }
            catch (\RuntimeException $error) { $rejected = true; }
            echo $rejected ? "true\n" : "false\n";
        }
        for ($bad_tag = 0; $bad_tag < 2; $bad_tag++) {
            $rejected = false;
            try {
                if ($bad_tag === 0) { $invalid = new \load_runtime\Call_Language_Binding(null, true); }
                else { $invalid = new \load_runtime\Call_Language_Binding(9, false); }
            } catch (\InvalidArgumentException $error) { $rejected = true; }
            echo $rejected ? "true\n" : "false\n";
        }
    }
}
