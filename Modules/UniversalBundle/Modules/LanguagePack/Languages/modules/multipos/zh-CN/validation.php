<?php 
return [
  'multiPOS' => [
    'name' => [
      'required' => '多位置名称是必需的。',
      'max' => '多位置名称不得超过 :max 个字符。',
    ],
    'type' => [
      'required' => '需要多位置类型。',
      'in' => '所选的多位置类型无效。',
    ],
  ],
];