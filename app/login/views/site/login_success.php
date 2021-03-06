<?php
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

use login\assets\AppAsset;

$this->title = '登陆成功';
AppAsset::register($this);  /* 注册appAsset */
AppAsset::addCssFile($this,'css/login_success.css');
AppAsset::addJsFile($this,'js/login_success.js');
?>

<?php
    $hasFrontend = false;
    $hasBackend = false;

    $isYunFrontend = false;
    $isOaFrontend = false;

    $isUcenterAdmin = false;

    $isYunBackendAdmin = false;
    $isOaBackendAdmin = false;

    $user = Yii::$app->user->identity;

    if($user->isYunFrontend || $user->isYunFrontendAdmin || $user->isSuperAdmin){
        $isYunFrontend = true;
        $hasFrontend = true;
    }

    if($user->isOaFrontend  || $user->isOaFrontendAdmin || $user->isSuperAdmin){
        $isOaFrontend = true;
        $hasFrontend = true;
    }

    if($user->isUcenterAdmin || $user->isSuperAdmin){
        $isUcenterAdmin = true;
        $hasBackend = true;
    }

    if($user->isYunBackendAdmin || $user->isSuperAdmin){
        $isYunBackendAdmin = true;
        $hasBackend = true;
    }

    if($user->isOaBackendAdmin || $user->isSuperAdmin){
        $isOaBackendAdmin = true;
        $hasBackend = true;
    }
?>
<?php $this->beginPage(); /* 页面开始标志位 */ ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody(); /* body开始标志位 */ ?>
<section id="site-login">
    <header class="text-center">
        <img class="logo" src="/images/logo.png" />
    </header>


    <div class="text-center main-content">
        <div class="success-div">
            <img src="/images/site-login-success/success.png"/>
        </div>
        <div class="word-div">
            <img src="/images/site-login/word2.png" style="width:500px;"/>
        </div>
        <?php if($hasFrontend):?>
        <div class="goto">
            <div class="title">
                您可以前往
            </div>
            <ul>
                <?php if($isOaFrontend):?>
                <li>
                    <?=Html::a('颂唐OA',Yii::$app->params['oaAppUrl'],['class'=>'btn btn-default btn-xs'])?>
                </li>
                <?php endif;?>
                <?php if($isYunFrontend):?>
                <li>
                    <?=Html::a('颂唐云',Yii::$app->params['yunAppUrl'],['class'=>'btn btn-default btn-xs'])?>
                </li>
                <?php endif;?>
            </ul>
        </div>
        <?php endif;?>
        <?php if($hasBackend):?>
        <div class="goto">
           <div class="title">
               如果您是管理员，可以前往
           </div>
           <ul>
                <?php if($isUcenterAdmin):?>
                <li>
                   <?=Html::a('颂唐用户中心',Yii::$app->params['ucenterAppUrl'],['class'=>'btn btn-default btn-xs'])?>
                </li>
                <?php endif;?>
                <?php if($isOaBackendAdmin):?>
                <li>
                   <?=Html::a('颂唐OA后台',Yii::$app->params['oaAppAdminUrl'],['class'=>'btn btn-default btn-xs'])?>
                </li>
                <?php endif;?>
                <?php if($isYunBackendAdmin):?>
                <li>
                   <?=Html::a('颂唐云后台',Yii::$app->params['yunAppAdminUrl'],['class'=>'btn btn-default btn-xs'])?>
                </li>
                <?php endif;?>
           </ul>
        </div>
        <?php endif;?>
        <div class="goto">
            <div class="title">欢迎，<?=Yii::$app->user->identity->name?></div>
            <ul>
                <li>
                    <?=Html::a('安全退出',Yii::$app->params['logoutUrl'],['class'=>'btn btn-default btn-xs'])?>
                </li>
            </ul>
        </div>
    </div>

    <footer class="text-center">
        Since 1993
    </footer>
</section>
<?php $this->endBody(); /* body结束标志位 */ ?>
</body>
</html>
<?php $this->endPage(); /* 页面结束标志位 */ ?>






