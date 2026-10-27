import copy
import importlib.util
import json
from pathlib import Path
import sys
import unittest

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location("concrete_names", ROOT / "middleware/tools/type_names.py")
names = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = names
spec.loader.exec_module(names)
DATA = json.loads((ROOT / "middleware/types.json").read_text())


class NamingTests(unittest.TestCase):
    def setUp(self):
        self.registry = names.Registry(copy.deepcopy(DATA))

    def test_depth_examples(self):
        for source, expected in [
            ("hash<T1<T2>,T1>", "hash_0T1_1T2_0T1_"),
            ("hash<T1,T2<T1>>", "hash_0T1_0T2_1T1_"),
            ("hash<A<B<C>>,D<E>>", "hash_0A_1B_2C_0D_1E_"),
        ]:
            self.assertEqual(names.structural_name(names.parse(source)), expected)

    def test_mappings_defaults_and_aliases(self):
        self.assertEqual(self.registry.resolve("vector<string>")[1], "vector_string")
        self.assertEqual(self.registry.resolve("int<32>")[1], "int32")
        self.assertEqual(self.registry.resolve("int<0032>"), self.registry.resolve("int<32>"))
        self.assertEqual(self.registry.resolve("int64")[0], "int<64>")
        self.assertEqual(self.registry.resolve("uint64")[0], "uint<64>")
        self.assertEqual(self.registry.resolve("uint<32>")[1], "uint32")
        self.assertEqual(self.registry.resolve("boolean")[0], "bool")
        self.assertEqual(self.registry.resolve("hash<string>"), self.registry.resolve("hash<string,string>"))
        self.assertEqual(self.registry.resolve("vector<int32>")[1], "vector_0int32_")
        self.assertEqual(self.registry.resolve("vector<int32>"), self.registry.resolve("vector<int<32>>"))
        self.assertEqual(self.registry.resolve("vector<vector_string>")[1], "vector_0vector_1string_")

    def test_dependent_defaults(self):
        data = copy.deepcopy(DATA)
        data['families']['bag'] = {
            'parameters':['type','type'], 'parameter_names':['Element','Payload'],
            'defaults':['vector<Element>']}
        data['families']['sizes'] = {
            'parameters':['integer','integer'], 'parameter_names':['N','M'],
            'values':[8,16,32,64], 'defaults':['32','N']}
        data['mappings'].append({'type':'sizes<32,32>','name':'sizes32'})
        registry = names.Registry(data)
        self.assertEqual(registry.resolve('bag<vector_string>'),
                         registry.resolve('bag<vector<string>,vector<vector<string>>>'))
        self.assertEqual(registry.resolve('sizes<>'), registry.resolve('sizes<32,32>'))
        self.assertEqual(registry.resolve('sizes')[1], 'sizes32')
        with self.assertRaises(ValueError):
            registry.resolve('string<>')

    def test_escape_is_reversible_and_separates_depth(self):
        samples = ["T1", "some_0Type", "somezU0Type", "scope::Type", "z", "A_9B"]
        encoded = [names.escape(x) for x in samples]
        self.assertEqual(len(set(encoded)), len(samples))
        for source, value in zip(samples, encoded):
            restored = ""
            index = 0
            while index < len(value):
                if value[index] == "z":
                    restored += {"Z": "z", "U": "_", "C": ":"}[value[index + 1]]
                    index += 2
                else:
                    restored += value[index]
                    index += 1
            self.assertEqual(restored, source)
            self.assertNotIn("_", value)

    def test_rejections(self):
        for text in ["vector_", "vector<string_>", "vector<>", "vector<string,bool>",
                     "missing<string>", "vector<32>", "int<string>", "int<7>",
                     "shared", "vector<string>garbage", "vector<int<8>>"]:
            with self.subTest(text=text), self.assertRaises(ValueError):
                self.registry.resolve(text)

    def test_registry_collisions(self):
        for name in ["string", "vector", "int32", "bad_", "class"]:
            data = copy.deepcopy(DATA)
            data["mappings"].append({"type": "vector<bool>", "name": name})
            with self.subTest(name=name), self.assertRaises(ValueError):
                names.Registry(data)

    def test_private_bindings_use_registry(self):
        text = self.registry.bindings("scalars")
        self.assertNotIn("json", text)
        self.assertNotIn("vector", text)
        self.assertNotIn("string", text)
        containers = self.registry.bindings("containers")
        self.assertIn("native_vector_string = scpp::vector_t<scpp::string_t>", containers)
        self.assertIn('#include "scpp/string_t.hpp"', containers)
        self.assertNotIn("json", containers)
        strings = self.registry.bindings("string")
        self.assertNotIn("vector", strings)
        self.assertNotIn("json", strings)
        self.assertIn("native_string = scpp::string_t", strings)
        document = self.registry.bindings("json")
        self.assertIn('#include "modules/json/document.hpp"', document)
        self.assertIn('#include "scpp/vector_t.hpp"', document)
        self.assertIn("native_int32 = scpp::int_t<std::int32_t>", text)
        self.assertIn("native_uint64 = scpp::int_t<std::uint64_t>", text)


if __name__ == "__main__":
    unittest.main()
