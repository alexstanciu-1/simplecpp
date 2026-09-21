#include <scpp/lang/php.hpp>
#include "__types/BackendPartitionExecutionArtifact.hpp"
#include "__types/BackendPartitionExecutionRow.hpp"
#include "__types/BackendProjectLinkExecutionRow.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_append_link.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_partition_by_execution_id.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_first_link.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_partition_debug_string.hpp"
#include "__callable/__latency_fn_backend_partition_readiness_partition_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_backend_partition_readiness[]; }
namespace scpp {
void __latency_fn_backend_partition_readiness_append_link(shared_p<BackendPartitionExecutionArtifact>& artifact, BackendProjectLinkExecutionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_partition_readiness::append_link", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_partition_readiness.phs", __latency_lines_backend_partition_readiness[26]);
	(void) artifact->links.append(row);
	artifact->link_count = php::count(artifact->links);
}

}

namespace scpp { extern const int __latency_lines_backend_partition_readiness[]; }
namespace scpp {
BackendPartitionExecutionRow __latency_fn_backend_partition_readiness_partition_by_execution_id(shared_p<BackendPartitionExecutionArtifact> artifact, int_t<std::uint32_t> executionId) {
	SCPP_CALL_DEPTH_GUARD("backend_partition_readiness::partition_by_execution_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_partition_readiness.phs", __latency_lines_backend_partition_readiness[27]);
	auto __latency_local_0 = artifact->partitions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_id), cast<int_t<>>(executionId)))) {
			return row;
		}
	}
	BackendPartitionExecutionRow empty = BackendPartitionExecutionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_partition_readiness[]; }
namespace scpp {
BackendProjectLinkExecutionRow __latency_fn_backend_partition_readiness_first_link(shared_p<BackendPartitionExecutionArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_partition_readiness::first_link", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_partition_readiness.phs", __latency_lines_backend_partition_readiness[28]);
	if (static_cast<bool>((php::count(artifact->links) > static_cast<int_t<> >(0)))) {
		return artifact->links[static_cast<int_t<> >(0)];
	}
	BackendProjectLinkExecutionRow empty = BackendProjectLinkExecutionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_partition_readiness[]; }
namespace scpp {
string_t __latency_fn_backend_partition_readiness_partition_debug_string(BackendPartitionExecutionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_partition_readiness::partition_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_partition_readiness.phs", __latency_lines_backend_partition_readiness[29]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->execution_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("backend_partition:symbol:") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t(":source:") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":request_list"));
}

}

namespace scpp { extern const int __latency_lines_backend_partition_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_partition_readiness_partition_stable_hash(BackendPartitionExecutionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_partition_readiness::partition_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_partition_readiness.phs", __latency_lines_backend_partition_readiness[30]);
	string_t identity = required_cast<string_t>((string_t("backend_partition:v2:") + cast<string_t>(cast<int_t<>>(row->execution_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->partition_key_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->backend_row_key_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->execution_status_id))));
	return php::stable_hash_string_u64(identity);
}

}
