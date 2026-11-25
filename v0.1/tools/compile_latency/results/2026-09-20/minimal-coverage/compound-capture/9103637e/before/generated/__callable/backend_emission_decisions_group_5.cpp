#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionBlockRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_equals.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_equals.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_equals.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_artifact_debug_string.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_debug_string.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_kind_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_debug_string.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_debug_string.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_kind_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_status_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_artifact_stable_hash.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_decision_stable_hash.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
BackendEmissionBlockRow __latency_fn_backend_emission_decisions_block_by_id(BackendEmissionDecisionArtifact artifact, int_t<std::uint32_t> blockId) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::block_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[50]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(blockId, cast<int_t<>>(artifact->block_count))))) {
		BackendEmissionBlockRow row = artifact->blocks[__latency_fn_structure_row_ids_dense_index(blockId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->block_id), cast<int_t<>>(blockId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->blocks;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->block_id), cast<int_t<>>(blockId)))) {
			return row;
		}
	}
	BackendEmissionBlockRow empty = BackendEmissionBlockRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
bool_t __latency_fn_backend_emission_decisions_decision_equals(BackendEmissionDecisionRow left, BackendEmissionDecisionRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::decision_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[51]);
	return ((((((((php::identical(cast<int_t<>>(left->decision_id), cast<int_t<>>(right->decision_id)) && php::identical(cast<int_t<>>(left->owner_source_unit_id), cast<int_t<>>(right->owner_source_unit_id))) && php::identical(cast<int_t<>>(left->owner_symbol_id), cast<int_t<>>(right->owner_symbol_id))) && php::identical(cast<int_t<>>(left->lowering_step_count), cast<int_t<>>(right->lowering_step_count))) && php::identical(cast<int_t<>>(left->blocked_request_count), cast<int_t<>>(right->blocked_request_count))) && php::identical(cast<int_t<>>(left->value_count), cast<int_t<>>(right->value_count))) && php::identical(cast<int_t<>>(left->block_count), cast<int_t<>>(right->block_count))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->blocked_reason_id), cast<int_t<>>(right->blocked_reason_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
bool_t __latency_fn_backend_emission_decisions_value_equals(BackendEmissionValueRow left, BackendEmissionValueRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[52]);
	return (((((((php::identical(cast<int_t<>>(left->value_id), cast<int_t<>>(right->value_id)) && php::identical(cast<int_t<>>(left->decision_id), cast<int_t<>>(right->decision_id))) && php::identical(cast<int_t<>>(left->lowering_step_id), cast<int_t<>>(right->lowering_step_id))) && php::identical(cast<int_t<>>(left->backend_request_id), cast<int_t<>>(right->backend_request_id))) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->value_role_id), cast<int_t<>>(right->value_role_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
bool_t __latency_fn_backend_emission_decisions_block_equals(BackendEmissionBlockRow left, BackendEmissionBlockRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::block_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[53]);
	return (((((php::identical(cast<int_t<>>(left->block_id), cast<int_t<>>(right->block_id)) && php::identical(cast<int_t<>>(left->decision_id), cast<int_t<>>(right->decision_id))) && php::identical(cast<int_t<>>(left->lowering_step_count), cast<int_t<>>(right->lowering_step_count))) && php::identical(cast<int_t<>>(left->value_count), cast<int_t<>>(right->value_count))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->blocked_reason_id), cast<int_t<>>(right->blocked_reason_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_artifact_debug_string(BackendEmissionDecisionArtifact artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::artifact_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[54]);
	return (string_t("backend_emission_decisions:") + cast<string_t>(cast<int_t<>>(artifact->decision_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->value_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->block_count)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_decision_debug_string(BackendEmissionDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::decision_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[55]);
	return (string_t("backend_emission_decision:") + cast<string_t>(cast<int_t<>>(row->decision_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_emission_decisions_decision_kind_name(row->decision_kind_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_emission_decisions_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_value_debug_string(BackendEmissionValueRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[56]);
	return (string_t("backend_emission_value:") + cast<string_t>(cast<int_t<>>(row->value_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->backend_request_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_emission_decisions_value_role_name(row->value_role_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_emission_decisions_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
string_t __latency_fn_backend_emission_decisions_block_debug_string(BackendEmissionBlockRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::block_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[57]);
	return (string_t("backend_emission_block:") + cast<string_t>(cast<int_t<>>(row->block_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_emission_decisions_block_kind_name(row->block_kind_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_emission_decisions_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_emission_decisions_artifact_stable_hash(BackendEmissionDecisionArtifact artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::artifact_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[58]);
	string_t identity = required_cast<string_t>((string_t("backend_emission_artifact:v1:") + cast<string_t>(cast<int_t<>>(artifact->artifact_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->source_model_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->emission_model_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->owner_source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->decision_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->value_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->local_operand_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->call_argument_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->control_flow_operand_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->block_count))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_emission_decisions_decision_stable_hash(BackendEmissionDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::decision_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[59]);
	string_t identity = required_cast<string_t>((string_t("backend_emission_decision:v1:") + cast<string_t>(cast<int_t<>>(row->decision_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->owner_source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->lowering_step_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_request_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->value_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->block_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_emission_decisions_value_stable_hash(BackendEmissionValueRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::value_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[60]);
	string_t identity = required_cast<string_t>((string_t("backend_emission_value:v2:") + cast<string_t>(cast<int_t<>>(row->value_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->decision_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->lowering_step_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->backend_request_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->value_role_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}
