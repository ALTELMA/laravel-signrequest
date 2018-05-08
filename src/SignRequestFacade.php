<?php

namespace Altelma\LaravelSignRequest;

use Illuminate\Support\Facades\Facade;

class SignRequestFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'SignRequest';
    }
}
