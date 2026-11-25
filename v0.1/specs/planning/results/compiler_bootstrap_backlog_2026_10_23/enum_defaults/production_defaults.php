<?php
require '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$defaults = (new ReflectionClass('scpp\compiler\type_use_modifier_node'))->getDefaultProperties();
if ($defaults['modifier'] !== constant('scpp\compiler\type_use_modifier_kind::by_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\type_use_modifier_node::modifier'); }
$defaults = (new ReflectionClass('scpp\compiler\object_type_node'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\object_type_operation::checked_cast')) { throw new RuntimeException('Default mismatch: scpp\compiler\object_type_node::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\value_probe_node'))->getDefaultProperties();
if ($defaults['probe_kind'] !== constant('scpp\compiler\value_probe_kind::isset')) { throw new RuntimeException('Default mismatch: scpp\compiler\value_probe_node::probe_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\ownership_observation_node'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\ownership_observation_kind::derive_weak')) { throw new RuntimeException('Default mismatch: scpp\compiler\ownership_observation_node::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\source_type_modifier_exposure'))->getDefaultProperties();
if ($defaults['modifier_kind'] !== constant('scpp\compiler\type_use_modifier_kind::by_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\source_type_modifier_exposure::modifier_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\semantic_type_definition'))->getDefaultProperties();
if ($defaults['definition_origin'] !== constant('scpp\compiler\type_definition_origin::language')) { throw new RuntimeException('Default mismatch: scpp\compiler\semantic_type_definition::definition_origin'); }
$defaults = (new ReflectionClass('scpp\compiler\floating_type_definition'))->getDefaultProperties();
if ($defaults['number_format'] !== constant('scpp\compiler\floating_format::binary')) { throw new RuntimeException('Default mismatch: scpp\compiler\floating_type_definition::number_format'); }
$defaults = (new ReflectionClass('scpp\compiler\abstract_nominal_type_definition'))->getDefaultProperties();
if ($defaults['nominal_kind_data'] !== constant('scpp\compiler\nominal_type_kind::structure')) { throw new RuntimeException('Default mismatch: scpp\compiler\abstract_nominal_type_definition::nominal_kind_data'); }
$defaults = (new ReflectionClass('scpp\compiler\abstract_template_type_definition'))->getDefaultProperties();
if ($defaults['result_kind_data'] !== constant('scpp\compiler\nominal_type_kind::structure')) { throw new RuntimeException('Default mismatch: scpp\compiler\abstract_template_type_definition::result_kind_data'); }
$defaults = (new ReflectionClass('scpp\compiler\result_type_definition'))->getDefaultProperties();
if ($defaults['family_kind'] !== constant('scpp\compiler\result_kind::structured_error')) { throw new RuntimeException('Default mismatch: scpp\compiler\result_type_definition::family_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\ownership_type_definition'))->getDefaultProperties();
if ($defaults['family_kind'] !== constant('scpp\compiler\ownership_kind::shared')) { throw new RuntimeException('Default mismatch: scpp\compiler\ownership_type_definition::family_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\conversion_decision'))->getDefaultProperties();
if ($defaults['context'] !== constant('scpp\compiler\conversion_context::assignment')) { throw new RuntimeException('Default mismatch: scpp\compiler\conversion_decision::context'); }
$defaults = (new ReflectionClass('scpp\compiler\conversion_decision'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\conversion_operation::callable_wrap')) { throw new RuntimeException('Default mismatch: scpp\compiler\conversion_decision::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\truthiness_decision'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\truthiness_operation::boolean_identity')) { throw new RuntimeException('Default mismatch: scpp\compiler\truthiness_decision::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\operator_decision'))->getDefaultProperties();
if ($defaults['source_operator'] !== constant('scpp\compiler\operator_kind::index')) { throw new RuntimeException('Default mismatch: scpp\compiler\operator_decision::source_operator'); }
$defaults = (new ReflectionClass('scpp\compiler\operator_decision'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\operator_operation::wrapper_equal')) { throw new RuntimeException('Default mismatch: scpp\compiler\operator_decision::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_await'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\await_operation::suspend')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_await::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_ownership_observation'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\ownership_observation_kind::derive_weak')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_ownership_observation::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_object_type'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\object_type_operation::checked_cast')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_object_type::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_object_construction'))->getDefaultProperties();
if ($defaults['storage'] !== constant('scpp\compiler\record_storage_kind::inline_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_object_construction::storage'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_record_construction'))->getDefaultProperties();
if ($defaults['storage'] !== constant('scpp\compiler\record_storage_kind::inline_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_record_construction::storage'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_take'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\take_operation::nullable_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_take::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_value_probe'))->getDefaultProperties();
if ($defaults['operation'] !== constant('scpp\compiler\value_probe_kind::isset')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_value_probe::operation'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_coalesce_expression'))->getDefaultProperties();
if ($defaults['presence'] !== constant('scpp\compiler\coalesce_presence::always')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_coalesce_expression::presence'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_coalesce_expression'))->getDefaultProperties();
if ($defaults['left_boundary'] !== constant('scpp\compiler\value_boundary_operation::copy_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_coalesce_expression::left_boundary'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_coalesce_expression'))->getDefaultProperties();
if ($defaults['fallback_boundary'] !== constant('scpp\compiler\value_boundary_operation::copy_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_coalesce_expression::fallback_boundary'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_conditional_expression'))->getDefaultProperties();
if ($defaults['true_boundary'] !== constant('scpp\compiler\value_boundary_operation::copy_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_conditional_expression::true_boundary'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_conditional_expression'))->getDefaultProperties();
if ($defaults['fallback_boundary'] !== constant('scpp\compiler\value_boundary_operation::copy_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_conditional_expression::fallback_boundary'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_compound_assignment_expression'))->getDefaultProperties();
if ($defaults['write_back_mode'] !== constant('scpp\compiler\compound_write_back::converted_assignment')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_compound_assignment_expression::write_back_mode'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_binding'))->getDefaultProperties();
if ($defaults['resolved_kind'] !== constant('scpp\compiler\binding_kind::unresolved')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_binding::resolved_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_field_access'))->getDefaultProperties();
if ($defaults['receiver_storage'] !== constant('scpp\compiler\record_storage_kind::inline_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_field_access::receiver_storage'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_call_argument'))->getDefaultProperties();
if ($defaults['boundary'] !== constant('scpp\compiler\value_boundary_operation::copy_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_call_argument::boundary'); }
$defaults = (new ReflectionClass('scpp\compiler\prepared_control_transfer'))->getDefaultProperties();
if ($defaults['transfer_kind'] !== constant('scpp\compiler\control_transfer_kind::break_construct')) { throw new RuntimeException('Default mismatch: scpp\compiler\prepared_control_transfer::transfer_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\preparation_lookup'))->getDefaultProperties();
if ($defaults['kind'] !== constant('scpp\compiler\preparation_lookup_kind::property_name')) { throw new RuntimeException('Default mismatch: scpp\compiler\preparation_lookup::kind'); }
$defaults = (new ReflectionClass('scpp\compiler\cpp_type_argument_binding'))->getDefaultProperties();
if ($defaults['kind'] !== constant('scpp\compiler\cpp_type_argument_kind::native_type')) { throw new RuntimeException('Default mismatch: scpp\compiler\cpp_type_argument_binding::kind'); }
$defaults = (new ReflectionClass('scpp\compiler\cpp_type_modifier_binding'))->getDefaultProperties();
if ($defaults['modifier_kind'] !== constant('scpp\compiler\type_use_modifier_kind::by_value')) { throw new RuntimeException('Default mismatch: scpp\compiler\cpp_type_modifier_binding::modifier_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\cpp_type'))->getDefaultProperties();
if ($defaults['literal'] !== constant('scpp\compiler\cpp_literal_kind::signed_integer')) { throw new RuntimeException('Default mismatch: scpp\compiler\cpp_type::literal'); }
$defaults = (new ReflectionClass('scpp\compiler\cpp_artifact'))->getDefaultProperties();
if ($defaults['artifact_kind'] !== constant('scpp\compiler\cpp_artifact_kind::translation_unit')) { throw new RuntimeException('Default mismatch: scpp\compiler\cpp_artifact::artifact_kind'); }
$defaults = (new ReflectionClass('scpp\compiler\runtime_type_definition_spec'))->getDefaultProperties();
if ($defaults['kind'] !== constant('scpp\compiler\nominal_type_kind::structure')) { throw new RuntimeException('Default mismatch: scpp\compiler\runtime_type_definition_spec::kind'); }
$defaults = (new ReflectionClass('scpp\compiler\callable_parameter'))->getDefaultProperties();
if ($defaults['mode'] !== constant('scpp\compiler\passing_mode::value')) { throw new RuntimeException('Default mismatch: scpp\compiler\callable_parameter::mode'); }
echo '40 production defaults pass';
