#include "scpp/api_generation.hpp"
#include "value.hpp"
#include "names.hpp"
#include "scpp/detail/string_access.hpp"
#include <set>
#include <functional>

namespace scpp::api_generation
{
	using substitutions = std::map<std::string, std::string>;
	static std::string expand(const std::string &text, const substitutions &context)
	{
		std::string out; std::size_t position=0;
		while (position<text.size()) {
			auto start=text.find("${",position);
			if (start==std::string::npos) { out+=text.substr(position); break; }
			out+=text.substr(position,start-position); auto end=text.find('}',start+2);
			if (end==std::string::npos) throw std::runtime_error("Unclosed metadata substitution");
			auto key=text.substr(start+2,end-start-2); auto found=context.find(key);
			if (found==context.end()) throw std::runtime_error("Unknown metadata substitution: "+key);
			out+=found->second; position=end+1;
		}
		return out;
	}
	static void add_include(std::set<std::string> &headers, const std::string &header)
	{
		if (header.empty()) return;
		if (header.find("..")!=std::string::npos || header.front()=='/' || header.find_first_of("\"\n\r")!=std::string::npos)
			throw std::runtime_error("Invalid header dependency: "+header);
		headers.insert(header);
	}
	static std::string includes(const std::set<std::string> &headers)
	{
		std::string out; for (const auto &header : headers) out+="#include \""+header+"\"\n"; return out;
	}
	static value registry_for(const value &metadata, const value &leaves, const value &names)
	{
		value r=value::object(); r["version"].type=value::kind::number; r["version"].scalar="1"; r["types"]=value::array(); r["families"]=value::object(); r["mappings"]=value::array();
		for (const auto &[key,v] : leaves.fields) r["types"].items.emplace_back(key);
		for (const auto &[key,v] : metadata.at("families").fields) {
			value f=value::object(); f["parameters"]=value::array();
			for (const auto &p : v.at("parameters").items) f["parameters"].items.emplace_back("type");
			f["parameter_names"]=v.at("parameters");
			if (v.has("defaults")) f["defaults"]=v.at("defaults");
			r["families"][key]=f;
		}
		// Published structural names are assertions, not authored aliases. Keep their
        // reserved trailing underscore out of the authored-name registry.
        for (const auto &[key,v] : names.fields) {
            auto name = v.text();
            if (!name.empty() && name.back() == '_') continue;
            // A zero-parameter recipe already has its canonical concrete name.
            // Importing it must not register an alias that shadows its own recipe.
            if (name == key && metadata.at("families").has(key)
                && metadata.at("families").at(key).at("parameters").items.empty()) continue;
            value m=value::object(); m["type"]=key; m["name"]=v; r["mappings"].items.push_back(m);
        }
        naming registry(r);
        for (const auto &[key,v] : names.fields) {
            auto name = v.text();
            if (!name.empty() && name.back() == '_' && registry.resolve(key).second != name)
                throw std::runtime_error("Conflicting structural name: " + key);
        }
		return r;
	}
	class generator final
	{
		value metadata_, request_, imports_, layouts_, leaves_, names_, exports_=value::object(), units_=value::array();
		std::set<std::string> visiting_;
		std::map<std::string,std::string> class_names_;
		naming naming_;
		static value merged_leaves(const value &input)
		{
			auto leaves=input.at("metadata").at("types");
            if (leaves.type!=value::kind::object) throw std::runtime_error("types must be an object");
            for (const auto &[key, leaf] : leaves.fields) {
                fields_only(leaf,"cpp header owner native borrow construct includes capabilities", "leaf "+key);
                leaf.at("cpp").text(); leaf.at("header").text(); leaf.at("owner").text();
            }
			for (const auto &[key,v] : input.at("leaves").fields) {
				fields_only(v,"cpp header owner native borrow construct includes capabilities", "leaf "+key);
                if (leaves.has(key)) for (const auto &[field, item] : v.fields) {
                    if (!leaves.at(key).has(field) || json_text(leaves.at(key).at(field))!=json_text(item))
                        throw std::runtime_error("Conflicting leaf identity: "+key);
                }
				if (!leaves.has(key)) leaves[key]=v;
			}
			return leaves;
		}
		value type(const std::string &text, const std::map<std::string,value> &types)
		{
			if (types.contains(text)) return types.at(text);
			return this->materialize(text);
		}
		void dependencies(const value &record, std::set<std::string> &headers, const substitutions &context)
		{
			if (record.has("includes") && record.at("includes").type!=value::kind::array) throw std::runtime_error("includes must be an array");
            if (record.has("includes")) for (const auto &h : record.at("includes").items) add_include(headers,expand(h.text(),context));
		}
		std::string borrow(const value &type, const std::string &name)
		{
			return expand(type.get("borrow","${value}"), {{"value",name},{"cpp",type.get("cpp")},{"native",type.get("native")}});
		}
        // Capability names are metadata identities; selection never dispatches on a type family.
        void validate_capabilities(const value &record) const
        {
            if (!record.has("capabilities")) return;
            const auto &list = record.at("capabilities");
            if (list.type != value::kind::array) throw std::runtime_error("capabilities must be an array");
            std::set<std::string> seen;
            for (const auto &item : list.items) {
                auto name = item.text();
                if (!this->metadata_.has("capabilities") || !this->metadata_.at("capabilities").has(name))
                    throw std::runtime_error("Unknown capability: " + name);
                if (!seen.insert(name).second) throw std::runtime_error("Duplicate capability: " + name);
            }
        }
        bool selected(const value &record, const std::map<std::string,value> &types) const
        {
            if (!record.has("requires_capabilities")) return true;
            const auto &requirements = record.at("requires_capabilities");
            if (requirements.type != value::kind::object)
                throw std::runtime_error("requires_capabilities must be an object");
            bool available = true;
            for (const auto &[parameter, list] : requirements.fields) {
                if (!types.contains(parameter)) throw std::runtime_error("Unknown capability parameter: " + parameter);
                value required = value::object(); required["capabilities"] = list;
                this->validate_capabilities(required);
                const auto &type = types.at(parameter);
                for (const auto &item : list.items) {
                    bool found = false;
                    if (type.has("capabilities")) for (const auto &capability : type.at("capabilities").items)
                        if (capability.text() == item.text()) found = true;
                    available = available && found;
                }
            }
            return available;
        }
        void capability_checks(const value &requirement, const std::map<std::string,value> &types, value &checks, std::set<std::string> &headers)
        {
            if (!requirement.has("requires_capabilities")) return;
            for (const auto &[parameter, list] : requirement.at("requires_capabilities").fields) {
                const auto &type = types.at(parameter);
                substitutions context{{"cpp",type.at("cpp").text()},{"native",type.get("native",type.at("cpp").text())}};
                for (const auto &item : list.items) {
                    const auto &recipe = this->metadata_.at("capabilities").at(item.text());
                    this->dependencies(recipe,headers,context);
                    for (const auto &check : recipe.at("checks").items) {
                        auto expression = expand(check.text(),context);
                        bool found = false;
                        for (const auto &existing : checks.items) if (existing.text() == expression) found = true;
                        if (!found) checks.items.emplace_back(expression);
                    }
                }
            }
        }
		// A binding body returns its binding result; owned return transport is outside that lambda.
		std::string operation(const value &op, substitutions context, const std::map<std::string,value> &types,
			std::set<std::string> &public_headers, std::set<std::string> &private_headers,
			const std::string &owner, std::string &declaration, value &checks, std::set<std::string> &probe_headers)
		{
			if (op.has("parameters") && op.at("parameters").type!=value::kind::array) throw std::runtime_error("parameters must be an array");
            if (op.has("uses") && op.at("uses").type!=value::kind::array) throw std::runtime_error("uses must be an array");
            fields_only(op,"name kind returns parameters const noexcept explicit expression body transport includes uses requires_capabilities", "operation");
			if (op.has("expression")==op.has("body")) throw std::runtime_error("Operation needs exactly one expression or body");
			if (!this->selected(op,types)) return "";
            this->capability_checks(op,types,checks,probe_headers);
			auto kind=op.get("kind","method");
			if (kind!="method" && kind!="constructor" && kind!="function") throw std::runtime_error("Unknown operation kind");
			if (op.has("uses")) for (const auto &dependency : op.at("uses").items) {
                auto key=expand(dependency.text(),context); auto t=this->type(key,types);
                context["public:"+key]=t.at("cpp").text(); context["native:"+key]=t.get("native",t.at("cpp").text());
                add_include(public_headers,t.get("header"));
            }
            auto name=expand(op.get("name"),context); std::string arguments="(";
			if (op.has("parameters")) for (const auto &p : op.at("parameters").items) {
				fields_only(p,"name type passing", "parameter");
				auto arg=p.at("name").text(); authored_name(arg);
				auto t=this->type(expand(p.at("type").text(),context),types); auto cpp=t.at("cpp").text();
				add_include(public_headers,t.get("header"));
				if (arguments!="(") arguments+=", ";
				auto passing=p.get("passing","value");
				if (passing=="const_ref") arguments+="const "+cpp+" &";
				else if (passing=="ref") arguments+=cpp+" &";
				else if (passing=="move_ref") arguments+=cpp+" &&";
				else if (passing=="value") arguments+=cpp+" ";
				else throw std::runtime_error("Unknown parameter passing: "+passing);
				arguments+=arg;
				context["public:"+arg]=arg; context["native:"+arg]=this->borrow(t,arg);
				if ((op.has("body") ? op.at("body").text() : op.at("expression").text()).find("${native:"+arg+"}")!=std::string::npos)
                    this->dependencies(t,private_headers,context);
            }
			arguments+=")";
			if (op.yes("const")) arguments+=" const";
			if (op.yes("noexcept")) arguments+=" noexcept";
			this->dependencies(op,private_headers,context);
			std::string result, body;
			if (kind=="constructor") {
				name=owner;
				if (!op.has("expression")) throw std::runtime_error("Constructor requires a native argument expression");
				body="::new (this->storage_) "+context.at("native_type")+"("+expand(op.at("expression").text(),context)+");";
			} else {
				auto t=this->type(expand(op.at("returns").text(),context),types); result=t.at("cpp").text(); add_include(public_headers,t.get("header"));
				bool is_void=result=="void";
				auto snippet=op.has("expression") ? (is_void ? "" : "return ")+expand(op.at("expression").text(),context)+";" : expand(op.at("body").text(),context);
				auto factory="[&]() -> decltype(auto) { "+snippet+" }";
				if (is_void) body=snippet;
				else if (op.get("transport","public")=="public") body="return ("+factory+")();";
				else if (op.get("transport")=="native") {
					if (!t.has("construct")) throw std::runtime_error("Missing native return transport for "+result);
					this->dependencies(t,private_headers,context);
					body="return "+expand(t.at("construct").text(),{{"factory",factory},{"value","("+factory+")()"},{"cpp",result},{"native",t.get("native")}})+";";
				} else throw std::runtime_error("Unknown result transport");
			}
			declaration=(op.yes("explicit") ? "explicit " : "")+(result.empty()?"":result+" ")+name+arguments+";\n";
			return (result.empty()?"":result+" ")+(owner.empty()?"":owner+"::")+name+arguments+" { "+body+" }\n";
		}
		void claim(const std::string &name, const std::string &identity)
		{
			if (name.find("__")!=std::string::npos) throw std::runtime_error("Reserved generated name: "+name);
			if (this->class_names_.contains(name) && this->class_names_.at(name)!=identity) throw std::runtime_error("Generated class collision: "+name);
			this->class_names_[name]=identity;
		}
		value materialize(const std::string &text)
		{
			auto [canonical,name]=this->naming_.resolve(text);
			if (this->leaves_.has(canonical)) return this->leaves_.at(canonical);
			if (this->exports_.has(canonical)) return this->exports_.at(canonical);
			if (this->imports_.has(canonical)) { this->exports_[canonical]=this->imports_.at(canonical); return this->exports_.at(canonical); }
			if (!this->visiting_.insert(canonical).second) throw std::runtime_error("Unresolved layout dependency cycle: "+canonical);
			auto node=this->naming_.normalize(parse_type(canonical)); const auto &recipe=this->metadata_.at("families").at(node.name);
			if (recipe.at("parameters").type!=value::kind::array || recipe.at("classes").type!=value::kind::array || recipe.at("classes").items.empty()) throw std::runtime_error("Family requires parameter and nonempty class arrays");
            fields_only(recipe,"parameters defaults binding native includes classes functions checks capabilities requires_capabilities", "family "+node.name);
			substitutions context{{"name",name},{"namebase",name.back()=='_'?name.substr(0,name.size()-1):name}};
			std::map<std::string,value> types;
			std::set<std::string> public_headers,private_headers,probe_headers;
			value dependencies=value::array();
            value requirements=value::array();
			for (std::size_t i=0;i<node.arguments.size();++i) {
				auto t=this->materialize(node.arguments[i].canonical()); auto param=recipe.at("parameters").items.at(i).text();
				context["type:"+param]=node.arguments[i].canonical();
				types[param]=t; context["public:"+param]=t.at("cpp").text(); context["native:"+param]=t.get("native",t.at("cpp").text());
				add_include(public_headers,t.get("header")); add_include(probe_headers,t.get("header")); dependencies.items.emplace_back(node.arguments[i].canonical());
			}
			if (!this->selected(recipe,types)) throw std::runtime_error("Unsatisfied family capabilities: "+canonical);
            this->capability_checks(recipe,types,requirements,probe_headers);
            if (recipe.has("binding")) {
				const auto &binding=this->metadata_.at("bindings").at(recipe.at("binding").text());
				context["binding"]=binding.at("cpp").text(); add_include(probe_headers,binding.at("header").text());
			}
			this->dependencies(recipe,private_headers,context);
            this->dependencies(recipe,probe_headers,context);
			auto native=expand(recipe.at("native").text(),context);
			value record=value::object(); record["name"]=name; record["cpp"]="scpp::"+name; record["header"]="scpp/"+name+".hpp";
			record["owner"]=this->request_.at("package"); record["native"]=native; record["dependencies"]=dependencies;
			record["source"]="include/core/generated/"+name+".cpp";
			record["borrow"]="detail::concrete_access::get<${native}>(${value})";
			record["construct"]="detail::concrete_access::make<${cpp}, ${native}>(${factory})";
			record["ordinary_api"]=this->metadata_.at("version");
            this->validate_capabilities(recipe);
            if (recipe.has("capabilities")) record["capabilities"]=recipe.at("capabilities");
			types["self"]=record;
			context["native_type"]=native;
			std::string header="namespace scpp {\nnamespace detail { struct concrete_access; }\n", source;
			value probes=value::array(); value checks=requirements; bool ready=true;
            if (recipe.has("checks")) {
                if (recipe.at("checks").type!=value::kind::array) throw std::runtime_error("checks must be an array");
                for (const auto &check : recipe.at("checks").items) checks.items.emplace_back(expand(check.text(),context));
            }
			// Declare every companion before any signature refers to it.
            std::set<std::string> class_ids;
			for (const auto &c : recipe.at("classes").items) {
				if (c.at("methods").type!=value::kind::array) throw std::runtime_error("methods must be an array");
                fields_only(c,"id name native lifecycle methods includes", "class");
				auto id=c.at("id").text(), cpp=expand(c.at("name").text(),context);
                if (!class_ids.insert(id).second) throw std::runtime_error("Duplicate class identity: "+id);
                if (id=="self" && cpp!=name) throw std::runtime_error("Primary class name must match selected concrete name");
				this->claim(cpp,canonical+":"+id); header+="class "+cpp+";\n";
				value t=record; t["cpp"]="scpp::"+cpp; t["native"]=expand(c.at("native").text(),context); types[id]=t; context["public:"+id]="scpp::"+cpp; context["native:"+id]=t.at("native").text();
				value reference=t; reference["cpp"]="scpp::"+cpp+" &"; types[id+"_ref"]=reference;
			}
			if (!class_ids.contains("self")) throw std::runtime_error("Missing primary class identity");
			for (const auto &c : recipe.at("classes").items) {
				auto id=c.at("id").text(), cpp=expand(c.at("name").text(),context), native_type=types.at(id).at("native").text();
				auto alias="native_"+cpp; auto key=canonical+":"+id;
				auto local=context; local["native_type"]=alias; local["self"]="detail::concrete_access::get<"+alias+">(*this)";
				this->dependencies(c,probe_headers,context);
				value probe=value::object(); probe["key"]=key; probe["native"]=native_type; probe["lifecycle"]=c.at("lifecycle"); probes.items.push_back(probe);
				std::string size="1",alignment="1";
				if (this->layouts_.has(key)) {
					const auto &layout=this->layouts_.at(key).items; size=layout.at(0).scalar; alignment=layout.at(1).scalar;
					if (id=="self") record["layout"]=this->layouts_.at(key);
				} else ready=false;
				source+="using "+alias+" = "+native_type+";\nstatic_assert(sizeof("+alias+")=="+size+" && alignof("+alias+")=="+alignment+");\n";
				header+="class "+cpp+" final {\n    alignas("+alignment+") unsigned char storage_["+size+"];\n    friend struct detail::concrete_access;\n    "+cpp+"(void (*construct)(void *, const void *), const void *context);\npublic:\n";
				source+=cpp+"::"+cpp+"(void (*construct)(void *, const void *), const void *context) { construct(this->storage_, context); }\n";
				const auto &life=c.at("lifecycle"); fields_only(life,"default copy move destroy", "lifecycle");
				for (const auto &op : {"default","copy","move","destroy"}) if (life.get(op)!="native" && life.get(op)!="disabled") throw std::runtime_error("Unknown lifecycle policy");
				if (life.get("destroy")!="native") throw std::runtime_error("Inline storage requires native destruction");
				if (life.get("default")=="native") { header+="    "+cpp+"();\n"; source+=cpp+"::"+cpp+"() { ::new (this->storage_) "+alias+"(); }\n"; }
				else header+="    "+cpp+"() = delete;\n";
				for (bool move : {false,true}) {
					auto mode=move?"move":"copy"; auto arg=(move?"":"const ")+cpp+(move?" &&other":" &other");
					auto rhs="detail::concrete_access::get<"+alias+">(other)"; if(move) rhs="std::move("+rhs+")";
					auto tail=life.get(mode)=="native"?";\n":" = delete;\n";
					header+="    "+cpp+"("+arg+")"+tail+"    "+cpp+" &operator=("+arg+")"+tail;
					if (life.get(mode)=="native") {
						source+=cpp+"::"+cpp+"("+arg+") { ::new (this->storage_) "+alias+"("+rhs+"); }\n";
						source+=cpp+" &"+cpp+"::operator=("+arg+") { if (this != &other) "+local.at("self")+" = "+rhs+"; return *this; }\n";
					}
				}
				header+="    ~"+cpp+"() noexcept;\n"; source+=cpp+"::~"+cpp+"() noexcept { "+local.at("self")+".~"+alias+"(); }\n";
				source+="static_assert(std::is_nothrow_destructible_v<"+alias+">);\n";
				for (const auto &op : c.at("methods").items) { std::string declaration; source+=this->operation(op,local,types,public_headers,private_headers,cpp,declaration,checks,probe_headers); header+="    "+declaration; }
				header+="};\n";
			}
			if (recipe.has("functions")) for (const auto &op : recipe.at("functions").items) { std::string declaration; source+=this->operation(op,context,types,public_headers,private_headers,"",declaration,checks,probe_headers); header+=declaration; }
			public_headers.erase(record.at("header").text());
			private_headers.insert(probe_headers.begin(),probe_headers.end());
			add_include(private_headers,record.at("header").text()); add_include(private_headers,"scpp/detail/concrete_access.hpp");
			value unit=value::object(); unit["identity"]=canonical; unit["header_path"]="public/"+record.at("header").text(); unit["source_path"]=record.at("source");
			unit["header"]="// Generated by scpp_api_generator.\n#pragma once\n"+includes(public_headers)+header+"}\n";
			unit["source"]="// Generated by scpp_api_generator.\n"+includes(private_headers)+"#include <type_traits>\nnamespace scpp {\n"+source+"}\n";
			unit["probe_includes"]=includes(probe_headers); unit["probes"]=probes; unit["checks"]=checks; unit["ready"]=ready?"yes":"no";
			this->units_.items.push_back(unit); this->exports_[canonical]=record; this->visiting_.erase(canonical); return record;
		}
	public:
		explicit generator(const value &input) : metadata_(input.at("metadata")), request_(input.at("request")), imports_(input.at("imports")), layouts_(input.at("layouts")),
			leaves_(merged_leaves(input)), names_(input.at("names")), naming_(registry_for(metadata_,leaves_,names_)) {}
		value run()
		{
			fields_only(this->metadata_,"version types families bindings functions capabilities", "API metadata");
			if (this->metadata_.at("version").text()!="api-v1") throw std::runtime_error("Unsupported API metadata version");
			if (this->metadata_.has("capabilities")) {
                if (this->metadata_.at("capabilities").type != value::kind::object)
                    throw std::runtime_error("capabilities registry must be an object");
                for (const auto &[name, recipe] : this->metadata_.at("capabilities").fields) {
                    authored_name(name);
                    fields_only(recipe,"checks includes", "capability " + name);
                    if (recipe.at("checks").type != value::kind::array || recipe.at("checks").items.empty())
                        throw std::runtime_error("Capability requires nonempty checks: " + name);
                }
            }
            for (const auto &[key,t] : this->leaves_.fields) { this->validate_capabilities(t); this->claim(t.at("cpp").text(),key); }
			for (const auto &[key,t] : this->imports_.fields) {
				if (this->naming_.resolve(key).second!=t.at("name").text()) throw std::runtime_error("Conflicting concrete name: "+key);
				this->claim(t.at("name").text(),key+":self");
			}
			for (const auto &root : this->request_.at("roots").items) this->materialize(root.text());
			if (this->request_.has("apis")) for (const auto &api : this->request_.at("apis").items) {
				const auto &f=this->metadata_.at("functions").at(api.text());
				fields_only(f,"namespace header source operation includes", "API function");
				std::set<std::string> headers,private_headers,probe_headers; std::string declaration; value checks=value::array();
				auto source=this->operation(f.at("operation"),{}, {},headers,private_headers,"",declaration,checks,probe_headers);
				this->dependencies(f,private_headers,{}); add_include(private_headers,f.at("header").text());
				value unit=value::object(); unit["identity"]=api; unit["header_path"]="public/"+f.at("header").text(); unit["source_path"]=f.at("source"); unit["ready"]="yes";
				unit["header"]="// Generated by scpp_api_generator.\n#pragma once\n"+includes(headers)+"namespace "+f.at("namespace").text()+" {\n"+declaration+"}\n";
				unit["source"]="// Generated by scpp_api_generator.\n"+includes(private_headers)+"namespace "+f.at("namespace").text()+" {\n"+source+"}\n";
				unit["probes"]=value::array(); unit["probe_includes"]=""; this->units_.items.push_back(unit);
			}
			for (const auto &[key,t] : this->imports_.fields) if (!this->exports_.has(key)) this->exports_[key]=t;
			value out=value::object(); out["types"]=this->exports_; out["units"]=this->units_; out["leaves"]=this->leaves_; return out;
		}
	};
	string generate(const string &request)
	{
		auto input=parse_json(detail::string_access::get(request).native_value()); value output;
		if (input.get("action")=="names") {
			naming names(input.at("registry")); output=value::array();
			for (const auto &t : input.at("types").items) { auto [key,name]=names.resolve(t.text()); value row=value::array(); row.items={key,name}; output.items.push_back(row); }
		} else output=generator(input).run();
		auto text=json_text(output); return string(text.data(),uint64(text.size()));
	}
}
