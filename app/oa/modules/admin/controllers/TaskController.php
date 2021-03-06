<?php

namespace oa\modules\admin\controllers;

use oa\models\Flow;
use oa\models\Form;
use oa\models\FormCategory;
use oa\models\FormItem;
use oa\models\Task;
use oa\models\TaskApplyUser;
use oa\models\TaskCategory;
use oa\models\TaskCategoryId;
use oa\models\TaskForm;
use oa\models\TaskUserWildcard;
use oa\modules\admin\components\AdminFunc;
use ucenter\models\Company;
use ucenter\models\District;
use ucenter\models\Industry;
use ucenter\models\Position;
use ucenter\models\User;
use Yii;
use yii\data\Pagination;
use yii\web\Response;
/**
 *  任务流程管理
 *  task flow
 */
class TaskController extends BaseController
{
    public function actionCategory()
    {
        $list = TaskCategory::find()->orderBy(['status'=>SORT_DESC,'type'=>SORT_ASC,'ord'=>SORT_ASC])->all();


        $params['list'] = $list;
        return $this->render('category',$params);
    }

    public function actionForm()
    {
        $query = Form::find();

        $count = $query->count();
        $pageSize = 20;
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' => $pageSize,'pageSizeParam'=>false]);
        $list = $query
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->orderBy(['id'=>SORT_ASC])
            ->all();

        $params['list'] = $list;
        $params['pages'] = $pages;
        $params['categoryList'] = TaskCategory::getDropdownList();
        return $this->render('form',$params);
    }

    public function actionFormPreview(){
        $form_id = Yii::$app->request->get('id',false);
        $params['html'] = FormItem::getHtmlByForm($form_id);
        return $this->render('form_preview',$params);
    }

    public function actionIndex()
    {
        //$aid = Yii::$app->request->get('aid',false);
        //$bid = Yii::$app->request->get('bid',false);
        $search = [
            'title'=>''
        ];
        $search2 = Yii::$app->request->get('search',[]);

        foreach($search2 as $k=>$v){
            if(isset($search[$k])){
                $search[$k] = $v;
            }
        }


        $query = Task::find()->where(['status'=>1]);

        foreach($search as $k=>$v){
            if($v!=''){
                //$query = $query->andWhere($k.' like "%'.$v.'%"');
                $query = $query->andFilterWhere(['like',$k,$v]);
            }
        }

        $count = $query->count();
        $pageSize = 20;
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' => $pageSize,'pageSizeParam'=>false]);
        $list = $query
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->orderBy('id desc')
            ->all();

        $params['list'] = $list;
        $params['pages'] = $pages;
        /*$params['districtArr'] = District::getNameArr();
        $params['industryArr'] = Industry::getNameArr();
        $params['companyArr'] = Company::getNameArr();*/
        //$params['pArr'] = Position::getNameArr();
        //$params['industryArr2'] = District::getIndustryRelationsArr($aid);

        //$params['aid'] = $aid;
        //$params['bid'] = $bid;
        $params['taskCategoryList'] = TaskCategory::getDropdownList();
        $params['formList'] = Form::getDropdownList();
        $params['search'] = $search;
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


    public function actionFormGet(){
        $errormsg = '';
        $result = false;
        $info = [];
        if(Yii::$app->request->isAjax){
            $id = Yii::$app->request->post('id',0);
            $form = Form::find()->where(['id'=>$id])->one();
            if($form){
                $info['title'] = $form->title;
                $category = FormCategory::find()->where(['form_id'=>$id])->all();
                $cateArr = [];
                foreach($category as $c){
                    $cateArr[] = $c->category_id;
                }
                $info['category_ids'] = implode(',',$cateArr);
                $result = true;
            }else{
                $errormsg = '表单不存在';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg,'info'=>$info];
    }

    public function actionTaskFormGet(){
        $errormsg = '';
        $result = false;
        $info = [];
        if(Yii::$app->request->isAjax){
            $id = Yii::$app->request->post('id',0);
            $task = Task::find()->where(['id'=>$id])->one();
            if($task){
                $info['title'] = $task->title;
                $form = TaskForm::find()->where(['task_id'=>$id])->all();
                $formArr = [];
                foreach($form as $f){
                    $formArr[] = $f->form_id;
                }
                $info['form_ids'] = implode(',',$formArr);
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


    public function actionFormCreate(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $title = trim(Yii::$app->request->post('title',false));
            $category_id = Yii::$app->request->post('category_id','');
            if($title==''){
                $errormsg = '标题不能为空！';
            }else{
                $exist = Form::find()->where(['title'=>$title])->one();
                if($exist){
                    $errormsg = '标题已存在!';
                }else{
                    if($category_id==''){
                        $errormsg = '请勾选至少一个分类！';
                    }else{
                        $form = new Form();
                        $form->title = $title;
                        $form->set_complete = 0;
                        $form->status = 1;
                        if($form->save()){
                            $categoryIds = explode(',',$category_id);

                            foreach($categoryIds as $cate_id){
                                $taskCategory = TaskCategory::find()->where(['id'=>$cate_id])->one();
                                if($taskCategory){
                                    $formCategory = new FormCategory();
                                    $formCategory->form_id = $form->id;
                                    $formCategory->category_id = $cate_id;
                                    $formCategory->save();
                                }
                            }
                            /*$user = User::find()->all();
                            foreach($user as $u){
                                $taskUser = new TaskApplyUser();
                                $taskUser->task_id = $task->id;
                                $taskUser->user_id = $u->id;
                                $taskUser->save();
                            }*/


                            Yii::$app->getSession()->setFlash('success','新增表单【'.$form->title.'】成功！');
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

    public function actionFormEdit(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $id = Yii::$app->request->post('form_id',0);
            $title = trim(Yii::$app->request->post('title',false));
            $category_id = Yii::$app->request->post('category_id','');
            $form = Form::find()->where(['id'=>$id])->one();
            if($form){
                if($form->set_complete == 1){
                    $errormsg = '表单状态为使用中！';
                }else{
                    if($title==''){
                        $errormsg = '标题不能为空！';
                    }else {
                        $exist = Form::find()->where(['title' => $title])->andWhere(['<>', 'id', $id])->one();
                        if ($exist) {
                            $errormsg = '标题已存在!';
                        } else {
                            if ($category_id == '') {
                                $errormsg = '请勾选至少一个分类！';
                            } else {
                                $form->title = $title;
                                $form->save();

                                FormCategory::deleteAll(['form_id' => $id]);
                                $categoryIds = explode(',', $category_id);

                                foreach ($categoryIds as $cate_id) {
                                    $taskCategory = TaskCategory::find()->where(['id' => $cate_id])->one();
                                    if ($taskCategory) {
                                        $formCategory = new FormCategory();
                                        $formCategory->form_id = $form->id;
                                        $formCategory->category_id = $cate_id;
                                        $formCategory->save();
                                    }
                                }


                                Yii::$app->getSession()->setFlash('success', '编辑表单【' . $form->title . '】成功！');
                                $result = true;
                            }
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

    public function actionTaskFormEdit(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $task_id = Yii::$app->request->post('task_id',0);
            $form_id = Yii::$app->request->post('form_id','');
            $task = Task::find()->where(['id'=>$task_id])->one();
            if($task){
                TaskForm::deleteAll(['task_id' => $task_id]);
                $formIds = explode(',', $form_id);

                foreach ($formIds as $f_id) {
                    $form = Form::find()->where(['id' => $f_id])->one();
                    if ($form) {
                        $taskForm = new TaskForm();
                        $taskForm->task_id = $task->id;
                        $taskForm->form_id = $f_id;
                        $taskForm->save();
                    }
                }


                Yii::$app->getSession()->setFlash('success', '编辑模板【' . $task->title . '】成功！');
                $result = true;

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
            $params['positionList'] = Flow::getPositionList($tid);
            return $this->render('flow',$params);
        }else{
            Yii::$app->getSession()->setFlash('error','流程设置对应的任务id不存在!');
            return $this->redirect(AdminFunc::adminUrl('task'));
        }
    }

    public function actionFormItem(){
        $id = Yii::$app->request->get('id',false);
        $form = Form::find()->where(['id'=>$id])->one();
        if($form){
            $formItem = FormItem::find()->where(['form_id'=>$id])->orderBy('ord asc')->all();
            $params['list'] = $formItem;
            $params['form'] = $form;

            $params['positionList'] = FormItem::getPositionList($id);
            return $this->render('form_item',$params);
        }else{
            Yii::$app->getSession()->setFlash('error','表单ID错误!');
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
            $position = trim(Yii::$app->request->post('position',''));
            if($title==''){
                $errormsg = '名称不能为空！';
            }else{
                $exist = Task::find()->where(['id'=>$tid])->one();
                if(!$exist){
                    $errormsg = '对应的任务ID不存在！';
                }else{

                    if($position == 'first'){
                        $step = 1;
                        Flow::ordDownAll($tid,$step);
                    }elseif($position == 'last'){
                        $last = Flow::find()->where(['task_id'=>$tid])->orderBy('step desc')->one();
                        if($last){
                            $step = intval($last->step) + 1;
                        }else{
                            $step = 1;
                        }
                    }else{
                        $existItem2 = Flow::find()->where(['task_id'=>$tid,'step'=>$position])->one();
                        if($existItem2){
                            $step = $position + 1;
                            Flow::ordDownAll($tid,$step);
                        }else{
                            $errormsg = '对应步骤的流程不存在！';
                        }
                    }
                    if($errormsg==''){
                        $flow = new Flow();
                        $flow->title = $title;
                        $flow->task_id = $tid;
                        $flow->user_id = $user_id;
                        $flow->type = $type;
                        $flow->enable_transfer = $enable_transfer;
                        $flow->step = $step;
                        $flow->status = 1;
                        if($flow->save()){
                            Yii::$app->getSession()->setFlash('success','新增流程【'.$flow->title.'】成功！');
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

    public function actionFormItemCreate(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $form_id =intval(Yii::$app->request->post('form_id',0));
            $key = trim(Yii::$app->request->post('key',false));
            $label = trim(Yii::$app->request->post('label',false));
            $label_width = trim(Yii::$app->request->post('label_width',false));
            $input_width = trim(Yii::$app->request->post('input_width',false));
            $input_type = intval(Yii::$app->request->post('input_type',0));
            $input_options = trim(Yii::$app->request->post('input_options',''));
            $position = trim(Yii::$app->request->post('position',''));
            if($key==false || $label==false){
                $errormsg = '名称不能为空！';
            }else{
                $form = Form::find()->where(['id'=>$form_id])->one();
                if(!$form){
                    $errormsg = '对应的表单ID不存在！';
                }else{
                    $existItem = FormItem::find()->where(['form_id'=>$form_id,'item_key'=>$key])->one();
                    if(!$existItem){
                        if($position == 'first'){
                            $ord = 1;
                            FormItem::ordDownAll($form_id,$ord);
                        }elseif($position == 'last'){
                            $last = FormItem::find()->where(['form_id'=>$form_id])->orderBy('ord desc')->one();
                            if($last){
                                $ord = intval($last->ord) + 1;
                            }else{
                                $ord = 1;
                            }
                        }else{
                            $existItem2 = FormItem::find()->where(['form_id'=>$form_id,'ord'=>$position])->one();
                            if($existItem2){
                                $ord = $position+1;
                                FormItem::ordDownAll($form_id,$ord);
                            }else{
                                $errormsg = '对应排序的选项不存在！';
                            }
                        }

                        if($errormsg ==''){
                            $formItem = new FormItem();
                            $formItem->form_id = $form_id;
                            $formItem->item_key = $key;
                            $valueArr = [
                                'label'=>$label,
                                'label_width'=>$label_width,
                                'input_width'=>$input_width,
                                'input_type'=>$input_type,
                                'input_options'=>explode("\n",$input_options)
                            ];

                            $formItem->item_value = json_encode($valueArr);
                            $formItem->ord = $ord;
                            $formItem->status = 1;
                            if($formItem->save()){
                                Yii::$app->getSession()->setFlash('success','新增选项【'.$form->title.'】成功！');
                                $result = true;
                            }else{
                                $errormsg = '保存失败，刷新页面重试!';
                            }
                        }
                    }else{
                        $errormsg = 'Key名重复!';
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



    public function actionFormItemEdit(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $form_id =intval(Yii::$app->request->post('form_id',0));
            $item_id =intval(Yii::$app->request->post('item_id',0));
            $key = trim(Yii::$app->request->post('key',false));
            $label = trim(Yii::$app->request->post('label',false));
            $label_width = trim(Yii::$app->request->post('label_width',false));
            $input_width = trim(Yii::$app->request->post('input_width',false));
            $input_type = intval(Yii::$app->request->post('input_type',0));
            $input_options = trim(Yii::$app->request->post('input_options',''));
            $position = trim(Yii::$app->request->post('position',''));
            if($key==false || $label==false){
                $errormsg = '名称不能为空！';
            }else{
                $form = Form::find()->where(['id'=>$form_id])->one();
                if(!$form){
                    $errormsg = '对应的表单ID不存在！';
                }else{
                    $existItem = FormItem::find()->where(['id'=>$item_id])->one();
                    if($existItem){
                        $existKey = FormItem::find()->where(['item_key'=>$key,'form_id'=>$form_id])->andWhere(['<>','id',$item_id])->one();
                        if(!$existKey){

                            $existItem->item_key = $key;
                            $valueArr = [
                                'label'=>$label,
                                'label_width'=>$label_width,
                                'input_width'=>$input_width,
                                'input_type'=>$input_type,
                                'input_options'=>explode("\n",$input_options)
                            ];

                            $existItem->item_value = json_encode($valueArr);
                            if($existItem->save()){
                                Yii::$app->getSession()->setFlash('success','编辑选项成功！');
                                $result = true;
                            }else{
                                $errormsg = '保存失败，刷新页面重试!';
                            }
                        }else{
                            $errormsg = 'Key名已存在!';
                        }
                    }else{
                        $errormsg = '对应选项不存在!';
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
            Yii::$app->getSession()->setFlash('success','模板【'.$task->title.'】启用成功！');
        }else{
            Yii::$app->getSession()->setFlash('error','模板ID不存在！');
        }
        return $this->redirect('/admin/task');

    }

    public function actionSetComplete2(){
        $id = Yii::$app->request->get('id',false);
        $task = Task::find()->where(['id'=>$id])->one();
        if($task){
            $task->set_complete = 0;
            $task->save();
            Yii::$app->getSession()->setFlash('success','模板【'.$task->title.'】暂停使用！');
        }else{
            Yii::$app->getSession()->setFlash('error','任务表ID不存在！');
        }
        return $this->redirect('/admin/task');

    }

    public function actionFormSetComplete(){
        $id = Yii::$app->request->get('id',false);
        $form = Form::find()->where(['id'=>$id])->one();
        if($form){
            $form->set_complete = 1;
            $form->save();
            Yii::$app->getSession()->setFlash('success','表单【'.$form->title.'】启用成功！');
        }else{
            Yii::$app->getSession()->setFlash('error','表单ID不存在！');
        }
        return $this->redirect('/admin/task/form');

    }

    public function actionFormSetComplete2(){
        $id = Yii::$app->request->get('id',false);
        $form = Form::find()->where(['id'=>$id])->one();
        if($form){
            $form->set_complete = 0;
            $form->save();
            Yii::$app->getSession()->setFlash('success','表单【'.$form->title.'】暂停使用！');
        }else{
            Yii::$app->getSession()->setFlash('error','表单ID不存在！');
        }
        return $this->redirect('/admin/task/form');

    }






    public function actionApplyUserDel(){
        $id = Yii::$app->request->get('id',false);
        $tid = Yii::$app->request->get('tid',false);
        $one = TaskUserWildcard::find()->where(['id'=>$id])->one();
        if($one){
            TaskUserWildcard::deleteAll(['id'=>$id]);
            Yii::$app->getSession()->setFlash('success','删除"发起人设置"完成！');
        }else{
            Yii::$app->getSession()->setFlash('error','发起人设置,不存在！');
        }
        return $this->redirect('/admin/task/apply-user?tid='.$tid);

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
            $params['list'] = $list;

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

    public function actionFormItemOrdChange(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){

            $form_id = intval(Yii::$app->request->post('form_id',0));
            $item_id = intval(Yii::$app->request->post('item_id',0));
            $action = Yii::$app->request->post('action','');

            $form = Form::find()->where(['id'=>$form_id])->one();
            if(!$form){
                $errormsg = '对应的表单ID不存在！';
            }else {
                $item = FormItem::find()->where(['form_id' => $form_id, 'id' => $item_id])->one();
                if (!$item) {
                    $errormsg = '对应选项不存在！';
                }else{
                    $ord = $item->ord;
                    if($action == 'up'){
                        $ord2 = $ord - 1;

                    }elseif($action == 'down'){
                        $ord2 = $ord + 1;

                    }else{
                        $errormsg = '动作参数错误！';
                    }


                    if($errormsg==''){
                        $item2 = FormItem::find()->where(['form_id'=>$form_id, 'ord'=>$ord2])->one();
                        if($item2){
                            $item2->ord = $ord;
                            $item2->save();
                            $item->ord = $ord2;
                            $item->save();

                            Yii::$app->getSession()->setFlash('success','修改选项排序成功！');
                            $result = true;
                        }else{
                            $errormsg = '要交换顺序的选项不存在！';
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

    public function actionFlowStepChange(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){

            $task_id = intval(Yii::$app->request->post('task_id',0));
            $flow_id = intval(Yii::$app->request->post('flow_id',0));
            $action = Yii::$app->request->post('action','');

            $task = Task::find()->where(['id'=>$task_id])->one();
            if(!$task){
                $errormsg = '对应的模板ID不存在！';
            }else {
                $flow = Flow::find()->where(['task_id' => $task_id, 'id' => $flow_id])->one();
                if (!$flow) {
                    $errormsg = '对应流程步骤不存在！';
                }else{
                    $step = $flow->step;
                    if($action == 'up'){
                        $step2 = $step - 1;

                    }elseif($action == 'down'){
                        $step2 = $step + 1;

                    }else{
                        $errormsg = '动作参数错误！';
                    }


                    if($errormsg==''){
                        $flow2 = Flow::find()->where(['task_id'=>$task_id, 'step'=>$step2])->one();
                        if($flow2){
                            $flow2->step = $step;
                            $flow2->save();
                            $flow->step = $step2;
                            $flow->save();

                            Yii::$app->getSession()->setFlash('success','修改步骤顺序成功！');
                            $result = true;
                        }else{
                            $errormsg = '要交换顺序的流程不存在！';
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

    public function actionApplyUserAdd(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){

            $tid = intval(Yii::$app->request->post('tid',0));
            $district_id = intval(Yii::$app->request->post('district_id',10000));
            $industry_id = intval(Yii::$app->request->post('industry_id',10000));
            $company_id = intval(Yii::$app->request->post('company_id',10000));
            $department_id = intval(Yii::$app->request->post('department_id',10000));
            $position_id = intval(Yii::$app->request->post('position_id',10000));

            $new = new TaskUserWildcard();
            $new->task_id = $tid;
            $new->district_id = $district_id;
            $new->industry_id = $industry_id;
            $new->company_id = $company_id;
            $new->department_id = $department_id;
            $new->position_id = $position_id;
            $new->save();



            Yii::$app->getSession()->setFlash('success','添加发起人成功！');
            $result = true;
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionApplyUserEdit(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){

            $id = intval(Yii::$app->request->post('edit_id',0));
            $one = TaskUserWildcard::find()->where(['id'=>$id])->one();
            if($one){
                $district_id = intval(Yii::$app->request->post('district_id',10000));
                $industry_id = intval(Yii::$app->request->post('industry_id',10000));
                $company_id = intval(Yii::$app->request->post('company_id',10000));
                $department_id = intval(Yii::$app->request->post('department_id',10000));
                $position_id = intval(Yii::$app->request->post('position_id',10000));

                $one->district_id = $district_id;
                $one->industry_id = $industry_id;
                $one->company_id = $company_id;
                $one->department_id = $department_id;
                $one->position_id = $position_id;
                $one->save();

                Yii::$app->getSession()->setFlash('success','编辑发起人成功！');
                $result = true;
            }else{
                $errormsg = '设置 没找到!';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }



    public function actionFlowDel(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $task_id = intval(Yii::$app->request->post('task_id',0));
            $flow_id = intval(Yii::$app->request->post('flow_id',0));
            $task = Task::find()->where(['id'=>$task_id])->one();
            if($task){
                if($task->set_complete == 0){
                    $flow = Flow::find()->where(['task_id'=>$task_id,'id'=>$flow_id])->one();
                    if($flow){
                        $flow->delete();
                        Flow::ordUpAll($task_id,$flow->step);
                        Yii::$app->getSession()->setFlash('success','删除流程【'.$flow->title.'】成功！');
                        $result = true;
                    }else{
                        $errormsg = '对应流程不存在！';
                    }
                }else{
                    $errormsg = '对应的模板不是暂停状态不能修改流程！';
                }
            }else{
                $errormsg = '对应的模板ID不存在！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionApplyUserAdd2233(){
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
            $task = Task::find()->where(['id'=>$task_id])->one();
            if(!$task){
                $errormsg = '对应的任务ID不存在！';
            }else{

                $task->status = 2;
                $task->save();
                //Task::deleteAll(['id'=>$task_id]);

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


    public function actionFormItemDelAll(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $form_id = Yii::$app->request->post('id','');
            $form = Form::find()->where(['id'=>$form_id])->one();
            if($form){
                if($form->set_complete == 0){
                    FormItem::deleteAll(['form_id'=>$form_id]);

                    Yii::$app->getSession()->setFlash('success','清空表单【'.$form->title.'】选项成功！');
                    $result = true;
                }else{
                    $errormsg = '表单不是暂停状态不能修改选项！';
                }
            }else{
                $errormsg = '对应的表单不存在！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionFormItemDel(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $form_id = Yii::$app->request->post('form_id','');
            $item_id = Yii::$app->request->post('item_id','');
            $form = Form::find()->where(['id'=>$form_id])->one();
            if($form){
                if($form->set_complete == 0){
                    $form_item = FormItem::find()->where(['form_id'=>$form_id,'id'=>$item_id])->one();
                    if($form_item){
                        $form_item->delete();
                        FormItem::ordUpAll($form->id,$form_item->ord);
                        Yii::$app->getSession()->setFlash('success','删除表单【'.$form->title.'】选项['.$form_item->item_key.']成功！');
                        $result = true;
                    }else{
                        $errormsg = '对应表单选项不存在！';
                    }
                }else{
                    $errormsg = '表单不是暂停状态不能修改选项！';
                }
            }else{
                $errormsg = '对应的表单不存在！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }

    public function actionFormDel(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $form_id = Yii::$app->request->post('form_id','');
            $form = Form::find()->where(['id'=>$form_id])->one();
            if($form){
                if($form->set_complete == 0){
                    $form->delete();
                    TaskForm::deleteAll(['form_id'=>$form->id]);
                    FormCategory::deleteAll(['form_id'=>$form->id]);
                    Yii::$app->getSession()->setFlash('success','删除表单【'.$form->title.'】成功！');
                    $result = true;
                }else{
                    $errormsg = '表单不是暂停状态不能删除！';
                }
            }else{
                $errormsg = '对应的表单不存在！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }


    public function actionCopy(){
        $errormsg = '';
        $result = false;
        if(Yii::$app->request->isAjax){
            $copy_from_id = Yii::$app->request->post('copy_from_id',0);
            $new_title = trim(Yii::$app->request->post('new_title',false));
            //$category_id = Yii::$app->request->post('category_id','');
            $copyFromTask = Task::find()->where(['id'=>$copy_from_id])->one();
            if($copyFromTask){
                if($new_title==''){
                    $errormsg = '标题不能为空！';
                }else {
                    $exist = Task::find()->where(['title' => $new_title])->one();
                    if ($exist) {
                        $errormsg = '标题已存在!';
                    } else {
                        //生成task主体
                        $newTask = new Task();
                        $newTask->title = $new_title;
                        $newTask->district_id = 10000;
                        $newTask->industry_id = 10000;
                        $newTask->company_id = 10000;
                        $newTask->department_id = 10000;
                        $newTask->ord = 0;
                        $newTask->set_complete = 0;
                        $newTask->status = 1;
                        if($newTask->save()){
                            $newId = $newTask->id;

                            //复制分类
                            $copyFromTCId = TaskCategoryId::find()->where(['task_id' => $copy_from_id])->All();
                            foreach($copyFromTCId as $tcid){
                                $newTcid = new TaskCategoryId();
                                $newTcid->task_id = $newId;
                                $newTcid->category_id = $tcid->category_id;
                                $newTcid->save();
                            }

                            //复制相关表单
                            $copyFromTFId = TaskForm::find()->where(['task_id' => $copy_from_id])->All();
                            foreach($copyFromTFId as $tfid){
                                $newTfid = new TaskForm();
                                $newTfid->task_id = $newId;
                                $newTfid->form_id = $tfid->form_id;
                                $newTfid->save();
                            }

                            //复制流程
                            $copyFromFlows = Flow::find()->where(['task_id' => $copy_from_id])->All();
                            foreach($copyFromFlows as $flow){
                                $newFlow = new Flow();
                                $newFlow->attributes = $flow->attributes;
                                $newFlow->task_id = $newId;
                                $newFlow->save();
                            }

                            //复制发起人
                            $copyFromTUId = TaskUserWildcard::find()->where(['task_id' => $copy_from_id])->All();
                            foreach($copyFromTUId as $tuid){
                                $newTuid = new TaskUserWildcard();
                                $newTuid->attributes = $tuid->attributes;
                                $newTuid->task_id = $newId;
                                $newTuid->save();
                            }


                            Yii::$app->getSession()->setFlash('success', '复制模板【' . $newTask->title . '】成功！');
                            $result = true;
                        }else{
                            $errormsg = '复制模板主体失败！';
                        }
                    }
                }
            }else{
                $errormsg = '原模板错误！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg];
    }


    public function actionExport(){
        ob_start();
        header("Content-type: text/html; charset=utf-8");

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName("微软雅黑")->setSize(10)->setBold(true);
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);



        $this->getOneSheet($objPHPExcel,0,'上海');
        $this->getOneSheet($objPHPExcel,1,'南京');



        //exit;

        ob_end_clean();
        //ob_clean();

        header('Content-Type: application/vnd.ms-excel');
        $filename = 'songtang_oa-task_template_'.date('Y-m-dTH:i:s');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }


    public function getOneSheet($objPHPExcel,$index,$district){
        if($index>0){
            $objPHPExcel->createSheet();
        }
        $objPHPExcel->setactivesheetindex($index);
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle($district);


        $objSheet->getColumnDimension('A')->setWidth(8);
        $objSheet->getColumnDimension('B')->setWidth(8);
        $objSheet->getColumnDimension('C')->setWidth(30);
        $objSheet->getColumnDimension('D')->setWidth(20);
        $objSheet->getColumnDimension('E')->setWidth(20);
        $objSheet->getColumnDimension('F')->setWidth(30);

        $objSheet->getColumnDimension('G')->setWidth(18);
        $objSheet->getColumnDimension('H')->setWidth(20);
        $objSheet->getColumnDimension('I')->setWidth(16);
        $objSheet->getColumnDimension('J')->setWidth(16);
        $objSheet->getColumnDimension('K')->setWidth(16);
        $objSheet->getColumnDimension('L')->setWidth(16);
        $objSheet->getColumnDimension('M')->setWidth(16);


        $objSheet->setCellValue('A1','#');
        $objSheet->setCellValue('B1','ID');
        $objSheet->setCellValue('C1','标题');
        $objSheet->setCellValue('D1','所属分类');
        $objSheet->setCellValue('E1','表单分配');
        $objSheet->setCellValue('F1','发起人');

        $objSheet->setCellValue('G1','部门领导 | 审批');
        $objSheet->setCellValue('H1','行控中心/综管部 | 审批');
        $objSheet->setCellValue('I1','财务部 | 审核');
        $objSheet->setCellValue('J1','总经理A | 审批');
        $objSheet->setCellValue('K1','总经理B | 审批');
        $objSheet->setCellValue('L1','总经理C | 审批');
        $objSheet->setCellValue('M1','综管部/财务部 | 执行');

        $objSheet->setCellValue('N1','状态');

        $tasks = Task::find()
            //->filterWhere(['like','title',$district])
            ->where(['status'=>1])
            ->andOnCondition('left(title,2) = "'.$district.'"')
            ->all();
        $i = 2;
        foreach($tasks as $task){
            echo $task->title.'<br/>';

            //分类
            $cateContent = '';
            $cates = TaskCategoryId::find()->where(['task_id'=>$task->id])->all();
            foreach($cates as $cate){
                $cateName = $cate->category->name;
                $cateContent .= ($cateContent!=''?"\n":'').$cateName;
                echo $cateName.'<br/>';
            }
            $objSheet->setCellValue('D'.$i,$cateContent);

            //表单
            $formContent = '';
            $forms = TaskForm::find()->where(['task_id'=>$task->id])->all();
            foreach($forms as $form){
                $formName = $form->form->title;
                $formContent .= ($formContent!=''?"\n":'').$formName;
                echo $formName.'<br/>';
            }
            $objSheet->setCellValue('E'.$i,$formContent);

            //发起人
            $userContent = '';
            $users = TaskUserWildcard::find()->where(['task_id'=>$task->id])->all();
            foreach($users as $user){
                $userOne = '';
                $userOne .= $user->district->name.' / ';
                $userOne .= $user->industry->name.' / ';
                $userOne .= $user->company->name.' / ';
                $userOne .= $user->department->name.' / ';  //暂时不处理有多层级的department  默认p_id = 0
                $userOne .= $user->position->name;   //职位 p_id >0
                $userContent .= ($userContent!=''?"\n":'').$userOne;


                echo $userOne.'<br/>';

            }
            $objSheet->setCellValue('F'.$i,$userContent);


            //流程
            $flows = Flow::find()->where(['task_id'=>$task->id])->all();
            foreach($flows as $flow){
                //var_dump($flow->user_id);
                $flowUserName = $flow->user_id>0?($flow->user?$flow->user->name:'N/A'):'选择';
                switch($flow->title) {
                    case '部门领导':
                        $objSheet->setCellValue('G'.$i,$flowUserName);
                        break;
                    case '行控中心/综管部':
                        $objSheet->setCellValue('H'.$i,$flowUserName);
                        break;
                    case '财务部':
                        $objSheet->setCellValue('I'.$i,$flowUserName);
                        break;
                    case '总经理A':
                        $objSheet->setCellValue('J'.$i,$flowUserName);
                        break;
                    case '总经理B':
                        $objSheet->setCellValue('K'.$i,$flowUserName);
                        break;
                    case '总经理C':
                        $objSheet->setCellValue('L'.$i,$flowUserName);
                        break;
                    case '综管部/财务部':
                        $objSheet->setCellValue('M'.$i,$flowUserName);
                        break;
                }
            }




            $objSheet->setCellValue('A'.$i,$i-1);
            $objSheet->setCellValue('B'.$i,$task->id);
            $objSheet->setCellValue('C'.$i,$task->title);
            $objSheet->setCellValue('N'.$i,($task->set_complete==1)?'启用':'暂停');

            $i++;
            echo '==========<br/>';
        }


    }



    public function actionImport(){
        $file = '/Users/dodomogu/Downloads/11.xls';


        $file = iconv("utf-8", "gb2312", $file);   //转码
        if(empty($file) OR !file_exists($file)) {
            die('file not exists!');
        }
        //include('PHPExcel.php');  //引入PHP EXCEL类
        $objRead = new \PHPExcel_Reader_Excel2007();   //建立reader对象
        if(!$objRead->canRead($file)){
            $objRead = new \PHPExcel_Reader_Excel5();
            if(!$objRead->canRead($file)){
                die('No Excel!');
            }
        }


        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $obj = $objRead->load($file);  //建立excel对象
        $sheet = 0;
        $currSheet = $obj->getSheet($sheet);   //获取指定的sheet表
        $columnH = $currSheet->getHighestColumn();   //取得最大的列号
        $columnCnt = array_search($columnH, $cellName);
        $rowCnt = $currSheet->getHighestRow();   //获取总行数

        $data = array();
        for($_row=1; $_row<=$rowCnt; $_row++){  //读取内容
            for($_column=0; $_column<=$columnCnt; $_column++){
                $cellId = $cellName[$_column].$_row;
                $cellValue = $currSheet->getCell($cellId)->getValue();
                //$cellValue = $currSheet->getCell($cellId)->getCalculatedValue();  #获取公式计算的值
                if($cellValue instanceof \PHPExcel_RichText){   //富文本转换字符串
                    $cellValue = $cellValue->__toString();
                }

                $data[$_row][$cellName[$_column]] = $cellValue;
            }
        }

        //ob_start();
        header("Content-type: text/html; charset=utf-8");
        echo '<table>';
        foreach($data as $d){
            echo '<tr>';
            foreach($d as $d1){
                echo '<td>';
                echo $d1;
                echo '</td>';
            }

            echo '</tr>';
        }
        echo '</table>';

        //ob_end_clean();

    }

}
