<?php
declare(strict_types=1);
namespace runtime_preparation\project;

use runtime_preparation as native;

/** Validate explicit source obligations without claiming an unresolved project module is self-contained. */
final class Module
{
    /** Merge direct/transitive source contracts once, rejecting cross-project or conflicting origins. */
    public static function from_arguments(string $project, array $arguments): ?module_contract
    {
        $sources = [];
        foreach ($arguments as $argument)
        {
            foreach ($argument->sources as $key => $source)
            {
                Adapter::validate($source);
                if (($key !== $source->key) || ($source->project !== $project)
                    || (isset($sources[$key]) && ($sources[$key] != $source))) {
                    throw new \RuntimeException('Conflicting or foreign project source contract');
                }
                $sources[$key] = $source;
            }
        }
        ksort($sources);
        return $sources === [] ? null : new module_contract($project, $sources);
    }

    /** Verify every source declaration and call reference; source implementations must remain absent here. */
    public static function imports(module_contract $contract, string $llvm): array
    {
        $authorized = [];
        $target = native\Metadata::target($llvm);
        foreach ($contract->sources as $source)
        {
            if ($source->target !== $target) {
                throw new \RuntimeException('Project source target/layout mismatch');
            }
            foreach ($source->operations as $operation)
            {
                if ($operation !== null)
                {
                    if (isset($authorized[$operation->symbol])) {
                        throw new \RuntimeException('Duplicate authorized source import');
                    }
                    $authorized[$operation->symbol] = $operation;
                }
            }
        }
        preg_match_all('/@(?:"(scpp_source_[^"]+)"|(scpp_source_[A-Za-z0-9_]+))/', $llvm, $uses, PREG_SET_ORDER);
        $required = [];
        foreach ($uses as $use)
        {
            $symbol = $use[1] !== '' ? $use[1] : $use[2];
            if (isset($required[$symbol])) {
                continue;
            }
            $operation = $authorized[$symbol] ?? throw new \RuntimeException('Unauthorized source import: ' . $symbol);
            $escaped = preg_quote($symbol, '/');
            if (preg_match('/^define[^\n]*@"?' . $escaped . '"?\(/m', $llvm)
                || (preg_match_all('/^declare void @"?' . $escaped . '"?\(([^\n]*)\)(?: #[0-9]+)?(?: [^\n]*)?$/m', $llvm, $declarations) !== 1)) {
                throw new \RuntimeException('Missing, duplicate or locally defined source import');
            }
            $parameters = explode(',', $declarations[1][0]);
            if (count($parameters) !== count($operation->abi['parameters'])) {
                throw new \RuntimeException('Source import parameter count mismatch');
            }
            foreach ($parameters as $parameter) {
                if (!preg_match('/^\s*ptr(?: noundef)?\s*$/D', $parameter)) {
                    throw new \RuntimeException('Unsupported source import parameter ABI');
                }
            }
            $required[$symbol] = $operation;
        }
        ksort($required);
        return $required;
    }

    /** Require the same explicit project context at candidate acceptance and publication. */
    public static function validate(module_contract $contract, string $directory, array $manifest, native\Clang_Toolchain $toolchain): void
    {
        $expected = json_decode(json_encode($contract, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['module_kind'] ?? null) !== 'project'
            || (($manifest['validation']['native_link_no_undefined'] ?? null) !== false)
            || (($manifest['validation']['source_imports_validated'] ?? null) !== true)
            || !isset($manifest['artifacts']['project.json'])) {
            throw new \RuntimeException('Project module validation contract mismatch');
        }
        $receipt = native\Files::json($directory . '/project.json');
        $variants = ['runtime.ll', 'runtime.bc', 'runtime.lto.bc', 'runtime.thin.bc'];
        if (($receipt['schema_version'] ?? null) !== 1 || (($receipt['contract'] ?? null) !== $expected)
            || (array_keys($receipt['required_imports'] ?? []) !== $variants)) {
            throw new \RuntimeException('Project source imports or contract mismatch');
        }
        foreach ($variants as $variant)
        {
            $llvm = $variant === 'runtime.ll' ? native\Files::read($directory . '/' . $variant)
                : $toolchain->inspect_bitcode($directory . '/' . $variant);
            $required = self::imports($contract, $llvm);
            $required = json_decode(json_encode($required, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            if ($receipt['required_imports'][$variant] !== $required) {
                throw new \RuntimeException('Project source imports differ from artifact: ' . $variant);
            }
        }
    }
}
