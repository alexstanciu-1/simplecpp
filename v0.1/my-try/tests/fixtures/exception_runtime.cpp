#include "lang/php/php_exceptions.hpp"

static int destroyed = 0;
struct legacy_failure final : scpp::Exception {
	legacy_failure() : scpp::Exception(scpp::string_t(std::string("a\0b", 3))) {}
	~legacy_failure() override { ++destroyed; }
	int code = 7;
};
struct unrelated_failure final : scpp::Exception {};

int main() {
	auto original = scpp::shared_p<legacy_failure>(std::make_shared<legacy_failure>());
	try {
		try { throw scpp::php::make_thrown(original); }
		catch (const scpp::php::thrown_object& thrown) {
			auto legacy = scpp::php::catch_as<scpp::Exception>(thrown);
			auto common = scpp::php::catch_as<scpp::php::exception_payload>(thrown);
			if (!legacy || !common || scpp::php::catch_as<unrelated_failure>(thrown)) { return 1; }
			if (common->message_string().native_value() != std::string("a\0b", 3)) { return 2; }
			if (scpp::php::catch_as<legacy_failure>(thrown).native_value().get() != original.native_value().get()) { return 3; }
			throw scpp::php::make_thrown(legacy);
		}
	} catch (const scpp::php::thrown_object& thrown) {
		scpp::php::catch_as<legacy_failure>(thrown)->code = 19;
	}
	if (original->code != 19 || destroyed != 0) { return 4; }
	original = scpp::shared_p<legacy_failure>{};
	if (destroyed != 1) { return 5; }
	auto factory = scpp::Exception::make(scpp::string_t("legacy"));
	if (factory->message_string().native_value() != "legacy") { return 6; }
	return 0;
}
