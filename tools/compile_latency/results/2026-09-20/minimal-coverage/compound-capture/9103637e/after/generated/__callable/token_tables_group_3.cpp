#include <scpp/lang/php.hpp>
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_token_tables_stream_debug_string.hpp"
#include "__callable/__latency_fn_token_tables_substrate_name.hpp"
namespace scpp { extern const int __latency_lines_token_tables[]; }
namespace scpp {
string_t __latency_fn_token_tables_stream_debug_string(shared_p<TokenStream> stream) {
	SCPP_CALL_DEPTH_GUARD("token_tables::stream_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_tables.phs", __latency_lines_token_tables[44]);
	return (string_t("token_stream:") + cast<string_t>(cast<int_t<>>(stream->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(stream->source_buffer_id)) + string_t(":") + cast<string_t>(__latency_fn_token_tables_substrate_name(stream->substrate_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(stream->token_count)));
}

}
