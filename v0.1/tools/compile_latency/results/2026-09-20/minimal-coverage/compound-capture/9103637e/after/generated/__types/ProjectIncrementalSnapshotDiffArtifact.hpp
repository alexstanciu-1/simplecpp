#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ProjectIncrementalSnapshotDiffRow.hpp"
#include "__types/ProjectIncrementalSnapshotRow.hpp"
namespace scpp {
class ProjectIncrementalSnapshotDiffArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> source_model_id = static_cast<int_t<> >(1);
	int_t<> diff_model_id = static_cast<int_t<> >(1);
	int_t<> snapshot_count = static_cast<int_t<> >(0);
	int_t<> diff_count = static_cast<int_t<> >(0);
	vector_t<ProjectIncrementalSnapshotRow> snapshots = vector_t<ProjectIncrementalSnapshotRow>{};
	vector_t<ProjectIncrementalSnapshotDiffRow> diffs = vector_t<ProjectIncrementalSnapshotDiffRow>{};
};
}
