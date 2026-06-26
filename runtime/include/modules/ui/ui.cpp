#include "modules/ui/ui.hpp"

#if SCPP_HAS_UI

namespace scpp::ui {

result<shared_p<app>> app_create() {
	return error_t(string_t("ui_app_create(): native ui backend is not implemented yet for this platform"));
}

result<shared_p<window>> window_create(const shared_p<app> &, const string_t &, const int_t &, const int_t &) {
	return error_t(string_t("ui_window_create(): native ui backend is not implemented yet for this platform"));
}

result<bool_t> window_show(const shared_p<window> &) {
	return error_t(string_t("ui_window_show(): native ui backend is not implemented yet for this platform"));
}

result<bool_t> window_close(const shared_p<window> &) {
	return error_t(string_t("ui_window_close(): native ui backend is not implemented yet for this platform"));
}

bool_t app_poll(const shared_p<app> &) {
	return bool_t(false);
}

shared_p<event> app_next_event(const shared_p<app> &) {
	return null;
}

void app_exit(const shared_p<app> &owner) {
	if (owner.has_value().native_value() && owner.get() != nullptr) {
		owner->exit_requested = bool_t(true);
	}
}

} // namespace scpp::ui

#endif
