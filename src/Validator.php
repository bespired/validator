<?php

namespace Validator;

//    "description"   => 'required|min-length:4|max-length:64',
//    "status"        => 'boolean',
//    "type"          => 'enum:bar:button:combination:component:design:editor:input:menu:select:text:thumb:tool:wrapper',

class Validator
{

    protected $data;
    protected $rules;
    protected $errors;
    protected $messages;

    public function __construct($data, $rules)
    {
        $json = gettype($data) === 'string' ? $data : json_encode($data);

        $this->data   = $json;
        $this->rules  = $rules;
        $this->errors = [];

        $this->messages = [
            'required'   => 'This cannot be empty.',
            'mandatory'  => 'This cannot be empty.',
            'min-length' => 'Not long enough.',
            'max-length' => 'Too long.',
            'boolean'    => 'Boolean is required.',
            'string'     => 'String is required.',
            'enum'       => 'Does not match the enum.',
        ];
    }

    private function validates($path, $value, $rule)
    {
        $rules = explode(':', $rule);

        if ($value === null) {
            if (($rules[0] === 'mandatory') || ($rules[0] === 'required')) {
                return false;
            }
        }

        switch ($rules[0]) {
            case "required":
            case "mandatory":
                return $value !== '';

            case "min-length":
                return ($value === null) || (strlen($value) >= $rules[1]);

            case "max-length":
                return ($value === null) || (strlen($value) <= $rules[1]);

            case "boolean":
                return gettype($value) === 'boolean';

            case "string":
                return gettype($value) === 'string';

            case "enum":
                return in_array($value, $rules);

        }

    }

    private function addError($path, $value, $rule)
    {
        $rulename = explode(':', $rule)[0];

        if (!array_key_exists($path, $this->errors)) {
            $this->errors[$path] = [
                'input'    => $path,
                'messages' => [],
            ];
        }

        $this->errors[$path]['messages'][] = $this->messages[$rulename];

    }

    public function validate()
    {

        $store = new \Peekmo\JsonPath\JsonStore($this->data);

        foreach ($this->rules as $path => $rules) {

            $query  = '$.' . $path;
            $values = $store->get($query);

            foreach ($values as $value) {

                foreach (explode("|", $rules) as $rule) {

                    if (!$this->validates($path, $value, $rule)) {
                        $this->addError($path, $value, $rule);
                    }

                }
            }
        }

        return count($this->errors) ? $this->errors : null;

    }
}
