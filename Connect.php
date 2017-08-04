<?php 


trait Connect {

    public function config(){
        $array = file('config', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $newArray;
        array_walk($array, function($item) use (&$newArray){
            $exp = explode('=', $item);
            $newArray[$exp[0]] = (string) $exp[1];
        });
        return $newArray;
    }

}

