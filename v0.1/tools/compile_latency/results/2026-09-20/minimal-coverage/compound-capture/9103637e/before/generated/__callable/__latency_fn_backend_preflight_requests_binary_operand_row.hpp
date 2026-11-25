#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendBinaryOperandRow;
struct FrontendLiteralPayloadRow;
struct FrontendNodeRow;
BackendBinaryOperandRow __latency_fn_backend_preflight_requests_binary_operand_row(int_t<std::uint32_t> ownerRowId, FrontendNodeRow leftNode, FrontendNodeRow rightNode, FrontendLiteralPayloadRow leftLiteral, FrontendLiteralPayloadRow rightLiteral);
}
