<?php

return [
    // MultiPOS validation messages
    'multiPOS' => [
        'name' => [
            'required' => 'The multi pos name is required.',
            'max' => 'The multi pos name may not be greater than :max characters.',
        ],
        'type' => [
            'required' => 'The multi pos type is required.',
            'in' => 'The selected multi pos type is invalid.',
        ],
    ],
];
