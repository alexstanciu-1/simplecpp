<?php
declare(strict_types=1);
namespace export_identity_test;
final class Probe {
    public static function run(string $text): void {
        $base = \resolve_types\Export_Type_Identity::language('p','ns','I');
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index);
            $ok = true;
            if ($fixture->member('mode')->text() === 'invalid') {
                $rejected = false;
                try { $bad = new \resolve_types\Export_Argument($base,$fixture->member('value')->text()); }
                catch (\LogicException $error) { $rejected = true; }
                $ok = $rejected;
            } else {
                $spelling = $fixture->member('text')->text();
                $language = \resolve_types\Export_Type_Identity::language($spelling,'ns','I');
                $provider = \resolve_types\Export_Type_Identity::provided($spelling,'id');
                $arguments /** vector<\resolve_types\Export_Argument> */ = [];
                $arguments[] = new \resolve_types\Export_Argument($language,null);
                $arguments[] = new \resolve_types\Export_Argument($provider,$fixture->member('value')->text());
                $family = \resolve_types\Export_Type_Identity::family($spelling,'Vec',$arguments);
                $source = \resolve_types\Export_Type_Identity::source($spelling,$spelling,'ns','S',$arguments);
                $array_identity = $source;
                for ($level = 0; $level < $fixture->member('depth')->integer(); $level++) { $array_identity = \resolve_types\Export_Type_Identity::fixed_array($array_identity,$level); }
                $expected = $fixture->member('expected');
                $ok = ($language->key() === $expected->at(0)->text()) && ($provider->key() === $expected->at(1)->text())
                    && ($family->key() === $expected->at(2)->text()) && ($source->key() === $expected->at(3)->text()) && ($array_identity->key() === $expected->at(4)->text());
                if ($language->is_source() || $provider->is_source() || $family->is_source() || $array_identity->is_source() || !$source->is_source()) { $ok = false; }
                $arguments[] = new \resolve_types\Export_Argument($base,null);
                if ($source->key() !== $expected->at(3)->text()) { $ok = false; }
            }
            echo $ok ? "true\n" : "false\n";
        }
        $rejected = false;
        try { $invalid = new \resolve_types\Export_Type_Identity(); $key = $invalid->key(); } catch (\LogicException $error) { $rejected = true; }
        echo $rejected ? "true\n" : "false\n";
        $source_rejected = false;
        try { $invalid_source = new \resolve_types\Export_Type_Identity(); $flag = $invalid_source->is_source(); } catch (\LogicException $error) { $source_rejected = true; }
        echo $source_rejected ? "true\n" : "false\n";
        $largest = \resolve_types\Export_Type_Identity::fixed_array($base,9223372036854775807);
        echo ($largest->key() === '["array",["language","p","ns","I"],"9223372036854775807"]') ? "true\n" : "false\n";
        $negative = false;
        try { $invalid_array = \resolve_types\Export_Type_Identity::fixed_array($base,-1); } catch (\InvalidArgumentException $error) { $negative = true; }
        echo $negative ? "true\n" : "false\n";
    }
}
