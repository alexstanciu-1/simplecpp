#include "scpp/api_generation.hpp"
#include "value.hpp"
#include "names.hpp"
#include "capabilities.hpp"
#include "operators.hpp"
#include "conversions.hpp"
#include "conditions.hpp"
#include "selections.hpp"
#include "prepared.hpp"
#include "access_paths.hpp"
#include "function_sets.hpp"
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
	class generator final
	{
		value api_imports_, api_exports_=value::object();
        value metadata_, request_, imports_, layouts_, leaves_, names_, exports_=value::object(), units_=value::array();
		std::set<std::string> visiting_;
        std::set<std::string> required_apis_;
		std::map<std::string,std::string> class_names_;
		naming naming_, function_naming_;
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
        void validate_capabilities(const value &record) const
        {
            scpp::api_generation::validate_capabilities(this->metadata_, record);
        }
        bool selected(const value &record, const std::map<std::string,value> &types) const
        {
            return capabilities_selected(this->metadata_, record, types);
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
        value lifecycle(const value &policy, const std::map<std::string,value> &types,
            value &checks, std::set<std::string> &headers)
        {
            fields_only(policy,"default copy move destroy copy_assignment move_assignment noexcept", "lifecycle");
            value resolved = value::object();
            for (const auto *mode : {"default", "copy", "move", "destroy", "copy_assignment", "move_assignment"}) {
                if ((std::string(mode) == "copy_assignment" || std::string(mode) == "move_assignment") && !policy.has(mode)) continue;
                const auto &rule = policy.at(mode);
                if (rule.type == value::kind::object) {
                    fields_only(rule, "requires_capabilities", "conditional lifecycle");
                    if (!rule.has("requires_capabilities") || rule.at("requires_capabilities").fields.empty())
                        throw std::runtime_error("Conditional lifecycle requires capabilities");
                    const bool enabled = this->selected(rule, types);
                    resolved[mode] = enabled ? "native" : "disabled";
                    if (enabled) this->capability_checks(rule, types, checks, headers);
                } else {
                    if (rule.text() != "native" && rule.text() != "disabled")
                        throw std::runtime_error("Unknown lifecycle policy");
                    resolved[mode] = rule;
                }
            }
            if (resolved.get("destroy") != "native")
                throw std::runtime_error("Inline storage requires native destruction");
            if (policy.has("noexcept")) {
                if (policy.at("noexcept").type != value::kind::array)
                    throw std::runtime_error("Lifecycle noexcept must be an array");
                value selected_modes = value::array();
                std::set<std::string> seen;
                for (const auto &item : policy.at("noexcept").items) {
                    const auto mode = item.text();
                    if (mode != "default" && mode != "copy" && mode != "move"
                        && mode != "copy_assignment" && mode != "move_assignment")
                        throw std::runtime_error("Unknown lifecycle noexcept operation: " + mode);
                    if (!seen.insert(mode).second)
                        throw std::runtime_error("Duplicate lifecycle noexcept operation: " + mode);
                    const auto fallback = mode == "copy_assignment" ? "copy" : mode == "move_assignment" ? "move" : mode;
                    if (resolved.get(mode, resolved.get(fallback)) == "native") selected_modes.items.push_back(item);
                }
                resolved["noexcept"] = selected_modes;
            }
            return resolved;
        }
        std::string scoped_condition_body(const value &plan, const std::string &operand,
            std::set<std::string> &headers)
        {
            const auto kind = plan.at("kind").text();
            if (kind=="constant_after_evaluation")
                return "return scpp::boolean(" + json_text(plan.at("value")) + ");";
            if (kind=="member_call") {
                add_include(headers,plan.at("header").text());
                return "return " + operand + "." + plan.at("method").text() + "();";
            }
            if (kind=="call") {
                this->required_apis_.insert(plan.at("api").text());
                add_include(headers,plan.at("header").text());
                return "return " + plan.at("function").text() + "(" + operand + ");";
            }
            if (kind!="carrier_condition" || plan.get("payload_access")=="owned_value")
                throw std::runtime_error("Scoped condition cannot extract an owned payload");
            std::string body;
            for (const auto &state : plan.at("states").items) {
                std::string branch;
                if (state.get("condition")!="payload")
                    branch = "return scpp::boolean(" + state.at("condition").text() + ");";
                else if (plan.get("payload_access")=="none")
                    branch = this->scoped_condition_body(plan.at("payload_condition"),operand,headers);
                else branch = "return " + expand(plan.at("payload_expression").text(),{{"value",operand}}) + ";";
                if (state.has("test_expression"))
                    body += "if (" + expand(state.at("test_expression").text(),{{"value",operand}}) + ") { " + branch + " }\n";
                else body += branch;
            }
            return body;
        }
		// A binding body returns its binding result; owned return transport is outside that lambda.
		std::string operation(const value &op, substitutions context, const std::map<std::string,value> &types,
			std::set<std::string> &public_headers, std::set<std::string> &private_headers,
			const std::string &owner, std::string &declaration, value &checks, std::set<std::string> &probe_headers)
		{
            if (op.has("condition_observation")) {
                if (owner.empty() || op.get("returns")!="boolean" || !op.yes("const")
                    || op.yes("static") || op.has("body") || !op.has("expression")
                    || !op.at("parameters").items.empty() || op.has("transport"))
                    throw std::runtime_error("Condition observation requires a scoped const boolean method");
                const auto parameter = op.at("condition_observation").text();
                const auto found = context.find("type:"+parameter);
                if (found==context.end()) throw std::runtime_error("Unknown condition observation parameter");
                auto observation_metadata = this->metadata_; observation_metadata["types"] = this->leaves_;
                const auto observation = scoped_condition(observation_metadata, found->second, this->names_);
                value resolved = op; resolved.fields.erase("condition_observation");
                // Validate unknown metadata even when the operation is unavailable.
                fields_only(resolved,"name kind returns parameters const static noexcept explicit expression body transport includes uses requires_capabilities", "operation");
                if (observation.type==value::kind::null || observation.get("kind")=="constant_after_evaluation") return "";
                const auto operand = op.at("expression").text();
                resolved.fields.erase("expression");
                resolved["body"] = "static_assert(std::is_same_v<decltype((" + operand + ")), const ${public:" + parameter + "} &>);\nconst auto &payload = " + operand + ";\n" + this->scoped_condition_body(observation,"payload",private_headers);
                return this->operation(resolved,context,types,public_headers,private_headers,owner,declaration,checks,probe_headers);
            }
			if (op.has("parameters") && op.at("parameters").type!=value::kind::array) throw std::runtime_error("parameters must be an array");
            if (op.has("uses") && op.at("uses").type!=value::kind::array) throw std::runtime_error("uses must be an array");
            fields_only(op,"name kind returns parameters const static noexcept explicit expression body transport includes uses requires_capabilities", "operation");
			if (op.has("expression")==op.has("body")) throw std::runtime_error("Operation needs exactly one expression or body");
			if (!this->selected(op,types)) return "";
            this->capability_checks(op,types,checks,probe_headers);
			auto kind=op.get("kind","method");
			if (kind!="method" && kind!="constructor" && kind!="function") throw std::runtime_error("Unknown operation kind");
            if (op.has("static") && op.at("static").type!=value::kind::boolean)
                throw std::runtime_error("static must be boolean");
            if (op.yes("static")) {
                if (owner.empty() || kind!="method" || op.yes("const") || op.yes("explicit"))
                    throw std::runtime_error("Static operation must be a non-const class method");
                context.erase("self");
            }
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
			declaration=std::string(op.yes("static") ? "static " : "")+(op.yes("explicit") ? "explicit " : "")+(result.empty()?"":result+" ")+name+arguments+";\n";
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
			auto application=this->naming_.bind(canonical); const auto &recipe=this->metadata_.at("families").at(application.type.name);
			if (recipe.at("parameters").type!=value::kind::array || recipe.at("classes").type!=value::kind::array || recipe.at("classes").items.empty()) throw std::runtime_error("Family requires parameter and nonempty class arrays");
            fields_only(recipe,"parameters defaults binding native includes classes functions checks capabilities conditional_capabilities requires_capabilities carrier access", "family "+application.type.name);
			substitutions context{{"name",name},{"namebase",name.back()=='_'?name.substr(0,name.size()-1):name}};
			std::map<std::string,value> types;
			std::set<std::string> public_headers,private_headers,probe_headers;
			value dependencies=value::array();
            value requirements=value::array();
			for (const auto &[param, argument] : application.arguments) {
				auto t=this->materialize(argument.canonical());
				context["type:"+param]=argument.canonical();
				types[param]=t; context["public:"+param]=t.at("cpp").text(); context["native:"+param]=t.get("native",t.at("cpp").text());
				add_include(public_headers,t.get("header")); add_include(probe_headers,t.get("header")); dependencies.items.emplace_back(argument.canonical());
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
            const auto capabilities=effective_capabilities(this->metadata_,recipe,types);
            if (!capabilities.items.empty()) record["capabilities"]=capabilities;
            if (recipe.has("conditional_capabilities"))
                for (const auto &[capability, condition] : recipe.at("conditional_capabilities").fields)
                    if (this->selected(condition,types)) this->capability_checks(condition,types,requirements,probe_headers);
            // Native argument/result transports need their representation headers even
            // when emitted in a free-function unit rather than the owning class unit.
            record["includes"]=value::array();
            auto transport_headers=probe_headers;
            transport_headers.insert(private_headers.begin(),private_headers.end());
            transport_headers.insert("scpp/detail/concrete_access.hpp");
            for (const auto &header : transport_headers) record["includes"].items.emplace_back(header);
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
                fields_only(c,"id name native lifecycle methods includes requires_capabilities", "class");
				auto id=c.at("id").text(), cpp=expand(c.at("name").text(),context);
                if (!class_ids.insert(id).second) throw std::runtime_error("Duplicate class identity: "+id);
                if (id=="self" && cpp!=name) throw std::runtime_error("Primary class name must match selected concrete name");
                if (id=="self" && c.has("requires_capabilities")) throw std::runtime_error("Primary class cannot be conditional");
                if (!this->selected(c,types)) continue;
                this->capability_checks(c,types,checks,probe_headers);
				this->claim(cpp,canonical+":"+id); header+="class "+cpp+";\n";
				value t=record; if (id!="self") t.fields.erase("capabilities"); t["cpp"]="scpp::"+cpp; t["native"]=expand(c.at("native").text(),context); types[id]=t; context["public:"+id]="scpp::"+cpp; context["native:"+id]=t.at("native").text();
				value reference=t; reference["cpp"]="scpp::"+cpp+" &"; types[id+"_ref"]=reference;
			}
			if (!class_ids.contains("self")) throw std::runtime_error("Missing primary class identity");
			for (const auto &c : recipe.at("classes").items) {
                if (!this->selected(c,types)) continue;
				auto id=c.at("id").text(), cpp=expand(c.at("name").text(),context), native_type=types.at(id).at("native").text();
				auto alias="native_"+cpp; auto key=canonical+":"+id;
				auto local=context; local["native_type"]=alias; local["self"]="detail::concrete_access::get<"+alias+">(*this)";
				this->dependencies(c,probe_headers,context);
                const auto life=this->lifecycle(c.at("lifecycle"),types,checks,probe_headers);
				value probe=value::object(); probe["key"]=key; probe["native"]=native_type; probe["lifecycle"]=life; probes.items.push_back(probe);
				std::string size="1",alignment="1";
				if (this->layouts_.has(key)) {
					const auto &layout=this->layouts_.at(key).items; size=layout.at(0).scalar; alignment=layout.at(1).scalar;
					if (id=="self") record["layout"]=this->layouts_.at(key);
				} else ready=false;
				source+="using "+alias+" = "+native_type+";\nstatic_assert(sizeof("+alias+")=="+size+" && alignof("+alias+")=="+alignment+");\n";
				header+="class "+cpp+" final {\n    alignas("+alignment+") unsigned char storage_["+size+"];\n    friend struct detail::concrete_access;\n    "+cpp+"(void (*construct)(void *, const void *), const void *context);\npublic:\n";
				source+=cpp+"::"+cpp+"(void (*construct)(void *, const void *), const void *context) { construct(this->storage_, context); }\n";
                const auto exception_spec = [&](const std::string &mode) -> std::string {
                    if (life.has("noexcept")) for (const auto &item : life.at("noexcept").items)
                        if (item.text() == mode) return " noexcept";
                    return "";
                };
                if (life.has("noexcept")) for (const auto &item : life.at("noexcept").items) {
                    const auto mode = item.text();
                    const auto trait = mode == "default" ? "default_constructible" : mode == "copy" ? "copy_constructible"
                        : mode == "move" ? "move_constructible" : mode == "copy_assignment" ? "copy_assignable" : "move_assignable";
                    source += "static_assert(std::is_nothrow_" + std::string(trait) + "_v<" + alias + ">);\n";
                }
                if (life.get("default")=="native") { header+="    "+cpp+"()"+exception_spec("default")+";\n"; source+=cpp+"::"+cpp+"()"+exception_spec("default")+" { ::new (this->storage_) "+alias+"(); }\n"; }
				else header+="    "+cpp+"() = delete;\n";
				for (bool move : {false,true}) {
					auto mode=move?"move":"copy"; auto arg=(move?"":"const ")+cpp+(move?" &&other":" &other");
					auto rhs="detail::concrete_access::get<"+alias+">(other)"; if(move) rhs="std::move("+rhs+")";
					const auto assignment=life.get(move ? "move_assignment" : "copy_assignment", life.get(mode));
					auto tail=life.get(mode)=="native"?";\n":" = delete;\n";
					auto assignment_tail=assignment=="native"?";\n":" = delete;\n";
					header+="    "+cpp+"("+arg+")"+exception_spec(mode)+tail+"    "+cpp+" &operator=("+arg+")"+exception_spec(move ? "move_assignment" : "copy_assignment")+assignment_tail;
					if (life.get(mode)=="native")
						source+=cpp+"::"+cpp+"("+arg+")"+exception_spec(mode)+" { ::new (this->storage_) "+alias+"("+rhs+"); }\n";
					if (assignment=="native")
						source+=cpp+" &"+cpp+"::operator=("+arg+")"+exception_spec(move ? "move_assignment" : "copy_assignment")+" { if (this != &other) "+local.at("self")+" = "+rhs+"; return *this; }\n";
				}
				header+="    ~"+cpp+"() noexcept;\n"; source+=cpp+"::~"+cpp+"() noexcept { "+local.at("self")+".~"+alias+"(); }\n";
				source+="static_assert(std::is_nothrow_destructible_v<"+alias+">);\n";
				for (const auto &op : c.at("methods").items) { std::string declaration; source+=this->operation(op,local,types,public_headers,private_headers,cpp,declaration,checks,probe_headers); if (!declaration.empty()) header+="    "+declaration; }
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
        value demanded_path(const std::string &name, const value &request)
        {
            authored_name(name);
            if (name.find("::") != std::string::npos) throw std::runtime_error("Access path name must be unqualified");
            const auto path = describe_access_path(this->metadata_, this->leaves_, this->names_, request);
            const bool creating = path.get("operation") == "store";
            const bool key_check = path.get("operation") == "key_check";
            const bool storing = creating || path.get("operation") == "existing_store";
            const bool erasing = path.get("operation") == "existing_erase";
            const bool probing = path.get("operation") == "prefix_ready";
            const bool observing = path.get("operation") == "observe";
            const bool writing = storing || erasing;
            value function = value::object(); function["namespace"] = "scpp";
            function["header"] = "scpp/access/" + name + ".hpp";
            function["source"] = "include/core/generated/" + name + ".cpp";
            const auto symbol = "path_" + name;
            this->claim(symbol, "path-api:" + name);
            value operation = value::object(); operation["name"] = symbol;
            operation["returns"] = (writing || key_check) ? value("void") : (probing || observing) ? value("boolean") : path.at("element");
            operation["parameters"] = value::array();
            auto parameter = [&](const std::string &id, const value &type, const std::string &passing) {
                value entry = value::object(); entry["name"] = id; entry["type"] = type; entry["passing"] = passing;
                operation["parameters"].items.push_back(entry);
            };
            if (!key_check) parameter("root", path.at("root"), writing ? "ref" : "const_ref");
            std::set<std::string> headers{"type_traits", "utility", "scpp/detail/concrete_access.hpp"};
            if (key_check) {
                const auto &step = path.at("steps").items.back();
                const auto key_type = this->materialize(step.at("key").text());
                parameter("key", step.at("key"), "const_ref");
                this->dependencies(key_type, headers, {});
                this->dependencies(step, headers, {});
                operation["body"] = expand(step.at("key_check").text(),
                    {{"key", "key"}, {"native_key", this->borrow(key_type, "key")}}) + ";";
                operation["includes"] = value::array();
                for (const auto &header : headers) operation["includes"].items.emplace_back(header);
                function["operation"] = operation;
                return function;
            }
            this->dependencies(this->materialize(path.at("root").text()), headers, {});
            if (observing) this->dependencies(path.at("observation"), headers, {});
            if (probing) this->dependencies(path.at("ready_protocol"), headers, {});
            unsigned index = 0;
            for (const auto &step : path.at("steps").items) {
                parameter("key_" + std::to_string(index++), step.at("key"), "const_ref");
                const auto receiver = this->materialize(step.at("receiver").text());
                this->dependencies(receiver, headers, {});
                this->dependencies(step, headers, {});
                const auto element = this->materialize(step.at("element").text());
                if (observing) this->dependencies(element, headers, {});
            }
            if (storing) parameter("value", path.at("element"), "value");
            const auto &steps = path.at("steps").items;
            if (path.get("operation") == "value_read") {
                const auto &step = steps.front();
                const auto key_type = this->materialize(step.at("key").text());
                this->dependencies(key_type, headers, {});
                operation["expression"] = expand(step.at("value_read").text(),
                    {{"receiver", "std::as_const(${native:root})"}, {"key", "key_0"},
                     {"native_key", this->borrow(key_type, "key_0")}});
                if (step.get("element_storage", "public") == "native") operation["transport"] = "native";
                operation["includes"] = value::array();
                for (const auto &header : headers) operation["includes"].items.emplace_back(header);
                function["operation"] = operation;
                return function;
            }
            if (!writing && !probing && !observing && steps.back().get("element_storage", "public") == "native")
                operation["transport"] = "native";
            auto traverse = [&](bool mutable_access) {
                std::string body;
                std::string current = mutable_access ? "${native:root}" : "std::as_const(${native:root})";
                if (steps.empty()) return "return boolean(" + expand(path.at("ready").text(),
                    {{"receiver", current}, {"removing", path.at("removing").text()}}) + ");\n";
                for (std::size_t i = 0; i < steps.size(); ++i) {
                    const auto &step = steps[i];
                    const auto element = this->materialize(step.at("element").text());
                    const bool native_element = step.get("element_storage", "public") == "native";
                    const auto key = "key_" + std::to_string(i);
                    const auto key_type = this->materialize(step.at("key").text());
                    this->dependencies(key_type, headers, {});
                    const auto stored_value = native_element ? this->borrow(element, "value") : "value";
                    const substitutions access_context{{"receiver", current}, {"key", key},
                        {"native_key", this->borrow(key_type, key)}, {"value", stored_value}, {"removing", path.at("removing").text()}};
                    const auto read = creating && i + 1 < steps.size() ? step.get("write_read", step.at("read").text()) : step.at("read").text();
                    const auto expression = expand(mutable_access ? step.at("mutable").text() : read, access_context);
                    if (!mutable_access && (probing || erasing || observing)) {
                        const auto present = expand(step.at("present").text(), access_context);
                        body += "if (!(" + present + ")) { " + (observing ? path.at("observation").at("missing").text() : probing ? "return boolean(false);" : "return;") + " }\n";
                    }
                    body += "static_assert(std::is_same_v<decltype((" + expression + ")), "
                        + (mutable_access ? "" : "const ") + (native_element ? element.at("native").text() : element.at("cpp").text()) + " &>);\n";
                    if (i + 1 == steps.size()) {
                        if (creating) body += expand(step.at(mutable_access ? "store" : "check_store").text(), access_context) + ";\n";
                        else if (mutable_access && erasing) body += expand(step.at("erase").text(), access_context) + ";\n";
                        else if (mutable_access) body += expression + " = " + (native_element ? this->borrow(element, "value") : "value") + ";\n";
                        else if (observing) {
                            body += "const auto &path_leaf = " + expression + ";\n";
                            substitutions observation_context{{"native", native_element ? "path_leaf" : this->borrow(element, "path_leaf")}};
                            if (!native_element) observation_context["value"] = "path_leaf";
                            body += "return boolean(" + expand(path.at("observation").at("expression").text(), observation_context) + ");\n";
                        }
                        else if (probing) {
                            body += "const auto &path_leaf = " + expression + ";\n";
                            body += "return boolean(" + expand(path.at("ready").text(),
                                {{"receiver", native_element ? "path_leaf" : this->borrow(element, "path_leaf")}, {"removing", path.at("removing").text()}}) + ");\n";
                        }
                        else body += (writing ? "(void)(" + expression + ");\n" : "return " + expression + ";\n");
                    } else {
                        const auto slot = "path_value_" + std::to_string(i);
                        body += std::string(mutable_access ? "auto &" : "const auto &") + slot + " = " + expression + ";\n";
                        current = native_element ? slot : this->borrow(element, slot);
                    }
                }
                return body;
            };
            operation["body"] = writing ? "{\n" + traverse(false) + "}\n" + traverse(true) : traverse(false);
            operation["includes"] = value::array();
            for (const auto &header : headers) operation["includes"].items.emplace_back(header);
            function["operation"] = operation;
            return function;
        }
        value demanded_function(const std::string &request, std::string &identity)
        {
            const auto application = this->function_naming_.bind(request);
            const auto &family = this->metadata_.at("function_families").at(application.type.name);
            identity = application.type.canonical();
            const auto name = this->function_naming_.resolve(identity).second;
            value bindings = value::object();
            for (const auto &[parameter, argument] : application.arguments) {
                const auto canonical = argument.canonical();
                const auto type = this->materialize(canonical);
                if (bindings.has(parameter) || bindings.has("public_" + parameter) || bindings.has("native_" + parameter))
                    throw std::runtime_error("Conflicting function parameter substitution: " + parameter);
                bindings[parameter] = canonical;
                bindings["public_" + parameter] = type.at("cpp");
                bindings["native_" + parameter] = type.get("native", type.at("cpp").text());
            }
            return instantiate_function(family.at("function"), name, bindings);
        }
	public:
		explicit generator(const value &input) : api_imports_(input.has("api_imports") ? input.at("api_imports") : value::object()), metadata_(input.at("metadata")), request_(input.at("request")), imports_(input.at("imports")), layouts_(input.at("layouts")),
			leaves_(merged_type_leaves(input)), names_(input.at("names")), naming_(metadata_naming_registry(metadata_,leaves_,names_)),
            function_naming_(function_naming_registry(metadata_,leaves_,names_)) {}
		value run()
		{
			fields_only(this->metadata_,"version types families bindings functions capabilities global_operators conversions function_sets function_families constant_sets conditions selections value_observations", "API metadata");
			if (this->metadata_.at("version").text()!="api-v1") throw std::runtime_error("Unsupported API metadata version");
            for (const auto &[key,t] : this->leaves_.fields) { this->validate_capabilities(t); this->claim(t.at("cpp").text(),key); }
            validate_import_capabilities(this->metadata_,this->leaves_,this->names_,this->imports_);
			for (const auto &[key,t] : this->imports_.fields) {
				if (this->naming_.resolve(key).second!=t.at("name").text()) throw std::runtime_error("Conflicting concrete name: "+key);
				this->claim(t.at("name").text(),key+":self");
			}
			for (const auto &root : this->request_.at("roots").items) this->materialize(root.text());
			validate_conditions(this->metadata_);
            validate_selections(this->metadata_);
			auto generated_apis=operator_functions(this->metadata_);
            for (const auto &[name, function] : conversion_functions(this->metadata_).fields) {
                if (generated_apis.has(name)) throw std::runtime_error("Duplicate generated API ownership");
                generated_apis[name] = function;
            }
            for (const auto &[name, function] : expand_function_sets(this->metadata_).fields) {
                if (generated_apis.has(name)) throw std::runtime_error("Duplicate generated API ownership");
                generated_apis[name] = function;
            }
            if (this->metadata_.has("constant_sets")) {
                if (this->metadata_.at("constant_sets").type != value::kind::object)
                    throw std::runtime_error("constant_sets must be an object");
                for (const auto &[name, constants] : this->metadata_.at("constant_sets").fields) {
                    if (generated_apis.has(name) || this->metadata_.at("functions").has(name))
                        throw std::runtime_error("Duplicate generated API ownership");
                    fields_only(constants,"namespace header source constants", "API constant set");
                    generated_apis[name] = constants;
                }
            }
            auto apis = this->request_.has("apis") ? this->request_.at("apis") : value::array();
            if (apis.type != value::kind::array) throw std::runtime_error("API demands require an array");
            if (this->request_.has("paths") && this->request_.at("paths").type != value::kind::object)
                throw std::runtime_error("Access path demands require a map");
            for (std::size_t index=0; index<apis.items.size() || !this->required_apis_.empty(); ++index) {
                if (index==apis.items.size()) {
                    for (const auto &dependency : this->required_apis_) apis.items.emplace_back(dependency);
                    this->required_apis_.clear();
                }
                auto api = apis.items[index];
                if (this->request_.has("paths") && this->request_.at("paths").has(api.text())) {
                    const auto function = this->demanded_path(api.text(), this->request_.at("paths").at(api.text()));
                    if ((generated_apis.has(api.text()) && json_text(generated_apis.at(api.text())) != json_text(function))
                        || this->metadata_.at("functions").has(api.text()))
                        throw std::runtime_error("Conflicting path API identity: " + api.text());
                    generated_apis[api.text()] = function;
                }
                const auto requested = parse_type(api.text());
                if (this->metadata_.has("function_families") && this->metadata_.at("function_families").has(requested.name)) {
                    std::string identity;
                    const auto function = this->demanded_function(api.text(), identity);
                    if (generated_apis.has(identity) && json_text(generated_apis.at(identity)) != json_text(function))
                        throw std::runtime_error("Conflicting demanded API identity: " + identity);
                    api = identity; generated_apis[identity] = function;
                }
                if (generated_apis.has(api.text()) && this->metadata_.at("functions").has(api.text()))
                    throw std::runtime_error("Duplicate global operator ownership");
                const auto &f=generated_apis.has(api.text()) ? generated_apis.at(api.text()) : this->metadata_.at("functions").at(api.text());
				fields_only(f,"namespace header source operation includes constants", "API declaration");
                if (f.has("operation") == f.has("constants"))
                    throw std::runtime_error("API declaration requires one function or constant set");
                if (this->api_exports_.has(api.text())) continue;
                this->api_exports_[api.text()]=f;
                if (this->api_imports_.has(api.text())) {
                    if (json_text(this->api_imports_.at(api.text())) != json_text(f))
                        throw std::runtime_error("Conflicting API ownership: "+api.text());
                    continue;
                }
				std::set<std::string> headers,private_headers,probe_headers; std::string declaration; value checks=value::array();
				std::string source;
                if (f.has("constants")) {
                    const auto &constants = f.at("constants");
                    if (constants.type != value::kind::array || constants.items.empty())
                        throw std::runtime_error("Constant set requires nonempty constants");
                    std::set<std::string> names;
                    for (const auto &constant : constants.items) {
                        fields_only(constant,"name type expression", "API constant");
                        const auto name = constant.at("name").text();
                        authored_name(name);
                        if (!names.insert(name).second) throw std::runtime_error("Duplicate constant name: " + name);
                        const auto expression = constant.at("expression").text();
                        if (expression.empty()) throw std::runtime_error("Constant requires expression");
                        const auto type = this->type(constant.at("type").text(),{});
                        add_include(headers,type.get("header"));
                        declaration += "inline constexpr " + type.at("cpp").text() + " " + name + "{" + expression + "};\n";
                    }
                } else source=this->operation(f.at("operation"),{}, {},headers,private_headers,"",declaration,checks,probe_headers);
				this->dependencies(f,private_headers,{}); add_include(private_headers,f.at("header").text());
				value unit=value::object(); unit["identity"]=api; unit["header_path"]="public/"+f.at("header").text(); unit["source_path"]=f.at("source"); unit["ready"]="yes";
				unit["header"]="// Generated by scpp_api_generator.\n#pragma once\n"+includes(headers)+"namespace "+f.at("namespace").text()+" {\n"+declaration+"}\n";
				unit["source"]="// Generated by scpp_api_generator.\n"+includes(private_headers)+"namespace "+f.at("namespace").text()+" {\n"+source+"}\n";
				unit["probes"]=value::array(); unit["probe_includes"]=""; this->units_.items.push_back(unit);
			}
            std::set<std::string> output_paths;
            for (const auto &unit : this->units_.items) {
                if (unit.at("header").text().size() >= 200000 || unit.at("source").text().size() >= 200000)
                    throw std::runtime_error("Generated code file must remain below 200KB");
                for (const auto *field : {"header_path", "source_path"}) {
                    if (!output_paths.insert(unit.at(field).text()).second)
                        throw std::runtime_error("Conflicting generated output path: " + unit.at(field).text());
                }
            }
			for (const auto &[key,t] : this->imports_.fields) if (!this->exports_.has(key)) this->exports_[key]=t;
			value out=value::object(); out["types"]=this->exports_; out["units"]=this->units_; out["leaves"]=this->leaves_;
            for (const auto &[key,api] : this->api_imports_.fields) if (!this->api_exports_.has(key)) this->api_exports_[key]=api;
            out["apis"]=this->api_exports_; return out;
		}
	};
	string generate(const string &request)
	{
		auto input=parse_json(detail::string_access::get(request).native_value()); value output;
        const auto prepared = validate_prepared_request(input);
        if (input.get("action").starts_with("resolve_")) {
            const auto leaves=merged_type_leaves(input);
            if (input.has("imports")) validate_import_capabilities(input.at("metadata"),leaves,
                input.has("names") ? input.at("names") : value::object(),input.at("imports"));
            input["metadata"]["types"]=leaves;
        }
		if (input.get("action")=="resolve_logical") {
            output=resolve_logical(input.at("metadata"),input.at("request"), input.has("names") ? input.at("names") : value::object());
        } else if (input.get("action")=="resolve_conditional") {
            output=resolve_conditional(input.at("metadata"),input.at("request"), input.has("names") ? input.at("names") : value::object());
        } else if (input.get("action")=="resolve_coalesce") {
            output=resolve_coalesce(input.at("metadata"),input.at("request"), input.has("names") ? input.at("names") : value::object());
        } else if (input.get("action")=="resolve_condition") {
            output=resolve_condition(input.at("metadata"),input.at("request"), input.has("names") ? input.at("names") : value::object());
        } else if (input.get("action")=="resolve_conversion") {
            output=resolve_conversion(input.at("metadata"),input.at("request"), input.has("names") ? input.at("names") : value::object());
        } else if (input.get("action")=="resolve_compound") {
            output=resolve_compound(input.at("metadata"),input.at("request"), input.has("names") ? input.at("names") : value::object());
        } else if (input.get("action")=="resolve_operator") {
            output=resolve_operator(input.at("metadata"),input.at("request"), input.has("names") ? input.at("names") : value::object());
        } else if (input.get("action")=="names") {
			naming names(input.at("registry")); output=value::array();
			for (const auto &t : input.at("types").items) { auto [key,name]=names.resolve(t.text()); value row=value::array(); row.items={key,name}; output.items.push_back(row); }
		} else output=generator(input).run();
		if (prepared.type != value::kind::null) output["preparation"] = prepared;
		auto text=json_text(output); return string(text.data(),uint64(text.size()));
	}
}
