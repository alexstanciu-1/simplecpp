option(SCPP_QUALIFY_JSON_STRING_RESERVE "Probe the native string capacity rule for JSON decoding" ON)

function(scpp_qualify_json_string_reserve)
  if(NOT SCPP_QUALIFY_JSON_STRING_RESERVE OR CMAKE_CROSSCOMPILING)
    return()
  endif()
  set(source "${PROJECT_SOURCE_DIR}/tools/layout_probe/json_string_capacity.cpp")
  set_property(DIRECTORY APPEND PROPERTY CMAKE_CONFIGURE_DEPENDS "${source}"
    "${PROJECT_SOURCE_DIR}/runtime/src/json/string_capacity.hpp")
  # Recheck in the active build configuration, including changes to compiler flags.
  set(CMAKE_TRY_COMPILE_CONFIGURATION "${CMAKE_BUILD_TYPE}")
  unset(scpp_capacity_compiled CACHE)
  unset(scpp_capacity_run CACHE)
  try_run(scpp_capacity_run scpp_capacity_compiled "${PROJECT_BINARY_DIR}/json-capacity-probe" "${source}"
    CXX_STANDARD 23 CXX_STANDARD_REQUIRED ON)
  if(scpp_capacity_compiled AND "${scpp_capacity_run}" STREQUAL "0")
    set(scpp_json_reserve_qualified TRUE PARENT_SCOPE)
  endif()
endfunction()

set(scpp_json_reserve_qualified FALSE)
if(SCPP_ENABLE_JSON OR BUILD_TESTING OR NOT DEFINED BUILD_TESTING)
  scpp_qualify_json_string_reserve()
endif()

# JSON depends on its publication's core; core never depends on JSON.
function(scpp_add_json target core generated)
  add_library(${target} STATIC ${generated}/src/json.cpp
    ${PROJECT_SOURCE_DIR}/runtime/src/json/json_native.cpp)
  if(scpp_json_reserve_qualified)
    target_compile_definitions(${target} PRIVATE SCPP_JSON_MEASURED_CAPACITY_8=1)
  endif()
  target_link_libraries(${target} PUBLIC ${core} PRIVATE scpp_boxed_storage)
  target_include_directories(${target} PRIVATE
    ${PROJECT_SOURCE_DIR}/runtime/src/json ${PROJECT_SOURCE_DIR}/runtime/src/boxed
    ${PROJECT_SOURCE_DIR}/runtime/src/hash ${PROJECT_SOURCE_DIR}/runtime/src/ownership
    ${PROJECT_SOURCE_DIR}/runtime/src/numeric)
  target_compile_features(${target} PUBLIC cxx_std_23)
endfunction()
