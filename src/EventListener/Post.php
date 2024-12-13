<?php

namespace LeadingSystems\MerconisBundle\EventListener;

use Contao\Input;

class Post
{
    public static array $postCache = [];

    public static function getFromKey($key)
    {
        return Post::$postCache[$key];
    }

    private static function savePostInput() : void
    {
        if(empty(Post::$postCache)){
            foreach ($_POST as $key => $value) {
                Post::$postCache[$key] = Input::post($key);
            }
        }
    }

    public function onInitializeSystem(): void
    {
        Post::savePostInput();
    }
}