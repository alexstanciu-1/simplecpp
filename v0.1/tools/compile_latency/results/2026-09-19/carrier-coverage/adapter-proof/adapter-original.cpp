#include "/tmp/scpp-edit-latency-20260919/app/.prism/generated/__project_units.hpp"
#include <cstdio>
using namespace scpp;
int main() {
{
    bool_t typed(false);
    auto& direct = backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerCandidateReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerCandidateReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerCandidateReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerCandidateReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $objectOutputWorkerCandidateReady") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerSelected(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerSelected(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerSelected: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)backend_object_link_cache::_norm_record_object_output_publication_metrics__objectOutputWorkerSelected(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $objectOutputWorkerSelected") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = compiler_entry_backend::_norm_append_assignment_backend_requests_in_source_range__binaryIndex(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)compiler_entry_backend::_norm_append_assignment_backend_requests_in_source_range__binaryIndex(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") { std::fprintf(stderr, "compiler_entry_backend::_norm_append_assignment_backend_requests_in_source_range__binaryIndex: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)compiler_entry_backend::_norm_append_assignment_backend_requests_in_source_range__binaryIndex(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = compiler_entry_backend::_norm_append_assignment_backend_request_by_source_row_id__binaryIndex(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)compiler_entry_backend::_norm_append_assignment_backend_request_by_source_row_id__binaryIndex(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") { std::fprintf(stderr, "compiler_entry_backend::_norm_append_assignment_backend_request_by_source_row_id__binaryIndex: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)compiler_entry_backend::_norm_append_assignment_backend_request_by_source_row_id__binaryIndex(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfEndLabel(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfEndLabel(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfEndLabel: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfEndLabel(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $activeIfEndLabel") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfElseLabel(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfElseLabel(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfElseLabel: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)llvm_text_from_plan::_norm_append_if_end_after_body_last__activeIfElseLabel(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $activeIfElseLabel") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfEndLabel(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfEndLabel(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfEndLabel: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfEndLabel(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $activeIfEndLabel") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfElseLabel(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfElseLabel(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfElseLabel: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)llvm_text_from_plan::_norm_append_after_return_branch_boundary__activeIfElseLabel(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $activeIfElseLabel") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = llvm_text_from_plan::_norm_append_after_return_branch_boundary__returned(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)llvm_text_from_plan::_norm_append_after_return_branch_boundary__returned(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "llvm_text_from_plan::_norm_append_after_return_branch_boundary__returned: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)llvm_text_from_plan::_norm_append_after_return_branch_boundary__returned(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $returned") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = llvm_text_from_plan::_norm_record_emission_llvm_publication_metrics__emissionLLVMWorkerCandidateReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)llvm_text_from_plan::_norm_record_emission_llvm_publication_metrics__emissionLLVMWorkerCandidateReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "llvm_text_from_plan::_norm_record_emission_llvm_publication_metrics__emissionLLVMWorkerCandidateReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)llvm_text_from_plan::_norm_record_emission_llvm_publication_metrics__emissionLLVMWorkerCandidateReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $emissionLLVMWorkerCandidateReady") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = lowering_plan::_norm_record_backend_lowering_publication_metrics__backendLoweringWorkerCandidateReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)lowering_plan::_norm_record_backend_lowering_publication_metrics__backendLoweringWorkerCandidateReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "lowering_plan::_norm_record_backend_lowering_publication_metrics__backendLoweringWorkerCandidateReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)lowering_plan::_norm_record_backend_lowering_publication_metrics__backendLoweringWorkerCandidateReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $backendLoweringWorkerCandidateReady") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scalar_loop_backend_requests::_norm_append_while_statement_sequence_requests__binaryIndex(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scalar_loop_backend_requests::_norm_append_while_statement_sequence_requests__binaryIndex(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") { std::fprintf(stderr, "scalar_loop_backend_requests::_norm_append_while_statement_sequence_requests__binaryIndex: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scalar_loop_backend_requests::_norm_append_while_statement_sequence_requests__binaryIndex(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scalar_loop_backend_requests::_norm_append_for_statement_sequence_requests__binaryIndex(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scalar_loop_backend_requests::_norm_append_for_statement_sequence_requests__binaryIndex(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") { std::fprintf(stderr, "scalar_loop_backend_requests::_norm_append_for_statement_sequence_requests__binaryIndex: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scalar_loop_backend_requests::_norm_append_for_statement_sequence_requests__binaryIndex(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $binaryIndex") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = storage_lifetime_readiness::_norm_record_storage_lifetime_publication_metrics__storageLifetimeWorkerCandidateReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)storage_lifetime_readiness::_norm_record_storage_lifetime_publication_metrics__storageLifetimeWorkerCandidateReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "storage_lifetime_readiness::_norm_record_storage_lifetime_publication_metrics__storageLifetimeWorkerCandidateReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)storage_lifetime_readiness::_norm_record_storage_lifetime_publication_metrics__storageLifetimeWorkerCandidateReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $storageLifetimeWorkerCandidateReady") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = type_capability_readiness::_norm_record_capability_readiness_publication_metrics__capabilityReadinessWorkerCandidateReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)type_capability_readiness::_norm_record_capability_readiness_publication_metrics__capabilityReadinessWorkerCandidateReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "type_capability_readiness::_norm_record_capability_readiness_publication_metrics__capabilityReadinessWorkerCandidateReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)type_capability_readiness::_norm_record_capability_readiness_publication_metrics__capabilityReadinessWorkerCandidateReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $capabilityReadinessWorkerCandidateReady") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = control_flow_dataflows::_norm_statement_local_name_and_type_ref__name(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)control_flow_dataflows::_norm_statement_local_name_and_type_ref__name(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "control_flow_dataflows::_norm_statement_local_name_and_type_ref__name: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)control_flow_dataflows::_norm_statement_local_name_and_type_ref__name(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $name") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = control_flow_transfers::_norm_append_transfer_row__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)control_flow_transfers::_norm_append_transfer_row__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "control_flow_transfers::_norm_append_transfer_row__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)control_flow_transfers::_norm_append_transfer_row__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = control_flow_transfers::_norm_append_statement_list_transfers__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)control_flow_transfers::_norm_append_statement_list_transfers__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "control_flow_transfers::_norm_append_statement_list_transfers__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)control_flow_transfers::_norm_append_statement_list_transfers__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_row_value__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)deterministic_work_ordering::_norm_stable_hash_mix_output_row__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = mt_stage_evaluation::_norm_stable_hash_mix_value__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_value__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "mt_stage_evaluation::_norm_stable_hash_mix_value__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_value__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = mt_stage_evaluation::_norm_stable_hash_mix_value__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_value__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "mt_stage_evaluation::_norm_stable_hash_mix_value__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_value__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = mt_stage_evaluation::_norm_stable_hash_mix_segment__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_segment__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "mt_stage_evaluation::_norm_stable_hash_mix_segment__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_segment__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = mt_stage_evaluation::_norm_stable_hash_mix_segment__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_segment__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "mt_stage_evaluation::_norm_stable_hash_mix_segment__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)mt_stage_evaluation::_norm_stable_hash_mix_segment__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = partition_merge_reductions::_norm_stable_hash_mix_row_value__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_row_value__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "partition_merge_reductions::_norm_stable_hash_mix_row_value__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_row_value__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = partition_merge_reductions::_norm_stable_hash_mix_row_value__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_row_value__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "partition_merge_reductions::_norm_stable_hash_mix_row_value__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_row_value__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = partition_merge_reductions::_norm_stable_hash_mix_output_row__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_output_row__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "partition_merge_reductions::_norm_stable_hash_mix_output_row__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_output_row__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = partition_merge_reductions::_norm_stable_hash_mix_output_row__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_output_row__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "partition_merge_reductions::_norm_stable_hash_mix_output_row__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)partition_merge_reductions::_norm_stable_hash_mix_output_row__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = resident_source_unit_frontend_payload_tables::_norm_record_worker_payload_handoff_metrics__frontendPayloadSourceWorkerReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)resident_source_unit_frontend_payload_tables::_norm_record_worker_payload_handoff_metrics__frontendPayloadSourceWorkerReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "resident_source_unit_frontend_payload_tables::_norm_record_worker_payload_handoff_metrics__frontendPayloadSourceWorkerReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)resident_source_unit_frontend_payload_tables::_norm_record_worker_payload_handoff_metrics__frontendPayloadSourceWorkerReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $frontendPayloadSourceWorkerReady") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = resident_source_unit_symbol_states::_norm_record_symbol_fact_publication_metrics__symbolFactWorkerCandidateReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)resident_source_unit_symbol_states::_norm_record_symbol_fact_publication_metrics__symbolFactWorkerCandidateReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "resident_source_unit_symbol_states::_norm_record_symbol_fact_publication_metrics__symbolFactWorkerCandidateReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)resident_source_unit_symbol_states::_norm_record_symbol_fact_publication_metrics__symbolFactWorkerCandidateReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $symbolFactWorkerCandidateReady") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_value__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_value__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_value__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_value__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_value__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_value__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_value__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_value__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_task_output__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_output__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_task_output__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_output__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_task_output__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_output__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_task_output__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_output__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_session__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_session__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_session__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_session__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_session__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_session__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_session__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_session__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_worker__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_worker__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_worker__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_worker__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_worker__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_worker__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_worker__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_worker__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_task_api__hashA(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_api__hashA(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_task_api__hashA: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_api__hashA(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashA") return 6;
    }
}
{
    int_t<> typed(7);
    auto& direct = scheduler_api::_norm_stable_hash_mix_task_api__hashB(typed);
    if (&direct != &typed) return 1;
    direct = int_t<>(17);
    if (!static_cast<bool>(php::identical(typed, int_t<>(17)))) return 2;
    mixed_t matching{int_t<>(7)};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_api__hashB(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") { std::fprintf(stderr, "scheduler_api::_norm_stable_hash_mix_task_api__hashB: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)scheduler_api::_norm_stable_hash_mix_task_api__hashB(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $hashB") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = project_reference_resolution::_norm_record_reference_contract_publication_metrics__referenceContractWorkerCandidateReady(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)project_reference_resolution::_norm_record_reference_contract_publication_metrics__referenceContractWorkerCandidateReady(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "project_reference_resolution::_norm_record_reference_contract_publication_metrics__referenceContractWorkerCandidateReady: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)project_reference_resolution::_norm_record_reference_contract_publication_metrics__referenceContractWorkerCandidateReady(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $referenceContractWorkerCandidateReady") return 6;
    }
}
{
    bool_t typed(false);
    auto& direct = project_symbol_index::_norm_declaration_body_shape__usedCachedDigest(typed);
    if (&direct != &typed) return 1;
    direct = bool_t(true);
    if (!static_cast<bool>(php::identical(typed, bool_t(true)))) return 2;
    mixed_t matching{bool_t(false)};
    try { (void)project_symbol_index::_norm_declaration_body_shape__usedCachedDigest(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_bool_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "project_symbol_index::_norm_declaration_body_shape__usedCachedDigest: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{string_t("wrong")};
    try { (void)project_symbol_index::_norm_declaration_body_shape__usedCachedDigest(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $usedCachedDigest") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = project_symbol_index::_norm_parameter_list_count__signatureParameterShape(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)project_symbol_index::_norm_parameter_list_count__signatureParameterShape(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "project_symbol_index::_norm_parameter_list_count__signatureParameterShape: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)project_symbol_index::_norm_parameter_list_count__signatureParameterShape(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $signatureParameterShape") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = source_units::_norm_source_read_worker_result_from_row__sourceText(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)source_units::_norm_source_read_worker_result_from_row__sourceText(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "source_units::_norm_source_read_worker_result_from_row__sourceText: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)source_units::_norm_source_read_worker_result_from_row__sourceText(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $sourceText") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = compiler_export_artifacts::_norm_append_tokens_tsv__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)compiler_export_artifacts::_norm_append_tokens_tsv__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "compiler_export_artifacts::_norm_append_tokens_tsv__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)compiler_export_artifacts::_norm_append_tokens_tsv__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = compiler_export_artifacts::_norm_append_parse_tsv__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)compiler_export_artifacts::_norm_append_parse_tsv__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "compiler_export_artifacts::_norm_append_parse_tsv__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)compiler_export_artifacts::_norm_append_parse_tsv__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = pure_tdd_stage_artifacts::_norm_append_tokens_tsv__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)pure_tdd_stage_artifacts::_norm_append_tokens_tsv__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "pure_tdd_stage_artifacts::_norm_append_tokens_tsv__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)pure_tdd_stage_artifacts::_norm_append_tokens_tsv__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = pure_tdd_stage_artifacts::_norm_append_parse_tsv__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)pure_tdd_stage_artifacts::_norm_append_parse_tsv__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "pure_tdd_stage_artifacts::_norm_append_parse_tsv__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)pure_tdd_stage_artifacts::_norm_append_parse_tsv__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = pure_tdd_stage_artifacts::_norm_append_backend_call_arguments_tsv__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)pure_tdd_stage_artifacts::_norm_append_backend_call_arguments_tsv__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "pure_tdd_stage_artifacts::_norm_append_backend_call_arguments_tsv__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)pure_tdd_stage_artifacts::_norm_append_backend_call_arguments_tsv__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    string_t typed("before");
    auto& direct = source_files::_norm_read_text__text(typed);
    if (&direct != &typed) return 1;
    direct = string_t("after");
    if (!static_cast<bool>(php::identical(typed, string_t("after")))) return 2;
    mixed_t matching{string_t("before")};
    try { (void)source_files::_norm_read_text__text(matching); return 3; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") { std::fprintf(stderr, "source_files::_norm_read_text__text: %s\n", error.what()); return 4; }
    }
    mixed_t wrong{bool_t(false)};
    try { (void)source_files::_norm_read_text__text(wrong); return 5; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "Unsupported runtime kind for normalized parameter $text") return 6;
    }
}
{
    string_t path("/tmp/scpp-edit-latency-20260919/carrier-coverage/adapter-proof/adapter-input.txt");
    string_t typed("before");
    if (!static_cast<bool>(source_files::read_text(path, typed))) return 7;
    if (!static_cast<bool>(php::identical(typed, string_t("adapter-reference-witness")))) return 8;
    mixed_t mixed{string_t("before")};
    try { (void)source_files::read_text(path, mixed); return 9; }
    catch (const std::runtime_error& error) {
        if (std::string(error.what()) != "mixed_t::as_string_ref is disabled in the current safe subset: native references to dynamic/interior storage are not supported") return 10;
    }
}
std::puts("normalizers=52;typed_aliasing_and_writes=ok;mixed_rejection=ok;wrapper=ok");
}
