#include <scpp/lang/php.hpp>
#include "__types/PartitionReadinessRow.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_name.hpp"
#include "__callable/__latency_fn_partition_readiness_debug_string.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_name.hpp"
#include "__callable/__latency_fn_partition_readiness_status_name.hpp"
#include "__callable/__latency_fn_partition_readiness_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
string_t __latency_fn_partition_readiness_debug_string(PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[51]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("partition_readiness:") + cast<string_t>(__latency_fn_partition_readiness_owner_kind_name(row->owner_kind_id)) + string_t(":source:") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":symbol:") + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t(":") + cast<string_t>(__latency_fn_partition_readiness_status_name(row->status_id)) + string_t(":") + cast<string_t>(__latency_fn_partition_readiness_blocked_reason_name(row->blocked_reason_id)));
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_partition_readiness_stable_hash(PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[52]);
	string_t identity = required_cast<string_t>((string_t("partition_readiness:v2:") + cast<string_t>(cast<int_t<>>(row->row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->owner_run_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->owner_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->input_snapshot_generation)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->local_row_first_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->local_row_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->merge_order_key)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->published_row_first_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->published_row_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->owner_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->partition_scope_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->publication_model_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id))));
	return php::stable_hash_string_u64(identity);
}

}
