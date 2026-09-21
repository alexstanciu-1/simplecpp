#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectManifest;
struct ProjectManifestSourceRow;
ProjectManifestSourceRow __latency_fn_project_manifest_source_by_id(shared_p<ProjectManifest> manifest, int_t<std::uint32_t> sourceId);
}
