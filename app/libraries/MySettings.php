<?php

namespace libraries;

use Ubiquity\orm\DAO;
use Ubiquity\utils\http\UCookie;

class MySettings {

    private static $default='["aceTheme": "github","theme": "light","bgColor":"automatic]';

    private static $defaultValues=['dark'=>['bgColor'=>'#2d2d30','aceTheme'=>'terminal','style'=>'inverted'],'light'=>['bgColor'=>'#ffffff','aceTheme'=>'github','style'=>'']];

    public static function getInitialSettings() {
        if(UserAuth::isAuth()){
            $u=UserAuth::getUser();
            UCookie::set('settings',$u->getSettings());
            return $u->getSettings_();
        }
        return self::getSettings();
    }

    public static function getSettings(){
        $settings=UCookie::get('settings',\json_encode(self::$default));
        return \json_decode($settings,true);
    }

    public static function saveSettings($settings): void {
        UCookie::set('settings',\json_encode($settings));
        if(UserAuth::isAuth()){
            $u=UserAuth::getUser();
            $u->setSettings(\json_encode($settings));
            DAO::save($u);
            UserAuth::setUser($u);
        }
    }

    public static function getAceTheme(){
        $aceTheme=self::getSettings()['aceTheme']??'automatic';
        if($aceTheme==='automatic'){
            $aceTheme=self::getDefault('aceTheme');
        }
        return $aceTheme;
    }

    public static function getTheme(){
        return self::getStyle()==='inverted'?'dark':'light';
    }

    public static function getStyle(){
        return self::getSettings()['style']??self::getDefault('style');
    }

    public static function getBgColor(){
        $bgColor=self::getSettings()['bgColor']??'automatic';
        if($bgColor==='automatic'){
            $bgColor=self::getDefault('bgColor');
        }
        return $bgColor;
    }

    private static function getDefault(string $part) {
        $theme=self::getSettings()['theme']??'light';
        return self::$defaultValues[$theme][$part]??'';
    }

    public static function getToggleValues(string $actualTheme): array {
        $theme=($actualTheme==='dark')?'light':'dark';
        return self::$defaultValues[$theme];
    }
}