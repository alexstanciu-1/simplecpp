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
    def __init__(self, data):
        if data["version"] != 1:
            raise ValueError("Unsupported registry version")
        self.types = set(data["types"])
        self.families = data["families"]
        self.mappings = {}
        self.aliases = {}
        self.claimed = {}
        for name in [*self.types, *self.families]:
            valid_name(name)
        for mapping in data["mappings"]:
            name = mapping["name"]
            valid_name(name)
            if "::" in name or name in CPP_KEYWORDS or name in self.aliases or name in self.families:
                raise ValueError(f"Conflicting concrete name: {name}")
            canonical = self.normalize(parse(mapping["type"])).canonical()
            if canonical in self.mappings or (name in self.types and canonical != name):
                raise ValueError(f"Conflicting mapping: {canonical}")
            self.mappings[canonical] = mapping
            self.aliases[name] = parse(canonical)

    def normalize(self, node):
        if not node.arguments and node.name in self.aliases:
            return self.aliases[node.name]
        if not node.arguments and node.name in self.types:
            return node
        if node.name not in self.families:
            raise ValueError(f"Unregistered type or template family: {node.name}")
        family = self.families[node.name]
        parameters = family["parameters"]
        defaults = family.get("defaults", [])
        count = len(node.arguments)
        if not len(parameters) - len(defaults) <= count <= len(parameters):
            raise ValueError(f"Wrong argument count for {node.name}")
        arguments = list(node.arguments)
        arguments.extend(parse(x) for x in defaults[len(defaults) - (len(parameters) - count):])
        normalized = []
        for kind, argument in zip(parameters, arguments):
            if kind == "type" and isinstance(argument, Type):
                normalized.append(self.normalize(argument))
            elif kind == "integer" and type(argument) is int and argument in family["values"]:
                normalized.append(argument)
            else:
                raise ValueError(f"Invalid {kind} argument for {node.name}")
        return Type(node.name, tuple(normalized))

    def resolve(self, text):
        node = self.normalize(parse(text))
        identity = node.canonical()
        if identity in self.mappings:
            name = self.mappings[identity]["name"]
        elif node.arguments:
            # Nested aliases never shorten the structural tree: registration of
            # another short name must not rename existing outer specializations.
            name = structural_name(self.structural_tree(node))
        else:
            name = node.name
        previous = self.claimed.setdefault(name, identity)
        if previous != identity:
            raise ValueError(f"Concrete name collision: {name}")
        return identity, name

    def structural_tree(self, node):
        if any(not isinstance(x, Type) for x in node.arguments):
            mapping = self.mappings.get(node.canonical())
            if mapping is None:
                raise ValueError("Non-type arguments require an explicit concrete mapping")
            return Type(mapping["name"])
        return Type(node.name, tuple(self.structural_tree(x) for x in node.arguments))

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


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("types", nargs="*")
    parser.add_argument("--registry", type=Path, default=Path(__file__).resolve().parents[1] / "types.json")
    parser.add_argument("--bindings-dir", type=Path)
    args = parser.parse_args()
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
