# Strict WebView Bridge Sample
Doc Status: supporting

This is a manual GUI sample for the strict PHP profile.

It demonstrates:

- `runtime.modules = ["webview", "datetime"]`
- loading a local app folder with `webview_load_app(...)`
- receiving browser-side `window.scpp.invoke(...)` calls through `webview_message`
- using `webview_message_id(...)`, `webview_message_command(...)`, and `webview_message_payload_json(...)`
- replying with `webview_reply_ok(...)`

Run from this folder:

```bash
php ../../../../../../bin/scpp.php build --build-runtime
.prism/build/main
```

Unlike the other strict project samples, this one is not part of the headless stdout runner because it opens a native window.
