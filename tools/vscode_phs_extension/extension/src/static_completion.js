"use strict";

const fs = require("fs");
const path = require("path");

const KEYWORDS = [
	"if",
	"else",
	"elseif",
	"switch",
	"case",
	"default",
	"for",
	"foreach",
	"while",
	"do",
	"break",
	"continue",
	"return",
	"try",
	"catch",
	"finally",
	"throw",
	"function",
	"class",
	"interface",
	"trait",
	"extends",
	"implements",
	"public",
	"protected",
	"private",
	"static",
	"abstract",
	"final",
	"const",
	"new",
	"use"
];

const TYPES = [
	"void",
	"bool",
	"int",
	"float",
	"string",
	"mixed",
	"dynamic",
	"vector",
	"hash",
	"callable",
	"nullable",
	"object"
];

function registerStaticCompletion(context) {
	const vscode = require("vscode");
	const provider = vscode.languages.registerCompletionItemProvider(
		{ language: "phs" },
		{
			provideCompletionItems(document, position) {
				const items = buildCompletionItems(document, position);
				return items;
			}
		},
		"$",
		">",
		":",
		" ",
		"("
	);

	context.subscriptions.push(provider);
}

function buildCompletionItems(document, position) {
	const context = analyzeContext(document, position);
	const items = [];

	if (context.variablePrefix) {
		for (const variableName of collectVisibleVariables(document, position)) {
			items.push(variableItem(variableName));
		}
		return items;
	}

	if (context.memberAccess) {
		return items;
	}

	if (context.typePosition) {
		for (const typeName of TYPES) {
			items.push(typeItem(typeName));
		}
		return items;
	}

	if (context.afterNew) {
		return collectDeclaredTypeNames(document).map(typeItem);
	}

	for (const keyword of KEYWORDS) {
		items.push(keywordItem(keyword));
	}
	for (const typeName of TYPES) {
		items.push(typeItem(typeName));
	}
	for (const builtinName of loadStrictBuiltinNames()) {
		items.push(functionItem(builtinName));
	}
	for (const variableName of collectVisibleVariables(document, position)) {
		items.push(variableItem(variableName));
	}

	return items;
}

function analyzeContext(document, position) {
	const linePrefix = document.lineAt(position.line).text.slice(0, position.character);

	return {
		variablePrefix: /\$[A-Za-z0-9_]*$/.test(linePrefix),
		memberAccess: /->\s*[A-Za-z0-9_]*$/.test(linePrefix),
		typePosition: /(?:\:\s*|\/\*\*\s*|@\w+\s+|(?:^|\s)(?:function|new|extends|implements)\s+|<\s*)[A-Za-z_0-9\\\\]*$/.test(linePrefix),
		afterNew: /\bnew\s+[A-Za-z_0-9\\\\]*$/.test(linePrefix)
	};
}

function collectVisibleVariables(document, position) {
	const maxLine = position.line;
	const names = new Set();

	for (let lineNumber = 0; lineNumber <= maxLine; lineNumber += 1) {
		const lineText =
			lineNumber === maxLine
				? document.lineAt(lineNumber).text.slice(0, position.character)
				: document.lineAt(lineNumber).text;

		for (const match of lineText.matchAll(/\$([A-Za-z_][A-Za-z0-9_]*)/g)) {
			names.add(`$${match[1]}`);
		}
	}

	return Array.from(names).sort();
}

function collectDeclaredTypeNames(document) {
	const names = new Set();
	const fullText = document.getText();

	for (const match of fullText.matchAll(/\b(?:class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_\\]*)/g)) {
		names.add(match[1]);
	}

	return Array.from(names).sort();
}

function keywordItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Keyword);
	item.detail = "PHS keyword";
	return item;
}

function typeItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.TypeParameter);
	item.detail = "PHS type";
	return item;
}

function functionItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Function);
	item.detail = "Strict builtin";
	item.insertText = `${label}($1)`;
	item.insertTextFormat = vscode.InsertTextFormat.Snippet;
	return item;
}

function variableItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Variable);
	item.detail = "Visible variable";
	item.insertText = label;
	return item;
}

let cachedBuiltinNames = null;

function loadStrictBuiltinNames() {
	if (cachedBuiltinNames !== null) {
		return cachedBuiltinNames;
	}

	const metadataPath = path.resolve(
		__dirname,
		"..",
		"..",
		"..",
		"..",
		"generators",
		"php",
		"specs",
		"php_runtime_symbols_strict.json"
	);

	try {
		const raw = fs.readFileSync(metadataPath, "utf8");
		const parsed = JSON.parse(raw);
		const targets = parsed.php_runtime_symbol_targets || {};
		cachedBuiltinNames = Object.keys(targets).sort();
		return cachedBuiltinNames;
	} catch (_error) {
		cachedBuiltinNames = [
			"take",
			"dbg",
			"json_decode",
			"json_encode",
			"fs_get",
			"fs_put",
			"io_open",
			"dt_now",
			"regex_match",
			"strlen"
		];
		return cachedBuiltinNames;
	}
}

module.exports = {
	registerStaticCompletion,
	loadStrictBuiltinNames,
	buildCompletionItems,
	analyzeContext,
	collectVisibleVariables,
	collectDeclaredTypeNames
};
