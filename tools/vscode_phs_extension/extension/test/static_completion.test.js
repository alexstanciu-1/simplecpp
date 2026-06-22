"use strict";

const assert = require("assert");
const Module = require("module");

const originalLoad = Module._load;
Module._load = function patchedLoad(request, parent, isMain) {
	if (request === "vscode") {
		return {
			CompletionItemKind: {
				Function: 1,
				Keyword: 2,
				Module: 3,
				TypeParameter: 4,
				Variable: 5
			},
			InsertTextFormat: {
				Snippet: 1
			},
			CompletionItem: class CompletionItem {
				constructor(label, kind) {
					this.label = label;
					this.kind = kind;
				}
			}
		};
	}
	return originalLoad(request, parent, isMain);
};

const completion = require("../src/static_completion");

function makeDocument(text, languageId) {
	const lines = text.replace(/\r\n/g, "\n").replace(/\r/g, "\n").split("\n");
	return {
		languageId,
		lineAt(line) {
			return { text: lines[line] || "" };
		},
		getText() {
			return text;
		}
	};
}

function labels(items) {
	return items.map((item) => item.label).sort();
}

function complete(text, languageId, line, character) {
	return completion.buildCompletionItems(makeDocument(text, languageId), { line, character }, languageId);
}

{
	const items = labels(complete("let text: string = \"\";\nfs.", "jss", 1, 3));
	assert(items.includes("get"), "fs. should suggest get");
	assert(items.includes("put"), "fs. should suggest put");
	assert(items.includes("exists"), "fs. should suggest exists");
}

{
	const items = labels(complete("let value: dynamic = json.", "jss", 0, 26));
	assert.deepStrictEqual(items, ["decode", "encode"]);
}

{
	const source = [
		"function add(x: int): int {",
		"\tlet total: int = x + 1;",
		"\t"
	].join("\n");
	const items = labels(complete(source, "jss", 2, 1));
	assert(items.includes("x"), "function parameter should be visible in JSS completion");
	assert(items.includes("total"), "let local should be visible in JSS completion");
	assert(items.includes("fs"), "reserved helper family should be visible in JSS completion");
	assert(items.includes("print"), "print should be visible in JSS completion");
}

{
	const source = [
		"class Box {",
		"}",
		"let box: "
	].join("\n");
	const items = labels(complete(source, "jss", 2, 9));
	assert(items.includes("Box"), "declared class should be visible in JSS type position");
	assert(items.includes("string"), "JSS scalar types should be visible in type position");
}

console.log("static completion tests ok");
