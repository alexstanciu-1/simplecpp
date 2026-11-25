#include "modules/source/source.hpp"
#include "modules/strings/strings.hpp"
#include <cassert>
#include <utility>

using scpp::source::source_buffer;
using scpp::source::byte_span;

template<class F> void rejects(F action) {
	bool threw = false;
	try { action(); } catch (const scpp::runtime_error &) { threw = true; }
	assert(threw);
}
void invalid(const byte_span &span) {
	rejects([&] { (void)span.byte_size(); });
	rejects([&] { (void)span.byte_at(0); });
	rejects([&] { (void)span.to_string(); });
	rejects([&] { (void)scpp::source::hash_bytes(span); });
}
int main() {
	invalid(byte_span{});
	byte_span expired;
	{ source_buffer buffer("abc"); expired = buffer.span(0, 3); }
	invalid(expired);
	source_buffer a("abc"); auto span = a.span(1, 2); auto span_copy = span;
	source_buffer b(a); auto b_span = b.span(0, 3);
	assert(a.release().native_value() == "abc"); invalid(span); invalid(span_copy);
	assert(b_span.to_string().native_value() == "abc");
	source_buffer c(std::move(b)); invalid(b_span);
	assert(c.slice(0, 3).native_value() == "abc" && b.empty());
	auto empty_span = b.span(0, 0); assert(empty_span.byte_size() == 0);
	b = c; invalid(empty_span);
	auto c_span = c.span(0, 3); auto destination = b.span(0, 3);
	b = std::move(c); invalid(c_span); invalid(destination);
	assert(b.slice(0, 3).native_value() == "abc" && c.empty());
	auto kept = b.span(0, 3); b = b; assert(kept.byte_size() == 3);
	scpp::str::text_builder builder;
	builder.append_byte_span(kept); (void)b.release(); invalid(kept);
	assert(builder.to_string().native_value() == "abc");
	rejects([&] { (void)b.span(1, 0); });
	rejects([&] { (void)b.span(0, static_cast<std::size_t>(-1)); });
	source_buffer binary(std::string("\0\xff", 2));
	auto bytes = binary.span(0, 2); assert(bytes.byte_at(0) == 0 && bytes.byte_at(1) == 255);
	rejects([&] { (void)bytes.byte_at(2); });
	assert(scpp::stable_hash::string_hex(scpp::string_t("abc")).native_value() == "e71fa2190541574b");
	assert(scpp::stable_hash::versioned_key(scpp::string_t("abc")).native_value() == "stable_hash_u64:fnv1a64:v2:e71fa2190541574b");
}
