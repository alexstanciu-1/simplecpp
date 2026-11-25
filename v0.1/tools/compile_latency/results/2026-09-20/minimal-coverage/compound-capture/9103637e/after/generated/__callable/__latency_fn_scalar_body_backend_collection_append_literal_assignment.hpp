#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ScalarBodyBackendCollection;
void __latency_fn_scalar_body_backend_collection_append_literal_assignment(shared_p<ScalarBodyBackendCollection>& body, int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> targetTypeRefId, int_t<std::uint32_t> targetLocalSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::int32_t> value, const string_t& valueText, int_t<std::uint16_t> operationId);
}
