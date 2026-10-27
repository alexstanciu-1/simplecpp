"""Build-time concrete naming; no source-language semantic analysis."""
import argparse
from dataclasses import dataclass
import json
from pathlib import Path
import re

CPP_KEYWORDS = set("""alignas alignof and and_eq asm auto bitand bitor bool break
case catch char char8_t char16_t char32_t class compl concept const consteval
constexpr constinit const_cast continue co_await co_return co_yield decltype default
delete do double dynamic_cast else enum explicit export extern false float for
friend goto if inline int long mutable namespace new noexcept not not_eq nullptr
operator or or_eq private protected public register reinterpret_cast requires return
short signed sizeof static static_assert static_cast struct switch template this
thread_local throw true try typedef typeid typename union unsigned using virtual
void volatile wchar_t while xor xor_eq""".split())


@dataclass(frozen=True)
class Type:
    name: str
    arguments: tuple = ()

    def canonical(self):
        if not self.arguments:
            return self.name
        return self.name + "<" + ",".join(
            x.canonical() if isinstance(x, Type) else str(x) for x in self.arguments) + ">"


def valid_name(name):
    parts = name.split("::")
    if any(not re.fullmatch(r"[A-Za-z][A-Za-z0-9_]*", p)
           or p.endswith("_") or "__" in p for p in parts):
        raise ValueError(f"Invalid authored/registered name: {name}")


def parse(text):
    tokens = re.findall(r"[A-Za-z][A-Za-z0-9_]*(?:::[A-Za-z][A-Za-z0-9_]*)*|[0-9]+|[<>,]|\S", text)
    position = 0

    def node():
        nonlocal position
        if position >= len(tokens):
            raise ValueError("Expected type")
        name = tokens[position]
        valid_name(name)
        position += 1
        arguments = []
        if position < len(tokens) and tokens[position] == "<":
            position += 1
            while True:
                if position < len(tokens) and tokens[position].isdigit():
                    arguments.append(int(tokens[position]))
                    position += 1
                else:
                    arguments.append(node())
                if position >= len(tokens) or tokens[position] not in (",", ">"):
                    raise ValueError("Expected ',' or '>'")
                token = tokens[position]
                position += 1
                if token == ">":
                    break
        return Type(name, tuple(arguments))

    result = node()
    if position != len(tokens):
        raise ValueError("Unexpected trailing tokens")
    return result


def escape(name):
    valid_name(name)
    # Escape z itself so zU/zC sequences in authored names cannot collide.
    return name.replace("z", "zZ").replace("_", "zU").replace(":", "zC")


def structural_name(root):
    """Preorder depth encoding. Caller validates family membership first."""
    result = escape(root.name)

    def visit(node, depth):
        nonlocal result
        if not isinstance(node, Type):
            raise ValueError("Unmapped non-type argument: add an explicit mapping")
        result += "_" + str(depth) + escape(node.name)
        for child in node.arguments:
            visit(child, depth + 1)

    for argument in root.arguments:
        visit(argument, 0)
    return result + "_"


class Registry:
    """Compatibility facade; canonical resolution is owned by the C++ library."""
    def __init__(self, data):
        self.data = data
        self.claimed = {}
        rows = self._resolve_many([m['type'] for m in data['mappings']])
        self.mappings = {row[0]: mapping for row, mapping in zip(rows, data['mappings'])}

    def _resolve_many(self, types):
        import sys
        tool_root = str(Path(__file__).resolve().parent)
        if tool_root not in sys.path:
            sys.path.insert(0, tool_root)
        from concrete_types import generator_tool
        import subprocess
        tool = generator_tool(Path(__file__).resolve().parents[1])
        response = subprocess.run([str(tool)], input=json.dumps({
            'action': 'names', 'registry': self.data, 'types': types}),
            text=True, capture_output=True)
        if response.returncode:
            raise ValueError(response.stderr.strip())
        return json.loads(response.stdout)

    def resolve(self, text):
        identity, name = self._resolve_many([text])[0]
        previous = self.claimed.setdefault(name, identity)
        if previous != identity:
            raise ValueError(f'Concrete name collision: {name}')
        return identity, name

    def normalize(self, node):
        return parse(self.resolve(node.canonical())[0])

    def bindings(self, family):
        if family not in ("scalars", "string", "containers", "json"):
            raise ValueError(f"Unknown bridge family: {family}")
        public = [m for m in self.mappings.values() if "public_header" in m]
        if any(m.get("bridge") not in ("scalars", "string", "containers", "json") for m in public):
            raise ValueError("Every public mapping needs a known bridge family")
        mappings = [m for m in public if m["bridge"] == family]
        headers = sorted({h for m in mappings
                          for h in [m["runtime_header"], *m.get("runtime_dependencies", [])]})
        return "#pragma once\n" + "".join(f'#include "{h}"\n' for h in headers) + (
            "namespace scpp::compiler_support::bindings {\n" + "".join(
                f'using native_{m["name"]} = {m["runtime"]};\n' for m in mappings) + "}\n")


def main(default_registry=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("types", nargs="*")
    parser.add_argument("--registry", type=Path, default=default_registry)
    parser.add_argument("--bindings-dir", type=Path)
    args = parser.parse_args()
    if args.registry is None:
        parser.error("--registry is required for the shared naming tool")
    registry = Registry(json.loads(args.registry.read_text()))
    if args.bindings_dir:
        args.bindings_dir.mkdir(parents=True, exist_ok=True)
        for family in ("scalars", "string", "containers", "json"):
            text = registry.bindings(family)
            path = args.bindings_dir / f"runtime_{family}.hpp"
            if not path.exists() or path.read_text() != text:
                path.write_text(text)
    for requested in args.types:
        identity, name = registry.resolve(requested)
        print(f"{identity} => {name}")


if __name__ == "__main__":
    main()
