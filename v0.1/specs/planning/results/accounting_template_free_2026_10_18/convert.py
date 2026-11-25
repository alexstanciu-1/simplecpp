#!/usr/bin/env python3
"""Bounded conversion of the accounting generated artifact, not a general C++ parser."""
import hashlib
import json
from pathlib import Path
import re
import shutil
HERE=Path(__file__).resolve().parent
ROOT=HERE.parents[3]
SOURCE=ROOT/'examples/accounting_workload/.prism/generated'
OUT=HERE/'converted'
OUT.mkdir(exist_ok=True)
containers={};handles=set();factories=set();tags=set();casts=set()
original={p.relative_to(SOURCE):p.read_text() for p in SOURCE.rglob('*') if p.suffix in ('.cpp','.hpp')}
alltext='\n'.join(original.values())
enums=re.findall(r'enum class (\w+) : std::int32_t',alltext)
classes=sorted(set(re.findall(r'^class (\w+) :',alltext,re.M)))
parents={}
for name,bases in re.findall(r'^class (\w+) : ([^{]+)\{',alltext,re.M):
 for parent in re.findall(r'public (\w+)',bases):
  if parent in classes: parents[name]=parent

def split_args(s):
 result=[];start=0;depth=0
 for i,c in enumerate(s):
  if c in '<({[':depth+=1
  elif c in '>)}]':depth-=1
  elif c==',' and depth==0:result.append(s[start:i].strip());start=i+1
 if s[start:].strip():result.append(s[start:].strip())
 return result

def type_name(text):
 text=text.strip()
 m=re.fullmatch(r'(\w+(?:::\w+)*)\s*<(.*)>',text,re.S)
 if not m:return text.replace('std::int64_t','int64_t').replace('std::int32_t','int32_t')
 kind,args=m.groups();args=split_args(args)
 if kind=='int_t':return 'I'
 if kind=='shared_p':handles.add(args[0]);return 'P_'+args[0]
 if kind in ('vector_t','hash_t'):
  value=type_name(args[0]);key=type_name(args[1]) if len(args)>1 else 'string_t'
  name=('V_'+value) if kind=='vector_t' else ('H_'+value+'_'+key)
  containers[name]=(kind,value,key);return name
 if kind=='std::type_identity':
  value=type_name(args[0]);tags.add(value);return 'Tag_'+value
 if kind=='std::function':return 'auto'
 raise ValueError(text)

pattern=re.compile(r'\b(int_t|shared_p|vector_t|hash_t|std::type_identity|std::function|create|cast|required_cast)\s*<')
def replace_types(s):
 out=[];pos=0
 while (m:=pattern.search(s,pos)):
  start=m.end()-1;depth=1;i=start+1
  while depth:
   if s[i]=='<':depth+=1
   elif s[i]=='>':depth-=1
   i+=1
  content=s[start+1:i-1]
  if m[1]=='create':factories.add(content.strip());name='create_'+content.strip()
  elif m[1] in ('cast','required_cast'):
   target=type_name(content);casts.add(target);name='convert_'+target
  else:name=type_name(s[m.start():i])
  out.extend([s[pos:m.start()],name]);pos=i
 out.append(s[pos:]);return ''.join(out)

transformed={}
for path,text in original.items():
 text=replace_types(text)
 text=re.sub(r'#include <(?:scpp/lang/php.hpp|cstddef|type_traits|utility)>', '#include "concrete.hpp"',text)
 text=text.replace('std::int32_t','int32_t').replace('std::int64_t','int64_t').replace('std::exception','::scpp::runtime_failure').replace('mixed_t argv','cli_arguments argv')
 transformed[path]=text
# Replace nonempty vector initializer lists with ordered concrete appends.
for path,text in transformed.items():
 for name,(kind,value,key) in containers.items():
  if kind!='vector_t':continue
  def init(m):
   values=split_args(m[1])
   return '([&]() { '+name+' init; '+''.join('init.push_back('+x+'); ' for x in values)+'return init; }())'
  text=re.sub(r'\b'+name+r'\{([^{}]+)\}',init,text)
 dest=OUT/path;dest.parent.mkdir(parents=True,exist_ok=True);dest.write_text(text)
for name in ['base.hpp','base.cpp']:shutil.copyfile(HERE/name,OUT/name)

header='#pragma once\n#include "base.hpp"\n#include "__project_fwd.hpp"\nnamespace scpp {\n'
for name in enums:header+=f'enum class {name} : int32_t;\n'
for name in sorted(handles):header+=f'struct P_{name};\n'
for name in sorted(handles):
 parent_ctor=''.join(f'    P_{name}(const P_{child}&);\n' for child,parent in parents.items() if parent==name)
 header+=f'''struct P_{name} {{
    owner_ref owner;
    {name} *ptr=nullptr;
    P_{name}()=default;
    P_{name}(const P_{name}&)=default;
    P_{name}& operator=(const P_{name}&)=default;
    P_{name}(P_{name}&& x) noexcept : owner(static_cast<owner_ref&&>(x.owner)),ptr(x.ptr) {{x.ptr=nullptr;}}
    P_{name}& operator=(P_{name}&& x) noexcept {{if(this!=&x) {{owner=static_cast<owner_ref&&>(x.owner);ptr=x.ptr;x.ptr=nullptr;}}return *this;}}
    P_{name}(owner_state *state,{name} *value) : owner(state),ptr(value) {{}}
{parent_ctor}    {name} *operator->() const {{ if(!ptr) fail("null_shared_dereference");return ptr; }}
}};
'''
for name in sorted(factories):header+=f'P_{name} create_{name}();\n'
for tag in sorted(tags):header+=f'struct Tag_{tag} {{}};\n'
header+='namespace php {\n'
for name in enums:
 header+=f'inline bool_t identical({name} a,{name} b) {{ return bool_t(a==b); }}\ninline bool_t not_identical({name} a,{name} b) {{ return bool_t(a!=b); }}\n'
header+='}\n'

support='#include "concrete.hpp"\nnamespace scpp {\n'
for name,(kind,value,key) in sorted(containers.items()):
 ishash=kind=='hash_t'
 fields=(f'{key} key{{}}; {value} value{{}}; bool live=false;' if ishash else f'{value} value{{}};')
 header+=f'''struct E_{name} {{ {fields} }};
class {name} {{
    E_{name} *data_=nullptr;
    size_t used_=0,capacity_=0;
'''
 if ishash:header+='    size_t count_=0; size_t *buckets_=nullptr; size_t bucket_count_=0;\n    size_t find(const '+key+'&) const;\n    void rehash(size_t);\n'
 header+=f'''    void grow();
public:
    {name}()=default;
    {name}(const {name}&);
    {name}({name}&&) noexcept;
    {name}& operator=(const {name}&);
    {name}& operator=({name}&&) noexcept;
    ~{name}();
    size_t size() const {{ return {'count_' if ishash else 'used_'}; }}
    size_t slots() const {{ return used_; }}
    bool live(size_t i) const {{ return i<used_{' && data_[i].live' if ishash else ''}; }}
    const E_{name}& entry(size_t i) const {{ return data_[i]; }}
'''
 if ishash:
  header+=f'''    bool contains(const {key}&) const;
    {value}& operator[](const {key}&);
    {value}& at(const {key}&);
    const {value}& at(const {key}&) const;
    bool_t remove(const {key}&);
'''
 else:
  header+=f'''    void push_back({value});
    void append({value} v) {{ push_back(static_cast<{value}&&>(v)); }}
    {value}& at(I i) {{ if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }}
    const {value}& at(I i) const {{ if(i.value<0 || static_cast<uint64_t>(i.value)>=used_) fail("bounds_error");return data_[i.value].value; }}
    {value}& operator[](I i) {{ return at(i); }}
    const {value}& operator[](I i) const {{ return at(i); }}
'''
 header+='};\n'
 header+=f'''struct Entry_{name} {{
    const {name} *source;size_t index;
    {value} value_copy() const {{ return source->entry(index).value; }}
    {('const '+key+'&') if ishash else 'I'} key() const {{ return {'source->entry(index).key' if ishash else 'I(static_cast<int64_t>(index))'}; }}
}};
struct Iterator_{name} {{
    const {name} *source;size_t index;
    void skip() {{ while(index<source->slots() && !source->live(index)) ++index; }}
    Iterator_{name}& operator++() {{ ++index;skip();return *this; }}
    bool operator!=(const Iterator_{name}& x) const {{ return index!=x.index; }}
    Entry_{name} operator*() const {{ return {{source,index}}; }}
}};
struct Range_{name} {{
    const {name} *source;
    Iterator_{name} begin() const {{ Iterator_{name} i{{source,0}};i.skip();return i; }}
    Iterator_{name} end() const {{ return {{source,source->slots()}}; }}
}};
inline Range_{name} foreach_range(const {name}& v) {{ return {{&v}}; }}
namespace php {{ inline I count(const {name}& v) {{ return I(static_cast<int64_t>(v.size())); }}
'''
 if ishash:header+=f'inline bool_t isset(const {name}& v,const {key}& k) {{ return bool_t(v.contains(k)); }}\n'
 header+='}\n'
 extra_move=',count_(x.count_),buckets_(x.buckets_),bucket_count_(x.bucket_count_)' if ishash else ''
 extra_zero='x.count_=x.bucket_count_=0;x.buckets_=nullptr;' if ishash else ''
 extra_assign='count_=x.count_;buckets_=x.buckets_;bucket_count_=x.bucket_count_;' if ishash else ''
 extra_delete='delete[] buckets_;' if ishash else ''
 support+=f'''{name}::{name}(const {name}& x) {{
    auto *next=new E_{name}[x.used_];
    try {{ for(size_t i=0;i<x.used_;++i) next[i]=x.data_[i]; }} catch(...) {{delete[] next;throw;}}
    data_=next;used_=capacity_=x.used_;
'''
 if ishash:support+='    count_=x.count_;try {if(x.bucket_count_) rehash(x.bucket_count_);} catch(...) {delete[] data_;throw;}\n'
 support+=f'''}}
{name}::{name}({name}&& x) noexcept : data_(x.data_),used_(x.used_),capacity_(x.capacity_){extra_move} {{x.data_=nullptr;x.used_=x.capacity_=0;{extra_zero}}}
{name}& {name}::operator=(const {name}& x) {{if(this!=&x) {{ {name} next(x);*this=static_cast<{name}&&>(next); }}return *this;}}
{name}& {name}::operator=({name}&& x) noexcept {{
    if(this!=&x) {{delete[] data_;{extra_delete}data_=x.data_;used_=x.used_;capacity_=x.capacity_;{extra_assign}
    x.data_=nullptr;x.used_=x.capacity_=0;{extra_zero}}}return *this;
}}
{name}::~{name}() {{delete[] data_;{extra_delete}}}
void {name}::grow() {{
    if(capacity_>SIZE_MAX/2/sizeof(E_{name})) fail("container_size_overflow");
    size_t capacity=capacity_ ? capacity_*2 : 4;
    auto *next=new E_{name}[capacity];
    // All emitted element/key representations have nonthrowing moves.
    for(size_t i=0;i<used_;++i) next[i]=static_cast<E_{name}&&>(data_[i]);
    delete[] data_;data_=next;capacity_=capacity;
}}
'''
 if not ishash:
  support+=f'void {name}::push_back({value} v) {{if(used_==capacity_) grow();data_[used_++].value=static_cast<{value}&&>(v);}}\n'
 else:
  support+=f'''size_t {name}::find(const {key}& k) const {{
    if(!bucket_count_) return SIZE_MAX;
    size_t slot=key_hash(k)&(bucket_count_-1);
    while(buckets_[slot]) {{size_t i=buckets_[slot]-1;if(data_[i].live && static_cast<bool>(data_[i].key==k)) return i;slot=(slot+1)&(bucket_count_-1);}}
    return SIZE_MAX;
}}
void {name}::rehash(size_t count) {{
    auto *next=new size_t[count]{{}};
    for(size_t i=0;i<used_;++i) if(data_[i].live) {{size_t slot=key_hash(data_[i].key)&(count-1);while(next[slot]) slot=(slot+1)&(count-1);next[slot]=i+1;}}
    delete[] buckets_;buckets_=next;bucket_count_=count;
}}
bool {name}::contains(const {key}& k) const {{return find(k)!=SIZE_MAX;}}
{value}& {name}::at(const {key}& k) {{size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}}
const {value}& {name}::at(const {key}& k) const {{size_t i=find(k);if(i==SIZE_MAX) fail("missing_key");return data_[i].value;}}
{value}& {name}::operator[](const {key}& k) {{
    size_t i=find(k);if(i!=SIZE_MAX) return data_[i].value;
    {key} stable_key=k;
    if(used_==capacity_) grow();
    if(used_+1>=bucket_count_/2) {{if(bucket_count_>SIZE_MAX/2/sizeof(size_t)) fail("container_size_overflow");rehash(bucket_count_ ? bucket_count_*2 : 8);}}
    data_[used_].key=static_cast<{key}&&>(stable_key);data_[used_].live=true;
    size_t slot=key_hash(data_[used_].key)&(bucket_count_-1);while(buckets_[slot]) slot=(slot+1)&(bucket_count_-1);
    buckets_[slot]=used_+1;++count_;return data_[used_++].value;
}}
bool_t {name}::remove(const {key}& k) {{size_t i=find(k);if(i==SIZE_MAX) return bool_t(false);
    data_[i].live=false;data_[i].value={value}{{}};data_[i].key={key}{{}};--count_;return bool_t(true);
}}
'''
for target in sorted(casts):
 if target in ('I','bool_t','string_t'):continue
 header+=f'inline {target} convert_{target}(const {target}& x) {{ return x; }}\n'
header+='}\n';support+='}\n'
(OUT/'concrete.hpp').write_text(header)
(OUT/'containers.cpp').write_text(support)
objects='#include "all.hpp"\nnamespace scpp {\n'
for child,parent in parents.items():
 if parent in handles and child in handles:objects+=f'P_{parent}::P_{parent}(const P_{child}& x) : owner(x.owner),ptr(static_cast<{parent}*>(x.ptr)) {{}}\n'
for name in sorted(factories):
 objects+=f'''static void drop_{name}(void *p) {{delete static_cast<{name}*>(p);}}
P_{name} create_{name}() {{auto *p=new {name}();auto *state=make_owner(p,drop_{name});return P_{name}(state,p);}}
'''
objects+='}\n';(OUT/'objects.cpp').write_text(objects)
units=next(SOURCE.glob('__project_units/*.hpp')).read_text()
units=units.replace('"../','"')
(OUT/'all.hpp').write_text(units)
manifest={'original_files':{str(p):hashlib.sha256(t.encode()).hexdigest() for p,t in original.items()},'application_cpp':[str(p) for p in original if p.suffix=='.cpp'],
          'classes':classes,'handles':sorted(handles),'containers':containers,'factories':sorted(factories),'casts':sorted(casts),'parents':parents,
          'support_sources':['base.cpp','containers.cpp','objects.cpp']}
(HERE/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
print(len(manifest['application_cpp']),'application units',len(handles),'handles',len(containers),'containers')
