#include <scpp/lang/php.hpp>
#include "__types/FrontendModelKernelCounters.hpp"
namespace scpp {
shared_p<FrontendModelKernelCounters> __latency_counter_create() { return create<FrontendModelKernelCounters>(); }
int_t<>& __latency_counter_reserve_call_count(const shared_p<FrontendModelKernelCounters>& value) { return value->reserve_call_count; }
int_t<>& __latency_counter_append_call_count(const shared_p<FrontendModelKernelCounters>& value) { return value->append_call_count; }
int_t<>& __latency_counter_lookup_call_count(const shared_p<FrontendModelKernelCounters>& value) { return value->lookup_call_count; }
int_t<>& __latency_counter_update_call_count(const shared_p<FrontendModelKernelCounters>& value) { return value->update_call_count; }
int_t<>& __latency_counter_materializer_call_count(const shared_p<FrontendModelKernelCounters>& value) { return value->materializer_call_count; }
}
