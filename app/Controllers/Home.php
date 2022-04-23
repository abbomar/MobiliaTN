<?php

namespace App\Controllers;


class Home extends BaseController
{
    public $userModel;
    public function __construct()
    {
        $this->userModel = model('UserModel');
    }

    public function index()
    {

        $data = [
            'user_id' => null,
            'phone_number' => '24509957',
            'full_name'    => 'CI test'
        ];

        echo $data['user_id'];

        $this->userModel->protect(false)->insert($data)->protect(true);

    }
}
