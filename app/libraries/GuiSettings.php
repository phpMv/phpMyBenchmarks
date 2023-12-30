<?php

namespace libraries;

use Ajax\php\ubiquity\JsUtils;
use Ajax\semantic\html\collections\form\HtmlFormCheckbox;
use Ajax\semantic\html\collections\form\HtmlFormInput;
use Ajax\semantic\html\collections\form\HtmlFormTextarea;
use Ajax\semantic\widgets\dataelement\DataElement;
use Ubiquity\utils\base\UArray;
use Ubiquity\utils\base\UIntrospection;
use Ubiquity\utils\base\UString;

class GuiSettings{

    private string $style;

    private JsUtils $jquery;

    private $ace_themes=[
        'automatic'=>'Automatic',
        'chrome' => 'Chrome',
        'clouds' => 'Clouds',
        'crimson_editor' => 'Crimson Editor',
        'dawn' => 'Dawn',
        'dreamweaver' => 'Dreamweaver',
        'eclipse' => 'Eclipse',
        'github' => 'GitHub',
        'iplastic' => 'IPlastic',
        'katzenmilch' => 'KatzenMilch',
        'kuroir' => 'Kuroir',
        'solarized_light' => 'Solarized Light',
        'sqlserver' => 'SQL Server',
        'textmate' => 'TextMate',
        'tomorrow' => 'Tomorrow',
        'xcode' => 'XCode',
        'ambiance' => 'Ambiance',
        'chaos' => 'Chaos',
        'clouds_midnight' => 'Clouds Midnight',
        'cobalt' => 'Cobalt',
        'dracula' => 'Dracula',
        'gob' => 'Greeon on Black',
        'gruvbox' => 'Gruvbox',
        'idle_fingers' => 'idle Fingers',
        'kr_theme' => 'krTheme',
        'merbivore' => 'Merbivore',
        'merbivore_soft' => 'Merbivore Soft',
        'mono_industrial' => 'Mono Industrial',
        'monokai' => 'Monokai',
        'pastel_on_dark' => 'Pastel on Dark',
        'solarized_dark' => 'Solarized Dark',
        'terminal' => 'Terminal',
        'tomorrow_night' => 'Tomorrow Night',
        'tomorrow_night_blue' => 'Tomorrow Night Blue',
        'tomorrow_night_bright' => 'Tomorrow Night Bright',
        'tomorrow_night_eighties' => 'Tomorrow Night 80s',
        'twilight' => 'Twilight',
        'vibrant_ink' => 'Vibrant Ink'
    ];

    protected $styles = [
        'inverted' => [
            'bgColor' => '#303030',
            'inverted' => true,
            'tdDefinition' => '#fff',
            'selectedRow' => 'black'
        ],
        '' => [
            'bgColor' => '#fbfbee',
            'selectedRow' => 'positive',
        ]
    ];

    public function __construct(JsUtils $jquery,string $style=''){
        $this->jquery=$jquery;
        $this->style=$style;
    }

    private function labeledInput($input, $value) {
        $lbl = "[empty]";
        if (UString::isNotNull($value))
            $lbl = $value;
        $lbl = $input->getField()->labeled($lbl);
        $lbl->addClass($this->style);
        return $input;
    }

    private function getArrayDataForm($id, $array, $fields) {
        $dbDe = new DataElement('de-' . $id, $array);
        $keys = \array_keys($array);
        $this->setDefaultValueFunctionArrayDF($dbDe,$fields,$array,$id);
        $dbDe->setFields($keys);
        \array_walk($keys, function (&$item) use ($id) {
            $item = $item . '<i title="Remove this key." class="close link red icon _see _delete" data-name="' . $id . '-' . $item . '" style="visibility: hidden;"></i>';
        });

        $dbDe->setCaptions($keys);
        return $dbDe;
    }
    private function setDefaultValueFunctionArrayDF($dbDe,$fields,$array,$id=null){
        $dbDe->setDefaultValueFunction(function ($name, $value) use ($id, $fields,$array) {
            $newId=isset($id)?"$id-$name":$name;
            $r=$array[$name];
            if (\is_callable($r)) {
                $input = new HtmlFormTextarea($newId);
                $df = $input->getDataField();
                $df->setProperty("rows", "3");
                $df->setProperty("data-editor", "true");
                $value = \htmlentities(UIntrospection::closure_dump($r));
                $input->setValue($value);
                return $input;
            }
            if (\is_array($r)) {
                if(UArray::isAssociative($r) && count($r)>0) {
                    return $this->getArrayDataForm($newId, $r, $fields);
                }
                $input = new HtmlFormTextarea($newId);
                $value = \htmlentities(UArray::asPhpArray($r,'array'));
                $input->setValue($value);
                return $input;
            }
            if (UString::isBoolean($value) && !UString::startswith($value,'getenv(')) {
                $input = new HtmlFormCheckbox($newId, '', 'true', 'slider');
                $input->setChecked($value);
                $input->getField()
                    ->forceValue();
                return $input;
            }
            $input = new HtmlFormInput($newId, null, $fields['types'][$name] ?? 'text', $value);
            return $this->labeledInput($input, $value);
        });
    }

    public function getConfigPartDataForm($config, $identifier = 'settings',$asCompo=true) {
        $fields = [
            'types' => [
                'password' => 'password',
                'port' => 'number'
            ]
        ];
        $de = $this->jquery->semantic()->dataElement($identifier, $config);
        $keys = \array_keys($config);
        $this->setDefaultValueFunctionArrayDF($de,$fields,$config);
        $de->setFields($keys);
        $de->addField('_toDelete');
        $de->fieldAsDropDown('_toDelete', [], true, [
            'id' => 'toDelete',
            'jsCallback' => function ($elm) {
                $elm->getField()
                    ->setAllowAdditions(true)
                    ->addClass($this->style)
                    ->setOnAdd("let self=$('[data-name='+addedValue+']');let table=self.closest('table tbody');self.closest('tr').hide();while(table && table.children(':visible').length==0){let next=table.closest('tr').closest('table tbody');table.closest('tr').hide();table=next;}")
                    ->setOnRemove("let self=$('[data-name='+removedValue+']');let tr=self.closest('tr');tr.show();tr.parents('tr').show();");
            }
        ]);



        \array_walk($keys, function (&$item) {
            $item = $item . '<i title="Remove this key." class="close link red icon _see _delete" data-name="' . $item . '" style="visibility: hidden;"></i>';
        });
        $de->fieldAsDropDown('aceTheme',$this->ace_themes,false,['jsCallback'=>function($elm){
            $elm->getField()->asSearch('aceTheme');
        }]);
        $de->setCaptions($keys);
        $de->setCaption('_toDelete', '<div class="ui cancel-all icon '.$this->style.' button"><i class="remove icon"></i> Cancel all deletions</span>');
        if($asCompo) {
            $de->setLibraryId('_compo_');
        }
        $de->setEdition(true);
        $de->addClass($this->style);
        self::insertAce('php','monokai',$this->jquery);
        return $de;
    }

    public static function insertAce($language='php',$theme='monokai',$jquery=null) {
        $js = '
		$(function() {
		  $("textarea[data-editor]").each(function() {
		    var textarea = $(this);
		    var mode = textarea.data("editor");
		    var editDiv = $("<div>", {
		      position: "absolute",
		      width: "100%",
		      height: textarea.height(),
		      "class": textarea.attr("class")
		    }).insertBefore(textarea);
		    textarea.css("display", "none");
		    var editor = ace.edit(editDiv[0]);
		    editDiv.css("border-radius","4px");
			editDiv.css("margin-top","8px");
			editDiv.css("font-size","14px");
		    editor.$blockScrolling = Infinity ;
		    editor.renderer.setShowGutter(textarea.data("gutter"));
		    editor.getSession().setValue(textarea.val());
		    editor.getSession().setMode({path:"ace/mode/'.$language.'", inline:true});
		    editor.setTheme("ace/theme/'.$theme.'");
		    $("textarea[data-editor]").closest("form").on("ajaxSubmit",function() {
		      textarea.val(editor.getSession().getValue());
		    });
		  });
		});
		';
        $jquery->exec($js, true);
    }
}