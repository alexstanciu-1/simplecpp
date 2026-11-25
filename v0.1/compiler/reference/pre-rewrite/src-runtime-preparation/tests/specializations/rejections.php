<?php
declare(strict_types=1);

namespace runtime_preparation;

require_once dirname(__DIR__, 2) . '/bootstrap.php';

/** Negative request contracts must fail before native generation; acceptance must match the request. */
final class Request_Rejections
{
    /** Exercise malformed demands through the same export/definition owners as the positive driver. */
    public static function run(string $workspace): void
    {
        Files::directory($workspace . '/rejections');
        $request = Files::json(__DIR__ . '/request.json');
        $catalog = Files::json(__DIR__ . '/catalog.json');
        $cases = [];
        $bad = $request;
        $bad['specializations'][0]['arguments'] = [];
        $cases[] = [$bad, $catalog];
        $bad = $request;
        $bad['specializations'][0]['operations'] = ['construct', 'length'];
        $cases[] = [$bad, $catalog];
        $bad = $request;
        $bad['source_types'][0]['fields'][0]['type'] = 'missing';
        $cases[] = [$bad, $catalog];
        $bad = $request;
        $bad['specializations'][1]['arguments'] = $bad['specializations'][0]['arguments'];
        $cases[] = [$bad, $catalog];
        $bad = $catalog;
        $bad['families'][0]['operations'][2]['parameters'][0]['borrow_scope'] = 'retained';
        $cases[] = [$request, $bad];
        $bad = $catalog;
        $bad['families'][0]['operations'][2]['parameters'][0]['passing'] = 'unchecked_pointer';
        $cases[] = [$request, $bad];
        foreach ($cases as [$input, $definitions])
        {
            $failed = false;
            try {
                $export = Specialization_Request::export($input, $definitions, 'simple_cpp');
                Files::write_json($workspace . '/rejections/request.json', $export['definitions']);
                new Definitions($workspace . '/rejections');
            }
            catch (\RuntimeException) {
                $failed = true;
            }
            if (!$failed) {
                throw new \RuntimeException('Unsupported request was accepted');
            }
        }

        // Correct artifact checksums are insufficient if the consumer expected another source type.
        $accepted = Files::json($workspace . '/accepted.json');
        $definitions = Files::json($workspace . '/inputs/definitions/request.json');
        foreach ($definitions['types'] as &$type) {
            if ($type['kind'] === 'value_record') {
                $type['fields'][0]['writable'] = !$type['fields'][0]['writable'];
            }
        }
        unset($type);
        Files::write_json($workspace . '/rejections/request.json', $definitions);
        $failed = false;
        try {
            new Prepared_Request($workspace . '/output', new Definitions($workspace . '/rejections'),
                $accepted['config']['provider'], $accepted['preparation']['input_key']);
        }
        catch (\RuntimeException) {
            $failed = true;
        }
        if (!$failed) {
            throw new \RuntimeException('Mismatched source contract was accepted');
        }
        fwrite(STDOUT, "7 request/acceptance rejection checks passed\n");
    }
}

Request_Rejections::run($argv[1]);
