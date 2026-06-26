package dev.simplecpp.smoke;

import android.app.Activity;
import android.os.Bundle;
import android.webkit.WebSettings;
import android.webkit.WebView;

public final class ScppWebViewSmokeActivity extends Activity {
	private WebView webView;

	static {
		System.loadLibrary("simplecpp_webview_smoke");
	}

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		webView = new WebView(this);
		WebSettings settings = webView.getSettings();
		settings.setJavaScriptEnabled(true);
		setContentView(webView);

		nativeAttach(webView);
		nativeLoadHtml("<!doctype html><html><body><h1>Simple C++ WebView</h1></body></html>");
	}

	@Override
	protected void onDestroy() {
		nativeDetach();
		webView = null;
		super.onDestroy();
	}

	private native void nativeAttach(WebView view);
	private native void nativeLoadHtml(String html);
	private native void nativeDetach();
}
