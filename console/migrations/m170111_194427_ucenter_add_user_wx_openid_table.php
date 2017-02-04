<?php

use yii\db\Migration;

class m170111_194427_ucenter_add_user_wx_openid_table extends Migration
{
    public function init(){
        $this->db = Yii::$app->db_ucenter;
        parent::init();
    }

    public function up()
    {
        $this->createTable('user_wx_openid',[
            'appid'=> $this->string(200)->notNull(),
            'user_id'=> $this->integer(11)->notNull(),
            'openid'=>$this->string(100)->notNull()
        ]);
        $this->addPrimaryKey('pk','user_wx_openid',['appid','user_id']);
        $this->createIndex('app','user_wx_openid','appid');


        $this->createTable('user_wx_session',[
            'key'=> $this->string(100),
            'value'=> $this->text()
        ]);
        $this->addPrimaryKey('pk','user_wx_session','key');

        $this->createTable('user_app_auth',[
            'app'=> $this->string(20)->notNull(),
            'user_id'=> $this->integer(11)->notNull(),
            'is_enable'=> $this->smallInteger(1)->defaultValue(0)
        ]);
        $this->addPrimaryKey('pk','user_app_auth',['app','user_id']);
        $this->createIndex('app_name','user_app_auth','app');

    }

    public function down()
    {
        $this->dropTable('user_app_auth');
        $this->dropTable('user_wx_session');
        $this->dropTable('user_wx_openid');
    }
}