<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use oa\assets\AppAsset;
use yii\helpers\Url;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?=Yii::$app->id. ($this->title?'_'.Html::encode($this->title):'') ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?php
NavBar::begin([
    'brandLabel' => Html::img('/images/logo.png'),
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-default navbar-fixed-top',
        'id'=>'top-navbar'
    ],
]);
    $menuItems = [];
    $menuItems[] = ['label' => '首页', 'url' => Url::to('/'),'options'=>['class'=>'nav-do-btn'],'active'=>$this->context->getRoute()=='site/index'?true:false];
    $menuItems[] = ['label' => '发起申请', 'url' => Url::to('/apply/create'),'options'=>['class'=>'nav-do-btn'],'active'=>$this->context->getRoute()=='apply/create'?true:false];
    $menuItems[] = ['label' => '我的申请', 'url' => Url::to('/apply/my'),'options'=>['class'=>'nav-do-btn'],'active'=>$this->context->getRoute()=='apply/my'?true:false];
    $menuItems[] = ['label' => '待办事项', 'url' => Url::to('/apply/todo'),'options'=>['class'=>'nav-do-btn'],'active'=>$this->context->getRoute()=='apply/todo'||$this->context->getRoute()=='apply/do'?true:false];
    $menuItems[] = ['label' => '相关事项', 'url' => Url::to('/apply/related'),'options'=>['class'=>'nav-do-btn'],'active'=>$this->context->getRoute()=='apply/related'?true:false];
    $menuItems[] = ['label' => '办结事项', 'url' => Url::to('/apply/done'),'options'=>['class'=>'nav-do-btn'],'active'=>$this->context->getRoute()=='apply/done'?true:false];
    $menuItems[] = ['label' => '安全退出', 'url' => Yii::$app->params['logoutUrl'],'options'=>['class'=>'nav-exit-btn']];
echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right','id'=>'top-nav'],

    'items' => $menuItems,
]);
NavBar::end();
?>
<?php if(Yii::$app->controller->route=='site/index'):?>
    <div style="background:url('/images/main/site/index_banner_bg.jpg');height:500px;overflow: hidden;margin-top:110px;text-align: center;">
        <img src="/images/main/site/index_banner.jpg" style="height:500px;width:1200px;margin:0 auto;" />
    </div>
<?php endif;?>
<div class="wrap">
    <div class="container" <?php if(Yii::$app->controller->route=='site/index'):?>style="padding-top:16px;"<?php endif;?>>
        <?=$this->render('sidebar')?>
        <?/*=$this->render('page_head')*/?>
        <section id="main">
            <?= $content ?>
        </section>
    </div>
</div>
<footer class="footer">
    <!--<div class="container">
        <p class="pull-left">&copy; My Company </p>

        <p class="pull-right"></p>
    </div>-->
    <div class=" text-center">
        <div class="logo-line">
            <img src="/images/footer.png" style="width:1140px;">
        </div>
        <!--<div class="txt-line">
            Tel: 021-50103599  Fax: 021-50103598  Email: songtang@126.com
        </div>
        <div class="txt-line">
            地址：上海市-闵行区-中春路9988号 Add: No. 9988, Zhongchun Road, Minhang District, Shanghai
        </div>-->
    </div>
</footer>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
