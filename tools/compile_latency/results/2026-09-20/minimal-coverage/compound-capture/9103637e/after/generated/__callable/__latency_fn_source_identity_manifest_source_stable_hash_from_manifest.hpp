#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectManifest;
struct ProjectManifestSourceRow;
int_t<std::uint64_t> __latency_fn_source_identity_manifest_source_stable_hash_from_manifest(shared_p<ProjectManifest> manifest, ProjectManifestSourceRow row);
}
