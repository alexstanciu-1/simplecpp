#!/usr/bin/env python3
"""Unchanged value definitions and standalone row/table copy proof."""
import argparse,re,subprocess
from pathlib import Path
from experiment import require_scratch,write_changed
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--out',type=Path,required=True);p.add_argument('--provider',action='store_true');a=p.parse_args();app=a.app.resolve();require_scratch(app);a.out.mkdir(parents=True,exist_ok=True);build=app/'.prism/build';gen=app/'.prism/expanded/generated'
definitions=[('TypeTraitRow','type_trait_row'),('TypeTraitTable','type_trait_table')]
if a.provider:definitions.append(('ProviderTraitDescriptorRow','provider_trait_descriptor_row'))
for name,file in definitions:
 original=re.search(r'^struct '+name+r' \{\n[\s\S]*?^};',(app/'.prism/generated/structures/capabilities'/f'{file}.hpp').read_text(),re.M)[0]
 assert original in (gen/'__private_types'/f'{name}.hpp').read_text()
code='''#include "__private_types/TypeTraitTable.hpp"
#include <cstdio>
using namespace scpp;
int main() {
 TypeTraitRow row{};
 row.integer_width_bits = int_t<std::uint16_t>(32);
 TypeTraitRow copy = row;
 copy.integer_width_bits = int_t<std::uint16_t>(99);
 TypeTraitTable table{};
 table.traits.push_back(row);
 TypeTraitTable table_copy = table;
 table_copy.traits.push_back(copy);
 TypeTraitRow stored = table.traits[int_t<>(0)];
 stored.integer_width_bits = int_t<std::uint16_t>(17);
 TypeTraitRow retained = table.traits[int_t<>(0)];
 if (!static_cast<bool>(php::identical(row.integer_width_bits,int_t<std::uint16_t>(32)))) return 1;
 if (!static_cast<bool>(php::identical(copy.integer_width_bits,int_t<std::uint16_t>(99)))) return 2;
 if (!static_cast<bool>(php::identical(retained.integer_width_bits,int_t<std::uint16_t>(32)))) return 3;
 if (table.traits.size()!=1 || table_copy.traits.size()!=2) return 4;
 std::puts("definition_bytes=unchanged;row_copy=32:99;stored_value=32;table_copy=1:2;standalone_headers=ok");
}
'''
if a.provider:
 code='#include "__private_types/ProviderTraitDescriptorRow.hpp"\n'+code.replace('int main() {','''int main() {
 ProviderTraitDescriptorRow provider{};
 provider.integer_width_bits = int_t<std::uint16_t>(32);
 auto provider_copy = provider;
 provider_copy.integer_width_bits = int_t<std::uint16_t>(99);
 if (!static_cast<bool>(php::identical(provider.integer_width_bits,int_t<std::uint16_t>(32)))) return 5;
 std::puts("provider_definition=unchanged;provider_copy=independent;standalone_header=ok");
''')
write_changed(build/'value-proof.cpp',code);graph=(build/'latency-expanded.ninja').read_text();link=re.search(r'^build main: link (.+)$',graph,re.M);libs=[s for s in link[1].split() if not s.endswith('.o')];graph=graph.replace('default main','');graph+='\nbuild value-proof.o: compile value-proof.cpp | expanded_runtime_pch.hpp.gch\nbuild value-proof: link value-proof.o '+' '.join(libs)+'\ndefault value-proof\n';write_changed(build/'value-proof.ninja',graph)
r=subprocess.run(['ninja','-f','value-proof.ninja','-j12'],cwd=build,text=True,capture_output=True);(a.out/'build.log').write_text(r.stdout+r.stderr);r.check_returncode();r=subprocess.run([str(build/'value-proof')],cwd=app,text=True,capture_output=True);(a.out/'run.log').write_text(r.stdout+r.stderr);r.check_returncode();(a.out/'value-proof.cpp').write_text(code);print(r.stdout.strip())
