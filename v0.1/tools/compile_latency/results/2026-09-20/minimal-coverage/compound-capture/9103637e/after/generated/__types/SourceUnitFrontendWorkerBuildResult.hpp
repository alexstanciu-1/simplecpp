#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class FrontendModel;
class ProjectSymbolIndex;
class SourceUnitFrontendWorkerFrontendPayloadCarrier;
class TokenStream;
class SourceUnitFrontendWorkerBuildResult {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> tokenizer_elapsed_us = static_cast<int_t<> >(0);
	int_t<> parser_elapsed_us = static_cast<int_t<> >(0);
	int_t<> symbol_worker_elapsed_us = static_cast<int_t<> >(0);
	int_t<> symbol_count = static_cast<int_t<> >(0);
	int_t<> symbol_result_ready = static_cast<int_t<> >(0);
	int_t<> symbol_declaration_sidecar_selected = static_cast<int_t<> >(0);
	int_t<> symbol_model_fallback_selected = static_cast<int_t<> >(0);
	int_t<> result_payload_copy_bytes = static_cast<int_t<> >(0);
	shared_p<SourceUnitFrontendWorkerFrontendPayloadCarrier> frontend_carrier;
	shared_p<TokenStream> tokens;
	shared_p<FrontendModel> model;
	shared_p<ProjectSymbolIndex> symbols;
	SourceUnitFrontendWorkerBuildResult();
};
}
