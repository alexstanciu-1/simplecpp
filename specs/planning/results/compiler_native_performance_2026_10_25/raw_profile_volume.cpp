#include <iostream>
#include <fstream>
#include <string>
#include <vector>
#include <unordered_map>
#include <algorithm>
#include <charconv>
#include <cstdint>
struct Trace {uint64_t ip,parent;};
struct Cost {uint64_t bytes=0,calls=0;};
uint64_t hex(const char*& p){while(*p==' ')++p;uint64_t v=0;auto e=p;while(*e && *e!=' ')++e;std::from_chars(p,e,v,16);p=e;return v;}
int main(int argc,char**argv){
 std::ios::sync_with_stdio(false);std::cin.tie(nullptr);
 std::string line;std::vector<Trace> traces{{0,0}};std::unordered_map<uint64_t,Cost> costs;uint64_t bytes=0,calls=0,timestamp=0,base=0;
 while(std::getline(std::cin,line)){
  if(line.empty())continue;const char* p=line.c_str()+1;
  if(line[0]=='t'){auto ip=hex(p);auto parent=hex(p);traces.push_back({ip,parent});}
  else if(line[0]=='+'){auto size=hex(p);auto trace=hex(p);bytes+=size;++calls;if(size>=65536){auto& c=costs[trace];c.bytes+=size;++c.calls;}}
  else if(line[0]=='c')timestamp=hex(p);
  else if(line.rfind("m 1 x ",0)==0){p=line.c_str()+6;base=hex(p);}
 }
 std::vector<std::pair<uint64_t,Cost>> ordered(costs.begin(),costs.end());std::sort(ordered.begin(),ordered.end(),[](auto&a,auto&b){return a.second.bytes>b.second.bytes;});
 std::ofstream out(argv[1]);out<<"{\"total_requested_bytes\":"<<bytes<<",\"allocation_calls\":"<<calls<<",\"last_timestamp\":"<<timestamp<<",\"binary_base\":"<<base<<",\"top\":[";
 for(size_t i=0;i<std::min(size_t(30),ordered.size());++i){if(i)out<<',';auto [t,c]=ordered[i];out<<"{\"bytes\":"<<c.bytes<<",\"calls\":"<<c.calls<<",\"ips\":[";bool first=true;while(t){auto row=traces.at(t);if(!first)out<<',';first=false;out<<row.ip;t=row.parent;}out<<"]}";}
 out<<"]}\n";std::cerr<<"allocations="<<calls<<" bytes="<<bytes<<" timestamp="<<timestamp<<"\n";
}
