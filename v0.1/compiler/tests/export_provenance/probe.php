<?php
declare(strict_types=1);
namespace export_provenance_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $accepted = true; $matches = true;
            try {
                if ($fixture->member('mode')->text() === 'project') {
                    $source = $fixture->member('source')->text(); $key = $fixture->member('key')->text();
                    $byte = $fixture->member('byte')->integer(); $key_byte = $fixture->member('key_byte')->integer();
                    if ($byte >= 0) { $source = $source . string_byte_from_int($byte); }
                    if ($key_byte >= 0) { $key = $key . string_byte_from_int($key_byte); }
                    $project = new \compile\Native_Project($key,$source,$fixture->member('output')->text());
                    if ($fixture->member('accept')->boolean()) {
                        $expected = $fixture->member('expected_source')->text();
                        if ($byte >= 0) {
                            if (($byte !== 46) && ($byte !== 47)) {
                                $expected = $expected . string_byte_from_int($byte);
                                if (string_byte_at($project->source_root,3) !== $byte) { $matches = false; }
                            }
                        }
                        if (($project->source_root !== $expected) || ($project->output_root !== '/out') || ($project->project_key !== $key)) { $matches = false; }
                        $relocated = new \compile\Native_Project($key,'/relocated','/elsewhere');
                        if ($relocated->project_key !== $project->project_key) { $matches = false; }
                    }
                } else {
                    $values = $fixture->member('values');
                    $config = new \prepare_backend\Backend_Configuration($values->at(0)->text(),$values->at(1)->text(),$values->at(2)->text(),$values->at(3)->text(),$values->at(4)->text(),$values->at(5)->text(),$values->at(6)->text());
                    if (($config->backend_key !== $values->at(0)->text()) || ($config->target_triple !== $values->at(1)->text()) || ($config->data_layout !== $values->at(2)->text())
                        || ($config->cpu !== $values->at(3)->text()) || ($config->features !== $values->at(4)->text()) || ($config->abi_key !== $values->at(5)->text()) || ($config->runtime_key !== $values->at(6)->text())) { $matches = false; }
                }
            } catch (\InvalidArgumentException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
