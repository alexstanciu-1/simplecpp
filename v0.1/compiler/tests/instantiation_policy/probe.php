<?php
declare(strict_types=1);
namespace instantiation_policy_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $row = $cases->at($i);
            $expected = $row->member('value')->integer();
            $actual = 0;
            $failed = false;
            try { $actual = \instantiate\Instantiation_Policy::parse('policy.json', $row->member('text')->text()); }
            catch (\RuntimeException $error) { $failed = true; }
            catch (\JsonException $error) { $failed = true; }
            $valid = $actual === $expected;
            if ($expected === 0) { $valid = $failed; }
            else { if ($failed) { $valid = false; } }
            if ($i === 0) {
                $path = \instantiate\Instantiation_Policy::input_path('inputs');
                if ($path !== 'inputs/instantiation_limits.json') { $valid = false; }
                if (\instantiate\Instantiation_Policy::load('inputs/policy.json') !== 4096) { $valid = false; }
                $missing = false;
                try { $unused = \instantiate\Instantiation_Policy::load('inputs/missing-policy.json'); }
                catch (\RuntimeException $error) { $missing = true; }
                if (!$missing) { $valid = false; }
            }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
