<?php

use yii\db\Migration;

class m161216_094111_fake_entities extends Migration
{
    public function up()
    {
        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string()->notNull(),
            'sku' => $this->string()->notNull()->defaultValue(''),
            'price' => $this->float()->notNull()->defaultValue(0),
        ]);
        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string()->notNull(),
        ]);
        $this->createTable('{{%product_category}}', [
            'product_id' => $this->integer()->unsigned()->notNull(),
            'category_id' => $this->integer()->unsigned()->notNull(),
        ]);
        $this->createIndex('pair', '{{%product_category}}', ['product_id', 'category_id'], true);
        $this->addForeignKey('pcp', '{{%product_category}}', 'product_id', '{{%product}}', 'id', 'cascade', 'cascade');
        $this->addForeignKey('pcc', '{{%product_category}}', 'category_id', '{{%category}}', 'id', 'cascade', 'cascade');

    }

    public function down()
    {
        $this->dropTable('{{%product_category}}');
        $this->dropTable('{{%product}}');
        $this->dropTable('{{%category}}');
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
