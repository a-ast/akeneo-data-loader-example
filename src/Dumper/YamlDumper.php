<?php

namespace App\Dumper;

use Symfony\Component\Yaml\Yaml;

class YamlDumper
{
    public function dump(string $filePath, array $data)
    {
        foreach ($data as &$item) {
            $item = array_filter($item, function ($value, $key) {

                return
                    $value !== null &&
                    $value !== [] &&
                    !in_array($key, ['_links', 'sort_order']);

            }, ARRAY_FILTER_USE_BOTH);
        }

        $yaml = Yaml::dump($data, 3);

        file_put_contents($filePath, $yaml);
    }
}
