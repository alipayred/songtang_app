<?php

namespace oa\assets;

use yii\web\AssetBundle;

/**
 * Main oa application asset bundle.
 */
class AppMobileAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/mobile/weui.min.css',
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];

//导入当前页的功能js文件，注意加载顺序，这个应该最后调用  文件路径相对@web即可
    public static function addJsFile($view, $jsfile) {
        $view->registerJsFile($jsfile, ['depends' => self::className()]);
    }
    //导入当前页的功能js代码，注意加载顺序，这个应该最后调用  文件路径相对@web即可
    public static function addJs($view, $jsString) {
        $view->registerJs($jsString, ['depends' => self::className()]);
    }
    //导入当前页的样式css文件，注意加载顺序，这个应该最后调用  文件路径相对@web即可
    public static function addCssFile($view, $cssfile) {
        $view->registerCssFile($cssfile,['depends' => self::className()]);
    }}
