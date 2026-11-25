#include <scpp/lang/php.hpp>
#include "__types/SemanticConversionLookupRow.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_row_by_conversion_id.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_rows.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_row_by_runtime_types.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_rows.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_row_by_type_refs_and_permission.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_rows.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_boundary_bridge_conversion_count.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_conversion_count.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_debug_string.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_deferred_template_or_family_count.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_explicit_conversion_count.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_implicit_conversion_count.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_known_type_ref_pair_count.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_runtime_config_cast_count.hpp"
#include "__callable/__latency_fn_semantic_conversion_lookup_unknown_type_ref_pair_count.hpp"
namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
shared_p<SemanticConversionLookupRow> __latency_fn_semantic_conversion_lookup_row_by_conversion_id(int_t<std::uint16_t> conversionId) {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::row_by_conversion_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[23]);
	auto __latency_local_0 = __latency_fn_semantic_conversion_lookup_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->conversion_id), cast<int_t<>>(conversionId)))) {
			return row;
		}
	}
	shared_p<SemanticConversionLookupRow> empty = create<SemanticConversionLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
shared_p<SemanticConversionLookupRow> __latency_fn_semantic_conversion_lookup_row_by_runtime_types(const string_t& sourceRuntimeType, const string_t& targetRuntimeType) {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::row_by_runtime_types", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[24]);
	auto __latency_local_0 = __latency_fn_semantic_conversion_lookup_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(row->source_runtime_type, sourceRuntimeType) && php::identical(row->target_runtime_type, targetRuntimeType)))) {
			return row;
		}
	}
	shared_p<SemanticConversionLookupRow> empty = create<SemanticConversionLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
shared_p<SemanticConversionLookupRow> __latency_fn_semantic_conversion_lookup_row_by_type_refs_and_permission(int_t<std::uint32_t> sourceTypeRefId, int_t<std::uint32_t> targetTypeRefId, bool_t requireImplicit) {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::row_by_type_refs_and_permission", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[25]);
	auto __latency_local_0 = __latency_fn_semantic_conversion_lookup_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->source_type_ref_id), cast<int_t<>>(sourceTypeRefId)) && php::identical(cast<int_t<>>(row->target_type_ref_id), cast<int_t<>>(targetTypeRefId))))) {
			if (static_cast<bool>((requireImplicit && row->implicit_allowed))) {
				return row;
			}
			if (static_cast<bool>(((!requireImplicit) && row->explicit_allowed))) {
				return row;
			}
		}
	}
	shared_p<SemanticConversionLookupRow> empty = create<SemanticConversionLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_conversion_lookup[]; }
namespace scpp {
string_t __latency_fn_semantic_conversion_lookup_debug_string() {
	SCPP_CALL_DEPTH_GUARD("semantic_conversion_lookup::debug_string", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_conversion_lookup.phs", __latency_lines_semantic_conversion_lookup[26]);
	return (string_t("semantic_conversion_lookup:rows=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_conversion_count())) + string_t(":runtime_casts=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_runtime_config_cast_count())) + string_t(":deferred_template_or_family=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_deferred_template_or_family_count())) + string_t(":known_type_ref_pairs=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_known_type_ref_pair_count())) + string_t(":unknown_type_ref_pairs=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_unknown_type_ref_pair_count())) + string_t(":implicit=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_implicit_conversion_count())) + string_t(":explicit=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_explicit_conversion_count())) + string_t(":boundary=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_boundary_bridge_conversion_count())) + string_t(":lowering_not_wired=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_conversion_count())) + string_t(":lossy_unspecified=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_conversion_lookup_conversion_count())) + string_t(":state=scalar_lookup_ready_source_consumption_blocked"));
}

}
