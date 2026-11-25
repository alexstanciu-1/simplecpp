<?php
declare(strict_types=1);
namespace lifecycle_import_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $role = $fixture->member('role')->integer();
            $raw = $fixture->member('operations');
            $operations /** hash<\scpp\Json_View> */ = [];
            for ($slot = 0; $slot < $raw->size(); $slot++) { $key = $raw->key($slot); $operations[$key] = $raw->member($key); }
            $accepted = true; $correct = true;
            try {
                if ($role === 0) {
                    $lifetime = \load_runtime\Lifecycle_Import::lifetime($fixture->member('type'), $operations, 'provider');
                    $policy = $lifetime->policy();
                    $actual = (int)$policy->copy . ':' . (int)$policy->cleanup . ':' . (int)$policy->construction . ':' . (int)$policy->assignment . ':' . (int)$policy->expiring;
                    $correct = $actual === $fixture->member('policy')->text();
                } else {
                    $op = \load_runtime\Lifecycle_Import::operation($fixture->member('type'), $operations, 'provider', $role);
                    $correct = $op->imported && ($op->kind === $role) && ($op->provider === 'provider') && ($op->provider_id === 'op') && ($op->link_name === 'bridge_op') && ($op->calling_convention === 'ccc');
                }
            } catch (\RuntimeException $error) { $accepted = false; }
            catch (\InvalidArgumentException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $correct) ? "true\n" : "false\n";
        }
    }
}
