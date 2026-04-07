<?php 
return [
  'multiPOS' => [
    'name' => [
      'required' => 'Çoklu konum adı gereklidir.',
      'max' => 'Çoklu konum adı :max karakterden büyük olamaz.',
    ],
    'type' => [
      'required' => 'Çoklu pos türü gereklidir.',
      'in' => 'Seçilen çoklu pos türü geçersiz.',
    ],
  ],
];