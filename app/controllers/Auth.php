<?php
namespace controllers;
 use libraries\GuiSettings;
 use libraries\MySettings;
 use Ubiquity\client\oauth\OAuthManager;
 use Ubiquity\orm\DAO;
use models\User;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButton;
use Ajax\semantic\html\base\constants\Social;
use libraries\GUI;
 use Ubiquity\utils\base\UArray;
 use Ubiquity\utils\http\UCookie;
 use Ubiquity\utils\http\URequest;

 /**
 * Controller Auth
 **/
class Auth extends ControllerBase{

	public function index(){
		$this->jquery->get("Auth/infoUser","#divInfoUser");
		echo $this->jquery->compile();
	}

	public function signup(){
		$this->sign(function(){
			$frm=$this->semantic->defaultAccount("frm-account",new User());
			$frm->setSubmitParams("Auth/signCheck/true","#ajax");
			$this->jquery->postOn("change", "#frm-account-login-0", "Auth/signCheck","{'login':$(this).val()}","#ajax");
		}, ["title1"=>"Sign up with","title2"=>"Create an account"]);
	}

	public function signin(){
		$this->sign(function(){
			$frm=$this->semantic->defaultLogin("frm-account",new User());
			$frm->setSubmitParams("Auth/connect","#main-container");
		}, ["title1"=>"Sign in with","title2"=>"Log in with your account"]);
	}

	public function connect(){
		if(isset($_POST["login"])){
			$users=DAO::getAll("models\User","login='".$_POST["login"]."' OR email='".$_POST["login"]."'");
			foreach ($users as $user){
				if($user->getPassword()==@$_POST["password"]){
					$_SESSION["user"]=$user;
                    MySettings::getInitialSettings();
                    GUI::applySettings($this->jquery);
					$header=$this->jquery->semantic()->htmlHeader("headerUser",3);
					$header->asImage($user->getAvatar(), $user->getLogin(),"connected");
					echo GUI::showSimpleMessage($this->jquery, $header, "info","");
					$this->forward("controllers\Nol","index",[],true,true);
					$this->jquery->get("Auth/infoUser","#divInfoUser",["hasLoader"=>false]);
					break;
				}
			}
		}
		if(!isset($_SESSION["user"])){
			echo GUI::showSimpleMessage($this->jquery, "Failed to connect : bad login or password.", "error","warning circle");
			$this->forward("controllers\Auth","signin",[],true,true);
		}
		echo $this->jquery->compile($this->view);
	}

	public function signCheck($beforeSubmit=false){
		if(isset($_POST["login"])){
			$nb=DAO::count("models\User","login='".$_POST["login"]."' AND idAuthProvider is null");
			if($nb>0){
				$this->jquery->exec("$('#frm-account').form('add errors', {login: 'This login is already in use.'});$('#frm-account').form('add prompt', 'login')",true);
			}else{
				$this->jquery->exec("$('#frm-account .ui.error.message ul:contains(\"This login is already in use.\")').remove();",true);
				if($beforeSubmit=="true"){
					$this->jquery->post("Auth/createAccount","#main-container",\json_encode($_POST),["ajaxTransition"=>"random"]);
				}
			}
			echo $this->jquery->compile();
		}
	}

	public function createAccount(){
		$user=new User();
		URequest::setValuesToObject($user,$_POST);
		$user->setAvatar("img/male.png");
		$key=md5(\microtime(true));
		$user->setAuthkey($key);
		try{
		if(DAO::insert($user)){
			echo GUI::showSimpleMessage($this->jquery, "An email was sent to <b>".$user->getEmail()."</b>, containing a link to activate your account.", "info");
		}else{
			echo GUI::showSimpleMessage($this->jquery, "The account was not created due to an error.<br>Try again later.", "error");
		}
		}catch(\Exception $e){
			echo GUI::showSimpleMessage($this->jquery, "The account was not created due to an error.<br>Try again later.", "error");
		}
		echo $this->jquery->compile();
	}

	public function activateAccount($key){
		$user=DAO::getOne("models\User", "authkey='".$key."'");
		if($user!==null){
			$user->setAuthkey(null);
			DAO::update($user);
			$bt=new HtmlButton("bt-signin","Sign in");
			$bt->addIcon("sign in");
			$bt->getOnClick("Auth/signin","#main-container",["ajaxTransition"=>"random"]);
			echo GUI::showSimpleMessage($this->jquery, ["Your account has been activated. You can now log in as <b>`".$user->getLogin()."`</b>:<br>",$bt], "","announcement");
		}else{
			echo GUI::showSimpleMessage($this->jquery, "Unable to activate account.<br>Check the given url and try again.", "warning","warning");
		}
		$this->forward("controllers\Main","index",[],true,true);

		echo $this->jquery->compile($this->view);
	}

	private function sign($formCallback,$titles){
		$bt=HtmlButton::social("bt-github", Social::GITHUB);
		$bt->asLink(URequest::getUrl("oauth/GitHub"));
        $this->setStyle($bt);
		$bt->compile($this->jquery,$this->view);

		$bt2=HtmlButton::social("bt-google", 'google');
		$bt2->asLink(URequest::getUrl("oauth/Google"));
        $this->setStyle($bt2);
		$bt2->compile($this->jquery,$this->view);

		$bt3=HtmlButton::social("bt-linkedin", Social::LINKEDIN);
		$bt3->asLink(URequest::getUrl("oauth/LinkedIn"));
        $this->setStyle($bt3);
		$bt3->compile($this->jquery,$this->view);

		$formCallback();

		$this->jquery->compile($this->view);
		$this->loadView("Auth/sign.html",$titles);
	}

	public function infoUser() {
		echo UserAuth::getInfoUser($this->jquery);
		echo $this->jquery->compile();
	}

	public function disconnect(){
        $user=UserAuth::getUser();
		$authProvider=$user->getAuthProvider();
		if(isset($authProvider)){
			$adapter=OAuthManager::startAdapter($user->getAuthProvider()->getName());
			$adapter->disconnect();
		}
		unset($_SESSION["user"]);

		$header=$this->jquery->semantic()->htmlHeader("headerUser",3);
		$header->asImage($user->getAvatar(), $user->getLogin(),"Bye!");
		$message=$this->semantic->htmlMessage("message",$header);
		$message->setDismissable()->setTimeout(5000);
		echo $message->compile($this->jquery);
        UCookie::delete('settings');
        GUI::applySettings($this->jquery);
        $this->jquery->get("Auth/infoUser","#divInfoUser",["hasLoader"=>false]);
		$this->forward("controllers\Nol","index",[],true,true);
		echo $this->jquery->compile();
	}

    public function settings(){
        $config=MySettings::getSettings();
        $guiSettings=new GuiSettings($this->jquery);
        $this->addConfigBehavior();
        $guiSettings->getConfigPartDataForm($config,'settings');
        $this->addSubmitConfigBehavior([
            'form' => '#settings',
            'response' => '#main-container'
        ], [
            'submit' => "/Auth/_submitSettings",
            'source' => "/Auth/_getSettingsSource",
            'form' => "/Auth/_refreshSettingsFrmGlobal"
        ], [
            'submit' => '',
            'cancel' => '$("#main-container").html("");'
        ]);
        $this->jquery->renderView('Auth/settings.html');
    }

    public function toggleTheme(){
        $theme=MySettings::getTheme();
        $newValues=MySettings::getToggleValues($theme);
        if($newValues['style']==='inverted'){
            $js=<<<JS
$('.ui,.icon').not('#secondary .menu,#secondary.menu').addClass('inverted');
$('body').css('background-color','{$newValues['bgColor']}');
$('#idTheme>i').removeClass('moon').addClass('sun');
JS;
        }else{
            $js=<<<JS
$('.ui,.icon').not('#secondary .menu,#secondary.menu').removeClass('inverted');
$('body').css('background-color','{$newValues['bgColor']}');
$('#idTheme>i').removeClass('sun').addClass('moon');
JS;
       }
        MySettings::saveSettings($newValues);
        $this->jquery->exec($js,true);
        $this->forward("controllers\Nol","index",[],true,true);
        echo $this->jquery->compile();
    }

    private function addConfigBehavior(): void {
        $this->jquery->mouseleave('td', '$(this).find("i._see").css({"visibility":"hidden"});');
        $this->jquery->mouseenter('td', '$(this).find("i._see").css({"visibility":"visible"});');
        $this->jquery->click('._delete', 'let tDf=$("[name=_toDelete]");tDf.closest(".ui.dropdown").dropdown("set selected",$(this).attr("data-name"));');
        $this->jquery->click('.cancel-all','$("[name=_toDelete]").closest(".ui.dropdown").dropdown("clear");');
        $this->jquery->exec('$("._delete").closest("td").css({"min-width":"200px","width":"1%","white-space": "nowrap"});',true);
    }

    private function addSubmitConfigBehavior(array $ids, array $urls, array $callbacks) {
        $this->jquery->postFormOnClick("#save-config-btn", $urls['submit'], 'frm-settings', $ids['response'], [
            'jsCallback' => $callbacks['submit'],
            'hasLoader' => 'internal'
        ]);
        $this->jquery->execOn("click", "#bt-Canceledition", $callbacks['cancel']);
        $this->sourcePartBehavior($ids,$urls,'frm-settings','frm-source');
    }

    private function sourcePartBehavior($ids,$urls,$frmConfig='frm-settings',$frmSource='frm-source'){
        $this->jquery->execAtLast("$('._tabConfig .item').tab();");
        $this->jquery->execAtLast("$('._tabConfig .item').tab({'onVisible':function(value){
			if(value=='source'){
			" . $this->jquery->postFormDeferred($urls['source'], $frmConfig, '#tab-source', [
                'hasLoader' => false
            ]) . "}else{
			" . $this->jquery->postFormDeferred($urls['form'], $frmSource, $ids['form'], [
                'hasLoader' => false,
                'jqueryDone' => 'replaceWith'
            ]) . "
		}
		}});");
    }

    public function _submitSettings() {
        $user=UserAuth::getUser();
        $config=MySettings::getSettings();
        $result = $this->getConfigPartFromPost($config);
        $toDelete = $_POST['_toDelete'] ?? '';
        unset($_POST['_toDelete']);
        $toDeletes = \explode(',', $toDelete);
        $this->removeDeletedsFromArray($result, $toDeletes);
        $this->removeEmpty($result);
        try {
           MySettings::saveSettings($result);
                echo GUI::showSimpleMessage($this->jquery,"The configuration file has been successfully modified!", "success", "checkmark circle");
        } catch (\Exception $e) {
            echo GUI::showSimpleMessage($this->jquery,"Your configuration contains errors.<br>The configuration file has not been saved.<br>" . $e->getMessage(), "error", "warning circle");
        }
        $this->forward("controllers\Nol","index",[],true,true);
        GUI::applySettings($this->jquery);
        echo $this->jquery->compile();
    }

    public function _getSettingsSource(){
        $this->getConfigSourcePart(MySettings::getSettings(),'Settings','code');
    }

    public function _refreshSettingsFrmGlobal(){
        $this->refreshConfigFrmPart(MySettings::getSettings());
    }

    private function refreshConfigFrmPart($original, $identifier = 'settings') {
        $toRemove = [];
        $update = $this->evalPostArray($_POST['src']);
        $this->arrayUpdateRecursive($original, $update, $toRemove);
        $this->getConfigPartFrmDataForm($original, $identifier);
        if (\count($toRemove) > 0) {
            $this->jquery->execAtLast("$('[name=_toDelete]').closest('.ui.dropdown').dropdown('set selected'," . \json_encode($toRemove) . ");");
        }
        $this->jquery->renderView('@framework/main/component.html');
    }

    private function getConfigPartFrmDataForm($config, $identifier = 'settings') {
        $gui=new GuiSettings($this->jquery);
        $df = $gui->getConfigPartDataForm ($config, $identifier);
        $this->addConfigBehavior();
        return $df;
    }

    private function evalPostArray($post):array{
        $filename=\ROOT.'cache/config/tmp.cache.php';
        $result = \preg_replace('/getenv\(\'(.*?)\'\)/', '"getenv(\'$1\')"', $post);
        $result = \preg_replace('/getenv\(\"(.*?)\"\)/', "'getenv(\"\$1\")'", $result);
        \file_put_contents($filename,"<?php return $result;");
        return include $filename;
    }

    private function getConfigSourcePart($original, $title, $icon) {
        $toDelete = URequest::post('_toDelete','');
        $toRemove = \explode(',', $toDelete);
        $update = $this->getConfigPartFromPost($original);
        $this->arrayUpdateRecursive($original, $update, $toRemove, '', true);
        $src = UArray::asPhpArray($original, "array", 1, true);
        $frm = $this->jquery->semantic()->htmlForm('frm-source');
        $frm->addContent("<div class='ui ribbon blue label'><i class='ui $icon icon'></i> $title</div><br>");
        $textarea = $frm->addTextarea('src', '', $src, null, 20);
        $frm->addInput('toDeleteSrc', null, 'hidden', $toDelete);
        $frm->setLibraryId('_compo_');
        $textarea->getDataField()->setProperty('data-editor', true);
        GuiSettings::insertAce('php', 'monokai',$this->jquery);

        $this->jquery->renderView('@framework/main/component.html');
    }

    private function arrayUpdateRecursive(&$original, &$update, &$toRemove, $key = '', $remove = false) {
        foreach ($original as $k => $v) {
            $nKey = ($key == null) ? $k : ($key . '-' . $k);
            if (\array_key_exists($k, $update)) {
                if (\is_array($update[$k]) && \is_array($v)) {
                    $this->arrayUpdateRecursive($original[$k], $update[$k], $toRemove, $nKey, $remove);
                } else {
                    if (\array_search($nKey, $toRemove) === false) {
                        $original[$k] = $update[$k];
                    }
                }
            } else {
                if (\array_search($nKey, $toRemove) === false) {
                    $toRemove[] = $nKey;
                }
            }
            if ($remove && \array_search($nKey, $toRemove) !== false) {
                unset($original[$k]);
            }
            unset($update[$k]);
        }
        foreach ($update as $k => $v) {
            if (\array_search($k, $toRemove) === false) {
                $original[$k] = $v;
            }
        }
    }

    private function getConfigPartFromPost(array $result) {
        $postValues = $_POST;
        foreach ($postValues as $key => $value) {
            if ('_toDelete' != $key) {
                if (strpos($key, "-") === false) {
                    $result[$key] = $value;
                } else {
                    $keys = explode('-', $key);
                    $v = &$result;
                    foreach ($keys as $k) {
                        if (! isset($v[$k])) {
                            $v[$k] = [];
                        }
                        $v = &$v[$k];
                    }
                    $v = $value;
                }
            }
        }
        return $result;
    }

    private function removeEmpty(&$array) {
        foreach ($array as $k => $value) {
            if ($value == null) {
                unset($array[$k]);
            } elseif (\is_array($value)) {
                if (\count($value) == 0) {
                    unset($array[$k]);
                } else {
                    $this->removeEmpty($array[$k]);
                }
            }
        }
    }

    private function removeDeletedsFromArray(&$result, $toDeletes) {
        $v = &$result;
        foreach ($toDeletes as $toDeleteOne) {
            if ($toDeleteOne != null) {
                if (strpos($toDeleteOne, "-") === false) {
                    unset($result[$toDeleteOne]);
                } else {
                    $keys = explode('-', $toDeleteOne);
                    $v = &$result;
                    $s = \count($keys);
                    for ($i = 0; $i < $s - 1; $i ++) {
                        $v = &$v[$keys[$i]];
                    }
                    unset($v[\end($keys)]);
                }
            }
        }
    }
}