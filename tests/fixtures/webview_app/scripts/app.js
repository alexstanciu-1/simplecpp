setTimeout(async () => {
	const status = document.getElementById("status");
	const payload = document.getElementById("payload");

	try {
		const reply = await window.scpp.invoke("bridge.ping", { source: "folder-app-smoke" });
		status.textContent = "Bridge reply received";
		payload.textContent = JSON.stringify(reply);
		document.body.dataset.bridge = "ok";
	} catch (error) {
		status.textContent = "Bridge reply failed";
		payload.textContent = error && error.message ? error.message : String(error);
		document.body.dataset.bridge = "error";
	}
}, 500);
