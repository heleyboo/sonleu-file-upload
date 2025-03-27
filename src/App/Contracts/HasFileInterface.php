<?php

namespace SonLeu\File\App\Contracts;

interface HasFileInterface
{
    /**
     * @return string
     */
    public function getHasFileClass();

    /**
     * @return int
     */
    public function getHasFileId();
}
