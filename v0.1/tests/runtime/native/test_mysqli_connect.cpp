#include "test_common.hpp"

// Verifies the first mysqli vertical slice only:
// - class surface is available through the umbrella runtime header
// - constructor can be called
// - connect status fields are readable
// - close() remains safe to call
static void test_mysqli_connect_surface() {
	const scpp::string_t unreachable_host("127.0.0.1");
	const scpp::string_t username("invalid_user");
	const scpp::string_t password("invalid_pass");
	const scpp::string_t database("invalid_db");

	scpp::mysqli db(
		unreachable_host,
		username,
		password,
		database,
		scpp::int_t<>(1),
		scpp::string_t(""));

	// The exact value depends on whether the mysqli module is linked and whether
	// a connector is available at runtime. The smoke test only asserts the field
	// access path and that close() stays callable.
	(void)db.connect_errno.native_value();
	(void)db.connect_error.native_value();
	db.close();
}

int main() {
	test_mysqli_connect_surface();
	return 0;
}
