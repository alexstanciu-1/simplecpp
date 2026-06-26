#include "scpp/ui.hpp"
#include "scpp/webview.hpp"

#include <chrono>
#include <iostream>
#include <string>
#include <thread>

int main() {
	auto app_result = scpp::ui::app_create();
	if (!app_result.has_value().native_value()) {
		std::cerr << app_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto app = app_result.value();
	auto window_result = scpp::ui::window_create(app, scpp::string_t("Simple C++ WebView"), scpp::int_t(700), scpp::int_t(460));
	if (!window_result.has_value().native_value()) {
		std::cerr << window_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto window = window_result.value();
	auto view_result = scpp::webview_runtime::create(window);
	if (!view_result.has_value().native_value()) {
		std::cerr << view_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto view = view_result.value();
	auto html_result = scpp::webview_runtime::load_html(
		view,
		scpp::string_t(R"HTML(<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
	body { font-family: sans-serif; margin: 48px; background: white; color: black; }
	h1 { font-size: 32px; margin: 0 0 18px; }
	.panel { border: 3px solid #2563eb; padding: 22px; width: 560px; }
	.label { color: #334155; font-size: 14px; text-transform: uppercase; }
	.status { font-size: 24px; font-weight: 700; margin-top: 10px; color: #15803d; }
	.payload { font-family: monospace; margin-top: 14px; color: #334155; }
</style>
</head>
<body>
<h1>Simple C++ WebView</h1>
<div class="panel">
	<div class="label">WebKitGTK bridge smoke</div>
	<div id="status" class="status">Waiting for bridge reply...</div>
	<div id="payload" class="payload">invoke: bridge.ping</div>
</div>
<script>
setTimeout(async function () {
	const status = document.getElementById("status");
	const payload = document.getElementById("payload");
	try {
		const reply = await window.scpp.invoke("bridge.ping", { source: "webkitgtk-smoke" });
		status.textContent = "Bridge reply received";
		payload.textContent = JSON.stringify(reply);
		document.body.dataset.bridge = "ok";
	} catch (error) {
		status.textContent = "Bridge reply failed";
		payload.textContent = error && error.message ? error.message : String(error);
		document.body.dataset.bridge = "error";
	}
}, 500);
</script>
</body>
</html>)HTML")
	);
	if (!html_result.has_value().native_value()) {
		std::cerr << html_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	auto show_result = scpp::ui::window_show(window);
	if (!show_result.has_value().native_value()) {
		std::cerr << show_result.error()->get_message().native_value() << "\n";
		return 1;
	}

	bool replied = false;
	for (int i = 0; i < 160; ++i) {
		if (scpp::ui::app_poll(app).native_value()) {
			auto event = scpp::ui::app_next_event(app);
			if (scpp::ui::event_type(event).native_value() == "webview_message") {
				const std::string message = scpp::ui::event_text(event).native_value();
				std::cout << "webview_message: " << message << "\n";
				if (!replied && message.find("\"command\":\"bridge.ping\"") != std::string::npos) {
					auto reply_result = scpp::webview_runtime::reply_ok(
						view,
						scpp::int_t(1),
						scpp::string_t("{\"status\":\"pong\",\"transport\":\"WebKitGTK script-message\"}")
					);
					if (!reply_result.has_value().native_value()) {
						std::cerr << "webview bridge reply failed: " << reply_result.error()->get_message().native_value() << "\n";
					} else {
						std::cout << "webview_reply_ok: sent\n";
					}
					replied = true;
				}
			}
		}
		std::this_thread::sleep_for(std::chrono::milliseconds(50));
	}

	scpp::webview_runtime::close(view);
	(void) scpp::ui::window_close(window);
	scpp::ui::app_exit(app);
	return 0;
}
