

<?php
$group_id     = '1'; //вместо 1 пишем id своего сообщества
$access_token = ''; //тут свой токен
$message      = 'Hello, world!'; //сюда пишем сообщение к посту (обычно хештег)


$folder_mas = array('images/anime1','images/anime2','images/anime3');
$images = array();
//выбираем случайную папку
$folder = $folder_mas[rand(0,2)];
$all_files = scandir($folder);
while ($i++ < sizeof($all_files)){
    //выбираем только изображения с расширением .png, .jpg и .gif
    if (!strstr($all_files[$i],".png") and !strstr($all_files[$i],".jpg") and
    !strstr($all_files[$i],".gif")) continue;
    array_push($images, $all_files[$i]);
}
$img_random = $images[rand(0,sizeof($images)-1)];
$img_src = $folder."/".$img_random;



// Получение сервера vk для загрузки изображения.
$res = json_decode(file_get_contents(
    'https://api.vk.com/method/photos.getWallUploadServer?group_id='
    . $group_id . '&access_token=' . $access_token
));

if (!empty($res->response->upload_url)) {
    // Отправка изображения на сервер.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $res->response->upload_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array('photo' => '@' . $img_src));

    //  если у вас php 5.6 + удалите // с начала строки у строки ниже
    //curl_setopt($ch, CURLOPT_POSTFIELDS, array('photo' => new CurlFile($img_src)));

    $res = json_decode(curl_exec($ch));
    curl_close($ch);

    if (!empty($res->server)) {
        // Сохранение фото в группе.
        $res = json_decode(file_get_contents(
            'https://api.vk.com/method/photos.saveWallPhoto?group_id=' . $group_id
            . '&server=' . $res->server . '&photo='
            . stripslashes($res->photo) . '&hash='
            . $res->hash . '&access_token=' . $access_token
        ));

        if (!empty($res->response[0]->id)) {
            // Отправляем сообщение.
            $params = array(
                'access_token' => $access_token,
                'owner_id'     => '-' . $group_id,
                'from_group'   => '1',
                'message'      => $message,
                'attachments'  => $res->response[0]->id
            );

            file_get_contents(
                'https://api.vk.com/method/wall.post?' . http_build_query($params)
            );
        }
    }
}
unlink($img_src);
?>

