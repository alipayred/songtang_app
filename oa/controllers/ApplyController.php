<?php
namespace oa\controllers;

use oa\components\Func;
use oa\models\Apply;
use oa\models\ApplyCreateForm;
use oa\models\ApplyDoForm;
use oa\models\ApplyRecord;
use oa\models\Flow;
use oa\models\Task;
use oa\models\TaskApplyUser;
use yii\web\Response;
use Yii;

class ApplyController extends BaseController
{
    public function actionCreate(){
        $model = new ApplyCreateForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $new = new Apply();
            $new->attributes = $model->attributes;
            $new->user_id = Yii::$app->user->id;
            $new->flow_step = 1;
            $new->add_time = date('Y-m-d H:i:s');
            $new->edit_time = date('Y-m-d H:i:s');
            $new->status = 1;
            if($new->save()){
                //Yii::$app->session->setFlash()
                return $this->redirect('/');
            }
        }
        $params['model'] = $model;
        $params['tasks'] = Func::getTasksByUid(Yii::$app->user->id);
        return $this->render('create',$params);
    }

    /*
     * get-task-preview 获取申请任务的预览信息
     * 返回 html
     */
    public function actionGetTaskPreview(){
        $errormsg = '';
        $result = false;
        $html = '';
        if(Yii::$app->request->isAjax){
            $task_id = trim(Yii::$app->request->post('task_id',false));
            $task = Task::find()->where(['id'=>$task_id])->one();
            if($task){
                $flows = Flow::find()->where(['task_id'=>$task_id])->all();
                if(!empty($flows)){
                    $html.='<h3>申请表预览：</h3>';
                    foreach($flows as $f){
                        $htmlOne = '<li>';
                        $htmlOne.= '<div class="task-preview-step">步骤'.$f->step.'</div>';
                        $htmlOne.= '<div>标题：'.$f->title.'</div>';
                        $htmlOne.= '<div>类型：'.$f->typeName.'</div>';
                        $htmlOne.= '<div>操作人：'.$f->user->name.'</div>';
                        $htmlOne.= '</li>';
                        $html .= $htmlOne;
                    }
                    $result = true;
                }else{
                    $errormsg = '申请任务表没有设置流程！';
                }
            }else{
                $errormsg = '申请任务表不存在！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg,'html'=>$html];
    }


    /*
     * get-record 获取申请表详情
     * 返回 html
     */
    public function actionGetRecord(){
        $errormsg = '';
        $result = false;
        $html = '';
        if(Yii::$app->request->isAjax){
            $id = trim(Yii::$app->request->post('id',false));
            $apply = Apply::find()->where(['id'=>$id])->one();
            if($apply){
                $result = true;
                //1.发起申请
                $html = '<li><div>发起申请</div><div>操作人：<b>'.$apply->applyUser->name.'</b> 时间：<b>'.$apply->add_time.' </b></div></li>';

                //2.操作记录
                $records = ApplyRecord::find()->where(['apply_id'=>$id])->all();
                if(!empty($records)){
                    foreach($records as $r){
                        $htmlOne = '<li>';
                        $htmlOne.= '<div>步骤'.$r->flow->step.'</div>';
                        $htmlOne.= '<div>标题：<b>'.$r->flow->title.'</b>  操作类型：<b>'.$r->flow->typeName.'</b></div>';
                        $htmlOne.= '<div>操作人：<b>'.$r->flow->user->name.'</b> 时间: <b>'.$r->add_time.'</b> 结果：<b>'.Flow::getResultCn($r->flow->type,$r->result).'</b></div>';
                        $htmlOne.= '<div>备注信息：<b>'.$r->message.'</b></div>';
                        $htmlOne.= '</li>';
                        $html .= $htmlOne;
                    }
                }

                //3.剩余未完成操作
                $curStep = $apply->flow_step;
                $flow = Flow::find()->where(['task_id'=>$apply->task_id])->andWhere(['>=','step',$curStep])->all();
                foreach($flow as $f){
                    $htmlOne = '<li class="not-do">';
                    $htmlOne.= '<div>步骤'.$f->step.' 还未操作</div>';
                    $htmlOne.= '<div>标题：<b>'.$f->title.'</b>  操作类型：<b>'.$f->typeName.'</b></div>';
                    $htmlOne.= '<div>操作人：<b>'.$f->user->name.'</b> </div>';
                    $htmlOne.= '</li>';
                    $html .= $htmlOne;
                }
            }else{
                $errormsg = '申请表不存在！';
            }
        }else{
            $errormsg = '操作错误，请重试!';
        }
        $response=Yii::$app->response;
        $response->format=Response::FORMAT_JSON;
        $response->data=['result'=>$result,'errormsg'=>$errormsg,'html'=>$html];
    }


    //我的申请
    public function actionMy(){
        $list = Apply::find()->where(['user_id'=>Yii::$app->user->id])->orderBy('add_time desc')->all();

        $params['list'] = $list;
        return $this->render('my',$params);
    }

    //待办事项
    public function actionTodo(){
        //检索出所有与你相关的流程
        $flow = Flow::find()->where(['user_id'=>Yii::$app->user->id])->all();
        if(!empty($flow)){
            $list = Apply::find();
            //使用 任务id和流程步骤数 搜索当前的申请表中匹配的
            foreach($flow as $f){
                $list= $list->orWhere(['task_id'=>$f->task_id,'flow_step'=>$f->step]);
            }
            //按时间倒序
            $list = $list->orderBy('add_time desc')->all();
        }else{
            $list = [];
        }

        $params['list'] = $list;
        return $this->render('todo',$params);
    }

    //相关事项
    public function actionRelated(){
        //检索出所有与你相关的流程  按task_id分组
        $flow = Flow::find()->where(['user_id'=>Yii::$app->user->id])->groupBy('task_id')->select('task_id')->all();
        if(!empty($flow)){
            $taskIds = [];
            foreach($flow as $f){
                $taskIds[] = $f->task_id;
            }
            $list = Apply::find()->where(['task_id'=>$taskIds])->orderBy('add_time desc')->all();
        }else{
            $list = [];
        }


        $params['list'] = $list;
        return $this->render('related',$params);
    }

    /*//完结事项
    public function actionRelated(){
        //检索出所有与你相关的流程  按task_id分组
        $flow = Flow::find()->where(['user_id'=>Yii::$app->user->id])->groupBy('task_id')->select('task_id')->all();
        if(!empty($flow)){
            $taskIds = [];
            foreach($flow as $f){
                $taskIds[] = $f->task_id;
            }
            $list = Apply::find()->where(['task_id'=>$taskIds])->orderBy('add_time desc')->all();
        }else{
            $list = [];
        }


        $params['list'] = $list;
        return $this->render('related',$params);
    }*/


    public function actionDo(){
        $id = Yii::$app->request->get('id',false);
        $apply = Apply::find()->where(['id'=>$id])->one();
        if($apply){
            //1.发起申请
            $html = '<li><div>发起申请</div><div>操作人：<b>'.$apply->applyUser->name.'</b> 时间：<b>'.$apply->add_time.' </b></div></li>';

            //2.操作记录
            $records = ApplyRecord::find()->where(['apply_id'=>$id])->all();
            if(!empty($records)){
                foreach($records as $r){
                    $htmlOne = '<li>';
                    $htmlOne.= '<div>步骤'.$r->flow->step.'</div>';
                    $htmlOne.= '<div>标题：<b>'.$r->flow->title.'</b>  操作类型：<b>'.$r->flow->typeName.'</b></div>';
                    $htmlOne.= '<div>操作人：<b>'.$r->flow->user->name.'</b> 时间: <b>'.$r->add_time.'</b> 结果：<b>'.Flow::getResultCn($r->flow->type,$r->result).'</b></div>';
                    $htmlOne.= '<div>备注信息：<b>'.$r->message.'</b></div>';
                    $htmlOne.= '</li>';
                    $html .= $htmlOne;
                }
            }

            //3.剩余未完成操作
            $html2 = '';
            $curStep = $apply->flow_step;
            $flow = Flow::find()->where(['task_id'=>$apply->task_id])->andWhere(['>','step',$curStep])->all();
            foreach($flow as $f){
                $htmlOne = '<li class="not-do">';
                $htmlOne.= '<div>步骤'.$f->step.' 还未操作</div>';
                $htmlOne.= '<div>标题：<b>'.$f->title.'</b>  操作类型：<b>'.$f->typeName.'</b></div>';
                $htmlOne.= '<div>操作人：<b>'.$f->user->name.'</b> </div>';
                $htmlOne.= '</li>';
                $html2 .= $htmlOne;
            }
            $flow = Flow::find()->where(['task_id'=>$apply->task_id,'step'=>$apply->flow_step,'user_id'=>Yii::$app->user->id])->one();
            if($flow){
                $model = new ApplyDoForm();
                $model->result = 1;

                if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                    $record = new ApplyRecord();
                    $record->attributes = $model->attributes;
                    $record->apply_id = $apply->id;
                    $record->flow_id = $flow->id;
                    $record->add_time = date('Y-m-d H:i:s');
                    if($record->save()){
                        //操作类型为 1 approval审核 和 3 execute执行  结果为0 进行打回操作
                        if($record->result==0 && in_array($flow->type,[Flow::TYPE_APPROVAL,Flow::TYPE_EXECUTE])){
                            if($flow->back_step==0){
                                //打回到发起者 改为失败状态
                                $apply->status = Apply::STATUS_FAILURE;
                            }else{
                                $apply->flow_step = $flow->back_step;
                            }
                        }else{
                            //查找是否还有后续流程
                            $exist = Flow::find()->where(['task_id'=>$apply->task_id])->andWhere(['>','step',$flow->step])->one();
                            if($exist){
                                $apply->flow_step++;
                            }else{
                                // 没有就完成此申请  改为成功状态
                                $apply->status= Apply::STATUS_SUCCESS;
                            }
                        }
                        $apply->save();

                        //Yii::$app->session->setFlash()
                        return $this->redirect('/apply/todo');
                    }
                }
                $params['model'] = $model;
                $params['apply'] = $apply;
                $params['flow'] = $flow;
                $params['html'] = $html;
                $params['html2'] = $html2;
                return $this->render('do',$params);
            }
        }



    }

}
