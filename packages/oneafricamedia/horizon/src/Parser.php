<?php

namespace Oneafricamedia\Horizon;

use Symfony\Component\Yaml\Parser as YamlParser;

class Parser implements ParserContract
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new YamlParser();
    }

    public function parseSchema($name)
    {
        $path = base_path("resources/schemas/$name.yml");
        $schema = $this->parser->parse(file_get_contents($path));
        return $schema;
    }
}
