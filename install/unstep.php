<?
use \Bitrix\Main\Localization\Loc;

if(!check_bitrix_sessid()){
    return;
}

if($ex=$APPLICATION->GetException()){
    echo CAdminMessage::ShowMessage(array(
        "TYPE"=>"ERROR",
        "MESSAGE"=>Loc::getMessage("MOD_UNINST_ERR"),
        "DETAILS"=>$ex->GetString(),
        "HTML"=>true,
    ));

}
else{
    echo CAdminMessage::ShowMessage(Loc::getMessage("MOD_UNINST_OK"));
}

?>
<form action="<?=$APPLICATION->GetCurPage();?>">
    <input type="hidden" nam="lang" value="<?=LANGUAGE_ID;?>">
    <input type="submit" name="" value="<?=Loc::getMessage("MOD_BACK");?>">
</form>