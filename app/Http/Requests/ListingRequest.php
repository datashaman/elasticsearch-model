<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Oneafricamedia\Horizon\ParserContract;

class ListingRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(ParserContract $parser)
    {
        // $routeName = $this->route()->getName();

        $rules = [
            'schema' => 'required',
        ];

        $schema = $parser->parseSchema($this['schema']);

        foreach ($schema['properties'] as $property) {
            $property_rules = [];

            if($property['required']) {
                $property_rules[] = 'required';
            }

            if(!empty($property_rules)) {
                $rules[$property['id']] = implode('|', $property_rules);
            }
        }

        return $rules;
    }
}
