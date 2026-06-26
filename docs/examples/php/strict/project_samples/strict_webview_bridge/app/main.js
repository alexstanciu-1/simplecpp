const statusEl = document.getElementById("status");
const payloadEl = document.getElementById("payload");

try {
	const reply = await window.scpp.invoke("bridge.ping", {
		source: "strict-webview-bridge",
		time: new Date().toISOString()
	});
	statusEl.textContent = "Native reply received";
	payloadEl.textContent = JSON.stringify(reply, null, 2);
	document.body.dataset.bridge = "ok";
} catch (error) {
	statusEl.textContent = "Bridge reply failed";
	payloadEl.textContent = error && error.message ? error.message : String(error);
	document.body.dataset.bridge = "error";
}
