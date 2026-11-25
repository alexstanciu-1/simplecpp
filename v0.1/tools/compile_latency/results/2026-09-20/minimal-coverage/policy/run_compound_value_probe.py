#!/usr/bin/env python3
"""Three-record C++ analogue of 7d58f273; not a twelve-source history replay.

Use the already-built value-publication policy. Preserve all existing fields and
add the historical field types under probe names. The real descriptor-to-row
writer transfers eight added fields. The full application checks value copies.
"""
import argparse,difflib,hashlib,json,re,subprocess
from pathlib import Path
from experiment import require_scratch,measure,write_changed
from run_corpus import warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--out',type=Path,required=True);p.add_argument('--private-provider',action='store_true');a=p.parse_args()
app=a.app.resolve();require_scratch(app);out=a.out.resolve();out.mkdir(parents=True,exist_ok=False)
gen=app/'.prism/expanded/generated';config='latency-expanded.ninja'
fields=[('uint16_t','numeric_kind_id'),('uint16_t','integer_width_bits'),('uint16_t','integer_width_policy_id'),('uint16_t','alias_policy_id'),('uint32_t','canonical_numeric_type_ref_id'),('uint16_t','numeric_operator_mask'),('uint16_t','numeric_status_id'),('uint16_t','numeric_blocked_reason_id')]
table_fields=[('uint16_t','lookup_policy_status_id'),('uint32_t','lookup_index_threshold'),('uint32_t','numeric_ready_count'),('uint32_t','numeric_blocked_count')]
headers={'ProviderTraitDescriptorRow':'structures/capabilities/provider_trait_descriptor_row.hpp','TypeTraitRow':'__private_types/TypeTraitRow.hpp','TypeTraitTable':'__private_types/TypeTraitTable.hpp'}
if a.private_provider:headers['ProviderTraitDescriptorRow']='__private_types/ProviderTraitDescriptorRow.hpp'
writer='__callable/type_traits_row_from_descriptor.cpp';keys=[*headers.values(),writer,'main.cpp'];original={k:(gen/k).read_text() for k in keys};changed=dict(original)
for name,key in headers.items():
 needle='struct '+name+' {\n';assert changed[key].count(needle)==1
 declarations=''.join('\tint_t<std::'+t+'> latency_'+f+'{};\n' for t,f in (table_fields if name=='TypeTraitTable' else fields))
 changed[key]=changed[key].replace(needle,needle+declarations,1)
needle='\treturn row;';assert changed[writer].count(needle)==1
changed[writer]=changed[writer].replace(needle,''.join('\trow->latency_'+f+' = descriptor->latency_'+f+';\n' for _,f in fields)+needle)
proof='ProviderTraitDescriptorRow latency_descriptor{};\n'
for i,(t,f) in enumerate(fields,1):proof+=f'latency_descriptor.latency_{f} = int_t<std::{t}>({i});\n'
proof+='auto latency_row = __latency_fn_type_traits_row_from_descriptor(latency_descriptor);\nauto latency_copy = latency_row;\n'
for i,(t,f) in enumerate(fields,1):
 proof+=f'if (!static_cast<bool>(php::identical(latency_row.latency_{f}, int_t<std::{t}>({i})))) return 91;\nlatency_copy.latency_{f} = int_t<std::{t}>(99);\n'
proof+='TypeTraitTable latency_table{};\nlatency_table.traits.push_back(latency_row);\n'
for i,(t,f) in enumerate(table_fields,1):proof+=f'latency_table.latency_{f} = int_t<std::{t}>({i});\n'
proof+='auto latency_table_copy = latency_table;\nlatency_table_copy.traits.push_back(latency_copy);\n'
for i,(t,f) in enumerate(table_fields,1):proof+=f'latency_table_copy.latency_{f} = int_t<std::{t}>(99);\nif (!static_cast<bool>(php::identical(latency_table.latency_{f}, int_t<std::{t}>({i})))) return 92;\n'
proof+='auto latency_stored = latency_table.traits[int_t<>(0)];\n'
for i,(t,f) in enumerate(fields,1):proof+=f'if (!static_cast<bool>(php::identical(latency_stored.latency_{f}, int_t<std::{t}>({i}))) || !static_cast<bool>(php::identical(latency_row.latency_{f}, int_t<std::{t}>({i})))) return 93;\n'
proof+='if (latency_table.traits.size()!=1 || latency_table_copy.traits.size()!=2) return 94;\nstd::puts("compound_value=20_fields;writer=8;independent_copies=ok;table_sizes=1:2");\n'
needle='int __scpp_main() {\n';assert changed['main.cpp'].count(needle)==1
changed['main.cpp']=''.join('#include "'+k+'"\n' for k in headers.values())+'#include "__callable/__latency_fn_type_traits_row_from_descriptor.hpp"\n#include <cstdio>\n'+changed['main.cpp'].replace(needle,needle+proof,1)
# Stable groups contain definition bytes, rather than including individual CPPs.
# Update the single active owner as well as the inspectable individual artifact.
body=re.search(r'TypeTraitRow __latency_fn_type_traits_row_from_descriptor\([\s\S]*?^}',original[writer],re.M)[0]
new_body=re.search(r'TypeTraitRow __latency_fn_type_traits_row_from_descriptor\([\s\S]*?^}',changed[writer],re.M)[0]
groups=[q for q in (gen/'__callable').glob('type_traits_group_*.cpp') if body in q.read_text()]
assert len(groups)==1, 'Expected one grouped writer definition'
group=str(groups[0].relative_to(gen));original[group]=groups[0].read_text();changed[group]=original[group].replace(body,new_body,1);keys.append(group)
graph=(app/'.prism/build'/config).read_text();assert str(groups[0]) in graph, 'Writer group is not a native graph input'
corpus=json.loads((app.parent/'corpus.json').read_text());status={'scope':'generated C++ analogue of twenty added fields across three value records plus real writer and executable witnesses; not full historical replay','private_provider':a.private_provider,'jobs':12,'frontend_assumed_seconds':1.5,'restored':False,'phase':'baseline','original_generated_hashes':{k:hashlib.sha256(v.encode()).hexdigest() for k,v in original.items()}}
def save():(out/'status.json').write_text(json.dumps(status,indent=2)+'\n')
try:
 warm(app,config);verify(app,corpus,out,'before');save()
 (out/'generated-edit.patch').write_text(''.join(''.join(difflib.unified_diff(original[k].splitlines(True),changed[k].splitlines(True),fromfile='before/'+k,tofile='after/'+k)) for k in keys))
 for k,v in changed.items():write_changed(gen/k,v)
 status['phase']='edit';save();outputs=None
 for i in range(3):
  for k in keys:(gen/k).touch()
  label='edit-'+str(i);measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label)
  assert 'compound_value=20_fields;writer=8;independent_copies=ok;table_sizes=1:2' in (out/(label+'.run.log')).read_text().splitlines()
  row=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current=sorted(s['output'] for s in row['native_steps'])
  if outputs is None:outputs=current
  assert current==outputs
  if row['native_wall_seconds']>20:status['early_stop']='single successful screen above20s';break
 status['result']='measured';save()
except BaseException as error:
 status['result']='failed';status['error']=str(error);save();raise
finally:
 for k,v in original.items():write_changed(gen/k,v)
 status['phase']='restore';save();warm(app,config);verify(app,corpus,out,'restored')
 assert all((gen/k).read_text()==v for k,v in original.items())
 status['restored']=True;status['phase']='complete';save()
