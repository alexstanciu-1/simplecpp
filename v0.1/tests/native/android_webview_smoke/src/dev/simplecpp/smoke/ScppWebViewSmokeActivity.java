package dev.simplecpp.smoke;

import android.app.Activity;
import android.graphics.Bitmap;
import android.os.Bundle;
import android.webkit.JavascriptInterface;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

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
		webView.setWebViewClient(new SmokeWebViewClient());
		webView.addJavascriptInterface(new SmokeJavascriptBridge(), "SimpleCpp");
		setContentView(webView);

		nativeAttach(webView);
		nativeLoadHtml("<!doctype html><html><body><h1>Simple C++ WebView</h1><script>window.SimpleCpp.postMessage('android-ready');</script></body></html>");
		webView.postDelayed(new Runnable() {
			@Override
			public void run() {
				nativeAssertEvents();
			}
		}, 2500);
	}

	@Override
	protected void onDestroy() {
		nativeDetach();
		webView = null;
		super.onDestroy();
	}

	private native void nativeAttach(WebView view);
	private native void nativeLoadHtml(String html);
	private native void nativeOnNavigationStarted(String url);
	private native void nativeOnNavigationFinished(String url);
	private native void nativeOnLoadFailed(String message, String url);
	private native void nativeOnMessage(String message, String url);
	private native void nativeAssertEvents();
	private native void nativeDetach();

	private final class SmokeWebViewClient extends WebViewClient {
		@Override
		public void onPageStarted(WebView view, String url, Bitmap favicon) {
			nativeOnNavigationStarted(url == null ? "" : url);
		}

		@Override
		public void onPageFinished(WebView view, String url) {
			nativeOnNavigationFinished(url == null ? "" : url);
		}

		@Override
		public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
			String url = request == null || request.getUrl() == null ? "" : request.getUrl().toString();
			String message = error == null || error.getDescription() == null ? "" : error.getDescription().toString();
			nativeOnLoadFailed(message, url);
		}
	}

	private final class SmokeJavascriptBridge {
		@JavascriptInterface
		public void postMessage(final String message) {
			runOnUiThread(new Runnable() {
				@Override
				public void run() {
					nativeOnMessage(message == null ? "" : message, webView == null || webView.getUrl() == null ? "" : webView.getUrl());
				}
			});
		}
	}
}
