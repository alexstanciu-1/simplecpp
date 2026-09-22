<?php
declare(strict_types=1);
namespace load_runtime;

/** Schema-normalized declarations, not verified files or an accepted runtime package. */
final class Package_Target {
    public function __construct(public readonly string $triple, public readonly string $data_layout) {}
}
final class Package_Pointer {
    public function __construct(public readonly string $input_key, public readonly string $manifest_sha256) {}
}
final class Package_Manifest {
    public function __construct(public readonly string $provider, public readonly string $input_key,
        public readonly Package_Target $target, public readonly bool $project,
        public readonly array $artifacts /** hash<string> */, public readonly string $metadata,
        public readonly array $modules /** hash<string> */, public readonly string $link_driver,
        public readonly array $link_arguments /** vector<string> */,
        public readonly array $driver_hashes /** hash<string> */) {}
}
final class Package_Metadata {
    public function __construct(public readonly array $types /** vector<\scpp\Json_View> */,
        public readonly array $operations /** vector<\scpp\Json_View> */) {}
}
