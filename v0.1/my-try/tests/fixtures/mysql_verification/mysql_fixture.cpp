#include "scpp/lang/php.hpp"
#include "lang/php/support/php_mysqli.hpp"
#include <iostream>
#include <stdexcept>

#if !defined(SCPP_HAS_MYSQLI) || !SCPP_HAS_MYSQLI
#error "The integration proof requires a real enabled MySQL connector"
#endif

static void require(bool value, const char *message) {
	if (!value) throw std::runtime_error(message);
}

static void rows(scpp::mysqli &database, bool associative) {
	auto query = database.query(scpp::string_t("SELECT name FROM sample ORDER BY id"));
	require(query.has_value().native_value(), "SELECT did not return a result payload");
	auto result = query.value();
	for (const char *expected : {"first", "second"}) {
		auto row = associative ? result->fetch_assoc() : result->fetch_row();
		require(!row.is_null().native_value(), "Row exhausted early");
		const auto &value = associative ? row.get_table().at(scpp::string_t("name"))
			: row.get_table().at(scpp::int_t<>(0));
		require(value.get_string().native_value() == expected, "Row order/value mismatch");
	}
	for (int repeat = 0; repeat < 2; ++repeat) {
		auto end = associative ? result->fetch_assoc() : result->fetch_row();
		require(end.is_null().native_value(), "Exhaustion must return null repeatedly");
	}
}

int main(int argc, char **argv) {
	try {
		require(argc == 2, "Expected private socket path");
		scpp::mysqli database(scpp::string_t("localhost"), scpp::string_t("scpp_fixture"),
			scpp::string_t("fixture-only"), scpp::string_t("scpp_verification"),
			scpp::int_t<>(0), scpp::string_t(argv[1]));
		require(database.connect_errno.native_value() == 0, "Private socket connection failed");
		require(database.connect_error.native_value().empty(), "Success retained a connection error");
		rows(database, true);
		rows(database, false);
		database.close();
		database.close();
		scpp::mysqli refused(scpp::string_t("localhost"), scpp::string_t("scpp_fixture"),
			scpp::string_t("wrong-fixture-password"), scpp::string_t("scpp_verification"),
			scpp::int_t<>(0), scpp::string_t(argv[1]));
		require(refused.connect_errno.native_value() != 0, "Invalid credentials unexpectedly succeeded");
		require(!refused.connect_error.native_value().empty(), "Connection failure lacks diagnostic");
		refused.close();
		std::cout << "connected; ordered assoc/row; repeated null exhaustion; close; rejected credentials\n";
		return 0;
	} catch (const std::exception &error) {
		std::cerr << error.what() << '\n';
		return 1;
	}
}
