#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionBlockRow.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_block_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_backend_emission_decisions[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_emission_decisions_block_stable_hash(BackendEmissionBlockRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_emission_decisions::block_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_emission_decisions.phs", __latency_lines_backend_emission_decisions[61]);
	string_t identity = required_cast<string_t>(string_t("backend_emission_block:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->block_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->decision_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->lowering_step_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->value_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)));
	return php::stable_hash_string_u64(identity);
}

}
