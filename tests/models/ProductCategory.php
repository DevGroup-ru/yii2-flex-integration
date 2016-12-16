<?php

namespace DevGroup\FlexIntegration\Tests\models;

use Yii;
use yii\db\ActiveRecord;

class ProductCategory extends ActiveRecord
{
    public function rules()
    {
        return [
            [['product_id', 'category_id'], 'required'],
        ];
    }

    public static function tableName()
    {
        return '{{%product_category}}';
    }
}
