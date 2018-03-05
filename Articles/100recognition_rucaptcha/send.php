<?php

$file_dir = dirname(__FILE__).'/captchas/';

include_once dirname(__FILE__).'/mcurl.class.php';
$mcurl = new mcurl();


function myscandir($dir, $sort=0)
{
    $list = scandir($dir, $sort);
    
    // если директории не существует
    if (!$list) return false;
    
    // удаляем . и .. (я думаю редко кто использует)
    if ($sort == 0) unset($list[0],$list[1]);
    else unset($list[count($list)-1], $list[count($list)-1]);
    return $list;
}
$files = myscandir($file_dir);

$key = 'APIKEY'; /// Ваш рукапча ключ
$thread = '1'; /// Максимальное количество потоков
$limit = '5'; /// Ограничение одновременно загруженных капчей в сервис
if($thread > $limit){
    $thread = $limit;
}

echo '<pre>';
$captcha_ids = array();
$new_captcha_ids = array();
do{
    
    // Капчи в работе
    if(!empty($captcha_ids)){
        foreach($captcha_ids as $captchaid => $value){
            $answer = file_get_contents("http://rucaptcha.com/res.php?action=get&id=$captchaid&key=$key");
            if(substr_count($answer, 'OK')> 0 || substr_count($answer, 'ERROR')> 0){
                $new_captcha_ids[$captchaid] = $value;
                $new_captcha_ids[$captchaid]['answer'] = $answer;
                $new_captcha_ids[$captchaid]['date_end'] = date('Y-m-d H:i:s');
                unset($captcha_ids[$captchaid]);
            }
        }
    }
    
    if(count($captcha_ids) < $limit && !empty($files)){
        $urls = array();
        $post = array();
        $mcurl->threads = $limit - count($captcha_ids);  
        if($mcurl->threads > $thread){
            $mcurl->threads = $thread;
        }
        $mcurl->timeout = 20;    
        
        $files_slice = array_slice($files, 0, $mcurl->threads); 
        foreach($files_slice as $_key => $img){        
            $post[$_key]['method'] = 'base64';
            $post[$_key]['key'] = $key;
            $type = pathinfo($file_dir.$img, PATHINFO_EXTENSION);
            $data_image = file_get_contents($file_dir.$img);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data_image);
            $post[$_key]['body'] = $base64;
            $urls[$_key] = 'http://rucaptcha.com/in.php';
        }
        array_splice($files, 0, (count($files) - $mcurl->threads) * -1);
        if((count($files) - $mcurl->threads) <= 0){
            unset($files);
        }
        
        unset($result);    
        $mcurl->multipost($urls, $post, $result);
        foreach($result as $_key => $id){
            if(substr_count($id, 'OK') > 0){
                $id = substr(strrchr($id, '|'), 1);
                $captcha_ids[$id] = array(
                    'img' => $files_slice[$_key],
                    'date' => date('Y-m-d H:i:s'),
                );
            }
        }      
    }

    // Ответ дан, записываем
    if(!empty($new_captcha_ids)){
        foreach($new_captcha_ids as $captchaid => $value){
            $text = $captchaid.";".$value['img'].";".$value['date'].";".$value['date_end'].";".$value['answer'].";".round(strtotime($value['date_end']) - strtotime($value['date']), 2);$fp = fopen(dirname(__FILE__).'/log.csv', 'a+');fwrite($fp, $text."\r\n");fclose($fp);         
            unset($new_captcha_ids[$captchaid]);
        }
    }    
    
    sleep(1);
    
    if(empty($files) && empty($new_captcha_ids) && empty($captcha_ids)){
        break;
    }
}while(true);

die('END');