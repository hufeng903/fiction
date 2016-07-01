<?php

namespace frontend\controllers;

use common\models\Fiction;
use common\models\Http;
use Goutte\Client;
use yii\base\Exception;
use yii\helpers\Html;
use Yii;

class FicController extends BaseController
{
    //小说章节目录页
    public function actionList()
    {
        $dk = $this->get('dk');
        $fk = $this->get('fk');
        $cache = Yii::$app->cache;
        if ($dk && $fk && isset(Yii::$app->params['ditch'][$dk]['fiction_list'][$fk])) {
            $fiction = Yii::$app->params['ditch'][$dk]['fiction_list'][$fk];
            $list = $cache->get('ditch_' . $dk . '_fiction_list' . $fk . '_fiction_list');
            if ($list === false) {
                $list = Fiction::getFictionList($dk, $fk);
                $cache->set('ditch_' . $dk . '_fiction_list' . $fk . '_fiction_list', $list, 60*60*24);
            }
            return $this->render('list', [
                'fiction' => $fiction,
                'list' => $list,
                'dk' => $dk,
                'fk' => $fk,
            ]);
        } else {
            $this->err404('页面未找到');
        }
    }

    //小说详情页
    public function actionDetail()
    {
        $dk = $this->get('dk');
        $fk = $this->get('fk');
        $url = $this->get('url');
        $text = $this->get('text');
        if (isset(Yii::$app->params['ditch'][$dk]['fiction_list'][$fk]) && !empty($url)) {
            $fiction = Yii::$app->params['ditch'][$dk]['fiction_list'][$fk];
            $client = new Client();
            $crawler = $client->request('GET', $url);
            try {
                if ($crawler) {
                    $detail = $crawler->filter($fiction['fiction_detail_rule']);
                    if ($detail) {
                        $content = '';
                        global $content;
                        $detail->each(function($node) use ($content){
                            global $content;
                            $text = $node->html();
                            $text = preg_replace('/<script.*?>.*?<\/script>/', '', $text);
                            $text = preg_replace('/(<br\s?\/?>){2,}/', '<br/>', $text);
                            $text = strip_tags($text, '<p><div><br>');
                            $content = $content . $text;
                        });
                    }
                }
            } catch (Exception $e) {
                //todo 处理查找失败
            }
            $content = isset($content) ? $content : '未获取到指定章节';

            return $this->render('detail', [
                'content' => $content,
                'fiction' => $fiction,
                'text' => $text,
                'dk' => $dk,
                'fk' => $fk,
            ]);
        } else {
            $this->err404('页面未找到');
        }

    }
}