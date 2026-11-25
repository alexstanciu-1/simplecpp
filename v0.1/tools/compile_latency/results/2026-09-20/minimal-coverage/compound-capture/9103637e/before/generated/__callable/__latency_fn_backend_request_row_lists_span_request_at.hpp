#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
class BackendRequestRowList;
struct BackendRequestRowSpan;
BackendRequestAuthorizationRow __latency_fn_backend_request_row_lists_span_request_at(shared_p<BackendRequestRowList> list, BackendRequestRowSpan span, int_t<> offset);
}
