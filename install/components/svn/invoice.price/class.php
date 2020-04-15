<? /**
 *Класс компонента запроса счета для товара
 */
use Bitrix\Main\Context,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem,
    Bitrix\Main\Mail\Event;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CInvoicePrice extends CBitrixComponent
{
    function GeneratePassword($number)
    {
        $arr = array('a','b','c','d','e','f',
            'g','h','i','j','k','l',
            'm','n','o','p','r','s',
            't','u','v','x','y','z',
            'A','B','C','D','E','F',
            'G','H','I','J','K','L',
            'M','N','O','P','R','S',
            'T','U','V','X','Y','Z',
            '1','2','3','4','5','6',
            '7','8','9','0','.',',',
            '(',')','[',']','!','?',
            '&','^','%','@','*','$',
            '<','>','/','|','+','-',
            '{','}','`','~');
        // Генерируем пароль
        $pass = "";
        for($i = 0; $i < $number; $i++)
        {
            // Вычисляем случайный индекс массива
            $index = rand(0, count($arr) - 1);
            $pass .= $arr[$index];
        }
        return $pass;
    }
    function RegUser($email){
        $user = new CUser;
        $rsUser = CUser::GetByLogin($email);
        if($arUser = $rsUser->Fetch()){
            return $arUser['ID'];
        }

        $password=$this->GeneratePassword(8);
        $arFields = Array(
            "NAME"              => "Пользователь",
            "LAST_NAME"         => "счет",
            "EMAIL"             => $email,
            "LOGIN"             => $email,
            "LID"               => "ru",
            "ACTIVE"            => "Y",
            "GROUP_ID"          => array(3),
            "PASSWORD"          => $password,
            "CONFIRM_PASSWORD"  => $password

        );

        $ID = $user->Add($arFields);
        if (intval($ID) > 0):
          
            $siteId = Context::getCurrent()->getSite();
            Event::send(array(
                "EVENT_NAME" => $this->arParams['EVENT_MESSAGE_ID'],
                "LID" => $siteId,
                "C_FIELDS" => array(
                    "EMAIL" => $email,
                    "PASSWORD" => $password,

                ) ,
                ));
            return $ID;
        else:
            echo $user->LAST_ERROR;
        endif;
    }

    function Order($idUser)
    {
        //получаем инфоблок из ID товара
        $res = CIBlockElement::GetByID($this->arParams['ID_PRODUCT']);

        if ($ar_res = $res->GetNext()):
            $iBlockID = $ar_res['IBLOCK_ID'];
            //проверяем торговые предложения
            $offers = CIBlockPriceTools::GetOffersArray(array(
                'IBLOCK_ID' => $iBlockID,
                'HIDE_NOT_AVAILABLE' => 'Y',
                'CHECK_PERMISSIONS' => 'Y'
            ), array($this->arParams['ID_PRODUCT']), null, null, null, null, null, null, array('CURRENCY_ID' => $sale_currency), $idUser, null);
            ////////////////////////////////
            if (!empty($offers)) {
                $idProduct = $offers[0]['ID'];
            } else {
                $idProduct = $this->arParams['ID_PRODUCT'];
            }
            global $USER;
            Bitrix\Main\Loader::includeModule("sale");
            Bitrix\Main\Loader::includeModule("catalog");
            $request = Context::getCurrent()->getRequest();
            $siteId = Context::getCurrent()->getSite();
            $currencyCode = CurrencyManager::getBaseCurrency();
            $order = Order::create($siteId, $USER->isAuthorized() ? $USER->GetID() : $idUser);
            $order->setPersonTypeId(1);
            $order->setField('CURRENCY', $currencyCode);

            if ($request->getPost('MESSAGE')) {
                $order->setField('USER_DESCRIPTION', $request->getPost('MESSAGE')); // Устанавливаем поля комментария покупателя
            }

            $basket = Basket::create($siteId);
            $item = $basket->createItem('catalog', $idProduct);
            $item->setFields(array(
                'QUANTITY' => 1,
                'CURRENCY' => $currencyCode,
                'LID' => $siteId,
                'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
            ));
            $order->setBasket($basket);
            // Создаём одну отгрузку и устанавливаем способ доставки - "Без доставки" (он служебный)
            $shipmentCollection = $order->getShipmentCollection();
            $shipment = $shipmentCollection->createItem();
            $service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
            $shipment->setFields(array(
                'DELIVERY_ID' => $service['ID'],
                'DELIVERY_NAME' => $service['NAME'],
            ));
            $shipmentItemCollection = $shipment->getShipmentItemCollection();
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());

// Создаём оплату со способом #1
            $paymentCollection = $order->getPaymentCollection();
            $payment = $paymentCollection->createItem();
            $paySystemService = PaySystem\Manager::getObjectById(1);
            $payment->setFields(array(
                'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
                'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
            ));

// Устанавливаем свойства
            $propertyCollection = $order->getPropertyCollection();
            //$phoneProp = $propertyCollection->getPhone();
            // $comment=$request->getPost('MESSAGE');
            //$phoneProp->setValue($comment);
            $nameProp = $propertyCollection->getPayerName();
            $nameProp->setValue($name);

// Сохраняем
            $order->doFinalAction(true);
            $result = $order->save();
            $orderId = $order->getId();
        endif;
    }

    function executeComponent()
    {
        global $USER;
        try {
            $request = Context::getCurrent()->getRequest();
            if ($request->isPost()){
                if (!$USER->IsAuthorized()){
                    $email=$request->getPost("user_email");
                    $idUser = $this->RegUser($email);
                }else{
                    $idUser=$USER->GetID();
                }

                $this->Order($idUser);
            }

            $this->includeComponentTemplate($this->page);
        } catch (Exception $e) {
            ShowError($e);
        }
    }
}

?>