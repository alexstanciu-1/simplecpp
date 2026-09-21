#include <scpp/lang/php.hpp>
#include "__types/RuntimeHelperContractReadinessArtifact.hpp"
#include "__types/RuntimeHelperContractReadinessRow.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_artifact_json.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_json_escape.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_row_json.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_debug_string.hpp"
#include "__callable/__latency_fn_runtime_helper_contract_readiness_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_contract_readiness_artifact_json(shared_p<RuntimeHelperContractReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::artifact_json", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[22]);
	string_t json = required_cast<string_t>((string_t("{\"artifact\":\"runtime_helper_contract_readiness_artifact\",\"schema_version\":") + cast<string_t>(cast<int_t<>>(artifact->schema_version))));
	json = (cast<string_t>(json) + string_t(",\"row_count\":") + cast<string_t>(cast<int_t<>>(artifact->row_count)));
	json = (cast<string_t>(json) + string_t(",\"declaration_ready_count\":") + cast<string_t>(cast<int_t<>>(artifact->declaration_ready_count)));
	json = (cast<string_t>(json) + string_t(",\"generator_allowed_ready_count\":") + cast<string_t>(cast<int_t<>>(artifact->generator_allowed_ready_count)));
	json = (cast<string_t>(json) + string_t(",\"callable_demand_blocked_count\":") + cast<string_t>(cast<int_t<>>(artifact->callable_demand_blocked_count)));
	json = (cast<string_t>(json) + string_t(",\"string_storage_blocked_count\":") + cast<string_t>(cast<int_t<>>(artifact->string_storage_blocked_count)));
	json = (cast<string_t>(json) + string_t(",\"lowering_blocked_count\":") + cast<string_t>(cast<int_t<>>(artifact->lowering_blocked_count)));
	json = (cast<string_t>(json) + string_t(",\"accepted_count\":") + cast<string_t>(cast<int_t<>>(artifact->accepted_count)));
	json = (cast<string_t>(json) + string_t(",\"blocked_count\":") + cast<string_t>(cast<int_t<>>(artifact->blocked_count)));
	json = (cast<string_t>(json) + string_t(",\"authority_identity_hash_nonzero\":") + cast<string_t>(php::ternary_eval([&]() -> decltype(auto) { return php::not_identical(artifact->authority_identity_hash, __latency_fn_structure_row_ids_uint64_from_int(static_cast<int_t<> >(0))); }, [&]() -> decltype(auto) { return string_t("true"); }, [&]() -> decltype(auto) { return string_t("false"); })));
	json = (cast<string_t>(json) + string_t(",\"policy_state\":\"") + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_json_escape(artifact->policy_state)) + string_t("\""));
	json = (cast<string_t>(json) + string_t(",\"rows\":["));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((index > static_cast<int_t<> >(0)))) {
			json = (cast<string_t>(json) + string_t(","));
		}
		json = (cast<string_t>(json) + cast<string_t>(__latency_fn_runtime_helper_contract_readiness_row_json(row)));
		index = (index + static_cast<int_t<> >(1));
	}
	return (cast<string_t>(json) + string_t("]}"));
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_contract_readiness_debug_string(shared_p<RuntimeHelperContractReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[23]);
	string_t text = required_cast<string_t>((string_t("runtime_helper_contract_readiness:rows=") + cast<string_t>(cast<int_t<>>(artifact->row_count))));
	text = (cast<string_t>(text) + string_t(":declared=") + cast<string_t>(cast<int_t<>>(artifact->declaration_ready_count)));
	text = (cast<string_t>(text) + string_t(":generator_allowed=") + cast<string_t>(cast<int_t<>>(artifact->generator_allowed_ready_count)));
	text = (cast<string_t>(text) + string_t(":callable_demand_blocked=") + cast<string_t>(cast<int_t<>>(artifact->callable_demand_blocked_count)));
	text = (cast<string_t>(text) + string_t(":string_storage_blocked=") + cast<string_t>(cast<int_t<>>(artifact->string_storage_blocked_count)));
	text = (cast<string_t>(text) + string_t(":lowering_blocked=") + cast<string_t>(cast<int_t<>>(artifact->lowering_blocked_count)));
	text = (cast<string_t>(text) + string_t(":accepted=") + cast<string_t>(cast<int_t<>>(artifact->accepted_count)));
	text = (cast<string_t>(text) + string_t(":blocked=") + cast<string_t>(cast<int_t<>>(artifact->blocked_count)));
	text = (cast<string_t>(text) + string_t(":state=") + cast<string_t>(artifact->policy_state));
	return text;
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_contract_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_runtime_helper_contract_readiness_stable_hash(shared_p<RuntimeHelperContractReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_contract_readiness::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/runtime_helper_contract_readiness.phs", __latency_lines_runtime_helper_contract_readiness[24]);
	string_t identity = required_cast<string_t>(string_t("runtime_helper_contract_readiness:v1:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->declaration_ready_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->generator_allowed_ready_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->callable_demand_blocked_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->string_storage_blocked_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->lowering_blocked_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->accepted_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->blocked_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(artifact->policy_state));
	return php::stable_hash_string_u64(identity);
}

}
