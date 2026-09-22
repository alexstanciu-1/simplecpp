<?php
declare(strict_types=1);

/*
 * Role: Explicit project module acceptance context, separate from self-contained runtime packages.
 * Used by: Project_Import; Package_Adapter; final source export closure
 * Flow: verified native receipt + current compiler exports -> bound module -> final link obligations.
 */
namespace load_runtime;

final class project_binding {
    /** Receipt bytes authenticate the selected portable contract; exports bind it to this compiler lineage.
     * @param array<string, \prepare_backend\source_type_export> $exports Exact portable source keys. */
    public function __construct(public readonly string $receipt, public readonly array $exports)
    {
    }
}
