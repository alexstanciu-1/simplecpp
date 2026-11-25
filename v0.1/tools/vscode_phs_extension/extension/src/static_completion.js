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

const JSS_KEYWORDS = [
	"let",
	"if",
	"else",
	"switch",
	"case",
	"default",
	"for",
	"of",
	"while",
	"do",
	"break",
	"continue",
	"return",
	"function",
	"class",
	"extends",
	"public",
	"static",
	"const",
	"new",
	"use",
	"namespace",
	"delete",
	"async",
	"await"
];

const TYPES = [
	"void",
	"bool",
	"int",
	"float",
	"string",
	"mixed",
	"dynamic",
	"error",
	"vector",
	"hash",
	"callable",
	"nullable",
	"object"
];

const JSS_HELPERS = {
	fs: [
		"get",
		"put",
		"mkdir",
		"scan",
		"size",
		"mtime",
		"touch",
		"rmdir",
		"remove",
		"copy",
		"rename",
		"realpath",
		"exists",
		"is_dir",
		"is_file",
		"is_link",
		"basename",
		"dirname"
	],
	io: [
		"open",
		"seek",
		"tell",
		"read_line",
		"read",
		"write",
		"rewind",
		"flush",
		"close",
		"eof"
	],
	json: [
		"decode",
		"encode"
	],
	dt: [
		"parse",
		"format",
		"parse_iso_utc",
		"format_iso_utc",
		"format_now"
	]
};

function registerStaticCompletion(context) {
	const vscode = require("vscode");
	const phsProvider = vscode.languages.registerCompletionItemProvider(
		{ language: "phs" },
		{
			provideCompletionItems(document, position) {
				const items = buildCompletionItems(document, position, "phs");
				return items;
			}
		},
		"$",
		">",
		":",
		" ",
		"("
	);

	const jssProvider = vscode.languages.registerCompletionItemProvider(
		{ language: "jss" },
		{
			provideCompletionItems(document, position) {
				return buildCompletionItems(document, position, "jss");
			}
		},
		".",
		":",
		" ",
		"("
	);

	context.subscriptions.push(phsProvider, jssProvider);
}

function buildCompletionItems(document, position, languageId) {
	if (languageId === "jss" || document.languageId === "jss") {
		return buildJssCompletionItems(document, position);
	}

	const context = analyzeContext(document, position, "phs");
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

function buildJssCompletionItems(document, position) {
	const context = analyzeContext(document, position, "jss");
	const items = [];

	if (context.helperFamily !== null) {
		return (JSS_HELPERS[context.helperFamily] || []).map((name) => helperMemberItem(context.helperFamily, name));
	}

	if (context.memberAccess) {
		return items;
	}

	if (context.typePosition) {
		for (const typeName of TYPES) {
			items.push(jssTypeItem(typeName));
		}
		for (const typeName of collectDeclaredTypeNames(document)) {
			items.push(jssTypeItem(typeName));
		}
		return items;
	}

	if (context.afterNew) {
		return collectDeclaredTypeNames(document).map(jssTypeItem);
	}

	for (const keyword of JSS_KEYWORDS) {
		items.push(jssKeywordItem(keyword));
	}
	for (const typeName of TYPES) {
		items.push(jssTypeItem(typeName));
	}
	for (const helperFamily of Object.keys(JSS_HELPERS).sort()) {
		items.push(helperFamilyItem(helperFamily));
	}
	items.push(functionItem("take"));
	items.push(functionItem("print"));
	for (const variableName of collectVisibleJssNames(document, position)) {
		items.push(variableItem(variableName));
	}

	return items;
}

function analyzeContext(document, position, languageId) {
	const linePrefix = document.lineAt(position.line).text.slice(0, position.character);
	const jssHelperMatch = languageId === "jss" ? linePrefix.match(/\b(fs|io|json|dt)\.\s*[A-Za-z0-9_]*$/) : null;

	return {
		variablePrefix: /\$[A-Za-z0-9_]*$/.test(linePrefix),
		memberAccess: languageId === "jss" ? /\.\s*[A-Za-z0-9_]*$/.test(linePrefix) : /->\s*[A-Za-z0-9_]*$/.test(linePrefix),
		typePosition: languageId === "jss"
			? /(?:\:\s*|(?:^|\s)(?:function|new|extends)\s+|<\s*)[A-Za-z_0-9\\\\?]*$/.test(linePrefix)
			: /(?:\:\s*|\/\*\*\s*|@\w+\s+|(?:^|\s)(?:function|new|extends|implements)\s+|<\s*)[A-Za-z_0-9\\\\]*$/.test(linePrefix),
		afterNew: /\bnew\s+[A-Za-z_0-9\\\\]*$/.test(linePrefix),
		helperFamily: jssHelperMatch ? jssHelperMatch[1] : null
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

function collectVisibleJssNames(document, position) {
	const maxLine = position.line;
	const names = new Set();

	for (let lineNumber = 0; lineNumber <= maxLine; lineNumber += 1) {
		const lineText =
			lineNumber === maxLine
				? document.lineAt(lineNumber).text.slice(0, position.character)
				: document.lineAt(lineNumber).text;

		for (const match of lineText.matchAll(/\blet\s+([A-Za-z_][A-Za-z0-9_]*)/g)) {
			names.add(match[1]);
		}
		for (const match of lineText.matchAll(/\bfunction\s+[A-Za-z_][A-Za-z0-9_]*\s*\(([^)]*)\)/g)) {
			collectJssParameterNames(match[1], names);
		}
		for (const match of lineText.matchAll(/\(([^)]*)\)\s*:\s*[A-Za-z_][A-Za-z0-9_<>?]*\s*=>/g)) {
			collectJssParameterNames(match[1], names);
		}
	}

	return Array.from(names).sort();
}

function collectJssParameterNames(parameterText, names) {
	for (const part of String(parameterText).split(",")) {
		const match = part.trim().match(/^([A-Za-z_][A-Za-z0-9_]*)\s*:/);
		if (match) {
			names.add(match[1]);
		}
	}
}

function collectVisibleVariablesBeforeLineText(text, lineNumber1Based) {
	const normalized = String(text).replace(/\r\n/g, "\n").replace(/\r/g, "\n");
	const lines = normalized.split("\n");
	const maxIndexExclusive = Math.max(0, Math.min(lines.length, lineNumber1Based - 1));
	const names = new Set();

	for (let lineIndex = 0; lineIndex < maxIndexExclusive; lineIndex += 1) {
		for (const match of lines[lineIndex].matchAll(/\$([A-Za-z_][A-Za-z0-9_]*)/g)) {
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

function jssKeywordItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Keyword);
	item.detail = "JSS keyword";
	return item;
}

function typeItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.TypeParameter);
	item.detail = "PHS type";
	return item;
}

function jssTypeItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.TypeParameter);
	item.detail = "JSS type";
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

function helperFamilyItem(label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Module);
	item.detail = "JSS reserved helper family";
	return item;
}

function helperMemberItem(family, label) {
	const vscode = require("vscode");
	const item = new vscode.CompletionItem(label, vscode.CompletionItemKind.Function);
	item.detail = `JSS ${family} helper`;
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
	collectVisibleJssNames,
	collectVisibleVariablesBeforeLineText,
	collectDeclaredTypeNames
};
