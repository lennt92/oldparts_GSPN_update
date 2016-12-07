<?
#!/usr/bin/php 
$_SERVER["DOCUMENT_ROOT"] = "C:\Bitrix\www"; 
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"]; 
define("NO_KEEP_STATISTIC", true); 
define("NOT_CHECK_PERMISSIONS", true); 
set_time_limit(0); 
define("LANG", "ru"); 
require($_SERVER["DOCUMENT_ROOT"]."\bitrix\modules\main\include\prolog_before.php"); 

/*подключаем необходимые модюли Битрикс*/
CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');
include_once('C:\samsung\partsRequest.php');

/*Выбираем элементы с установленным свойством - "обновление по GSPN"*/
$arSelect = Array("ID", "NAME", "PROPERTY_old_part", "PROPERTY_gspn_task");
$arFilter = Array("IBLOCK_ID"=>8,  "ACTIVE"=>"Y", "PROPERTY_USE_GSPN_VALUE"=>"да");
$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>50), $arSelect);

/*Цыкл в котором пробегаемся по выбранным элементам*/
while($ob = $res->GetNextElement())
{
	$arFields = $ob->GetFields();

	if($arFields['PROPERTY_OLD_PART_VALUE'] === "") continue;
	
	$resGSPN = partRequest($arFields['PROPERTY_OLD_PART_VALUE']);
	$priceGSPN = str_replace(",","", $resGSPN->peData->unitPrice);
	$pos = strpos ($priceGSPN,".");
	$priceGSPN = substr($priceGSPN , 0 , $pos+2);

	$newPrice = $priceGSPN + $priceGSPN*0.18; 
	$newPrice += $newPrice*($arFields["PROPERTY_GSPN_TASK_VALUE"]/100);
	
	$quaintity = $resGSPN->peData->stockAvail;

	if ($quaintity === ''){
			$quaintity = 0;
			$arLoadProductArray = array("status" => 55);		 //	устанавливаем статус "нет на складе"
	}else {
		$quaintity = str_replace(",","", $quaintity);
		$arLoadProductArray = array("status" => 53);   //	устанавливаем статус "в наличии"
	}
	
	$PRODUCT_ID = $arFields["ID"]; // - ID элемента 
	$PRICE_TYPE_ID = 1; // - ID типа цены, по умолчанию "розничная" 

	$resPrice = CPrice::GetList(
        array(),
        array(
                "PRODUCT_ID" => $PRODUCT_ID,
                "CATALOG_GROUP_ID" => $PRICE_TYPE_ID
            )
    );

	$arPriseData = Array(
		"PRODUCT_ID" => $PRODUCT_ID,
		"CATALOG_GROUP_ID" => $PRICE_TYPE_ID,
		"PRICE" => $newPrice,
		"CURRENCY" => "RUB",
	);

	if ($arr = $resPrice->Fetch())
	{
		CPrice::Update($arr["ID"], $arPriseData);
	}

	$arrQuantity = array('QUANTITY' => (int)$quaintity);
	CCatalogProduct::Update($PRODUCT_ID, $arrQuantity);
	CIBlockElement::SetPropertyValuesEx($PRODUCT_ID, false, $arLoadProductArray);
	}
	require($_SERVER["DOCUMENT_ROOT"]."\bitrix\modules\main\include\epilog_after.php"); 
?>