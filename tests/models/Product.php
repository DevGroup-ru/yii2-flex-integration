<?php

namespace DevGroup\FlexIntegration\Tests\models;

use Yii;
use yii\db\ActiveRecord;

class Product extends ActiveRecord
{
    public function rules()
    {
        return [
            ['name', 'required'],
            ['sku', 'default', 'value' => 'no_sku'],
        ];
    }

    public static function tableName()
    {
        return '{{%product}}';
    }

    public function getCategories()
    {
        return $this->hasMany(Category::className(), ['id' => 'category_id'])
            ->viaTable('{{%product_category}', ['product_id' => 'id']);
    }
}
