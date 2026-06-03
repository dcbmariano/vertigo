<?php 

if(!function_exists('filtra_url')){

    function filtra_url($url){

        $url = str_replace('index.php/', '', $url);
        $url = str_replace('index.php', '', $url);

        return $url;

    }
}
