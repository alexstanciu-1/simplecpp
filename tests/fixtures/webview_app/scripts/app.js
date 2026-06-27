const topbar = document.querySelector(".topbar");
const menuToggle = document.querySelector(".menu-toggle");

if (topbar && menuToggle) {
	menuToggle.addEventListener("click", () => {
		const open = !topbar.classList.contains("menu-open");
		topbar.classList.toggle("menu-open", open);
		menuToggle.setAttribute("aria-expanded", open ? "true" : "false");
	});
}

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
