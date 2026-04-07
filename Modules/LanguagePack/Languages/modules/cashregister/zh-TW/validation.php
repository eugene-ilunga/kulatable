<?php 
return [
  'denomination' => [
    'name' => [
      'required' => '宗派名稱為必填項。',
      'max' => '面額名稱不得超過 :max 個字符。',
    ],
    'value' => [
      'required' => '面額為必填項。',
      'numeric' => '面值必須是數字。',
      'min' => '面值必須至少為 :min。',
      'max' => '面值不得大於 :max。',
    ],
    'type' => [
      'required' => '面額類型為必填項。',
      'in' => '所選面額類型無效。',
    ],
    'description' => [
      'max' => '面額描述不得超過 :max 個字符。',
    ],
  ],
];