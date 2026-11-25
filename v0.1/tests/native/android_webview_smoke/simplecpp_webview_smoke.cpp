#include "scpp/webview.hpp"

#include <jni.h>

namespace {

scpp::shared_p<scpp::ui_app> smoke_app = scpp::null;
scpp::shared_p<scpp::ui_window> smoke_window = scpp::null;
scpp::shared_p<scpp::webview> smoke_view = scpp::null;

void throw_illegal_state(JNIEnv *env, const char *message) {
	if (env == nullptr) {
		return;
	}
	jclass exception_class = env->FindClass("java/lang/IllegalStateException");
	if (exception_class != nullptr) {
		env->ThrowNew(exception_class, message);
		env->DeleteLocalRef(exception_class);
	}
}

scpp::string_t read_jstring(JNIEnv *env, jstring value) {
	if (env == nullptr || value == nullptr) {
		return scpp::string_t("");
	}
	const char *text = env->GetStringUTFChars(value, nullptr);
	if (text == nullptr) {
		return scpp::string_t("");
	}
	scpp::string_t result(text);
	env->ReleaseStringUTFChars(value, text);
	return result;
}

void enqueue_smoke_event(JNIEnv *env, const char *type, jstring message, jstring url) {
	if (!smoke_view.has_value().native_value()) {
		throw_illegal_state(env, "native event callback requires attached WebView");
		return;
	}
	auto queued = scpp::webview_runtime::enqueue_event(
		smoke_view,
		scpp::string_t(type),
		read_jstring(env, message),
		read_jstring(env, url)
	);
	if (!queued.has_value().native_value()) {
		throw_illegal_state(env, "native event callback failed to queue event");
	}
}

} // namespace

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeAttach(JNIEnv *env, jobject activity, jobject webview) {
	if (env == nullptr || activity == nullptr || webview == nullptr) {
		throw_illegal_state(env, "nativeAttach requires Activity and WebView");
		return;
	}

	JavaVM *vm = nullptr;
	if (env->GetJavaVM(&vm) != JNI_OK || vm == nullptr) {
		throw_illegal_state(env, "nativeAttach failed to resolve JavaVM");
		return;
	}

	smoke_app = scpp::shared<scpp::ui_app>();
	smoke_window = scpp::shared<scpp::ui_window>();
	smoke_window->app_handle = smoke_app;
	auto attached = scpp::webview_runtime::android_attach_activity_webview(smoke_window, vm, activity, webview);
	if (!attached.has_value().native_value()) {
		throw_illegal_state(env, "nativeAttach failed to attach Activity WebView");
		smoke_window = scpp::null;
		smoke_app = scpp::null;
		return;
	}

	auto created = scpp::webview_runtime::create(smoke_window);
	if (!created.has_value().native_value()) {
		scpp::webview_runtime::android_detach_activity_webview(smoke_window);
		smoke_window = scpp::null;
		smoke_app = scpp::null;
		throw_illegal_state(env, "nativeAttach failed to create WebView handle");
		return;
	}
	smoke_view = created.value();
}

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeLoadHtml(JNIEnv *env, jobject, jstring html) {
	if (!smoke_view.has_value().native_value()) {
		throw_illegal_state(env, "nativeLoadHtml requires attached WebView");
		return;
	}
	if (env == nullptr || html == nullptr) {
		throw_illegal_state(env, "nativeLoadHtml requires HTML text");
		return;
	}

	const char *text = env->GetStringUTFChars(html, nullptr);
	if (text == nullptr) {
		throw_illegal_state(env, "nativeLoadHtml failed to read HTML text");
		return;
	}
	auto loaded = scpp::webview_runtime::load_html(smoke_view, scpp::string_t(text));
	env->ReleaseStringUTFChars(html, text);
	if (!loaded.has_value().native_value()) {
		throw_illegal_state(env, "nativeLoadHtml failed");
	}
}

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeOnNavigationStarted(JNIEnv *env, jobject, jstring url) {
	enqueue_smoke_event(env, "webview_navigation_started", nullptr, url);
}

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeOnNavigationFinished(JNIEnv *env, jobject, jstring url) {
	enqueue_smoke_event(env, "webview_navigation_finished", nullptr, url);
}

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeOnLoadFailed(JNIEnv *env, jobject, jstring message, jstring url) {
	enqueue_smoke_event(env, "webview_load_failed", message, url);
}

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeOnMessage(JNIEnv *env, jobject, jstring message, jstring url) {
	enqueue_smoke_event(env, "webview_message", message, url);
}

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeAssertEvents(JNIEnv *env, jobject) {
	if (!smoke_app.has_value().native_value()) {
		throw_illegal_state(env, "nativeAssertEvents requires app queue");
		return;
	}

	bool saw_ready = false;
	bool saw_navigation_finished = false;
	bool saw_message = false;
	while (!smoke_app->pending_events.empty()) {
		auto event = smoke_app->pending_events.front();
		smoke_app->pending_events.pop_front();
		if (!event.has_value().native_value()) {
			continue;
		}
		const auto type = event->type.native_value();
		if (type == "webview_ready") {
			saw_ready = true;
		}
		if (type == "webview_navigation_finished") {
			saw_navigation_finished = true;
		}
		if (type == "webview_message" && event->message.native_value() == "android-ready") {
			saw_message = true;
		}
	}

	if (!saw_ready) {
		throw_illegal_state(env, "nativeAssertEvents did not receive webview_ready");
		return;
	}
	if (!saw_navigation_finished) {
		throw_illegal_state(env, "nativeAssertEvents did not receive webview_navigation_finished");
		return;
	}
	if (!saw_message) {
		throw_illegal_state(env, "nativeAssertEvents did not receive webview_message");
	}
}

extern "C" JNIEXPORT void JNICALL
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeDetach(JNIEnv *, jobject) {
	if (smoke_view.has_value().native_value()) {
		scpp::webview_runtime::close(smoke_view);
		smoke_view = scpp::null;
	}
	if (smoke_window.has_value().native_value()) {
		scpp::webview_runtime::android_detach_activity_webview(smoke_window);
		smoke_window = scpp::null;
	}
	smoke_app = scpp::null;
}
