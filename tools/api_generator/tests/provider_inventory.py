"""Expected configure-time headers from the fully prepared runtime publication."""


def prepared_provider_headers(plan, runtime):
    graph = plan['submodules']
    headers = {graph['nodes'][provider]['header'] for provider in graph['published']}
    if 'in_array' in plan.get('collection_transforms', []):
        headers.add('scpp/collection_scan.hpp')
    if 'structural_decoder' in plan:
        headers.add('scpp/decoder.hpp')
    if 'structural_encoder' in plan:
        headers.add('scpp/encoder.hpp')
    for function in plan.get('string_functions', {}).values():
        headers.update(adapter['header'] for adapter in function.get('adapters', {}).values())
    for family in ('text_helpers', 'text_builders', 'cli_helpers'):
        headers.update(item['header'] for item in plan.get(family, {}).values())
    implode = plan.get('string_functions', {}).get('implode', {})
    if 'php' in implode.get('adapters', {}) and any(
            h['value'] == 'string' for h in plan.get('hashes', {}).get('specializations', [])):
        headers.add('scpp/php/implode_hash.hpp')
    if (runtime / 'public/scpp/value_error.hpp').exists():
        headers.add('scpp/value_error.hpp')
    return sorted(headers)
