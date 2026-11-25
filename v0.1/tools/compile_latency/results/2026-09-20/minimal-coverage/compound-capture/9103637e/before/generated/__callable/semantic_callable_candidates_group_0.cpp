#include <scpp/lang/php.hpp>
#include "__types/SemanticCallableCandidateRow.hpp"
#include "__types/semantic_callable_candidates.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_schema_version.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_callable_kind_runtime_helper_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_status_declared_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_status_not_declared_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_source_consumption_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_namespace_policy_runtime_helper_contract_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_candidate_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_generator_allowed_candidate_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_generated_identity.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_authority_identity_hash.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_generated_identity.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_callable_kind_runtime_helper_id.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_namespace_policy_runtime_helper_contract_id.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_row.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_source_consumption_blocked_id.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_status_declared_id.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_status_not_declared_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_core_namespace.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_php_namespace.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_row.hpp"
#include "__callable/__latency_fn_semantic_callable_candidates_rows.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_cast_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_compare_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_create_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_identical_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_not_identical_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_ref_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_shared_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_to_string_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_unique_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_value_id.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_declarations_helper_weak_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
bool_t semantic_callable_candidates::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == semantic_callable_candidates::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_callable_candidates_schema_version() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::schema_version", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_callable_candidates_callable_kind_runtime_helper_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::callable_kind_runtime_helper_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_callable_candidates_status_declared_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::status_declared_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_callable_candidates_status_not_declared_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::status_not_declared_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_callable_candidates_source_consumption_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::source_consumption_blocked_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_semantic_callable_candidates_namespace_policy_runtime_helper_contract_id() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::namespace_policy_runtime_helper_contract_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_semantic_callable_candidates_candidate_count() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::candidate_count", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[6]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(13));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_semantic_callable_candidates_generator_allowed_candidate_count() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::generator_allowed_candidate_count", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[7]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(13));
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
string_t __latency_fn_semantic_callable_candidates_generated_identity() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::generated_identity", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[8]);
	return string_t("runtime_helper_candidates=13;generator_allowed=13;helpers=create,shared,unique,weak,value,ref,cast,to_string,identical,not_identical,concat_assign,echo_eval,compare");
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_semantic_callable_candidates_authority_identity_hash() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::authority_identity_hash", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[9]);
	return php::stable_hash_string_u64(__latency_fn_semantic_callable_candidates_generated_identity());
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
shared_p<SemanticCallableCandidateRow> __latency_fn_semantic_callable_candidates_row(int_t<std::uint16_t> candidateId, int_t<std::uint16_t> helperId, const string_t& helperKey, bool_t generatorAllowed) {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::row", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[10]);
	shared_p<SemanticCallableCandidateRow> row = create<SemanticCallableCandidateRow>();
	row->candidate_id = candidateId;
	row->helper_id = helperId;
	row->callable_kind_id = __latency_fn_semantic_callable_candidates_callable_kind_runtime_helper_id();
	row->namespace_policy_id = __latency_fn_semantic_callable_candidates_namespace_policy_runtime_helper_contract_id();
	row->generator_allowed_status_id = php::ternary_eval([&]() -> decltype(auto) { return generatorAllowed; }, [&]() -> decltype(auto) { return __latency_fn_semantic_callable_candidates_status_declared_id(); }, [&]() -> decltype(auto) { return __latency_fn_semantic_callable_candidates_status_not_declared_id(); });
	row->source_consumption_status_id = __latency_fn_semantic_callable_candidates_source_consumption_blocked_id();
	row->helper_key = helperKey;
	row->core_namespace = __latency_fn_semantic_runtime_abi_declarations_core_namespace();
	row->php_namespace = __latency_fn_semantic_runtime_abi_declarations_php_namespace();
	row->authority_source = string_t("vendor/simple_cpp/runtime/specs/config.json:runtime_helpers_contract");
	row->source_consumption_note = string_t("candidate_row_ready_source_resolution_and_argument_contracts_blocked");
	return row;
}

}

namespace scpp { extern const int __latency_lines_semantic_callable_candidates[]; }
namespace scpp {
vector_t<shared_p<SemanticCallableCandidateRow>> __latency_fn_semantic_callable_candidates_rows() {
	SCPP_CALL_DEPTH_GUARD("semantic_callable_candidates::rows", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_callable_candidates.phs", __latency_lines_semantic_callable_candidates[11]);
	vector_t<shared_p<SemanticCallableCandidateRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(13));
	{
	auto __latency_local_0 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_runtime_abi_declarations_helper_create_id(), string_t("create"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_semantic_runtime_abi_declarations_helper_shared_id(), string_t("shared"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3)), __latency_fn_semantic_runtime_abi_declarations_helper_unique_id(), string_t("unique"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), __latency_fn_semantic_runtime_abi_declarations_helper_weak_id(), string_t("weak"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), __latency_fn_semantic_runtime_abi_declarations_helper_value_id(), string_t("value"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6)), __latency_fn_semantic_runtime_abi_declarations_helper_ref_id(), string_t("ref"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7)), __latency_fn_semantic_runtime_abi_declarations_helper_cast_id(), string_t("cast"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_semantic_runtime_abi_declarations_helper_to_string_id(), string_t("to_string"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(9)), __latency_fn_semantic_runtime_abi_declarations_helper_identical_id(), string_t("identical"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), __latency_fn_semantic_runtime_abi_declarations_helper_not_identical_id(), string_t("not_identical"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(11)), __latency_fn_semantic_runtime_abi_declarations_helper_concat_assign_id(), string_t("concat_assign"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(12)), __latency_fn_semantic_runtime_abi_declarations_helper_echo_eval_id(), string_t("echo_eval"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_semantic_callable_candidates_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(13)), __latency_fn_semantic_runtime_abi_declarations_helper_compare_id(), string_t("compare"), bool_t(static_cast<bool_t>(true)));
	(void) rows.push_back(__latency_local_12);
	}
	return rows;
}

}
