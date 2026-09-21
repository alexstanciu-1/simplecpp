#include <scpp/lang/php.hpp>
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_hex_digit.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_hex_escape_byte.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_escape_c_string_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_hex_escape_byte.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_string_literal_global_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_escape_c_string_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_string_literal_global_reference.hpp"
#include "__callable/__latency_fn_llvm_abi_call_lowering_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_string_literal_global_reference.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_echo_string_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_from_cstr_descriptor.hpp"
#include "__callable/__latency_fn_string_runtime_abi_release_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_local_store_literal_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_string_literal_global_reference.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_from_cstr_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_local_release_calls.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_release_descriptor.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_llvm_hex_escape_byte(int_t<> byte) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::llvm_hex_escape_byte", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[106]);
	int_t<> high = required_cast<int_t<>>(cast<int_t<>>((byte / static_cast<int_t<> >(16))));
	int_t<> low = required_cast<int_t<>>((byte % static_cast<int_t<> >(16)));
	return (string_t("\\") + cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_hex_digit(high)) + cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_hex_digit(low)));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_llvm_escape_c_string_bytes(const string_t& literalText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::llvm_escape_c_string_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[107]);
	str::text_builder out = required_cast<str::text_builder>(str::text_builder_create());
	int_t<> length = required_cast<int_t<>>(str::byte_length(literalText));
	str::text_builder_reserve_bytes(out, ((length * static_cast<int_t<> >(4)) + static_cast<int_t<> >(4)));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < length))) {
		int_t<> byte = required_cast<int_t<>>(php::string_byte_at(literalText, index));
		if (static_cast<bool>(php::identical(byte, static_cast<int_t<> >(10)))) {
			str::text_builder_append_string(out, string_t("\\0A"));
		}
		else {
			if (static_cast<bool>(php::identical(byte, static_cast<int_t<> >(13)))) {
				str::text_builder_append_string(out, string_t("\\0D"));
			}
			else {
				if (static_cast<bool>(php::identical(byte, static_cast<int_t<> >(9)))) {
					str::text_builder_append_string(out, string_t("\\09"));
				}
				else {
					if (static_cast<bool>(php::identical(byte, static_cast<int_t<> >(34)))) {
						str::text_builder_append_string(out, string_t("\\22"));
					}
					else {
						if (static_cast<bool>(php::identical(byte, static_cast<int_t<> >(92)))) {
							str::text_builder_append_string(out, string_t("\\5C"));
						}
						else {
							if (static_cast<bool>(((byte >= static_cast<int_t<> >(32)) && (byte <= static_cast<int_t<> >(126))))) {
								str::text_builder_append_string(out, str::byte_slice(literalText, index, static_cast<int_t<> >(1)));
							}
							else {
								str::text_builder_append_string(out, __latency_fn_llvm_text_from_plan_llvm_hex_escape_byte(byte));
							}
						}
					}
				}
			}
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return str::text_builder_take_string(out);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_string_literal_global_text(str::text_builder& lines, int_t<> valueId, shared_p<BackendStringLiteralOperandRow> literal) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_string_literal_global_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[108]);
	int_t<> globalByteCount = required_cast<int_t<>>((cast<int_t<>>(literal->byte_length) + static_cast<int_t<> >(1)));
	str::text_builder_append_string(lines, string_t("@.scpp_string_"));
	str::text_builder_append_int(lines, valueId);
	str::text_builder_append_string(lines, string_t(" = private unnamed_addr constant ["));
	str::text_builder_append_int(lines, globalByteCount);
	str::text_builder_append_string(lines, string_t(" x i8] c\""));
	str::text_builder_append_string(lines, __latency_fn_llvm_text_from_plan_llvm_escape_c_string_bytes(literal->literal_text));
	str::text_builder_append_string(lines, string_t("\\00\"\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_string_literal_global_reference(int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::string_literal_global_reference", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[109]);
	return (string_t("@.scpp_string_") + cast<string_t>(valueId));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(str::text_builder& lines, shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row, const string_t& resultName, const string_t& argumentText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_runtime_abi_call_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[110]);
	string_t callText = required_cast<string_t>(__latency_fn_llvm_abi_call_lowering_call_text(row, resultName, argumentText));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(callText, string_t(""))))) {
		str::text_builder_append_string(lines, (string_t("  ") + cast<string_t>(callText)));
	}
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls(str::text_builder& lines, int_t<> valueId, shared_p<BackendStringLiteralOperandRow> literal) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_echo_string_runtime_calls", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[111]);
	__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id(lines, valueId, valueId, literal);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id(str::text_builder& lines, int_t<> valueId, int_t<> globalId, shared_p<BackendStringLiteralOperandRow> literal) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_echo_string_runtime_calls_from_global_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[112]);
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> literalCtor = __latency_fn_string_runtime_abi_literal_from_cstr_descriptor(bridge);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> echoString = __latency_fn_string_runtime_abi_echo_string_descriptor(bridge);
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> release = __latency_fn_string_runtime_abi_release_descriptor(bridge);
	string_t resultName = required_cast<string_t>(string_t("%runtime_string_"));
	resultName = (cast<string_t>(resultName) + cast<string_t>(valueId));
	string_t literalArgs = required_cast<string_t>((string_t("ptr ") + cast<string_t>(__latency_fn_llvm_text_from_plan_string_literal_global_reference(globalId)) + string_t(", i64 ") + cast<string_t>(cast<int_t<>>(literal->byte_length))));
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, literalCtor, resultName, literalArgs);
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, echoString, string_t(""), (string_t("ptr ") + cast<string_t>(resultName)));
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, release, string_t(""), (string_t("ptr ") + cast<string_t>(resultName)));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_runtime_string_local_store_literal_calls(str::text_builder& lines, int_t<> valueId, int_t<> globalId, int_t<> slotIndex, int_t<> align, shared_p<BackendStringLiteralOperandRow> literal) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_runtime_string_local_store_literal_calls", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[113]);
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> literalCtor = __latency_fn_string_runtime_abi_literal_from_cstr_descriptor(bridge);
	string_t resultName = required_cast<string_t>(string_t("%runtime_string_"));
	resultName = (cast<string_t>(resultName) + cast<string_t>(valueId));
	string_t literalArgs = required_cast<string_t>((string_t("ptr ") + cast<string_t>(__latency_fn_llvm_text_from_plan_string_literal_global_reference(globalId)) + string_t(", i64 ") + cast<string_t>(cast<int_t<>>(literal->byte_length))));
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, literalCtor, resultName, literalArgs);
	str::text_builder_append_string(lines, (string_t("  store ptr ") + cast<string_t>(resultName) + string_t(", ptr ")));
	__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
	str::text_builder_append_string(lines, string_t(", align "));
	str::text_builder_append_int(lines, align);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_runtime_string_local_release_calls(str::text_builder& lines, int_t<> valueId, int_t<> slotIndex, int_t<> align) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_runtime_string_local_release_calls", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[114]);
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> release = __latency_fn_string_runtime_abi_release_descriptor(bridge);
	__latency_fn_llvm_text_from_plan_append_local_load_text(lines, valueId, slotIndex, string_t("ptr"), align);
	__latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(lines, release, string_t(""), (string_t("ptr %local_load_") + cast<string_t>(valueId)));
}

}
