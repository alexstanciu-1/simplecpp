#include <scpp/lang/php.hpp>
#include "__types/SemanticConversionLookupRow.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_implicit_conversion_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_explicit_conversion_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_boundary_bridge_conversion_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_generated_identity.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_authority_identity_hash.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_generated_identity.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_lossiness_authority_unspecified_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_lowering_status_not_wired_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_row.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_form_constructor_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_form_named_cast_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_kind_boundary_implicit_v1_visible_intention_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_kind_explicit_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_kind_implicit_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_permission_boundary_implicit_bridge_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_permission_explicit_only_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_permission_implicit_allowed_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_row.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_float_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_semantic_conversion_lookup_implicit_conversion_count() {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::implicit_conversion_count", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[16]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_semantic_conversion_lookup_explicit_conversion_count() {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::explicit_conversion_count", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[17]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(16));
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_semantic_conversion_lookup_boundary_bridge_conversion_count() {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::boundary_bridge_conversion_count", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[18]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
string_t __latency_fn_semantic_conversion_lookup_generated_identity() {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::generated_identity", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[19]);
	return string_t("runtime_casts=46;conversion_rows=26;deferred_template_or_family=20;known_type_ref_pairs=9;implicit=6;explicit=16;boundary=4");
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_semantic_conversion_lookup_authority_identity_hash() {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::authority_identity_hash", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[20]);
	return php::stable_hash_string_u64(__latency_fn_semantic_conversion_lookup_generated_identity());
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
shared_p<SemanticConversionLookupRow> __latency_fn_semantic_conversion_lookup_row(int_t<std::uint16_t> conversionId, int_t<std::uint16_t> kindId, int_t<std::uint16_t> formId, int_t<std::uint16_t> permissionId, int_t<std::uint32_t> sourceTypeRefId, int_t<std::uint32_t> targetTypeRefId, int_t<std::uint32_t> authorityIndex, bool_t implicitAllowed, bool_t explicitAllowed, bool_t requiresRuntimeCheck, const string_t& sourceRuntimeType, const string_t& targetRuntimeType, const string_t& castName, const string_t& policyRole, const string_t& diagnosticKey) {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::row", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[21]);
	shared_p<SemanticConversionLookupRow> row = create<SemanticConversionLookupRow>();
	row->conversion_id = conversionId;
	row->kind_id = kindId;
	row->form_id = formId;
	row->permission_id = permissionId;
	row->lossiness_status_id = __latency_fn_semantic_conversion_lookup_lossiness_authority_unspecified_id();
	row->lowering_status_id = __latency_fn_semantic_conversion_lookup_lowering_status_not_wired_id();
	row->source_type_ref_id = sourceTypeRefId;
	row->target_type_ref_id = targetTypeRefId;
	row->authority_index = authorityIndex;
	row->implicit_allowed = implicitAllowed;
	row->explicit_allowed = explicitAllowed;
	row->requires_runtime_check = requiresRuntimeCheck;
	row->source_runtime_type = sourceRuntimeType;
	row->target_runtime_type = targetRuntimeType;
	row->cast_name = castName;
	row->policy_role = policyRole;
	row->llvm_opcode = string_t("");
	row->runtime_symbol = castName;
	row->diagnostic_key = diagnosticKey;
	row->authority_source = string_t("vendor/simple_cpp/runtime/specs/config.json:casts");
	return row;
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
vector_t<shared_p<SemanticConversionLookupRow>> __latency_fn_semantic_conversion_lookup_rows() {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::rows", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[22]);
	vector_t<shared_p<SemanticConversionLookupRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(26));
	{
	auto __latency_local_0 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1)), __latency_fn_semantic_conversion_lookup_kind_implicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_implicit_allowed_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(6)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(false)), string_t("int_t"), string_t("float_t"), string_t(""), string_t(""), string_t("conversion.implicit.constructor.int_t_to_float_t"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(7)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("bool_t"), string_t("int_t"), string_t(""), string_t(""), string_t("conversion.explicit.constructor.bool_t_to_int_t"));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(8)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("bool_t"), string_t("float_t"), string_t(""), string_t(""), string_t("conversion.explicit.constructor.bool_t_to_float_t"));
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(9)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("bool_t"), string_t("bool"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.bool_t_to_bool"));
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(10)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("int_t"), string_t("bool_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.int_t_to_bool_t"));
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_float_id(), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(11)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("float_t"), string_t("bool_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.float_t_to_bool_t"));
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_float_id(), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(12)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("float_t"), string_t("int_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.float_t_to_int_t"));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(13)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(true)), string_t("string_t"), string_t("string_t"), string_t("cast"), string_t("identity"), string_t("conversion.explicit.named_cast.string_t_to_string_t"));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(9)), __latency_fn_semantic_conversion_lookup_kind_implicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_implicit_allowed_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(22)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("null_t"), string_t("mixed_t"), string_t(""), string_t("box_into_mixed_runtime_value"), string_t("conversion.implicit.constructor.null_t_to_mixed_t"));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10)), __latency_fn_semantic_conversion_lookup_kind_implicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_implicit_allowed_id(), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(23)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("bool_t"), string_t("mixed_t"), string_t(""), string_t("box_into_mixed_runtime_value"), string_t("conversion.implicit.constructor.bool_t_to_mixed_t"));
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(11)), __latency_fn_semantic_conversion_lookup_kind_implicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_implicit_allowed_id(), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(24)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("int_t"), string_t("mixed_t"), string_t(""), string_t("box_into_mixed_runtime_value"), string_t("conversion.implicit.constructor.int_t_to_mixed_t"));
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(12)), __latency_fn_semantic_conversion_lookup_kind_implicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_implicit_allowed_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(25)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("float_t"), string_t("mixed_t"), string_t(""), string_t("box_into_mixed_runtime_value"), string_t("conversion.implicit.constructor.float_t_to_mixed_t"));
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(13)), __latency_fn_semantic_conversion_lookup_kind_implicit_id(), __latency_fn_semantic_conversion_lookup_form_constructor_id(), __latency_fn_semantic_conversion_lookup_permission_implicit_allowed_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(26)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("string_t"), string_t("mixed_t"), string_t(""), string_t("box_into_mixed_runtime_value"), string_t("conversion.implicit.constructor.string_t_to_mixed_t"));
	(void) rows.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(14)), __latency_fn_semantic_conversion_lookup_kind_boundary_implicit_v1_visible_intention_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_boundary_implicit_bridge_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(30)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("mixed_t"), string_t("bool_t"), string_t("cast"), string_t("delegate_by_runtime_kind_to_native_rule_then_extract_or_runtime_error_including_strict_string_literals"), string_t("conversion.boundary_implicit_v1_visible_intention.named_cast.mixed_t_to_bool_t"));
	(void) rows.push_back(__latency_local_13);
	}
	{
	auto __latency_local_14 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(15)), __latency_fn_semantic_conversion_lookup_kind_boundary_implicit_v1_visible_intention_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_boundary_implicit_bridge_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(31)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("mixed_t"), string_t("int_t"), string_t("cast"), string_t("delegate_by_runtime_kind_to_native_rule_then_extract_or_runtime_error_including_strict_string_parse"), string_t("conversion.boundary_implicit_v1_visible_intention.named_cast.mixed_t_to_int_t"));
	(void) rows.push_back(__latency_local_14);
	}
	{
	auto __latency_local_15 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(16)), __latency_fn_semantic_conversion_lookup_kind_boundary_implicit_v1_visible_intention_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_boundary_implicit_bridge_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(32)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("mixed_t"), string_t("float_t"), string_t("cast"), string_t("delegate_by_runtime_kind_to_native_rule_then_extract_or_runtime_error_including_strict_string_parse"), string_t("conversion.boundary_implicit_v1_visible_intention.named_cast.mixed_t_to_float_t"));
	(void) rows.push_back(__latency_local_15);
	}
	{
	auto __latency_local_16 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(17)), __latency_fn_semantic_conversion_lookup_kind_boundary_implicit_v1_visible_intention_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_boundary_implicit_bridge_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(33)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), string_t("mixed_t"), string_t("string_t"), string_t("cast"), string_t("delegate_by_runtime_kind_to_native_rule_then_extract_or_runtime_error"), string_t("conversion.boundary_implicit_v1_visible_intention.named_cast.mixed_t_to_string_t"));
	(void) rows.push_back(__latency_local_16);
	}
	{
	auto __latency_local_17 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(18)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(35)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("bool_t"), string_t("int_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.bool_t_to_int_t"));
	(void) rows.push_back(__latency_local_17);
	}
	{
	auto __latency_local_18 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(19)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_bool_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(36)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("bool_t"), string_t("float_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.bool_t_to_float_t"));
	(void) rows.push_back(__latency_local_18);
	}
	{
	auto __latency_local_19 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(20)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_int_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(37)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("int_t"), string_t("float_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.int_t_to_float_t"));
	(void) rows.push_back(__latency_local_19);
	}
	{
	auto __latency_local_20 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(21)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(38)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(true)), string_t("string_t"), string_t("bool_t"), string_t("cast"), string_t("strict_string_bool_literal_set"), string_t("conversion.explicit.named_cast.string_t_to_bool_t"));
	(void) rows.push_back(__latency_local_20);
	}
	{
	auto __latency_local_21 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(22)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(39)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(true)), string_t("string_t"), string_t("int_t"), string_t("cast"), string_t("strict_full_string_base10_parse"), string_t("conversion.explicit.named_cast.string_t_to_int_t"));
	(void) rows.push_back(__latency_local_21);
	}
	{
	auto __latency_local_22 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(23)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(40)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(true)), string_t("string_t"), string_t("float_t"), string_t("cast"), string_t("strict_full_string_finite_decimal_parse"), string_t("conversion.explicit.named_cast.string_t_to_float_t"));
	(void) rows.push_back(__latency_local_22);
	}
	{
	auto __latency_local_23 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(24)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_int_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(41)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("int_t"), string_t("string_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.int_t_to_string_t"));
	(void) rows.push_back(__latency_local_23);
	}
	{
	auto __latency_local_24 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(25)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_float_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(42)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("float_t"), string_t("string_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.float_t_to_string_t"));
	(void) rows.push_back(__latency_local_24);
	}
	{
	auto __latency_local_25 = __latency_fn_semantic_conversion_lookup_row(__latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(26)), __latency_fn_semantic_conversion_lookup_kind_explicit_id(), __latency_fn_semantic_conversion_lookup_form_named_cast_id(), __latency_fn_semantic_conversion_lookup_permission_explicit_only_id(), __latency_fn_type_refs_bool_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(43)), bool_t(static_cast<bool_t>(false)), bool_t(static_cast<bool_t>(true)), bool_t(static_cast<bool_t>(false)), string_t("bool_t"), string_t("string_t"), string_t("cast"), string_t(""), string_t("conversion.explicit.named_cast.bool_t_to_string_t"));
	(void) rows.push_back(__latency_local_25);
	}
	return rows;
}

}
