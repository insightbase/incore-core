<?php
namespace App\Component\Front\SettingControl;

interface SettingControlFactory
{
    public function create():SettingControl;
}