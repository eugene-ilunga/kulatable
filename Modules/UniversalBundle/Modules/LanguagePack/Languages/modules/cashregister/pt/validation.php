<?php 
return [
  'denomination' => [
    'name' => [
      'required' => 'O nome da denominação é obrigatório.',
      'max' => 'O nome da denominação não pode ter mais que :max caracteres.',
    ],
    'value' => [
      'required' => 'O valor da denominação é obrigatório.',
      'numeric' => 'O valor da denominação deve ser um número.',
      'min' => 'O valor da denominação deve ser pelo menos :min.',
      'max' => 'O valor da denominação não pode ser superior a :max.',
    ],
    'type' => [
      'required' => 'O tipo de denominação é obrigatório.',
      'in' => 'O tipo de denominação selecionado é inválido.',
    ],
    'description' => [
      'max' => 'A descrição da denominação não pode ter mais que :max caracteres.',
    ],
  ],
];