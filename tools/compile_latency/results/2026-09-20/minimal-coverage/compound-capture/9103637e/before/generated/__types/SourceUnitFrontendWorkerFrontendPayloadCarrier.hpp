#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class SourceUnitFrontendWorkerFrontendPayloadCarrier {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> token_count = static_cast<int_t<> >(0);
	int_t<> token_segment_count = static_cast<int_t<> >(0);
	int_t<> token_reserved_segment_bytes = static_cast<int_t<> >(0);
	int_t<> token_segment_slack_bytes = static_cast<int_t<> >(0);
	int_t<> frontend_node_count = static_cast<int_t<> >(0);
	int_t<> frontend_node_segment_count = static_cast<int_t<> >(0);
	int_t<> frontend_node_reserved_segment_bytes = static_cast<int_t<> >(0);
	int_t<> frontend_node_segment_slack_bytes = static_cast<int_t<> >(0);
	int_t<> declaration_count = static_cast<int_t<> >(0);
	int_t<> statement_count = static_cast<int_t<> >(0);
	int_t<> expression_count = static_cast<int_t<> >(0);
	int_t<> parser_error_count = static_cast<int_t<> >(0);
};
}
