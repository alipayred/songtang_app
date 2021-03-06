<?php

namespace yun\modules\admin\controllers;

use common\components\CommonFunc;
use ucenter\models\User;
use Yii;
use yun\models\Attribute;
use yun\models\DirPermission;
use yun\components\YunFunc;
use yun\models\Dir;
use yun\components\DirFunc;
use yun\models\UserWildcard;
use yun\modules\admin\models\DirForm;

class DirController extends BaseController
{

    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $this->view->title = '板块目录 - 列表';

        $dir_id = Yii::$app->request->get('dir_id',false);  //目录

        $dirList_1 = CommonFunc::getByCache(Dir::className(),'getDropDownList',[0,true,false,1],'yun:dir/drop-down-list'); //第一层目录

        $dirList_2 = [];

        $list = [];

        $curDir = CommonFunc::getByCache(Dir::className(),'getOne',[$dir_id],'yun:dir/one');

        if($curDir && $curDir->status==1){

            $parents = CommonFunc::getByCache(Dir::className(),'getParents',[$dir_id],'yun:dir/parents');
           // $parents2 = Dir::getParents($dir_id);

            $dirLvl_1 = isset($parents[1])?$parents[1]:null;
            $dirLvl_2 = isset($parents[2]) && $dirLvl_1?$parents[2]:null;
            if($dirLvl_1){
                $dirList_2 = CommonFunc::getByCache(Dir::className(),'getDropDownList',[$dirLvl_1->id,true,false,1],'yun:dir/drop-down-list');
            }
        }else{
            $dirLvl_1 = null;
            $dirLvl_2 = null;
        }

        if($curDir){
            if($curDir->level==2){
                $list = CommonFunc::getByCache(Dir::className(),'getListArr',[$dir_id,true,true,true],'yun:dir/list-arr');
            }else{
                $list = CommonFunc::getByCache(Dir::className(),'getListArr',[$dir_id,true,true,true,0],'yun:dir/list-arr');
            }
        }

        $params['list'] = $list;
        $params['dirList_1'] = $dirList_1;
        $params['dirList_2'] = $dirList_2;
        $params['dirLvl_1'] = $dirLvl_1;
        $params['dirLvl_2'] = $dirLvl_2;


        return $this->render('index',$params);
    }


    public function actionAddAndEdit(){
        $model = new DirForm();
        $dir = null;
        $id = Yii::$app->request->get('id',false);
        $p_id = Yii::$app->request->get('p_id',false);
        $action = null ;
        if($id!=false){
            $dir = Dir::find()->where(['id'=>$id])->one();
            if($dir){
                $this->view->title = '板块目录 - 编辑';
                $model->setAttributes($dir->attributes);
                $action = 'edit';
            }else{
                Yii::$app->response->redirect('dir')->send();
            }
        }elseif($p_id!=false){
            $parDir = Dir::find()->where(['id'=>$p_id,'is_leaf'=>0])->one();
            if($parDir){
                $model->p_id = $p_id;
                $model->level = $parDir->level + 1;
                $model->status = 1;
                $this->view->title = '板块目录 - 添加';
                $action = 'add';
            }else{
                Yii::$app->response->redirect('dir')->send();
            }
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if($dir == null){
                $dir = new Dir();
                $dir->setAttributes($model->attributes);
                $dir->more_cate = 0;

                //查找出当前父目录下的其他子目录 ord 最小的
                $lastDir = Dir::find()->where(['p_id'=>$p_id])->orderBy('ord desc')->one();
                /*if($lastDir){
                    //将原本is_last子目录改为0
                    $lastDir->is_last = 0;
                    $lastDir->save();
                    //赋予新建的目录ord = lastDir->ord - 1  is_last = 1
                }*/

                if($dir->ord == 0){
                    if($lastDir){
                        $dir->ord = $lastDir->ord + 1;
                        $lastDir->is_last = 0;
                        $lastDir->save();
                    }else{
                        $dir->ord = 1;
                    }
                    $dir->is_last = 1;

                }else{
                    if($dir->ord >= $lastDir->ord){
                        $dir->is_last = 1;
                        $lastDir->is_last = 0;
                        $lastDir->save();
                    }else{
                        $dir->is_last = 0;
                    }
                }

            }else{
                $dir->setAttributes($model->attributes);
            }

            if($dir->save()){
                //清除缓存
                $this->clearCache();
                /*$cache = Yii::$app->cache;
                $cache->delete('treeDataId');
                //$this->clearTreeDataCache();*/

                //重定向
                $parents = Dir::getParents($dir->id);
                $redirect = ['admin/dir'];
                if(isset($parents[2])){
                    $redirect['dir_id'] = $parents[2]->id;
                }elseif(isset($parents[1])){
                    $redirect['dir_id'] = $parents[1]->id;
                }
                Yii::$app->response->redirect($redirect)->send();
            }
        }

        $params['model'] = $model;
        $params['action'] = $action;
        return $this->render('add_and_edit',$params);
    }

    public function actionPermission(){
        $dir_id = Yii::$app->request->get('dir_id',false);
        $dir = Dir::find()->where(['id'=>$dir_id])->one();
        if($dir){
            $params['dir'] = $dir;

            $permission = DirPermission::find()->where(['dir_id'=>$dir->id])->orderBy('user_match_type asc')->all();

            $params['permission_list'] = $permission;
        }else{
            return Yii::$app->response->redirect('dir');
        }

        return $this->render('permission',$params);
    }

    public function actionPermissionSave(){
        $result = false;
        $error_message='';
        $dir_id = Yii::$app->request->post('dir_id',false);
        $dir = Dir::find()->where(['id'=>$dir_id])->one();

        if($dir){
            $pmInfo = Yii::$app->request->post('pm');

            foreach($pmInfo as $pmId=>$pmOne ){
                //var_dump($pmOne['user_match_param'][3]);exit;
                $pm = DirPermission::find()->where(['id'=>$pmId,'dir_id'=>$dir_id])->one();
                if($pm){
                    $pm->attributes = $pmOne;
                    if($pmOne['user_match_type']==1){
                        $pm->user_match_param_id = 0;
                    }elseif($pmOne['user_match_type']==3){
                        $user_wildcard = UserWildcard::find()->where(['pm_id'=>$pmId])->one();
                        if($user_wildcard){
                            $user_wildcard->district_id = $pmOne['user_match_param'][3]['district_id'];
                            $user_wildcard->industry_id = $pmOne['user_match_param'][3]['industry_id'];
                            $user_wildcard->company_id = $pmOne['user_match_param'][3]['company_id'];
                            $user_wildcard->department_id = $pmOne['user_match_param'][3]['department_id'];
                            $user_wildcard->position_id = $pmOne['user_match_param'][3]['position_id'];
                            $user_wildcard->save();
                            $pm->user_match_param_id = $user_wildcard->id;
                        }else{
                            $user_wildcard_new = new UserWildcard();
                            $user_wildcard_new->district_id = $pmOne['user_match_param'][3]['district_id'];
                            $user_wildcard_new->industry_id = $pmOne['user_match_param'][3]['industry_id'];
                            $user_wildcard_new->company_id = $pmOne['user_match_param'][3]['company_id'];
                            $user_wildcard_new->department_id = $pmOne['user_match_param'][3]['department_id'];
                            $user_wildcard_new->position_id = $pmOne['user_match_param'][3]['position_id'];

                            //$user_wildcard_new->setAttributes($pmOne['user_match_param'][3]);
                            $user_wildcard_new->pm_id = $pmId;
                            $user_wildcard_new->save();
                            $pm->user_match_param_id = $user_wildcard_new->id;
                        }
                    }elseif($pmOne['user_match_type']==7){
                        $pm->user_match_param_id = $pmOne['user_match_param'][7];
                    }


                    $pm->save();
                }
            }


            $result = true;
        }

        $arr = [];
        $arr['error'] = $error_message;
        $arr['result'] = $result;
        echo json_encode($arr);
        Yii::$app->end();

    }

    public function actionIndex2(){
        $list = Dir::getListArr(0,true,true,true);

        $params['list'] = $list;
        return $this->render('index2',$params);
    }


    //职员版
    public function actionWatchPermission(){
        $dir_id = Yii::$app->request->get('dir_id',false);
        $dir = Dir::find()->where(['id'=>$dir_id])->one();
        if($dir){
            $userQuery = User::find()->where(['status'=>1]);

            //$userQuery = $userQuery->limit(10);

            $userList = $userQuery->all();

            $params['userList'] = $userList;
            $params['dir'] = $dir;
            return $this->render('watch_permission',$params);
        }else{
            echo 'wrong dir_id';exit;
        }
    }


    private function clearCache(){
        $cache = Yii::$app->cache;
        $keyList = YunFunc::$cacheKeyList;
        foreach($keyList['dir'] as $k){
            $cache->delete('yun:dir/'.$k);
        }
    }

    public function actionFixOrd(){


        $this->fixOrd(0);
        /*$dir1 = Dir::find()->where(['p_id'=>0])->orderBy('id asc')->all();

        foreach($dir1 as $d1){

            $dir2 = Dir::find()->where(['p_id'=>$d1->id])->orderBy('id asc')->all();

            $ord = 1;
            foreach($dir2 as $d2){
                $d2->ord = $ord;
                $d2->last = $ord == count($dir2)?1:0;
                $d2->save();


            }

        }*/

    }


    private function fixOrd($pid){
        $dir = Dir::find()->where(['p_id'=>$pid])->orderBy('id asc')->all();
        $ord = 1;
        foreach($dir as $d){
            $d->ord = $ord;
            $d->is_last = $ord == count($dir)?1:0;
            $d->save();
$ord++;
            $this->fixOrd($d->id);

        }
    }

    public function actionAttrLimit(){
        $dir_id = Yii::$app->request->get('id',false);
        $dir = Dir::find()->where(['id'=>$dir_id])->one();
        if($dir){
            if($_POST){
                $districtAttr = Yii::$app->request->post('isDistrictLimit',false) == 1 ? Yii::$app->request->post('districtCheck',[]) : false;
                $industryAttr = Yii::$app->request->post('isIndustryLimit',false) == 1 ? Yii::$app->request->post('industryCheck',[]) : false;

                $dir->attr_limit = json_encode([Attribute::TYPE_DISTRICT=>$districtAttr,Attribute::TYPE_INDUSTRY=>$industryAttr]);
                $dir->save();
                $this->refresh();

            }


            $districtAttr = false;
            $industryAttr = false;
            $attributes = json_decode($dir->attr_limit,true);
            if($attributes){
                if(isset($attributes[Attribute::TYPE_DISTRICT]) && is_array($attributes[Attribute::TYPE_DISTRICT])){
                    $districtAttr = [];
                    foreach($attributes[Attribute::TYPE_DISTRICT] as $attr){
                        $districtAttr[] = $attr;
                    }
                }
                if(isset($attributes[Attribute::TYPE_INDUSTRY]) && is_array($attributes[Attribute::TYPE_INDUSTRY])){
                    $industryAttr = [];
                    foreach($attributes[Attribute::TYPE_INDUSTRY] as $attr){
                        $industryAttr[] = $attr;
                    }
                }
            }

            $params['districtArr'] = $districtAttr;
            $params['industryArr'] = $industryAttr;
            $params['dir'] = $dir;
            return $this->render('attr_limit',$params);
        }else{
            echo 'wrong dir_id';exit;
        }

    }


    
}
