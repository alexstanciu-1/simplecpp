#include <scpp/lang/php.hpp>
#include "__types/PrimitiveTypeRefSpellingDescriptorRow.hpp"
#include "__types/ProviderTraitDescriptorRow.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_trait_descriptor_by_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_trait_descriptors.hpp"
#include "__callable/__latency_fn_type_traits_numeric_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_type_traits_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_traits_status_unknown_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_byte_span_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_curl_handle_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_null_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_spelling_descriptors.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_buffer_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_line_index_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_source_location_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_spelling_descriptor.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_string_parts_builder_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_string_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_text_builder_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_token_buffer_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_authority_source_list.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_authority_path_count.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_debug_string.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_module_resource_count.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_count.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_provider_trait_descriptor_count.hpp"
#include "__callable/__latency_fn_semantic_type_ref_providers_runtime_singleton_count.hpp"
namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
ProviderTraitDescriptorRow __latency_fn_semantic_type_ref_providers_provider_trait_descriptor_by_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::provider_trait_descriptor_by_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[36]);
	auto __latency_local_0 = __latency_fn_semantic_type_ref_providers_provider_trait_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row;
		}
	}
	ProviderTraitDescriptorRow empty = ProviderTraitDescriptorRow{};
	empty->status_id = __latency_fn_type_traits_status_unknown_id();
	empty->numeric_status_id = __latency_fn_type_traits_status_blocked_id();
	empty->numeric_blocked_reason_id = __latency_fn_type_traits_numeric_blocked_reason_unknown_type_trait_id();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> __latency_fn_semantic_type_ref_providers_provider_spelling_descriptors() {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::provider_spelling_descriptors", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[37]);
	vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(10));
	{
	auto __latency_local_0 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_string_type_ref_id(), string_t("string"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_source_buffer_type_ref_id(), string_t("source_buffer"));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_byte_span_type_ref_id(), string_t("byte_span"));
	(void) rows.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_token_buffer_type_ref_id(), string_t("token_buffer"));
	(void) rows.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_source_line_index_type_ref_id(), string_t("source_line_index"));
	(void) rows.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_source_location_type_ref_id(), string_t("source_location"));
	(void) rows.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_string_parts_builder_type_ref_id(), string_t("string_parts_builder"));
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_text_builder_type_ref_id(), string_t("text_builder"));
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_curl_handle_type_ref_id(), string_t("curl_handle"));
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_semantic_type_ref_providers_spelling_descriptor(__latency_fn_semantic_type_ref_providers_null_type_ref_id(), string_t("null"));
	(void) rows.push_back(__latency_local_9);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
string_t __latency_fn_semantic_type_ref_providers_authority_source_list() {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::authority_source_list", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[38]);
	return string_t("vendor/simple_cpp/runtime/specs/config.json:types.string_t,compiler/docs/future/type_ref_boundary_stabilization_plan_2026_08_03.md:compiler_support_runtime_singletons,vendor/simple_cpp/specs/builtins/curl/first_pass.md:strict_runtime_surface,vendor/simple_cpp/runtime/specs/spec.md:null_t");
}

}

namespace scpp { extern const int __latency_lines_semantic_type_ref_providers[]; }
namespace scpp {
string_t __latency_fn_semantic_type_ref_providers_debug_string() {
	SCPP_CALL_DEPTH_GUARD("semantic_type_ref_providers::debug_string", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_type_ref_providers.phs", __latency_lines_semantic_type_ref_providers[39]);
	return (string_t("semantic_type_ref_providers:providers=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_provider_count())) + string_t(":trait_descriptors=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_provider_trait_descriptor_count())) + string_t(":runtime_singletons=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_runtime_singleton_count())) + string_t(":module_resources=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_module_resource_count())) + string_t(":authority_paths=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_type_ref_providers_authority_path_count())) + string_t(":state=runtime_provider_type_refs_generated"));
}

}
