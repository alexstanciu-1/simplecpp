#include <scpp/runtime.hpp>

// Coverage marker: covered primary RT-BOOL-07.
// Expected compile failure:
// RT-BOOL-07 / RT-STR-06 implicit string_t -> bool_t must not exist.

int main() {
	using namespace scpp;
	bool_t value = string_t("true");
	(void)value;
	return 0;
}
