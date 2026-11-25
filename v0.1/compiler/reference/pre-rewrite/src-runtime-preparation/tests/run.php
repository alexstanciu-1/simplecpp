<?php
declare(strict_types=1);

namespace runtime_preparation;

require_once dirname(__DIR__) . '/bootstrap.php';

/** End-to-end provider proof with consumers that never include the runtime header. */
final class Preparation_Test
{
    private string $workspace;
    private Clang_Toolchain $toolchain;
    private int $checks = 0;

    /** Prove package generation, reuse, publication and native ordinary/LTO execution. */
    public function run(): void
    {
        $this->workspace = sys_get_temp_dir() . '/runtime-preparation-test-' . bin2hex(random_bytes(6));
        Files::directory($this->workspace);
        $config = Files::json(dirname(__DIR__) . '/config.json');
        $config['definitions_directory'] = dirname(__DIR__) . '/definitions';
        $config['include_directories'] = array_map(static fn(string $path): string => realpath(Files::path(dirname(__DIR__), $path)), $config['include_directories']);
        $config['output_directory'] = $this->workspace . '/string-output';
        $this->toolchain = new Clang_Toolchain($config, dirname(__DIR__));
        Files::write_json($this->workspace . '/config.json', $config);
        try
        {
            $this->symbol_spelling();
            $first = (new Runtime_Preparation())->run($this->workspace . '/config.json');
            $this->check($first['status'] === 'built', 'first package build');
            $manifest = Files::json($first['manifest']);
            $package = dirname($first['manifest']);
            $metadata = Files::json($package . '/metadata.json');
            $types = array_column($metadata['types'], null, 'id');
            $this->check($types['size']['fact_prefix'] === 'rp_type_X_simple__cpp_X_size', 'metadata exposes the readable type fact prefix');
            $this->check(str_contains(Files::read($package . '/runtime.ll'),
                '@rp_type_X_simple__cpp_X_size_X_move__constructible = constant i64 1'), 'Clang facts use the published readable identity');
            $this->check(is_file($package . '/' . $types['string']['declaration_artifact']), 'metadata locates the raw declaration artifact');
            $this->check(($types['string']['size_bytes'] > 0) && ($types['string']['alignment_bytes'] > 0), 'measured layout');
            $this->check(!$types['string']['cpp_traits']['trivially_copyable'], 'nontrivial C++ copy trait');
            $this->check(($types['string']['lifecycle']['copy_construct'] ?? null) === 'string.copy', 'explicit copy binding advertised');
            $this->check($manifest['validation']['native_link_no_undefined'], 'native link validation');
            $this->string_semantics($config, $manifest);
            $source = $this->consumer($metadata, 'string', 'string.byte_length', [0, 3, 100], 0);
            $this->native($source, $first['manifest'], 'runtime.bc', 'string-native');
            $this->native($source, $first['manifest'], 'runtime.lto.bc', 'string-full', 'full');
            $this->native($source, $first['manifest'], 'runtime.thin.bc', 'string-thin', 'thin');
            $this->check(true, 'ordinary/full/ThinLTO string execution, including embedded zero and long contents');
            $snapshot = Files::read($config['output_directory'] . '/current.json');
            $before = filemtime($package . '/runtime.bc');
            // Simulate packages left by the previous retention policy. Cleanup
            // must also run when the current package needs no regeneration.
            $stale = $config['output_directory'] . '/packages/' . str_repeat('a', 64) . '-12345678';
            Files::directory($stale . '/nested');
            Files::write($stale . '/nested/runtime.bc', 'obsolete');
            $outside = $this->workspace . '/outside-package';
            Files::directory($outside);
            Files::write($outside . '/keep.txt', 'keep');
            symlink($outside, $stale . '/linked');
            $unrelated = $config['output_directory'] . '/packages/notes';
            Files::directory($unrelated);
            Files::write($unrelated . '/keep.txt', 'keep');
            $reuse = (new Runtime_Preparation())->run($this->workspace . '/config.json');
            clearstatcache();
            $this->check(($reuse['status'] === 'reused') && ($reuse['manifest'] === $first['manifest'])
                && (filemtime($package . '/runtime.bc') === $before), 'unchanged artifact reuse');
            $this->check($reuse['clang_commands'] === 3, 'reuse performs version/dependency inspection only');
            $this->check(Files::read($config['output_directory'] . '/current.json') === $snapshot, 'unchanged publication pointer');
            $this->check(!is_dir($stale), 'reuse removes superseded packages');
            $this->check((Files::read($outside . '/keep.txt') === 'keep')
                && (Files::read($unrelated . '/keep.txt') === 'keep'), 'cleanup preserves unrelated files and symlink targets');
            $this->single_package($config['output_directory'], $reuse['manifest']);
            $this->invalid_runtime_argument($metadata, $first['manifest']);
            $this->fixture($config);
            fwrite(STDOUT, json_encode(['status' => 'passed', 'checks' => $this->checks,
                'workspace' => $this->workspace], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        }
        catch (\Throwable $error) {
            fwrite(STDERR, 'Probe artifacts retained in ' . $this->workspace . "\n");
            throw $error;
        }
    }

    /** Check reversible full identities and stable symbols across definition ordering and extension. */
    private function symbol_spelling(): void
    {
        $this->check(Symbols::name(['type', 'simple_cpp', 'size', 'move_constructible'])
            === 'rp_type_X_simple__cpp_X_size_X_move__constructible', 'readable qualified fact spelling');
        $this->check(Symbols::name(['op', 'simple_cpp', 'string.byte_length'])
            === 'rp_op_X_simple__cpp_X_string_x2E_byte__length', 'punctuation and underscore spelling');
        $this->check(Symbols::name(['scpp::string_t']) === 'rp_scpp_x3A__x3A_string__t', 'qualified C++ spelling');
        $this->check(Symbols::append(Symbols::name(['type', 'simple_cpp', 'size']), 'move_constructible')
            === Symbols::name(['type', 'simple_cpp', 'size', 'move_constructible']), 'appended facts use the same component encoding');

        // An independent token decoder checks reversibility, especially where
        // literal underscores touch component separators or hexadecimal escapes.
        $components = ['', '_', '__', '_X_', '_x2E_', '.', 'a_X_', '_Xa', 'a', 'a_', 'a.b', 'é'];
        $seen = [];
        foreach ($components as $first)
        {
            foreach ($components as $second)
            {
                $tuple = [$first, $second];
                $symbol = Symbols::name($tuple);
                if (isset($seen[$symbol]) || ($this->decode_symbol($symbol) !== $tuple)) {
                    throw new \RuntimeException('Symbol encoding merged distinct identities');
                }
                $seen[$symbol] = true;
            }
        }
        $this->check(true, 'distinct component boundaries and literal escape spellings round-trip');
        $bytes = '';
        for ($byte = 0; $byte < 256; ++$byte) {
            $bytes .= chr($byte);
        }
        $symbol = Symbols::name([$bytes]);
        $this->check(preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $symbol) === 1, 'all byte values produce identifier-safe spelling');
        $this->check($this->decode_symbol($symbol) === [$bytes], 'all byte values round-trip exactly');
        $this->check(Symbols::name(['op', 'a.b', 'c']) !== Symbols::name(['op', 'a', 'b.c']), 'provider and operation boundaries remain distinct');
        $this->check(Symbols::name(['type', 'a', 'b']) !== Symbols::name(['op', 'a', 'b']), 'type and operation namespaces remain distinct');
    }

    /** Decode escaped identity components for independent round-trip checks.
     * @return list<string> */
    private function decode_symbol(string $symbol): array
    {
        $encoded = substr($symbol, 3);
        preg_match_all('/_X_|__|_x[0-9A-F]{2}_|[A-Za-z0-9]+/', $encoded, $matches);
        if (!str_starts_with($symbol, 'rp_') || (implode('', $matches[0]) !== $encoded)) {
            throw new \RuntimeException('Invalid symbol spelling');
        }
        $components = [''];
        $index = 0;
        foreach ($matches[0] as $token)
        {
            if ($token === '_X_') {
                $components[++$index] = '';
            }
            elseif ($token === '__') {
                $components[$index] .= '_';
            }
            elseif (str_starts_with($token, '_x')) {
                $components[$index] .= chr(hexdec(substr($token, 2, 2)));
            }
            else {
                $components[$index] .= $token;
            }
        }
        return $components;
    }

    /** Check the real provider string copy and byte-content contracts with native code.
     * @param array<string, mixed> $config @param array<string, mixed> $manifest */
    private function string_semantics(array $config, array $manifest): void
    {
        // Separate provider-behavior check: exported consumers below still use
        // only the generated ABI. Byte length alone cannot prove content copying.
        $source = <<<'CPP'
#include <scpp/string_t.hpp>
#include <string_view>
int main() {
    std::string input(100, 'x');
    scpp::string_t value{std::string_view(input)};
    input[0] = 'y';
    input.clear();
    if (value.native_value() != std::string(100, 'x')) return 1;
    char small[] = {'a', '\0', 'b'};
    scpp::string_t second{std::string_view(small, 3)};
    small[0] = 'z';
    return second.native_value() == std::string("a\0b", 3) ? 0 : 2;
}
CPP;
        $path = $this->workspace . '/string-semantics.cpp';
        Files::write($path, $source);
        $includes = array_map(static fn(string $directory): string => '-I' . $directory, $config['include_directories']);
        $this->toolchain->run([$manifest['link_driver']['executable'], ...$manifest['link_driver']['arguments'],
            '-std=c++23', ...$includes, $path, '-o', $this->workspace . '/string-semantics']);
        $this->toolchain->run([$this->workspace . '/string-semantics']);
        $this->check(true, 'string construction owns independent byte contents');
    }

    /** Generate a small independent caller using the exported lifecycle and ABI symbols.
     * @param array<string, mixed> $metadata @param list<int> $sizes */
    private function consumer(array $metadata, string $type_id, string $method_id, array $sizes, int $adjustment): string
    {
        $types = array_column($metadata['types'], null, 'id');
        $operations = array_column($metadata['operations'], null, 'id');
        $type = $types[$type_id];
        $construct = $operations[$type['lifecycle']['construct']]['symbol'];
        $destroy = $operations[$type['lifecycle']['destroy']]['symbol'];
        $measure = $operations[$method_id]['symbol'];
        $source = "#include \"abi.hpp\"\n#include <cstddef>\n";
        $source .= "int main() {\n  const char bytes[100] = {'a', '\\0', 'b'};\n";
        foreach ($sizes as $index => $size) {
            $source .= '  alignas(' . $type['alignment_bytes'] . ') std::byte storage' . $index . '[' . $type['size_bytes'] . "];\n";
            $source .= '  ' . $construct . '(storage' . $index . ', bytes, ' . $size . ");\n";
            $source .= '  auto length' . $index . ' = ' . $measure . '(storage' . $index . ");\n";
            $source .= '  ' . $destroy . '(storage' . $index . ");\n";
            $source .= '  if (length' . $index . ' != ' . ($size + $adjustment) . ') return ' . ($index + 1) . ";\n";
        }
        return $source . "  return 0;\n}\n";
    }

    /** Compile and execute a consumer against the selected prepared module. */
    private function native(string $source, string $manifest_path, string $module, string $name, ?string $lto = null): void
    {
        $manifest = Files::json($manifest_path);
        $package = dirname($manifest_path);
        $source_path = $this->workspace . '/' . $name . '.cpp';
        Files::write($source_path, $source);
        $command = [$manifest['link_driver']['executable'], ...$manifest['link_driver']['arguments'], '-std=c++23', '-I' . $package];
        if ($lto !== null) {
            $linker = Files::executable('/', 'ld.lld-18');
            $command = [...$command, '-O2', '-flto=' . $lto, '--ld-path=' . $linker];
        }
        $inputs = ($lto === null) ? [$source_path, $package . '/' . $module]
            : [$source_path, '-Xlinker', $package . '/' . $module];
        $this->toolchain->run([...$command, ...$inputs, '-o', $this->workspace . '/' . $name]);
        $this->toolchain->run([$this->workspace . '/' . $name]);
    }

    /** Require the bridge to stop clearly on invalid runtime storage.
     * @param array<string, mixed> $metadata */
    private function invalid_runtime_argument(array $metadata, string $manifest): void
    {
        $operations = array_column($metadata['operations'], null, 'id');
        $symbol = $operations['string.byte_length']['symbol'];
        $source = '#include "abi.hpp"' . "\nint main() { " . $symbol . "(nullptr); }\n";
        try {
            $this->native($source, $manifest, 'runtime.bc', 'invalid-runtime');
        }
        catch (\RuntimeException $error) {
            $this->check(str_contains($error->getMessage(), 'runtime operation string.byte_length failed: null object'), 'clear stop at ABI error boundary');
            return;
        }
        throw new \RuntimeException('Invalid runtime argument unexpectedly succeeded');
    }

    /** Exercise a second provider and package invalidation, publication and LTO dependencies.
     * @param array<string, mixed> $config */
    private function fixture(array $config): void
    {
        $folder = $this->workspace . '/fixture';
        Files::directory($folder);
        Files::directory($folder . '/definitions');
        Files::write($folder . '/offset.hpp', "#pragma once\ninline constexpr unsigned offset = 0;\n");
        Files::write($folder . '/extent.hpp', <<<'CPP'
#pragma once
#include <string_view>
#include "offset.hpp"
namespace specimen {
class extent {
    std::size_t count_;
public:
    explicit extent(std::string_view input): count_(input.size()) {}
    std::size_t measure() const { return count_ + offset; }
};
}
CPP);
        $definition = ['schema_version' => 1, 'types' => [
            ['id' => 'extent', 'cpp_name' => 'specimen::extent', 'header' => 'extent.hpp', 'kind' => 'runtime_value', 'storage' => 'inline',
                'lifecycle' => ['construct' => 'extent.create', 'destroy' => 'extent.release']],
            ['id' => 'count', 'cpp_name' => 'std::size_t', 'header' => 'cstddef', 'kind' => 'integer']],
            'operations' => [
                ['id' => 'extent.create', 'kind' => 'construct_from_bytes', 'type' => 'extent', 'error_policy' => 'terminate'],
                ['id' => 'extent.release', 'kind' => 'destroy', 'type' => 'extent', 'error_policy' => 'terminate'],
                ['id' => 'extent.measure', 'kind' => 'const_method', 'type' => 'extent', 'member' => 'measure', 'result_type' => 'count', 'error_policy' => 'terminate']]];
        Files::write_json($folder . '/definitions/extent.json', $definition);
        $config['definitions_directory'] = 'definitions';
        $config['output_directory'] = 'output with spaces';
        $config['include_directories'] = [$folder];
        $configuration = $folder . '/config.json';
        Files::write_json($configuration, $config);
        $first = (new Runtime_Preparation())->run($configuration);
        $metadata = Files::json(dirname($first['manifest']) . '/metadata.json');
        $this->native($this->consumer($metadata, 'extent', 'extent.measure', [3], 0), $first['manifest'], 'runtime.bc', 'extent-native');
        $this->check(true, 'second provider type uses the same adapters');

        // Retain the same independently compiled caller while the provider body changes.
        $this->lto_caller($metadata, $first['manifest']);
        $this->lto_link($first['manifest'], 3, 'before');
        Files::write(dirname($first['manifest']) . '/obsolete.txt', 'must not survive replacement');
        Files::write($folder . '/offset.hpp', "#pragma once\ninline constexpr unsigned offset = 1;\n");
        $changed = (new Runtime_Preparation())->run($configuration);
        $this->check(($changed['status'] === 'built') && ($changed['input_key'] !== $first['input_key']), 'transitive header invalidation');
        $this->lto_link($changed['manifest'], 4, 'after');
        $additional = $definition['operations'][2];
        $additional['id'] = 'a.measure';
        $definition['operations'][] = $additional;
        Files::write_json($folder . '/definitions/extent.json', $definition);
        $expanded = (new Runtime_Preparation())->run($configuration);
        $this->check(($expanded['status'] === 'built') && ($expanded['input_key'] !== $changed['input_key']), 'adding an operation invalidates preparation');
        $changed = $expanded;
        $updated = Files::json(dirname($changed['manifest']) . '/metadata.json');
        $bindings = array_column($updated['operations'], 'symbol', 'id');
        foreach ($metadata['operations'] as $operation) {
            $this->check($bindings[$operation['id']] === $operation['symbol'], 'adding an earlier-sorted operation preserves existing symbol: ' . $operation['id']);
        }
        $this->check($bindings['a.measure'] !== $bindings['extent.measure'], 'distinct operations sharing a C++ method have distinct identities');
        $this->native($this->consumer($updated, 'extent', 'a.measure', [3], 1), $changed['manifest'], 'runtime.bc', 'extent-added-operation');
        $this->check($changed['manifest'] === $first['manifest'], 'changed inputs keep the stable manifest path');
        $this->check(!is_file(dirname($changed['manifest']) . '/obsolete.txt'), 'whole-package replacement removes obsolete artifacts');
        $this->single_package($folder . '/output with spaces', $changed['manifest']);

        $pointer_path = $folder . '/output with spaces/current.json';
        $accepted = Files::read($pointer_path);
        $definition['operations'][2]['member'] = 'missing_method';
        Files::write_json($folder . '/definitions/extent.json', $definition);
        $this->failure($configuration, 'missing_method');
        $this->check(Files::read($pointer_path) === $accepted, 'failed C++ preparation preserves accepted package');
        $this->single_package($folder . '/output with spaces', $changed['manifest']);
        $this->check(glob($folder . '/output with spaces/.preparing-*') === [], 'failed private build cleanup');
        $definition['operations'][2]['member'] = 'measure';
        // A declaration can compile into an unresolved call. Package preparation
        // must fail its native link check instead of advertising that operation.
        $header = Files::read($folder . '/extent.hpp');
        Files::write($folder . '/extent.hpp', str_replace('std::size_t measure() const { return count_ + offset; }',
            'std::size_t measure() const;', $header));
        Files::write_json($folder . '/definitions/extent.json', $definition);
        $this->failure($configuration, 'undefined reference');
        $this->check(Files::read($pointer_path) === $accepted, 'missing implementation does not publish');
        Files::write($folder . '/extent.hpp', $header);
        $definition['operations'][2]['unexpected'] = true;
        Files::write_json($folder . '/definitions/extent.json', $definition);
        $this->failure($configuration, 'unknown [unexpected]');
        unset($definition['operations'][2]['unexpected']);
        Files::write_json($folder . '/definitions/extent.json', $definition);
        $original_directory = getcwd();
        try {
            chdir('/');
            $reused = (new Runtime_Preparation())->run($configuration);
        }
        finally {
            chdir($original_directory);
        }
        $this->check($reused['status'] === 'reused', 'lock released after failure');
        $this->check($reused['manifest'] === $changed['manifest'], 'configuration paths independent of working directory');

        Files::write(dirname($changed['manifest']) . '/runtime.bc', 'damaged');
        $repaired = (new Runtime_Preparation())->run($configuration);
        $this->check(($repaired['status'] === 'built') && ($repaired['manifest'] === $changed['manifest']), 'damaged artifact replaced at the stable path');
        $this->check(Files::read(dirname($repaired['manifest']) . '/runtime.bc') !== 'damaged', 'repair replaces damaged contents');
        $this->single_package($folder . '/output with spaces', $repaired['manifest']);
        $this->native($this->consumer($metadata, 'extent', 'extent.measure', [3], 1),
            $repaired['manifest'], 'runtime.bc', 'extent-repaired');
        $this->publication_failure($configuration, $folder, $repaired['manifest']);
        $this->interrupted_publication($configuration, $folder, $repaired['manifest']);
    }

    /** Check stable replacement leaves one current package without obsolete generated artifacts. */
    private function single_package(string $output, string $manifest): void
    {
        $paths = [];
        if (is_dir($output . '/packages')) {
            foreach (new \FilesystemIterator($output . '/packages') as $entry) {
                if (preg_match('/^[a-f0-9]{64}-[a-f0-9]{8}$/D', $entry->getFilename())) {
                    $paths[] = $entry->getPathname();
                }
            }
        }
        $this->check(($paths === []) && ($manifest === $output . '/package/manifest.json'), 'stable package path without hashed directories');
        $this->check((glob($output . '/.preparing-*') === []) && (glob($output . '/.current-*') === [])
            && !file_exists($output . '/.previous-package'), 'no temporary publication folders or pointers remain');
        $pointer = Files::json($output . '/current.json');
        $this->check(hash_file('sha256', $manifest) === $pointer['manifest_sha256'], 'current pointer matches stable manifest');
        foreach (Files::json($manifest)['artifacts'] as $name => $hash) {
            $this->check(hash_file('sha256', dirname($manifest) . '/' . $name) === $hash, 'published artifact matches manifest: ' . $name);
        }
    }

    /** Inject publication failure and verify the accepted package survives. */
    private function publication_failure(string $configuration, string $folder, string $manifest): void
    {
        $output = $folder . '/output with spaces';
        $accepted = Files::read($manifest);
        // Obstruct the final pointer rename after the replacement directory has
        // been installed, proving that publication restores the previous files.
        rename($output . '/current.json', $output . '/saved-current.json');
        Files::directory($output . '/current.json');
        Files::write($folder . '/offset.hpp', "#pragma once\ninline constexpr unsigned offset = 2;\n");
        try {
            $this->failure($configuration, 'Cannot publish runtime package pointer');
            $this->check(Files::read($manifest) === $accepted, 'publication failure restores previous manifest');
        }
        finally {
            rmdir($output . '/current.json');
            rename($output . '/saved-current.json', $output . '/current.json');
            Files::write($folder . '/offset.hpp', "#pragma once\ninline constexpr unsigned offset = 1;\n");
        }
        $this->single_package($output, $manifest);
        $reused = (new Runtime_Preparation())->run($configuration);
        $this->check($reused['status'] === 'reused', 'publication failure releases the output lock');
    }

    /** Simulate interrupted replacement and check recovery selects a complete package. */
    private function interrupted_publication(string $configuration, string $folder, string $manifest): void
    {
        $output = $folder . '/output with spaces';
        $accepted = Files::read($manifest);
        rename($output . '/package', $output . '/.previous-package');
        // Simulate an uncommitted replacement; current.json still identifies
        // the backup. Rerunning must restore it before considering reuse.
        Files::directory($output . '/package');
        Files::write($manifest, '{}');
        $reused = (new Runtime_Preparation())->run($configuration);
        $this->check(($reused['status'] === 'reused') && (Files::read($manifest) === $accepted), 'interrupted replacement restores the accepted package');
        $this->single_package($output, $manifest);
        // After a committed replacement, a leftover backup is just cleanup work.
        Files::directory($output . '/.previous-package');
        Files::write($output . '/.previous-package/manifest.json', '{}');
        $reused = (new Runtime_Preparation())->run($configuration);
        $this->check(($reused['status'] === 'reused') && (Files::read($manifest) === $accepted), 'interrupted cleanup preserves the committed package');
        $this->single_package($output, $manifest);
    }

    /** Construct a caller whose provider operation can be optimized across modules.
     * @param array<string, mixed> $metadata */
    private function lto_caller(array $metadata, string $manifest_path): void
    {
        $types = array_column($metadata['types'], null, 'id');
        $operations = array_column($metadata['operations'], null, 'id');
        $type = $types['extent'];
        $source = '#include "abi.hpp"' . "\n#include <cstddef>\n#include <cstdio>\nint main() {\n";
        $source .= 'alignas(' . $type['alignment_bytes'] . ') std::byte storage[' . $type['size_bytes'] . "];\n";
        $source .= $operations['extent.create']['symbol'] . "(storage, \"abc\", 3);\n";
        $source .= 'auto result = ' . $operations['extent.measure']['symbol'] . "(storage);\n";
        $source .= $operations['extent.release']['symbol'] . "(storage);\n";
        $source .= "std::printf(\"%zu\\n\", result);\n}\n";
        Files::write($this->workspace . '/retained.cpp', $source);
        $manifest = Files::json($manifest_path);
        foreach (['full', 'thin'] as $mode) {
            $this->toolchain->run([$manifest['link_driver']['executable'], ...$manifest['link_driver']['arguments'],
                '-std=c++23', '-O2', '-flto=' . $mode, '-c', '-I' . dirname($manifest_path),
                $this->workspace . '/retained.cpp', '-o', $this->workspace . '/retained.' . $mode . '.bc']);
        }
    }

    /** Check cross-module optimization and changed provider behavior with retained caller bitcode. */
    private function lto_link(string $manifest_path, int $expected, string $step): void
    {
        $manifest = Files::json($manifest_path);
        $package = dirname($manifest_path);
        $operations = Files::json($package . '/metadata.json')['operations'];
        foreach (['full' => 'runtime.lto.bc', 'thin' => 'runtime.thin.bc'] as $mode => $module)
        {
            $executable = $this->workspace . '/lto-' . $mode . '-' . $step;
            $retained = $this->workspace . '/retained.' . $mode . '.bc';
            $before = hash_file('sha256', $retained);
            $provider_input = $this->workspace . '/provider-' . $mode . '-' . $step . '.bc';
            Files::write($provider_input, Files::read($package . '/' . $module));
            foreach (glob($retained . '*.opt.bc') as $old) {
                unlink($old);
            }
            $command = [$manifest['link_driver']['executable'], ...$manifest['link_driver']['arguments'],
                '-O2', '-flto=' . $mode, '--ld-path=' . Files::executable('/', 'ld.lld-18'),
                '-Wl,--save-temps', '-Xlinker', $retained, '-Xlinker', $provider_input, '-o', $executable];
            if ($mode === 'thin') {
                $command[] = '-Wl,--thinlto-cache-dir=' . $this->workspace . '/thin-cache';
            }
            $this->toolchain->run($command);
            $this->check(trim($this->toolchain->run([$executable])) === (string)$expected, $mode . ' LTO ' . $step . ' execution');
            $this->check(hash_file('sha256', $retained) === $before, 'unchanged ' . $mode . ' caller bitcode');
            $optimized = glob($executable . '*.opt.bc');
            if ($mode === 'thin') {
                $optimized = glob($retained . '*.opt.bc');
            }
            $this->check($optimized !== [], 'saved ' . $mode . ' optimized IR');
            $ir = '';
            foreach ($optimized as $path) {
                $ir .= $this->toolchain->inspect_bitcode($path);
            }
            $this->check(str_contains($ir, '@main('), 'optimized caller present');
            foreach ($operations as $operation) {
                $this->check(!preg_match('/\b(?:call|invoke)\b[^\n]*@' . $operation['symbol'] . '\(/', $ir), 'cross-module elimination of ' . $operation['id']);
            }
        }
    }

    /** Require a preparation failure to report its expected reason. */
    private function failure(string $configuration, string $message): void
    {
        try {
            (new Runtime_Preparation())->run($configuration);
        }
        catch (\Throwable $error) {
            $this->check(str_contains($error->getMessage(), $message), 'expected preparation diagnostic: ' . $message);
            return;
        }
        throw new \RuntimeException('Expected preparation failure: ' . $message);
    }

    private function check(bool $condition, string $label): void
    {
        if (!$condition) {
            throw new \RuntimeException('Failed: ' . $label);
        }
        ++$this->checks;
    }
}

(new Preparation_Test())->run();
