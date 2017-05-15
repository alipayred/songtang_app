<?php

namespace oa\modules\admin\controllers;

use oa\models\Flow;
use oa\models\Task;
use oa\models\TaskApplyUser;
use oa\models\TaskCategory;
use oa\models\TaskCategoryId;
use oa\models\TaskUserWildcard;
use oa\modules\admin\components\AdminFunc;
use ucenter\models\Company;
use ucenter\models\District;
use ucenter\models\Industry;
use ucenter\models\Position;
use ucenter\models\User;
use Yii;
use yii\web\Response;
/**
 *  任务流程管理
 *  task flow
 */
class TaskController extends BaseController
{
    public function actionCategory()
    {
        $list = TaskCategory::find()->all();


        $params['list'] = $list;
        return $this->render('category',$params);
    }


    public function actionIndex()
    {
        $aid = Yii::$app->request->get('aid',false);
        $bid = Yii::$app->request->get('bid',false);
        $list = Task::find()->all();


        $params['list'] = $list;
        /*$params['districtArr'] = District::getNameArr();
        $params['industryArr'] = Industry::getNameArr();
        $params['companyArr'] = Company::getNameArr();*/
        $params['pArr'] = Position::getNameArr();
        $params['industryArr2'] = District::getIndustryRelationsArr($aid);

        $params['aid'] = $aid;
        $params['bid'] = $bid;
        $params['taskCategoryList'] = TaskCategory::getDropdownList();
        return $this->render('index',$params);
    }


    public function actionGet(){
        $errormsg = '';
        $result = false;
        $info = [];
        if(Yii::$app->request->isAjax){
            $task_id = Yii::$app->request->post('id',0);
            $task = Task::find()->where(['id'=>$task_id])->one();
            if($task){
                $info['title'] = $task->title;
                $category = TaskCategoryId::find()->where(['task_id'=>$task_id])->all();
                $cateArr = [];
                foreach($category as $c){
                    $cateArr[] = $c->category_id;
                }
                $info['category_ids'] = implode(',',$cateArr);
                $result = true;
            }else{
                $errormsg = '模板不存在';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg,'info'=>$info];
    }

    public function actionCreate(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $title = trim(Yii::$app->request->post('title',false));
            $category_id = Yii::$app->request->post('category_id','');
            $district_id = intval(Yii::$app->request->post('district_id',10000));
            $industry_id = intval(Yii::$app->request->post('industry_id',10000));
            $company_id = intval(Yii::$app->request->post('company_id',10000));
            $department_id = intval(Yii::$app->request->post('department_id',10000));
            //AREA BUSINESS DEPARTMENT  TODO
            if($title==''){
                $errormsg = '标题不能为空！';
            }else{
                $exist = Task::find()->where(['title'=>$title])->one();
                if($exist){
                    $errormsg = '标题已存在!';
                }else{
                    if($category_id==''){
                        $errormsg = '请勾选至少一个模板分类！';
                    }else{
                        $task = new Task();
                        $task->title = $title;
                        $task->district_id = $district_id;
                        $task->industry_id = $industry_id;
                        $task->company_id = $company_id;
                        $task->department_id = $department_id;
                        $task->ord = 0;
                        $task->status = 1;
                        if($task->save()){
                            $categoryIds = explode(',',$category_id);

                            foreach($categoryIds as $cate_id){
                                $taskCategory = TaskCategory::find()->where(['id'=>$cate_id])->one();
                                if($taskCategory){
                                    $taskCategoryId = new TaskCategoryId();
                                    $taskCategoryId->task_id = $task->id;
                                    $taskCategoryId->category_id = $cate_id;
                                    $taskCategoryId->save();
                                }
                            }
                            /*$user = User::find()->all();
                            foreach($user as $u){
                                $taskUser = new TaskApplyUser();
                                $taskUser->task_id = $task->id;
                                $taskUser->user_id = $u->id;
                                $taskUser->save();
                            }*/


                            Yii::$app->getSession()->setFlash('success','新增模板【'.$task->title.'】成功！');
                            $result = true;
                        }else{
                            $errormsg = '保存失败，刷新页面重试!';
                        }
                    }
                }
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }


    public function actionEdit(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $task_id = Yii::$app->request->post('task_id',0);
            $title = trim(Yii::$app->request->post('title',false));
            $category_id = Yii::$app->request->post('category_id','');
            $task = Task::find()->where(['id'=>$task_id])->one();
            if($task){
                if($title==''){
                    $errormsg = '标题不能为空！';
                }else {
                    $exist = Task::find()->where(['title' => $title])->andWhere(['<>', 'id', $task_id])->one();
                    if ($exist) {
                        $errormsg = '标题已存在!';
                    } else {
                        if ($category_id == '') {
                            $errormsg = '请勾选至少一个模板分类！';
                        } else {
                            $task->title = $title;
                            $task->save();

                            TaskCategoryId::deleteAll(['task_id' => $task_id]);
                            $categoryIds = explode(',', $category_id);

                            foreach ($categoryIds as $cate_id) {
                                $taskCategory = TaskCategory::find()->where(['id' => $cate_id])->one();
                                if ($taskCategory) {
                                    $taskCategoryId = new TaskCategoryId();
                                    $taskCategoryId->task_id = $task->id;
                                    $taskCategoryId->category_id = $cate_id;
                                    $taskCategoryId->save();
                                }
                            }


                            Yii::$app->getSession()->setFlash('success', '编辑模板【' . $task->title . '】成功！');
                            $result = true;
                        }
                    }
                }
            }else{
                $errormsg = '模板错误！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionFlow(){
        $tid = Yii::$app->request->get('tid',false);
        $task = Task::find()->where(['id'=>$tid])->one();
        if($task){
            $flow = Flow::find()->where(['task_id'=>$tid])->orderBy('step asc')->all();
            $params['list'] = $flow;
            $params['task'] = $task;
            return $this->render('flow',$params);
        }else{
            Yii::$app->getSession()->setFlash('error','流程设置对应的任务id不存在!');
            return $this->redirect(AdminFunc::adminUrl('task'));
        }
    }

    public function actionFlowCreate(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $title = trim(Yii::$app->request->post('title',false));
            $type = intval(Yii::$app->request->post('type',0));
            $user_id = intval(Yii::$app->request->post('user_id',0));
            $tid = intval(Yii::$app->request->post('tid',0));
            $enable_transfer = intval(Yii::$app->request->post('enable_transfer',0));
            if($title==''){
                $errormsg = '名称不能为空！';
            }else{
                $exist = Task::find()->where(['id'=>$tid])->one();
                if(!$exist){
                    $errormsg = '对应的任务ID不存在！';
                }else{
                    /*$existUser = User::find()->where(['id'=>$user_id])->one();
                    if(!$existUser){
                        $errormsg = '所选职员ID不存在！';
                    }else{*/
                        $last = Flow::find()->where(['task_id'=>$tid])->orderBy('step desc')->one();
                        $flow = new Flow();
                        $flow->title = $title;
                        $flow->task_id = $tid;
                        $flow->user_id = $user_id;
                        $flow->type = $type;
                        $flow->enable_transfer = $enable_transfer;
                        $flow->step = isset($last)?$last->step+1:1;
                        $flow->status = 1;
                        if($flow->save()){
                            Yii::$app->getSession()->setFlash('success','新增流程【'.$flow->title.'】成功！');
                            $result = true;
                        }else{
                            $errormsg = '保存失败，刷新页面重试!';
                        }
                    /*}*/
                }
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionFlowEdit(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $title = trim(Yii::$app->request->post('title',false));
            $type = intval(Yii::$app->request->post('type',0));
            $user_id = intval(Yii::$app->request->post('user_id',0));
            $tid = intval(Yii::$app->request->post('tid',0));
            $flow_id = intval(Yii::$app->request->post('flow_id',0));
            $enable_transfer = intval(Yii::$app->request->post('enable_transfer',0));

            if($title==''){
                $errormsg = '名称不能为空！';
            }else{
                $exist = Task::find()->where(['id'=>$tid])->one();
                if(!$exist){
                    $errormsg = '对应的任务ID不存在！';
                }else{
                    $flow = Flow::find()->where(['task_id'=>$tid,'id'=>$flow_id])->one();
                    if(!$flow){
                        $errormsg = '流程不存在！';
                    }else{
                        $flow->title = $title;
                        $flow->task_id = $tid;
                        $flow->user_id = $user_id;
                        $flow->type = $type;
                        if($flow->save()){
                            Yii::$app->getSession()->setFlash('success','修改流程【'.$flow->title.'】成功！');
                            $result = true;
                        }else{
                            $errormsg = '保存失败，刷新页面重试!';
                        }
                    }
                }
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionSetComplete(){
        $id = Yii::$app->request->get('id',false);
        $task = Task::find()->where(['id'=>$id])->one();
        if($task){
            $task->set_complete = 1;
            $task->save();
            Yii::$app->getSession()->setFlash('success','任务表【'.$task->title.'】确认完成设置！');
        }else{
            Yii::$app->getSession()->setFlash('error','任务表ID不存在！');
        }
        return $this->redirect('/admin/task');

    }

    public function actionApplyUser(){
        $tid = Yii::$app->request->get('tid',false);
        $task = Task::find()->where(['id'=>$tid])->one();
        if($task){
            $list = TaskUserWildcard::find()->where(['task_id'=>$tid])->all();

            //$params['list'] = $applyUser;
            $userList = [];
            foreach($list as $l){
                $result = $l->getUsers();
                foreach($result as $r){
                    $userList[$r->id] = $r;
                }
            }

            /*$params['applyUserList'] = $applyUserList;

            $params['userList'] = User::find()->all();*/

            $params['userList'] = $userList;

            $params['task'] = $task;
            return $this->render('apply_user',$params);
        }else{
            Yii::$app->getSession()->setFlash('error','发起人设置对应的任务id不存在!');
            return $this->redirect(AdminFunc::adminUrl('task'));
        }
    }

    public function actionApplyUser22(){
        $tid = Yii::$app->request->get('tid',false);
        $task = Task::find()->where(['id'=>$tid])->one();
        if($task){
            $applyUser= TaskApplyUser::find()->where(['task_id'=>$tid])->all();

            //$params['list'] = $applyUser;
            $applyUserList = [];
foreach($applyUser as $au){
    $applyUserList[] = $au->user_id;
}
$params['applyUserList'] = $applyUserList;

            $params['userList'] = User::find()->all();

            $params['task'] = $task;
            return $this->render('apply_user',$params);
        }else{
            Yii::$app->getSession()->setFlash('error','发起人设置对应的任务id不存在!');
            return $this->redirect(AdminFunc::adminUrl('task'));
        }
    }

    public function actionApplyUserAdd(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $user_id = intval(Yii::$app->request->post('user_id',0));
            $tid = intval(Yii::$app->request->post('tid',0));
            $exist = Task::find()->where(['id'=>$tid])->one();
            if(!$exist){
                $errormsg = '对应的任务ID不存在！';
            }else{
                $existUser = User::find()->where(['id'=>$user_id])->one();
                if(!$existUser){
                    $errormsg = '所选职员ID不存在！';
                }else{
                    $existData = TaskApplyUser::find()->where(['task_id'=>$tid,'user_id'=>$user_id])->one();
                    if($existData){
                        $errormsg = '该任务表中此发起人已存在！';
                    }else{
                        $n = new TaskApplyUser();
                        $n->task_id = $tid;
                        $n->user_id = $user_id;
                        if($n->save()){
                            Yii::$app->getSession()->setFlash('success','添加发起人成功！');
                            $result = true;
                        }else{
                            $errormsg = '保存失败，刷新页面重试!';
                        }
                    }
                }
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }


    public function actionApplyUserAdd2(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $user_id = Yii::$app->request->post('user_id','');
            $tid = intval(Yii::$app->request->post('tid',0));
            $exist = Task::find()->where(['id'=>$tid])->one();
            if(!$exist){
                $errormsg = '对应的任务ID不存在！';
            }else{
                TaskApplyUser::deleteAll(['task_id'=>$tid]);

                $userIds = explode(',',$user_id);
                foreach($userIds as $uid){
                    $n = new TaskApplyUser();
                    $n->task_id = $tid;
                    $n->user_id = $uid;
                    $n->save();
                }

                Yii::$app->getSession()->setFlash('success','修改发起人成功！');
                $result = true;

            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionDelete(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $task_id = Yii::$app->request->post('id','');
            $exist = Task::find()->where(['id'=>$task_id])->one();
            if(!$exist){
                $errormsg = '对应的任务ID不存在！';
            }else{
                Task::deleteAll(['id'=>$task_id]);

                Yii::$app->getSession()->setFlash('success','删除任务表（模板）成功！');
                $result = true;

            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionFlowDeleteAll(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $task_id = Yii::$app->request->post('id','');
            $exist = Task::find()->where(['id'=>$task_id])->one();
            if(!$exist){
                $errormsg = '对应的任务ID不存在！';
            }else{
                FLow::deleteAll(['task_id'=>$task_id]);

                Yii::$app->getSession()->setFlash('success','清空任务表流程成功！');
                $result = true;

            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }
}
