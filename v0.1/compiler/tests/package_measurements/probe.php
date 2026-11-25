<?php
declare(strict_types=1);
namespace package_measurements_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $row = $fixture->member('row'); $accepted = true; $matches = true;
            try {
                $value = \load_runtime\Package_Type_Import::measure($row);
                if ($fixture->member('accept')->boolean()) {
                    $matches = ($value->id === $row->member('id')->text()) && ($value->storage->kind === $fixture->member('kind')->integer())
                        && ($value->storage->size_bytes === $fixture->member('size')->integer())
                        && ($value->storage->alignment_bytes === $fixture->member('alignment')->integer());
                    if ($value->storage->kind === \load_runtime\RUNTIME_STORAGE_INTEGER) {
                        $bits = 0; $signed_value = false;
                        if (!take_nullable($bits, $value->integer_bits)) { $matches = false; }
                        if (!take_nullable($signed_value, $value->signed)) { $matches = false; }
                        if (($bits !== $fixture->member('bits')->integer()) || ($signed_value !== $fixture->member('signed')->boolean())) { $matches = false; }
                    } else {
                        if (($value->integer_bits !== null) || ($value->signed !== null)) { $matches = false; }
                    }
                }
            } catch (\RuntimeException $error) { $accepted = false; }
            $ok = $accepted === $fixture->member('accept')->boolean();
            if ($accepted) { if (!$matches) { $ok = false; } }
            echo $ok ? "true\n" : "false\n";
        }
    }
}
