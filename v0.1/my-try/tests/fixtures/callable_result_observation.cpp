#include <scpp/lang/php.hpp>
#include <functional>

int main() {
	using callback = std::function<scpp::int_t<int64_t>()>;
	int calls = 0;
	callback present = [&]() { ++calls; return scpp::int_t<int64_t>(7); };
	callback absent;
	scpp::result<callback> value(present);
	scpp::result<callback> empty_value(absent);
	scpp::result<callback> failure(scpp::error);
	if (!scpp::condition_truthy(value).native_value()) { return 1; }
	if (scpp::condition_truthy(empty_value).native_value()) { return 2; }
	if (scpp::condition_truthy(failure).native_value()) { return 3; }
	if (!scpp::isset(empty_value).native_value()) { return 4; }
	if (scpp::isset(failure).native_value()) { return 5; }
	if (calls != 0) { return 6; }
	callback output = present;
	scpp::error_t error;
	if (scpp::php::take(output, error, failure).native_value()) { return 7; }
	if (!error.has_error().native_value() || !output) { return 8; }
	if (!scpp::php::take(output, error, empty_value).native_value()) { return 9; }
	if (error.has_error().native_value() || output) { return 10; }
	return 0;
}
