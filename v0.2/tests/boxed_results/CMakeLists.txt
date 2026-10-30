set(boxed_result_generated ${CMAKE_BINARY_DIR}/generated-boxed-result-proof)
set(boxed_result_publication ${CMAKE_CURRENT_SOURCE_DIR}/publication.json)
set(boxed_result_outputs)
foreach(path public/scpp/integers.hpp public/scpp/values.hpp public/scpp/numeric.hpp
    public/scpp/native_bridge.hpp public/scpp/short_names.hpp public/scpp/string.hpp public/scpp/boxed.hpp public/scpp/carriers.hpp
    src/runtime.cpp src/boxed.cpp prepared.json inventory.json)
  list(APPEND boxed_result_outputs ${boxed_result_generated}/${path})
endforeach()
add_custom_command(OUTPUT ${boxed_result_outputs}
  COMMAND ${generator_launcher} ${runtime_metadata} ${boxed_result_publication} ${boxed_result_generated}
  DEPENDS scpp_api_generator ${generator_sources} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${boxed_result_publication}
  VERBATIM)
add_custom_target(boxed_result_publication DEPENDS ${boxed_result_outputs})
add_library(boxed_result_proof_runtime STATIC ${boxed_result_generated}/src/runtime.cpp ${boxed_result_generated}/src/boxed.cpp)
add_dependencies(boxed_result_proof_runtime boxed_result_publication)
target_include_directories(boxed_result_proof_runtime PUBLIC ${boxed_result_generated}/public
  PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/numeric ${PROJECT_SOURCE_DIR}/runtime/src/sequence
    ${PROJECT_SOURCE_DIR}/runtime/src/boxed ${PROJECT_SOURCE_DIR}/runtime/src/ownership)
target_link_libraries(boxed_result_proof_runtime PRIVATE scpp_boxed_storage)
target_compile_features(boxed_result_proof_runtime PUBLIC cxx_std_23)
add_executable(boxed_result_consumer_test consumer.cpp)
target_link_libraries(boxed_result_consumer_test PRIVATE boxed_result_proof_runtime)
add_test(NAME boxed_result_consumer COMMAND boxed_result_consumer_test)
add_test(NAME boxed_result_freshness COMMAND ${generator_launcher} ${runtime_metadata} ${boxed_result_publication} ${boxed_result_generated} --check)
add_test(NAME boxed_result_boundary COMMAND ${CMAKE_COMMAND}
  -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
  -DGENERATED=${boxed_result_generated} -DWORK=${CMAKE_CURRENT_BINARY_DIR}/boundary
  -P ${CMAKE_CURRENT_SOURCE_DIR}/boundary.cmake)
add_test(NAME boxed_result_publication COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/publication.py
  --generator ${api_tooling} --source ${PROJECT_SOURCE_DIR} --facts ${native_facts}
  --context ${native_context} --cxx ${CMAKE_CXX_COMPILER})
