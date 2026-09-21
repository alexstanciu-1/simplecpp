#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestRowList;
struct BackendRequestRowSpan;
BackendRequestRowSpan __latency_fn_backend_request_row_lists_next_span(shared_p<BackendRequestRowList> list, BackendRequestRowSpan span);
}
