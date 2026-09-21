#include <scpp/lang/php.hpp>
#include "__types/TypeTraitRow.hpp"
#include "__types/TypeTraitTable.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_type_traits_alias_policy_name.hpp"
#include "__callable/__latency_fn_type_traits_integer_width_policy_name.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_name.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_name.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_name.hpp"
#include "__callable/__latency_fn_type_traits_row_debug_string.hpp"
#include "__callable/__latency_fn_type_traits_status_name.hpp"
#include "__callable/__latency_fn_type_traits_lookup_policy_name.hpp"
#include "__callable/__latency_fn_type_traits_table_debug_string.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_has_bit.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_has_bit.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_supports_integer_operator.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_has_bit.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_supports_numeric_operator.hpp"
#include "__callable/__latency_fn_type_traits_declares_integer_operator.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_has_bit.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_declares_numeric_operator.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_none_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_has_bit.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_minus_bit_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_plus_bit_id.hpp"
#include "__callable/__latency_fn_type_traits_supports_integer_operator.hpp"
#include "__callable/__latency_fn_type_traits_supports_integer_plus_minus.hpp"
namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_row_debug_string(TypeTraitRow row) {
	SCPP_CALL_DEPTH_GUARD("type_traits::row_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[79]);
	str::text_builder text = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(text, static_cast<int_t<> >(192));
	str::text_builder_append_string(text, string_t("type_trait:"));
	str::text_builder_append_string(text, __latency_fn_type_ref_identity_name(row->type_ref_id));
	str::text_builder_append_string(text, string_t(":provider="));
	str::text_builder_append_int(text, cast<int_t<>>(row->provider_id));
	str::text_builder_append_string(text, string_t(":numeric="));
	str::text_builder_append_string(text, __latency_fn_type_traits_status_name(row->numeric_status_id));
	str::text_builder_append_string(text, string_t(":kind="));
	str::text_builder_append_string(text, __latency_fn_type_traits_numeric_kind_name(row->numeric_kind_id));
	str::text_builder_append_string(text, string_t(":width="));
	str::text_builder_append_int(text, cast<int_t<>>(row->integer_width_bits));
	str::text_builder_append_string(text, string_t(":width_policy="));
	str::text_builder_append_string(text, __latency_fn_type_traits_integer_width_policy_name(row->integer_width_policy_id));
	str::text_builder_append_string(text, string_t(":alias_policy="));
	str::text_builder_append_string(text, __latency_fn_type_traits_alias_policy_name(row->alias_policy_id));
	str::text_builder_append_string(text, string_t(":canonical_numeric_type="));
	str::text_builder_append_string(text, __latency_fn_type_ref_identity_name(row->canonical_numeric_type_ref_id));
	str::text_builder_append_string(text, string_t(":operators="));
	str::text_builder_append_string(text, __latency_fn_type_traits_numeric_operator_mask_name(row->numeric_operator_mask));
	str::text_builder_append_string(text, string_t(":blocked_reason="));
	str::text_builder_append_string(text, __latency_fn_type_traits_numeric_blocked_reason_name(row->numeric_blocked_reason_id));
	return str::text_builder_take_string(text);
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
string_t __latency_fn_type_traits_table_debug_string(TypeTraitTable table) {
	SCPP_CALL_DEPTH_GUARD("type_traits::table_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[80]);
	str::text_builder text = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(text, static_cast<int_t<> >(128));
	str::text_builder_append_string(text, string_t("type_trait_table:schema="));
	str::text_builder_append_int(text, cast<int_t<>>(table->schema_version));
	str::text_builder_append_string(text, string_t(":traits="));
	str::text_builder_append_int(text, cast<int_t<>>(table->trait_count));
	str::text_builder_append_string(text, string_t(":numeric_ready="));
	str::text_builder_append_int(text, cast<int_t<>>(table->numeric_ready_count));
	str::text_builder_append_string(text, string_t(":numeric_blocked="));
	str::text_builder_append_int(text, cast<int_t<>>(table->numeric_blocked_count));
	str::text_builder_append_string(text, string_t(":lookup_policy="));
	str::text_builder_append_string(text, __latency_fn_type_traits_lookup_policy_name(table->lookup_policy_status_id));
	str::text_builder_append_string(text, string_t(":lookup_index_threshold="));
	str::text_builder_append_int(text, cast<int_t<>>(table->lookup_index_threshold));
	str::text_builder_append_string(text, string_t(":lookup_index_slots="));
	str::text_builder_append_int(text, cast<int_t<>>(table->lookup_index_slot_count));
	return str::text_builder_take_string(text);
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
bool_t __latency_fn_type_traits_numeric_operator_mask_has_bit(int_t<std::uint16_t> mask, int_t<std::uint16_t> bit) {
	SCPP_CALL_DEPTH_GUARD("type_traits::numeric_operator_mask_has_bit", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[81]);
	int_t<> maskValue = required_cast<int_t<>>(cast<int_t<>>(mask));
	int_t<> bitValue = required_cast<int_t<>>(cast<int_t<>>(bit));
	if (static_cast<bool>((bitValue <= static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> period = required_cast<int_t<>>((bitValue * static_cast<int_t<> >(2)));
	int_t<> remainder = required_cast<int_t<>>((maskValue % period));
	return (remainder >= bitValue);
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
bool_t __latency_fn_type_traits_supports_integer_operator(TypeTraitRow row, int_t<std::uint16_t> operatorBit) {
	SCPP_CALL_DEPTH_GUARD("type_traits::supports_integer_operator", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[82]);
	return (((php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())) && php::identical(cast<int_t<>>(row->numeric_status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))) && php::identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id()))) && __latency_fn_type_traits_numeric_operator_mask_has_bit(row->numeric_operator_mask, cast<int_t<std::uint16_t>>(operatorBit)));
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
bool_t __latency_fn_type_traits_supports_numeric_operator(TypeTraitRow row, int_t<std::uint16_t> operatorBit) {
	SCPP_CALL_DEPTH_GUARD("type_traits::supports_numeric_operator", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[83]);
	return (((php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())) && php::identical(cast<int_t<>>(row->numeric_status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))) && php::not_identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_none_id()))) && __latency_fn_type_traits_numeric_operator_mask_has_bit(row->numeric_operator_mask, cast<int_t<std::uint16_t>>(operatorBit)));
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
bool_t __latency_fn_type_traits_declares_integer_operator(TypeTraitRow row, int_t<std::uint16_t> operatorBit) {
	SCPP_CALL_DEPTH_GUARD("type_traits::declares_integer_operator", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[84]);
	return ((php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())) && php::identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id()))) && __latency_fn_type_traits_numeric_operator_mask_has_bit(row->numeric_operator_mask, cast<int_t<std::uint16_t>>(operatorBit)));
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
bool_t __latency_fn_type_traits_declares_numeric_operator(TypeTraitRow row, int_t<std::uint16_t> operatorBit) {
	SCPP_CALL_DEPTH_GUARD("type_traits::declares_numeric_operator", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[85]);
	return ((php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id())) && php::not_identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_none_id()))) && __latency_fn_type_traits_numeric_operator_mask_has_bit(row->numeric_operator_mask, cast<int_t<std::uint16_t>>(operatorBit)));
}

}

namespace scpp { extern const int __latency_lines_type_traits[]; }
namespace scpp {
bool_t __latency_fn_type_traits_supports_integer_plus_minus(TypeTraitRow row) {
	SCPP_CALL_DEPTH_GUARD("type_traits::supports_integer_plus_minus", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_traits.phs", __latency_lines_type_traits[86]);
	return (__latency_fn_type_traits_supports_integer_operator(row, __latency_fn_type_traits_numeric_operator_plus_bit_id()) && __latency_fn_type_traits_supports_integer_operator(row, __latency_fn_type_traits_numeric_operator_minus_bit_id()));
}

}
