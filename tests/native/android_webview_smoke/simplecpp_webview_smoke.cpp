#include "scpp/webview.hpp"

#include <jni.h>

namespace {

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

	smoke_window = scpp::shared<scpp::ui_window>();
	auto attached = scpp::webview_runtime::android_attach_activity_webview(smoke_window, vm, activity, webview);
	if (!attached.has_value().native_value()) {
		throw_illegal_state(env, "nativeAttach failed to attach Activity WebView");
		smoke_window = scpp::null;
		return;
	}

	auto created = scpp::webview_runtime::create(smoke_window);
	if (!created.has_value().native_value()) {
		scpp::webview_runtime::android_detach_activity_webview(smoke_window);
		smoke_window = scpp::null;
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
Java_dev_simplecpp_smoke_ScppWebViewSmokeActivity_nativeDetach(JNIEnv *, jobject) {
	if (smoke_view.has_value().native_value()) {
		scpp::webview_runtime::close(smoke_view);
		smoke_view = scpp::null;
	}
	if (smoke_window.has_value().native_value()) {
		scpp::webview_runtime::android_detach_activity_webview(smoke_window);
		smoke_window = scpp::null;
	}
}
