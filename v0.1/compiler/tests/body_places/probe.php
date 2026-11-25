<?php
declare(strict_types=1);
namespace body_places_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $case_data = $cases->at($i); $rows = $case_data->member('rows');
            $failed = false; $valid = true;
            try {
                $path /** vector<\check_bodies\Place_Projection> */ = [];
                for ($j = 0; $j < $rows->size(); $j++) {
                    $row = $rows->at($j);
                    $path[] = new \check_bodies\Place_Projection($row->at(0)->integer(), $row->at(1)->integer(), $row->at(2)->integer(), $row->at(3)->integer());
                }
                $place = new \check_bodies\Place($case_data->member('local')->integer(), $path);
                if ($place->size() !== $rows->size()) { $valid = false; }
                if ($place->allocation_backed() !== $case_data->member('allocation')->boolean()) { $valid = false; }
                for ($j = 0; $j < $place->size(); $j++) {
                    $projection = $place->at($j); $row = $rows->at($j);
                    if ($projection !== $path[$j]) { $valid = false; }
                    if (($projection->kind !== $row->at(0)->integer()) || ($projection->operand !== $row->at(1)->integer()) || ($projection->type_id !== $row->at(2)->integer()) || ($projection->call_end !== $row->at(3)->integer())) { $valid = false; }
                }
                $expected = $case_data->member('indices'); $seen = 0;
                $position = $place->next_index(0);
                while ($position !== -1) {
                    if ($seen >= $expected->size()) { $valid = false; break; }
                    if ($position !== $expected->at($seen)->integer()) { $valid = false; }
                    $seen++; $position = $place->next_index($position + 1);
                }
                if ($seen !== $expected->size()) { $valid = false; }
                $path[] = new \check_bodies\Place_Projection(\check_bodies\PROJECTION_ELEMENT, 1, 1);
                if ($place->size() !== $rows->size()) { $valid = false; }
                if ($place->size() > 0) {
                    $original = $place->at(0); $path[0] = new \check_bodies\Place_Projection(\check_bodies\PROJECTION_FIELD, 8, 2);
                    if ($place->at(0) !== $original) { $valid = false; }
                }
                $rejected = false;
                try { $unused = $place->at($place->size()); } catch (\OutOfBoundsException $error) { $rejected = true; }
                if (!$rejected) { $valid = false; }
                $rejected = false;
                try { $unused_index = $place->next_index(-1); } catch (\OutOfBoundsException $error) { $rejected = true; }
                if (!$rejected) { $valid = false; }
            } catch (\InvalidArgumentException $error) { $failed = true; }
            if ($failed !== $case_data->member('error')->boolean()) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
