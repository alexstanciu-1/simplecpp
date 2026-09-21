#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendStringLiteralOperandRow;
struct FrontendLiteralPayloadRow;
struct FrontendNodeRow;
shared_p<BackendStringLiteralOperandRow> __latency_fn_backend_preflight_requests_string_literal_operand_row(int_t<std::uint32_t> ownerRowId, FrontendNodeRow literalNode, FrontendLiteralPayloadRow literal, const string_t& literalText);
}
