#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectManifest;
struct ProjectManifestSourceRow;
string_t __latency_fn_source_identity_manifest_source_relative_path_from_manifest(shared_p<ProjectManifest> manifest, ProjectManifestSourceRow row);
}
