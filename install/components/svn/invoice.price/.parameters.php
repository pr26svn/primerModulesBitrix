<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule("iblock"))
    return;

$arTypesEx = CIBlockParameters::GetIBlockTypes(array("-"=>" "));

$arIBlocks=array();
$default_ib="";
$db_iblock = CIBlock::GetList(array("SORT"=>"ASC"), array("SITE_ID"=>$_REQUEST["site"], "TYPE" => ($arCurrentValues["IBLOCK_TYPE"]!="-"?$arCurrentValues["IBLOCK_TYPE"]:"")));
while($arRes = $db_iblock->Fetch()) {
    $arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];
    if($arRes["CODE"]=="invoice"){
        $default_ib=$arRes["ID"];
    }
}




$site = ($_REQUEST["site"] <> ''? $_REQUEST["site"] : ($_REQUEST["src_site"] <> ''? $_REQUEST["src_site"] : false));
$arFilter = Array("TYPE_ID" => "FEEDBACK_FORM", "ACTIVE" => "Y");
if($site !== false)
	$arFilter["LID"] = $site;

$arEvent = Array();
$dbType = CEventMessage::GetList($by="ID", $order="DESC", $arFilter);
while($arType = $dbType->GetNext())
	$arEvent[$arType["ID"]] = "[".$arType["ID"]."] ".$arType["SUBJECT"];

$arComponentParameters = array(
	"PARAMETERS" => array(
        "IBLOCK_TYPE" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("MFP_IBLOCK_DESC_LIST_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => $arTypesEx,
            "DEFAULT" => "invoice_price",
            "REFRESH" => "Y",
        ),
        "IBLOCK_ID" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("MFP_IBLOCK_DESC_LIST_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => $default_ib,
            "ADDITIONAL_VALUES" => "Y",
            "REFRESH" => "Y",
        ),
		"USE_CAPTCHA" => Array(
			"NAME" => GetMessage("MFP_CAPTCHA"), 
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y", 
			"PARENT" => "BASE",
		),
		"OK_TEXT" => Array(
			"NAME" => GetMessage("MFP_OK_MESSAGE"), 
			"TYPE" => "STRING",
			"DEFAULT" => GetMessage("MFP_OK_TEXT"), 
			"PARENT" => "BASE",
		),
		"EMAIL_TO" => Array(
			"NAME" => GetMessage("MFP_EMAIL_TO"), 
			"TYPE" => "STRING",
			"DEFAULT" => htmlspecialcharsbx(COption::GetOptionString("main", "email_from")), 
			"PARENT" => "BASE",
		),
		"REQUIRED_FIELDS" => Array(
			"NAME" => GetMessage("MFP_REQUIRED_FIELDS"), 
			"TYPE"=>"LIST", 
			"MULTIPLE"=>"Y", 
			"VALUES" => Array("NONE" => GetMessage("MFP_ALL_REQ"), "MESSAGE" => GetMessage("MFP_MESSAGE")),
			"DEFAULT"=>Array("MESSAGE"),
			"COLS"=>25, 
			"PARENT" => "BASE",
		),

		"EVENT_MESSAGE_ID" => Array(
			"NAME" => GetMessage("MFP_EMAIL_TEMPLATES"), 
			"TYPE"=>"LIST", 
			"VALUES" => $arEvent,
			"DEFAULT"=>"", 
			"MULTIPLE"=>"Y", 
			"COLS"=>25, 
			"PARENT" => "BASE",
		),
        "ID_PRODUCT" => Array(
            "NAME" => GetMessage("MFP_ID_PRODUCT"),
            "TYPE" => "STRING",
            "DEFAULT" => GetMessage("MFP_ID_PRODUCT"),
            "PARENT" => "BASE",
        ),

	)
);


?>