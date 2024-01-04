<?php

namespace libraries;

use Ubiquity\orm\DAO;
use Ubiquity\utils\http\UCookie;

class MySettings {

    private static $default='["aceTheme": "github","theme": "light","bgColor":"automatic]';

    private static $settings=null;

    private static $internalDefaultValues=['dark'=>['bgColor'=>'#2d2d30','aceTheme'=>'terminal','style'=>'inverted'],'light'=>['bgColor'=>'#ffffff','aceTheme'=>'solarized_light','style'=>'']];

    public static function getInitialSettings() {
        if(UserAuth::isAuth()){
            $u=UserAuth::getUser();
            UCookie::set('settings',$u->getSettings());
            return self::$settings=$u->getSettings_();
        }
        return self::getSettings();
    }

    public static function getSettings(){
        return self::$settings??\json_decode(UCookie::get('settings',\json_encode(self::$default)),true);
    }

    public static function saveSettings($settings): void {
        self::$settings=$settings;
        UCookie::set('settings',\json_encode($settings));
        if(UserAuth::isAuth()){
            $u=UserAuth::getUser();
            $u->setSettings(\json_encode($settings));
            DAO::save($u);
            UserAuth::setUser($u);
        }
    }

    public static function getAceTheme($settings=null){
        $settings??=self::getSettings();
        $aceTheme=$settings['aceTheme']??'automatic';
        if($aceTheme==='automatic'){
            $aceTheme=self::getDefault('aceTheme');
        }
        return $aceTheme;
    }

    public static function getTheme(){
        return self::getStyle()==='inverted'?'dark':'light';
    }

    public static function getStyle($settings=null){
        $settings??=self::getSettings();
        return $settings['style']??self::getDefault('style');
    }

    public static function getBgColor($settings=null){
        $settings??=self::getSettings();
        $bgColor=$settings['bgColor']??'automatic';
        if($bgColor==='automatic'){
            $bgColor=self::getDefault('bgColor');
        }
        return $bgColor;
    }

    private static function getDefault(string $part) {
        $theme=self::getSettings()['theme']??'light';
        return self::$internalDefaultValues[$theme][$part]??'';
    }

    public static function getDefaultThemeValues($theme){
        return ['theme'=>$theme,'bgColor'=>'automatic','aceTheme'=>'automatic'];
    }

    public static function getToggleValues(string $actualTheme): array {
        $theme=($actualTheme==='dark')?'light':'dark';
        return self::$settings=self::getDefaultThemeValues($theme);
    }
}