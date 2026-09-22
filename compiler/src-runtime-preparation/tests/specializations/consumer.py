"""Independent LLVM fixture generation from the isolated adapter's accepted facts."""


class Consumer:
    def __init__(self, accepted, demand):
        self.types = accepted["types"]
        self.record = self.types[demand["arguments"][0]]
        self.vector = self.types[demand["id"]]
        self.operations = {role: accepted["operations"][identity]
                           for role, identity in accepted["bindings"][demand["id"]].items()}
        self.target = accepted["manifest"]["target"]
        self.lines = []
        self.serial = 0

    def value(self, instruction):
        self.serial += 1
        name = f"%t{self.serial}"
        self.lines.append(f"  {name} = {instruction}")
        return name

    def call(self, role, arguments):
        operation = self.operations[role]
        abi = operation["abi"]
        assert len(arguments) == len(abi["parameters"])
        args = ", ".join(f"{p['type']} {p['attributes']} {v}" for p, v in zip(abi["parameters"], arguments))
        text = f"call {abi['return_attributes']} {abi['return_type']} @{operation['symbol']}({args})"
        if abi["return_type"] == "void":
            self.lines.append("  " + text)
            return None
        return self.value(text)

    def address(self, storage, field):
        return self.value(f"getelementptr i8, ptr {storage}, i64 {field['offset_bytes']}")

    def convert(self, value, source_bits, target_bits, signed=False):
        if source_bits == target_bits:
            return value
        operation = "trunc" if source_bits > target_bits else ("sext" if signed else "zext")
        return self.value(f"{operation} i{source_bits} {value} to i{target_bits}")

    def store(self, storage, values):
        for field, value in zip(self.record["fields"], values):
            bits = self.types[field["type"]]["value_bits"]
            value = self.convert(value, 64, bits)
            self.lines.append(f"  store i{bits} {value}, ptr {self.address(storage, field)}, align 1")

    def load(self, storage):
        values = []
        for field in self.record["fields"]:
            scalar = self.types[field["type"]]
            value = self.value(f"load i{scalar['value_bits']}, ptr {self.address(storage, field)}, align 1")
            values.append(self.convert(value, scalar["value_bits"], 64, scalar["signed"]))
        return values

    def allocate(self):
        for name, type_ in [("vector", self.vector), ("source", self.record), ("result", self.record)]:
            self.lines.append(f"  %{name} = alloca [{type_['size_bytes']} x i8], align {type_['alignment_bytes']}")
        self.call("construct", ["%vector"])

    def read(self, index):
        parameter = self.operations["read_copy"]["abi"]["parameters"][2]
        index = self.convert(index, 64, int(parameter["type"][1:]))
        self.call("read_copy", ["%result", "%vector", index])

    def generate(self):
        self.lines = [f'target triple = "{self.target["triple"]}"',
                      f'target datalayout = "{self.target["data_layout"]}"']
        for operation in self.operations.values():
            abi = operation["abi"]
            params = ", ".join(p["type"] + " " + p["attributes"] for p in abi["parameters"])
            self.lines.append(f"declare {abi['return_attributes']} {abi['return_type']} @{operation['symbol']}({params})")
        self.check()
        self.run()
        self.lines.append("define void @probe_bad() {\nentry:")
        self.allocate()
        self.read("0")
        self.lines.append("  unreachable\n}")
        return "\n".join(self.lines) + "\n"

    def check(self):
        self.lines.append("define i32 @probe_check() {\nentry:")
        self.allocate()
        initial = [str(11 + i) for i, _ in enumerate(self.record["fields"])]
        self.store("%source", initial)
        self.call("append_copy", ["%vector", "%source"])
        self.store("%source", ["99"] * len(initial))
        checks = []
        for _ in range(2):
            self.read("0")
            checks.extend(self.value(f"icmp eq i64 {value}, {expected}")
                          for value, expected in zip(self.load("%result"), initial))
            self.store("%result", ["77"] * len(initial))
        self.call("append_copy", ["%vector", "%source"])
        self.read("1")
        checks.extend(self.value(f"icmp eq i64 {value}, 99") for value in self.load("%result"))
        length = self.call("length", ["%vector"])
        bits = self.operations["length"]["abi"]["return_type"]
        checks.append(self.value(f"icmp eq {bits} {length}, 2"))
        self.call("destroy", ["%vector"])
        condition = checks[0]
        for check in checks[1:]:
            condition = self.value(f"and i1 {condition}, {check}")
        status = self.value(f"select i1 {condition}, i32 0, i32 1")
        self.lines.append(f"  ret i32 {status}\n}}")

    def run(self):
        self.lines.append("define i64 @probe_run(i64 %n, i64 %iterations, i64 %seed) noinline {\nentry:")
        self.allocate()
        self.lines.append("  br label %append\nappend:\n  %a = phi i64 [0, %entry], [%anext, %append]")
        base = self.value("add i64 %a, %seed")
        fields = [self.value(f"add i64 {base}, {i}") for i, _ in enumerate(self.record["fields"])]
        self.store("%source", fields)
        self.call("append_copy", ["%vector", "%source"])
        self.lines.extend(["  %anext = add i64 %a, 1", "  %more = icmp ult i64 %anext, %n",
                           "  br i1 %more, label %append, label %read", "read:",
                           "  %r = phi i64 [0, %append], [%rnext, %read]",
                           "  %sum = phi i64 [0, %append], [%nextsum, %read]"])
        length = self.call("length", ["%vector"])
        length_bits = int(self.operations["length"]["abi"]["return_type"][1:])
        length = self.convert(length, length_bits, 64)
        index = self.value(f"urem i64 %r, {length}")
        self.read(index)
        total = "%sum"
        for value in self.load("%result"):
            total = self.value(f"add i64 {total}, {value}")
        self.lines.extend([f"  %nextsum = add i64 {total}, 0", "  %rnext = add i64 %r, 1",
                           "  %again = icmp ult i64 %rnext, %iterations",
                           "  br i1 %again, label %read, label %exit", "exit:"])
        self.call("destroy", ["%vector"])
        self.lines.append("  ret i64 %nextsum\n}")

    def baseline(self):
        """Use actual exported types, but call the same provider helpers without ABI bridges."""
        functions = {}
        for role in ("append_copy", "length", "read_copy"):
            # cpp spellings are selected from resolved input definitions, supplied by the runner.
            functions[role] = self.native_targets[role]
        record, vector = self.record["cpp_name"], self.vector["cpp_name"]
        code = ['#include "source_types.hpp"', '#include <scpp_provider/sequence.hpp>', '#include <cstdint>',
                f'extern "C" __attribute__((noinline)) std::uint64_t probe_run(std::uint64_t n, std::uint64_t iterations, std::uint64_t seed) {{',
                f'  {vector} values;', f'  {record} source{{}};',
                '  for (std::uint64_t a = 0; a < n; ++a) {']
        for i, field in enumerate(self.record["fields"]):
            cpp = self.types[field["type"]]["cpp_name"]
            code.append(f'    source.{field["member"]} = static_cast<{cpp}>(a + seed + {i});')
        code += [f'    {functions["append_copy"]}(values, source);', '  }', '  std::uint64_t sum = 0;',
                 '  for (std::uint64_t r = 0; r < iterations; ++r) {',
                 f'    auto result = {functions["read_copy"]}(values, r % {functions["length"]}(values));']
        for field in self.record["fields"]:
            code.append(f'    sum += static_cast<std::uint64_t>(result.{field["member"]});')
        code += ['  }', '  return sum;', '}', 'extern "C" int probe_check() {',
                 f'  {vector} values;', f'  {record} source{{}};']
        for i, field in enumerate(self.record["fields"]):
            code.append(f'  source.{field["member"]} = {11 + i};')
        code.append(f'  {functions["append_copy"]}(values, source);')
        for field in self.record["fields"]:
            code.append(f'  source.{field["member"]} = 99;')
        code.append(f'  auto result = {functions["read_copy"]}(values, 0);')
        for i, field in enumerate(self.record["fields"]):
            code.append(f'  if (result.{field["member"]} != {11 + i}) return 1;')
            code.append(f'  result.{field["member"]} = 77;')
        code.append(f'  result = {functions["read_copy"]}(values, 0);')
        for i, field in enumerate(self.record["fields"]):
            code.append(f'  if (result.{field["member"]} != {11 + i}) return 2;')
        code.append(f'  {functions["append_copy"]}(values, source);')
        code.append(f'  result = {functions["read_copy"]}(values, 1);')
        for field in self.record["fields"]:
            code.append(f'  if (result.{field["member"]} != 99) return 3;')
        code += [f'  return {functions["length"]}(values) == 2 ? 0 : 4;', '}',
                 f'extern "C" void probe_bad() {{ {vector} value; (void){functions["read_copy"]}(value, 0); }}']
        return "\n".join(code) + "\n"
