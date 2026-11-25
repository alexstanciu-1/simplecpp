#include <scpp/lang/php.hpp>
#include "__types/ProjectManifest.hpp"
#include "__types/ProjectManifestSourceRow.hpp"
#include "__callable/__latency_fn_project_manifest_source_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_project_manifest_entry_source.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path_from_manifest.hpp"
namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
ProjectManifestSourceRow __latency_fn_project_manifest_source_by_id(shared_p<ProjectManifest> manifest, int_t<std::uint32_t> sourceId) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::source_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[10]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceId, manifest->source_count)))) {
		ProjectManifestSourceRow row = manifest->sources[__latency_fn_structure_row_ids_dense_index(sourceId)];
		if (static_cast<bool>(php::identical(row->source_id, sourceId))) {
			return row;
		}
	}
	auto __latency_local_0 = manifest->sources;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto source = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(source->source_id, sourceId))) {
			return source;
		}
	}
	ProjectManifestSourceRow empty = ProjectManifestSourceRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
ProjectManifestSourceRow __latency_fn_project_manifest_entry_source(shared_p<ProjectManifest> manifest) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::entry_source", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[11]);
	auto __latency_local_0 = manifest->sources;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto source = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(__latency_fn_source_identity_manifest_source_relative_path_from_manifest(manifest, source), manifest->entry_source_path))) {
			return source;
		}
	}
	ProjectManifestSourceRow empty = ProjectManifestSourceRow{};
	return empty;
}

}
