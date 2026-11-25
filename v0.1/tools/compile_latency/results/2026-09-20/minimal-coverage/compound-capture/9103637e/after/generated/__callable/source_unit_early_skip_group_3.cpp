#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitEarlySkipArtifact.hpp"
#include "__types/ResidentSourceUnitEarlySkipDecisionRow.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_action_name.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_debug_string.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_kind_name.hpp"
#include "__callable/__latency_fn_source_unit_early_skip_decision_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
ResidentSourceUnitEarlySkipDecisionRow __latency_fn_source_unit_early_skip_decision_by_id(ResidentSourceUnitEarlySkipArtifact artifact, int_t<std::uint32_t> decisionId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::decision_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[31]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(decisionId, cast<int_t<>>(artifact->decision_count))))) {
		ResidentSourceUnitEarlySkipDecisionRow row = artifact->decisions[__latency_fn_structure_row_ids_dense_index(decisionId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_id), cast<int_t<>>(decisionId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_id), cast<int_t<>>(decisionId)))) {
			return row;
		}
	}
	ResidentSourceUnitEarlySkipDecisionRow empty = ResidentSourceUnitEarlySkipDecisionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
string_t __latency_fn_source_unit_early_skip_decision_debug_string(ResidentSourceUnitEarlySkipDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::decision_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[32]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->decision_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("source_unit_early_skip:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->decision_id)));
	text = (cast<string_t>(text) + string_t(":source:") + cast<string_t>(cast<int_t<>>(row->source_unit_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_source_unit_early_skip_decision_kind_name(row->decision_kind_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_source_unit_early_skip_action_name(row->tokenize_parse_action_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_source_unit_early_skip[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_source_unit_early_skip_decision_stable_hash(ResidentSourceUnitEarlySkipDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("source_unit_early_skip::decision_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_early_skip.phs", __latency_lines_source_unit_early_skip[33]);
	string_t identity = required_cast<string_t>(string_t("source_unit_early_skip:v1:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->decision_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->owner_run_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_unit_key_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->previous_snapshot_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->current_snapshot_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->previous_content_hash)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->current_content_hash)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->previous_source_length)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->current_source_length)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->previous_line_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->current_line_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->gate_stage_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->decision_kind_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->tokenize_parse_action_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->dirty_status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->reuse_status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}
