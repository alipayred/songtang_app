<?php

namespace oa\models;
use ucenter\models\Company;
use ucenter\models\District;
use ucenter\models\Industry;
use Yii;
//oa 任务表 (即预先分配好的oa流程环节)
class Task extends \yii\db\ActiveRecord
{
    public static function getDb(){
        return Yii::$app->db_oa;
    }

    public function attributeLabels(){
        return [
            'id' => 'ID',
            'title' => '任务名称',
            'district_id' => '锁定地区',
            'industry_id' => '锁定行业',
            'company_id' => '锁定公司',
            'department_id' => '锁定部门',
            'ord' => '排序',
            'status' => '状态'
        ];
    }

    public function rules()
    {
        return [
            [['title'], 'required'],
            [['ord', 'status', 'district_id', 'industry_id', 'company_id', 'department_id'], 'integer'],
        ];
    }

    public static function getCategory($task_id){
        $taskCateId = TaskCategoryId::find()->where(['task_id'=>$task_id])->all();
        $cateIds = [];
        foreach($taskCateId as $t){
            $cateIds[] = $t->category_id;
        }
        $category = TaskCategory::find()->where(['id'=>$cateIds])->all();
        $list = [];
        foreach($category as $c){
            $list[$c->id] = $c->name;
        }
        return $list;
    }

    public static function getCategory2($task_id){
        $taskCateId = TaskCategoryId::find()->where(['task_id'=>$task_id])->all();
        $cateIds = [];
        foreach($taskCateId as $t){
            $cateIds[] = $t->category_id;
        }
        $category = TaskCategory::find()->where(['id'=>$cateIds])->orderBy(['type'=>SORT_ASC,'status'=>SORT_DESC,'ord'=>SORT_ASC])->all();
        $list = [];
        foreach($category as $c){
            $list[$c->type][$c->id] = $c->status==1?$c->name:'<span style="color:red;">'.$c->name.'</span>';
        }
        $typeList = TaskCategory::getTypeList();
        $return = '';
        foreach($list as $k=>$types){
            $return .= '<span style="color:#999;display:block;float:left;width:70px;">'.$typeList[$k].' : </span><span style="display:block;float:left;width:200px;">'.implode(' , ',$types).'</span><br/>';
        }




        return $return ;
    }


    public static function getForm($task_id){
        $taskFormId = TaskForm::find()->where(['task_id'=>$task_id])->all();
        $ids = [];
        foreach($taskFormId as $t){
            $ids[] = $t->form_id;
        }

        $forms = Form::find()->where(['id'=>$ids,'status'=>1])->all();
        $formList = [];
        foreach($forms as $f){
            $formList[$f->id] = $f->set_complete==0?('<span style="color:red;">'.$f->title.'[暂停中]</span>'):$f->title;
        }

        $formCategory = FormCategory::find()->where(['form_id'=>$ids])->all();
        $list = [];
        foreach($formCategory as $fc){
            if(isset($formList[$fc->form_id])){
                $list[$fc->category_id][] = $formList[$fc->form_id];
            }
        }
        $categoryList = TaskCategory::getItems();
        $return = '';



        foreach($list as $k=>$forms){


            $return .= '<span style="display:block;float:left;width:100%;"><span style="color:#999;display:block;float:left;width:92px;">'.$categoryList[$k].':</span><span style="display:block;float:left;width:140px;">'.implode(' , ',$forms).'</span></span><br/>';
        }


        return $return ;
    }


    public function getDistrict(){
        return $this->hasOne(District::className(), array('id' => 'district_id'));
    }

    public function getIndustry(){
        return $this->hasOne(Industry::className(), array('id' => 'industry_id'));
    }

    public function getCompany(){
        return $this->hasOne(Company::className(), array('id' => 'company_id'));
    }

    /*
     * 获取可发起申请的任务列表
     * 参数 district_id
     * 参数 industry_id
     * 参数 company_id
     * 参数 user_id
     * return  ['id1'=>'title1',...***]
     */
    public static function getList($district_id,$industry_id,$company_id,$user_id=false){
        $return = [];
        //有效的task列表
        $query = Task::find()->where(['status'=>1,'set_complete'=>1]);
        if($district_id>0){
            $query = $query->andWhere(['district_id'=>$district_id]);
        }
        if($industry_id>0){
            $query = $query->andWhere(['industry_id'=>$industry_id]);
        }
        if($company_id>0){
            $query = $query->andWhere(['company_id'=>$company_id]);
        }
        $tasks = $query->all();
        $taskIds = [];
        foreach($tasks as $t){
            $taskIds[] = $t->id;
        }
        if($user_id==false)
            $user_id = Yii::$app->user->id;

        $list = TaskApplyUser::find()->where(['user_id'=>$user_id,'task_id'=>$taskIds])->all();
        foreach($list as $l){
            $return[$l->task_id] = $l->task->category->name.'|'.$l->task->title;
        }
        return $return;
    }


    public static function isApplied($task_id){
        $list = Apply::find()->where(['task_id'=>$task_id])->all();
        if(!empty($list)){
            return true;
        }else{
            return false;
        }
    }


}
