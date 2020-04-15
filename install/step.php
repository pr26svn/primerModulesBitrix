<?
use \Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid()){
    return;
}

if($ex=$APPLICATION->GetException()){
    echo CAdminMessage::ShowMessage(array(
        "TYPE"=>"ERROR",
        "MESSAGE"=>Loc::getMessage("MOD_INST_ERR"),
        "DETAILS"=>$ex->GetString(),
        "HTML"=>true,
    ));

}
else{
    echo CAdminMessage::ShowMessage(Loc::getMessage("MOD_INST_OK"));
}
global $M;
foreach ($M as $mes)
    echo $mes;
?>
<br/>
<?=Loc::getMessage('SVN_ORDERMAIL_STEP_DESCRIPTION'); ?>

<br/>
<form action="<?=$APPLICATION->GetCurPage();?>">
    <input type="hidden" nam="lang" value="<?=LANGUAGE_ID;?>">
    <input type="submit" name="" value="<?=Loc::getMessage("MOD_BACK");?>">
</form>
