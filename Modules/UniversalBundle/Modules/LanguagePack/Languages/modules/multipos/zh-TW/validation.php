<?php 
return [
  'multiPOS' => [
    'name' => [
      'required' => '多位置名稱是必需的。',
      'max' => '多位置名稱不得超過 :max 個字符。',
    ],
    'type' => [
      'required' => '需要多位置類型。',
      'in' => '所選的多位置類型無效。',
    ],
  ],
];