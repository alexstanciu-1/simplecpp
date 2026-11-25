"use strict";

const fs = require("fs");
const path = require("path");

function getDebugRoot(projectRoot) {
	return path.join(projectRoot, ".prism", "debug");
}

function getDebugIndexPath(projectRoot) {
	return path.join(getDebugRoot(projectRoot), "index.json");
}

function readDebugIndex(projectRoot) {
	const indexPath = getDebugIndexPath(projectRoot);
	if (!fs.existsSync(indexPath)) {
		return null;
	}
	try {
		const parsed = JSON.parse(fs.readFileSync(indexPath, "utf8"));
		return parsed && typeof parsed === "object" ? parsed : null;
	} catch {
		return null;
	}
}

function listDebugSlots(projectRoot) {
	const index = readDebugIndex(projectRoot);
	if (!index || !Array.isArray(index.slots)) {
		return [];
	}
	return index.slots
		.filter((slot) => slot && typeof slot === "object")
		.map((slot) => normalizeDebugSlot(projectRoot, slot));
}

function getActiveDebugSlot(projectRoot) {
	const index = readDebugIndex(projectRoot);
	if (!index || !Array.isArray(index.slots) || typeof index.active_slot !== "string" || index.active_slot === "") {
		return null;
	}
	const slot = index.slots.find((entry) => entry && typeof entry === "object" && entry.name === index.active_slot);
	return slot ? normalizeDebugSlot(projectRoot, slot) : null;
}

function getLatestDebugSlot(projectRoot) {
	const slots = listDebugSlots(projectRoot);
	if (slots.length === 0) {
		return null;
	}
	const withSession = slots.filter((slot) => slot.session && typeof slot.session === "object");
	if (withSession.length === 0) {
		return null;
	}
	withSession.sort((left, right) => {
		const leftTs = parseTimestamp(left.session.last_used_at) || 0;
		const rightTs = parseTimestamp(right.session.last_used_at) || 0;
		return rightTs - leftTs;
	});
	return withSession[0];
}

function readDebugEventsAggregate(projectRoot, slot) {
	if (!slot || !slot.eventsPath) {
		return null;
	}
	try {
		return JSON.parse(fs.readFileSync(slot.eventsPath, "utf8"));
	} catch {
		return null;
	}
}

function readDebugSourceManifest(projectRoot, slot) {
	if (!slot || !slot.sourceManifestPath) {
		return null;
	}
	try {
		const parsed = JSON.parse(fs.readFileSync(slot.sourceManifestPath, "utf8"));
		return parsed && typeof parsed === "object" ? normalizeDebugSourceManifest(parsed) : null;
	} catch {
		return null;
	}
}

function getLatestRewrittenSourceEntry(projectRoot, slot) {
	const manifest = readDebugSourceManifest(projectRoot, slot);
	if (!manifest || manifest.files.length === 0) {
		return null;
	}
	return manifest.files[0];
}

function normalizeDebugSlot(projectRoot, slot) {
	const root = typeof slot.root === "string" ? path.join(projectRoot, slot.root) : null;
	return {
		name: typeof slot.name === "string" ? slot.name : "",
		root,
		empty: Boolean(slot.empty),
		session: slot.session && typeof slot.session === "object" ? slot.session : null,
		eventsPath: typeof slot.events_path === "string" ? path.join(projectRoot, slot.events_path) : null,
		planPath: typeof slot.plan_path === "string" ? path.join(projectRoot, slot.plan_path) : null,
		sourceManifestPath: typeof slot.source_manifest_path === "string" ? path.join(projectRoot, slot.source_manifest_path) : null
	};
}

function normalizeDebugSourceManifest(manifest) {
	const files = Array.isArray(manifest.files) ? manifest.files : [];
	return {
		version: typeof manifest.version === "number" ? manifest.version : 1,
		files: files
			.filter((entry) => entry && typeof entry === "object")
			.map((entry) => ({
				logicalSource: typeof entry.logical_source === "string" ? entry.logical_source : null,
				rewrittenSource: typeof entry.rewritten_source === "string" ? entry.rewritten_source : null,
				lineMap: typeof entry.line_map === "string" ? entry.line_map : null,
				relativePath: typeof entry.relative_path === "string" ? entry.relative_path : null
			}))
	};
}

function parseTimestamp(value) {
	if (typeof value !== "string" || value.trim() === "") {
		return null;
	}
	const parsed = Date.parse(value);
	return Number.isFinite(parsed) ? parsed : null;
}

module.exports = {
	getDebugRoot,
	getDebugIndexPath,
	readDebugIndex,
	listDebugSlots,
	getActiveDebugSlot,
	getLatestDebugSlot,
	readDebugEventsAggregate,
	readDebugSourceManifest,
	getLatestRewrittenSourceEntry
};
