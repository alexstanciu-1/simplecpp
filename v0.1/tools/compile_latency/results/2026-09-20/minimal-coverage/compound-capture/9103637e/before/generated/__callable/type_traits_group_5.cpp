#include <scpp/lang/php.hpp>
#include "__types/TypeTraitRow.hpp"
#include "__types/TypeTraitTable.hpp"
#include "__callable/__latency_fn_type_traits_lookup_row_id_for_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_row_by_row_id.hpp"
#include "__callable/__latency_fn_type_traits_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_name.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_scan_ok_id.hpp"
#include "__callable/__latency_fn_type_traits_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_traits_status_name.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_floating_point_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_name.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_unsigned_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_language_int_alias_i64_id.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_name.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_none_id.hpp"
#include "__callable/__latency_fn_type_traits_alias_policy_canonical_self_id.hpp"
#include "__callable/__latency_fn_type_traits_alias_policy_language_int_to_int64_id.hpp"
#include "__callable/__latency_fn_type_traits_alias_policy_name.hpp"
#include "__callable/__latency_fn_type_traits_alias_policy_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_floating_basic_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_name.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_plus_minus_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_name.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_unknown_type_trait_id.hpp"
namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
TypeTraitRow __latency_fn_type_traits_row_by_type_ref_id(TypeTraitTable table, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::row_by_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[71]);
	int_t<std::uint32_t> indexedRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_traits_lookup_row_id_for_type_ref_id(table, cast<int_t<std::uint32_t>>(typeRefId)));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(indexedRowId), static_cast<int_t<> >(0))))) {
		TypeTraitRow indexedRow = __latency_fn_type_traits_row_by_row_id(table, cast<int_t<std::uint32_t>>(indexedRowId));
		if (static_cast<bool>(php::identical(cast<int_t<>>(indexedRow->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return indexedRow;
		}
	}
	auto __latency_local_0 = table->traits;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row;
		}
	}
	TypeTraitRow empty = TypeTraitRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_lookup_policy_name(int_t<std::uint16_t> lookupPolicyId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::lookup_policy_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[72]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(lookupPolicyId), cast<int_t<>>(__latency_fn_type_traits_lookup_policy_scan_ok_id())))) {
		return string_t("scan_ok");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(lookupPolicyId), cast<int_t<>>(__latency_fn_type_traits_lookup_policy_index_recommended_id())))) {
		return string_t("index_recommended");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[73]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_type_traits_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_numeric_kind_name(int_t<std::uint16_t> numericKindId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::numeric_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[74]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(numericKindId), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_none_id())))) {
		return string_t("none");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(numericKindId), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id())))) {
		return string_t("signed_integer");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(numericKindId), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_unsigned_integer_id())))) {
		return string_t("unsigned_integer");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(numericKindId), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_floating_point_id())))) {
		return string_t("floating_point");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_integer_width_policy_name(int_t<std::uint16_t> policyId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::integer_width_policy_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[75]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_type_traits_integer_width_policy_none_id())))) {
		return string_t("none");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_type_traits_integer_width_policy_fixed_width_explicit_id())))) {
		return string_t("fixed_width_explicit");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_type_traits_integer_width_policy_language_int_alias_i64_id())))) {
		return string_t("language_int_alias_i64");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_alias_policy_name(int_t<std::uint16_t> policyId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::alias_policy_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[76]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_type_traits_alias_policy_none_id())))) {
		return string_t("none");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_type_traits_alias_policy_canonical_self_id())))) {
		return string_t("canonical_self");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_type_traits_alias_policy_language_int_to_int64_id())))) {
		return string_t("language_int_to_int64");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_numeric_operator_mask_name(int_t<std::uint16_t> mask) {
	SCPP_CALL_DEPTH_GUARD("type_traits::numeric_operator_mask_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[77]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(mask), cast<int_t<>>(__latency_fn_type_traits_numeric_operator_mask_none_id())))) {
		return string_t("none");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(mask), cast<int_t<>>(__latency_fn_type_traits_numeric_operator_mask_plus_minus_id())))) {
		return string_t("plus_minus");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(mask), cast<int_t<>>(__latency_fn_type_traits_numeric_operator_mask_signed_integer_basic_id())))) {
		return string_t("integer_basic");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(mask), cast<int_t<>>(__latency_fn_type_traits_numeric_operator_mask_floating_basic_id())))) {
		return string_t("floating_basic");
	}
	return string_t("custom");
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_numeric_blocked_reason_name(int_t<std::uint16_t> reasonId) {
	SCPP_CALL_DEPTH_GUARD("type_traits::numeric_blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[78]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_type_traits_numeric_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_type_traits_numeric_blocked_reason_not_numeric_type_id())))) {
		return string_t("not_numeric_type");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_type_traits_numeric_blocked_reason_unknown_type_trait_id())))) {
		return string_t("unknown_type_trait");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_type_traits_numeric_blocked_reason_backend_numeric_abi_not_ready_id())))) {
		return string_t("backend_numeric_abi_not_ready");
	}
	return string_t("unknown");
}

}
