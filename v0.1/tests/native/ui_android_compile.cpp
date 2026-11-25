#include "scpp/ui.hpp"

int main() {
	auto result = scpp::ui::app_create();
	return result.has_value().native_value() ? 0 : 0;
}
