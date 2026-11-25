<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/bootstrap.php';

final class Reuse_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new RuntimeException($message);
        }
    }

    public static function tool(\compile\Compiler_Session $session): \prepare_backend\LLVM_Toolchain
    {
        return (new ReflectionProperty($session, 'toolchain'))->getValue($session);
    }

    public static function shared(\compile\Compile_Result $a, \compile\Compile_Result $b): void
    {
        foreach (['resolutions', 'types', 'bodies', 'lifetimes', 'backend', 'lowered', 'llvm'] as $stage) {
            self::check($a->$stage === $b->$stage, 'Retain entire stage without selection/join: ' . $stage);
        }
        self::check(($a->symbols->current === $b->symbols->current) && ($b->symbols->changes === []), 'Empty catalog shares accepted symbols');
    }

    public static function rejects(callable $call, string $message): void
    {
        try {
            $call();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $message), 'Unexpected failure: ' . $error->getMessage());
            return;
        }
        throw new RuntimeException('Expected rejection');
    }
}
$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$output = getcwd() . '/program';
$config = getcwd() . '/backend.json';
$catalog = getcwd() . '/types.json';
copy(dirname(__DIR__, 2) . '/language/named_types.json', $catalog);
$data = ['clang' => 'clang', 'target' => null, 'compile_jobs' => 20, 'linker' => 'ld'];
file_put_contents($config, json_encode($data));
$session = new \compile\Compiler_Session(type_catalog_path: $catalog, backend_toolchain_path: $config);
$first = $session->compile($manifest, $output);
$original = $first->to_json();
$tool = Reuse_Test::tool($session);
$count = $tool->invocation_count();
$second = $session->compile($manifest, $output);
Reuse_Test::shared($first, $second);
Reuse_Test::check(($second->completed) && (!$second->inputs->context->full_rebuild) && ($second->native === $first->native)
    && ($tool->invocation_count() === $count) && ($first->to_json() === $original), 'Unchanged request keeps output, old snapshots and no tools');
foreach ($second->inputs->sources->files as $file) {
    Reuse_Test::check((!$file->needs_recompile) && ($file->change_state === \read_sources\file_change::unchanged), 'Fresh scan facts are exported');
}

// Repairs do not force semantic stages to reselect/rejoin unchanged inputs.
unlink($output);
$repair = $session->compile($manifest, $output);
Reuse_Test::shared($second, $repair);
Reuse_Test::check(($tool->invocation_count() === ($count + 1)) && ($repair->native->objects === $second->native->objects), 'Missing executable relinks only');
$object = $repair->native->objects[0];
file_put_contents($object->path, 'damaged object');
$count = $tool->invocation_count();
$repaired_object = $session->compile($manifest, $output);
Reuse_Test::shared($repair, $repaired_object);
Reuse_Test::check($tool->invocation_count() === ($count + 2), 'Tampered object recompiles and links through common native path');

// Explicit linker identity is a link-only dependency, with no object invalidation.
$wrapper = getcwd() . '/test-linker';
file_put_contents($wrapper, "#!/bin/sh\nexec /usr/bin/ld \"\$@\"\n");
chmod($wrapper, 0700);
$data['linker'] = $wrapper;
file_put_contents($config, json_encode($data));
$count = $tool->invocation_count();
$switched = $session->compile($manifest, $output);
Reuse_Test::shared($repaired_object, $switched);
Reuse_Test::check(($tool->invocation_count() === ($count + 1)) && ($switched->native->objects === $repaired_object->native->objects),
    'Linker change performs exactly one relink with all objects retained');
Reuse_Test::check(json_decode($switched->to_json(), true)['native']['linker']['executable'] === $wrapper, 'Export actual linker');
$count = $tool->invocation_count();
file_put_contents($wrapper, "# changed linker binary\n", FILE_APPEND);
$replaced = $session->compile($manifest, $output);
Reuse_Test::check($tool->invocation_count() === ($count + 1), 'Linker executable change invalidates link');
$before = $session->published;
$key = hash_file('sha256', $output);
file_put_contents($wrapper, "#!/bin/sh\necho deliberate-link-failure >&2\nexit 9\n");
Reuse_Test::rejects(static fn() => $session->compile($manifest, $output), 'deliberate-link-failure');
Reuse_Test::check(($session->published === $before) && (hash_file('sha256', $output) === $key), 'Link failure preserves publication');
file_put_contents($wrapper, "#!/bin/sh\nexec /usr/bin/ld \"\$@\"\n# repaired\n");
$fixed = $session->compile($manifest, $output);
Reuse_Test::shared($replaced, $fixed);
Reuse_Test::rejects(static fn() => $session->compile($manifest, $wrapper), 'Native output would replace compiler input');
$count = $tool->invocation_count();
$data['compile_jobs'] = 1;
file_put_contents($config, json_encode($data));
Reuse_Test::check(($session->compile($manifest, $output)->native === $fixed->native) && ($tool->invocation_count() === $count),
    'Scheduling change validates config but keeps native result');
$data['linker'] = 'missing-linker-for-proof';
file_put_contents($config, json_encode($data));
Reuse_Test::rejects(static fn() => $session->compile($manifest, $output), 'missing-linker-for-proof');
$data['linker'] = $wrapper;
file_put_contents($config, json_encode($data));
Reuse_Test::check($session->compile($manifest, $output)->native === $fixed->native, 'Missing linker repair reuses valid artifact');

// A backend input edit must leave the gate even when it resolves to the same target triple.
$data['target'] = $fixed->backend->configuration->target_triple;
file_put_contents($config, json_encode($data));
$retargeted = $session->compile($manifest, $output);
Reuse_Test::check(($retargeted->backend !== $fixed->backend) && ($retargeted->llvm !== $fixed->llvm)
    && ($retargeted->native !== $fixed->native) && (!$retargeted->inputs->context->full_rebuild),
    'Backend change refreshes downstream contracts without forcing language full rebuild');

// Exact language/manifest changes and invalid sources must not bypass validation.
file_put_contents($catalog, "\n", FILE_APPEND);
$catalog_changed = $session->compile($manifest, $output);
Reuse_Test::check(($catalog_changed->inputs->context->full_rebuild) && ($catalog_changed->types !== $fixed->types), 'Catalog change leaves early gate');
file_put_contents($manifest, "\n", FILE_APPEND);
Reuse_Test::check($session->compile($manifest, $output)->inputs->context->full_rebuild, 'Manifest change selects full work');
$source = $root . '/src/nested/value.phs';
$old = file_get_contents($source);
clearstatcache(true,$source);
$mtime=filemtime($source);
file_put_contents($source, 'invalid source input');
touch($source,$mtime+2);
$before = $session->published;
try {
    $session->compile($manifest, $output);
    throw new RuntimeException('Expected invalid-source diagnostic');
}
catch (\diagnostics\Source_Error $error) {
}
Reuse_Test::check($session->published === $before, 'Invalid edit cannot claim unchanged');
file_put_contents($source, $old);
touch($source,$mtime+4);
Reuse_Test::check($session->compile($manifest, $output)->completed, 'Source failure followed by repair');
echo "native reuse ok: early gate, shared stages, artifact repair, linker identity/failure/protection, config changes and source repair\n";
