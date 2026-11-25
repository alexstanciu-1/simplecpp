#include "regex_callbacks_oracle.hpp"
int main(int argc, char**) {
	using S = scpp::string_t; using I = scpp::int_t<>;
	using F = std::function<S(scpp::vector_t<S>)>;
	using H = scpp::hash_t<F, S>;
	return regex_callbacks_oracle(
		[](const S& p, const F& f, const S& s, const I& n) {
			I count; return scpp::regex::replace_callback(p, f, s, n, count);
		},
		[](const H& h, const S& s, const I& n) {
			I count; return scpp::regex::replace_callback_array(h, s, n, count);
		}, argc > 1);
}
