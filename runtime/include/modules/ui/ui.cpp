#include "modules/ui/ui.hpp"

#if SCPP_HAS_UI

#if defined(SCPP_UI_BACKEND_GTK) && SCPP_UI_BACKEND_GTK
#include <gtk/gtk.h>
#endif

#include <string>

namespace scpp::ui {

#if defined(SCPP_UI_BACKEND_GTK) && SCPP_UI_BACKEND_GTK

namespace {

struct callback_state final {
	shared_p<app> owner;
	shared_p<window> target;
};

void destroy_callback_state(gpointer data) {
	delete static_cast<callback_state *>(data);
}

gboolean handle_window_delete(GtkWidget *, GdkEvent *, gpointer data) {
	auto *state = static_cast<callback_state *>(data);
	if (state == nullptr || !state->owner.has_value().native_value()) {
		return TRUE;
	}

	auto event_value = shared<event>();
	event_value->type = string_t("window_close");
	event_value->window_handle = state->target;
	state->owner->pending_events.push_back(event_value);
	return TRUE;
}

void handle_window_destroy(GtkWidget *, gpointer data) {
	auto *state = static_cast<callback_state *>(data);
	if (state == nullptr || !state->target.has_value().native_value()) {
		return;
	}
	state->target->closed = bool_t(true);
	state->target->visible = bool_t(false);
	state->target->native_handle = nullptr;
}

[[nodiscard]] result<shared_p<app>> require_app(const shared_p<app> &owner, const char *function_name) {
	if (!owner.has_value().native_value() || owner.get() == nullptr) {
		return error_t(string_t(std::string(function_name) + " requires a valid ui_app"));
	}
	return owner;
}

[[nodiscard]] result<shared_p<window>> require_window(const shared_p<window> &target, const char *function_name) {
	if (!target.has_value().native_value() || target.get() == nullptr || target->native_handle == nullptr || target->closed.native_value()) {
		return error_t(string_t(std::string(function_name) + " requires an open ui_window"));
	}
	return target;
}

} // namespace

result<shared_p<app>> app_create() {
	int argc = 0;
	char **argv = nullptr;
	if (!gtk_init_check(&argc, &argv)) {
		return error_t(string_t("ui_app_create(): GTK initialization failed; check DISPLAY/Wayland session availability"));
	}

	auto owner = shared<app>();
	owner->backend = string_t("gtk");
	return owner;
}

result<shared_p<window>> window_create(const shared_p<app> &owner, const string_t &title, const int_t &width, const int_t &height) {
	auto checked_owner = require_app(owner, "ui_window_create()");
	if (!checked_owner.has_value().native_value()) {
		return *checked_owner.error();
	}
	if (width.native_value() <= 0 || height.native_value() <= 0) {
		return error_t(string_t("ui_window_create(): width and height must be positive"));
	}

	GtkWidget *native = gtk_window_new(GTK_WINDOW_TOPLEVEL);
	if (native == nullptr) {
		return error_t(string_t("ui_window_create(): GTK failed to create a native window"));
	}

	auto target = shared<window>();
	target->title = title;
	target->width = width;
	target->height = height;
	target->native_handle = native;

	gtk_window_set_title(GTK_WINDOW(native), title.native_value().c_str());
	gtk_window_set_default_size(GTK_WINDOW(native), static_cast<gint>(width.native_value()), static_cast<gint>(height.native_value()));

	auto *delete_state = new callback_state{owner, target};
	g_signal_connect_data(
		G_OBJECT(native),
		"delete-event",
		G_CALLBACK(handle_window_delete),
		delete_state,
		destroy_callback_state,
		static_cast<GConnectFlags>(0)
	);

	auto *destroy_state = new callback_state{owner, target};
	g_signal_connect_data(
		G_OBJECT(native),
		"destroy",
		G_CALLBACK(handle_window_destroy),
		destroy_state,
		destroy_callback_state,
		static_cast<GConnectFlags>(0)
	);

	return target;
}

result<bool_t> window_show(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_show()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	gtk_widget_show_all(GTK_WIDGET(target->native_handle));
	target->visible = bool_t(true);
	return bool_t(true);
}

result<bool_t> window_close(const shared_p<window> &target) {
	auto checked = require_window(target, "ui_window_close()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	gtk_widget_destroy(GTK_WIDGET(target->native_handle));
	target->closed = bool_t(true);
	target->visible = bool_t(false);
	target->native_handle = nullptr;
	return bool_t(true);
}

bool_t app_poll(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->exit_requested.native_value()) {
		return bool_t(false);
	}
	while (gtk_events_pending() != 0) {
		gtk_main_iteration_do(FALSE);
	}
	return bool_t(!owner->pending_events.empty());
}

shared_p<event> app_next_event(const shared_p<app> &owner) {
	if (!owner.has_value().native_value() || owner.get() == nullptr || owner->pending_events.empty()) {
		return null;
	}
	auto value = owner->pending_events.front();
	owner->pending_events.pop_front();
	return value;
}

void app_exit(const shared_p<app> &owner) {
	if (owner.has_value().native_value() && owner.get() != nullptr) {
		owner->exit_requested = bool_t(true);
	}
}

#else

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

#endif

} // namespace scpp::ui

#endif
