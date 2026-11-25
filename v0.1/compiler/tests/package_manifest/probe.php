<?php
declare(strict_types=1);
namespace package_manifest_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $accepted = true; $matches = true;
            try {
                $pointer = \load_runtime\Manifest_Reader::pointer($fixture->member('pointer'));
                $manifest = \load_runtime\Manifest_Reader::manifest($fixture->member('manifest'),$pointer,$fixture->member('project')->boolean());
                $metadata = \load_runtime\Manifest_Reader::metadata($fixture->member('metadata'),$manifest);
                if (!$fixture->member('accept')->boolean()) { $matches = false; }
                else {
                $matches = ($pointer->input_key === 'key') && (string_byte_len($pointer->manifest_sha256) === 64)
                    && ($manifest->provider === 'p') && ($manifest->input_key === 'key') && ($manifest->target->triple === 't')
                    && ($manifest->target->data_layout === 'd') && ($manifest->project === $fixture->member('project')->boolean())
                    && ($manifest->metadata === 'metadata.json') && ($manifest->modules['ordinary'] === 'runtime.bc')
                    && ($manifest->modules['full_lto'] === 'full.bc') && ($manifest->modules['thin_lto'] === 'thin.bc')
                    && (q_count($manifest->modules) === 3) && ($manifest->link_driver === '/clang') && (q_count($manifest->link_arguments) === 2)
                    && ($manifest->link_arguments[1] === '--target=t') && (string_byte_len($manifest->driver_hashes['/clang']) === 64)
                    && (q_count($metadata->types) === 1) && (q_count($metadata->operations) === 1);
                if ($metadata->types[0]->member('id')->text() !== 'T') { $matches = false; }
                if ($metadata->operations[0]->member('id')->text() !== 'op') { $matches = false; }
                $artifacts = $manifest->artifacts; $original = q_count($artifacts); $artifacts['later'] = 'changed';
                if (q_count($manifest->artifacts) !== $original) { $matches = false; }
                }
            } catch (\RuntimeException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
