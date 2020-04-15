<?
/**
 *
 */
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class svn_ordermail extends CModule
{
    function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__ . "/version.php");
        $this->MODULE_ID = "svn.ordermail";
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage("SVN_ORDERMAIL_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("SVN_ORDERMAIL_DESCRIPTION");

        $this->PARTNER_NAME = Loc::getMessage("SVN_ORDERMAIL_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("SVN_ORDERMAIL_PARTNER_URI");
    }

    /**
     *
     */
    function DoInstall()
    {
        global $APPLICATION;
        if ($this->isVersionD7()) {
            $this->create_catalog_iblock();
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
        } else {
            $APPLICATION->ThrowException(Loc::getMessage("SVN_ORDERMAIL_ERROR_D7"));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("SVN_ORDERMAIL_INSTALL_TITLE"), $this->GetPath() . '/install/step.php');
    }

    function DoUninstall()
    {
        global $APPLICATION;
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $APPLICATION->IncludeAdminFile(Loc::getMessage("SVN_ORDERMAIL_INSTALL_TITLE"), $this->GetPath() . '/install/unstep.php');
    }
   function InstallFiles()
   {
       CopyDirFiles($this->GetPath()."/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
   }

    function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'),'14.00.00');
    }
    function GetPath($notDocumentRoot=false){
        if($notDocumentRoot){
            return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
        }
        else{
            return(dirname(__DIR__));
        }

    }
    function create_catalog_iblock()
    {
        Loader::includeModule("iblock");
        $ib = new CIBlock;
        global $DB;
        global $APPLICATION;
        $arFields = Array(
            'ID'=>'invoice_price',
            'SECTIONS'=>'Y',
            'IN_RSS'=>'N',
            'SORT'=>100,
            'LANG'=>Array(
                'en'=>Array(
                    'NAME'=>'invoice_price',
                    'SECTION_NAME'=>'Sections',
                    'ELEMENT_NAME'=>'Products'
                ),
                'ru'=>Array(
                    'NAME'=>'Отправленные счета',
                    'SECTION_NAME'=>'Sections',
                    'ELEMENT_NAME'=>'Products'
                )
            )
        );

        $obBlocktype = new CIBlockType;
        $DB->StartTransaction();
        $res = $obBlocktype->Add($arFields);
        if(!$res)
        {
            $DB->Rollback();
            $APPLICATION->ThrowException('Error: '.$obBlocktype->LAST_ERROR);
        }
        else
            $DB->Commit();

        $IBLOCK_TYPE = "invoice_price"; // Тип инфоблока
        $SITE_ID = "s1"; // ID сайта

        /*
        // Айдишники групп, которым будем давать доступ на инфоблок
        $contentGroupId = $this->GetGroupIdByCode("CONTENT");
        $editorGroupId = $this->GetGroupIdByCode("EDITOR");
        $ownerGroupId = $this->GetGroupIdByCode("OWNER");
        */

        //===================================//
        // Создаем инфоблок каталога товаров //
        //===================================//

        // Настройка доступа
        $arAccess = array(
            "2" => "R", // Все пользователи
        );
    /*    if ($contentGroupId) $arAccess[$contentGroupId] = "X"; // Полный доступ
        if ($editorGroupId) $arAccess[$editorGroupId] = "W"; // Запись
        if ($ownerGroupId) $arAccess[$ownerGroupId] = "X"; // Полный доступ*/

        $arFields = Array(
            "ACTIVE" => "Y",
            "NAME" => "Отправленные счета",
            "CODE" => "invoice",
            "IBLOCK_TYPE_ID" => $IBLOCK_TYPE,
            "SITE_ID" => $SITE_ID,
            "SORT" => "5",
            "GROUP_ID" => $arAccess, // Права доступа
            "FIELDS" => array(
                "DETAIL_PICTURE" => array(
                    "IS_REQUIRED" => "N", // не обязательное
                    "DEFAULT_VALUE" => array(
                        "SCALE" => "Y", // возможные значения: Y|N. Если равно "Y", то изображение будет отмасштабировано.
                        "WIDTH" => "600", // целое число. Размер картинки будет изменен таким образом, что ее ширина не будет превышать значения этого поля.
                        "HEIGHT" => "600", // целое число. Размер картинки будет изменен таким образом, что ее высота не будет превышать значения этого поля.
                        "IGNORE_ERRORS" => "Y", // возможные значения: Y|N. Если во время изменения размера картинки были ошибки, то при значении "N" будет сгенерирована ошибка.
                        "METHOD" => "resample", // возможные значения: resample или пусто. Значение поля равное "resample" приведет к использованию функции масштабирования imagecopyresampled, а не imagecopyresized. Это более качественный метод, но требует больше серверных ресурсов.
                        "COMPRESSION" => "95", // целое от 0 до 100. Если значение больше 0, то для изображений jpeg оно будет использовано как параметр компрессии. 100 соответствует наилучшему качеству при большем размере файла.
                    ),
                ),
                "PREVIEW_PICTURE" => array(
                    "IS_REQUIRED" => "N", // не обязательное
                    "DEFAULT_VALUE" => array(
                        "SCALE" => "Y", // возможные значения: Y|N. Если равно "Y", то изображение будет отмасштабировано.
                        "WIDTH" => "140", // целое число. Размер картинки будет изменен таким образом, что ее ширина не будет превышать значения этого поля.
                        "HEIGHT" => "140", // целое число. Размер картинки будет изменен таким образом, что ее высота не будет превышать значения этого поля.
                        "IGNORE_ERRORS" => "Y", // возможные значения: Y|N. Если во время изменения размера картинки были ошибки, то при значении "N" будет сгенерирована ошибка.
                        "METHOD" => "resample", // возможные значения: resample или пусто. Значение поля равное "resample" приведет к использованию функции масштабирования imagecopyresampled, а не imagecopyresized. Это более качественный метод, но требует больше серверных ресурсов.
                        "COMPRESSION" => "95", // целое от 0 до 100. Если значение больше 0, то для изображений jpeg оно будет использовано как параметр компрессии. 100 соответствует наилучшему качеству при большем размере файла.
                        "FROM_DETAIL" => "Y", // возможные значения: Y|N. Указывает на необходимость генерации картинки предварительного просмотра из детальной.
                        "DELETE_WITH_DETAIL" => "Y", // возможные значения: Y|N. Указывает на необходимость удаления картинки предварительного просмотра при удалении детальной.
                        "UPDATE_WITH_DETAIL" => "Y", // возможные значения: Y|N. Указывает на необходимость обновления картинки предварительного просмотра при изменении детальной.
                    ),
                ),
                "SECTION_PICTURE" => array(
                    "IS_REQUIRED" => "N", // не обязательное
                    "DEFAULT_VALUE" => array(
                        "SCALE" => "Y", // возможные значения: Y|N. Если равно "Y", то изображение будет отмасштабировано.
                        "WIDTH" => "235", // целое число. Размер картинки будет изменен таким образом, что ее ширина не будет превышать значения этого поля.
                        "HEIGHT" => "235", // целое число. Размер картинки будет изменен таким образом, что ее высота не будет превышать значения этого поля.
                        "IGNORE_ERRORS" => "Y", // возможные значения: Y|N. Если во время изменения размера картинки были ошибки, то при значении "N" будет сгенерирована ошибка.
                        "METHOD" => "resample", // возможные значения: resample или пусто. Значение поля равное "resample" приведет к использованию функции масштабирования imagecopyresampled, а не imagecopyresized. Это более качественный метод, но требует больше серверных ресурсов.
                        "COMPRESSION" => "95", // целое от 0 до 100. Если значение больше 0, то для изображений jpeg оно будет использовано как параметр компрессии. 100 соответствует наилучшему качеству при большем размере файла.
                        "FROM_DETAIL" => "Y", // возможные значения: Y|N. Указывает на необходимость генерации картинки предварительного просмотра из детальной.
                        "DELETE_WITH_DETAIL" => "Y", // возможные значения: Y|N. Указывает на необходимость удаления картинки предварительного просмотра при удалении детальной.
                        "UPDATE_WITH_DETAIL" => "Y", // возможные значения: Y|N. Указывает на необходимость обновления картинки предварительного просмотра при изменении детальной.
                    ),
                ),
                // Символьный код элементов
                "CODE" => array(
                    "IS_REQUIRED" => "Y", // Обязательное
                    "DEFAULT_VALUE" => array(
                        "UNIQUE" => "Y", // Проверять на уникальность
                        "TRANSLITERATION" => "Y", // Транслитерировать
                        "TRANS_LEN" => "30", // Максмальная длина транслитерации
                        "TRANS_CASE" => "L", // Приводить к нижнему регистру
                        "TRANS_SPACE" => "-", // Символы для замены
                        "TRANS_OTHER" => "-",
                        "TRANS_EAT" => "Y",
                        "USE_GOOGLE" => "N",
                    ),
                ),
                // Символьный код разделов
                "SECTION_CODE" => array(
                    "IS_REQUIRED" => "Y",
                    "DEFAULT_VALUE" => array(
                        "UNIQUE" => "Y",
                        "TRANSLITERATION" => "Y",
                        "TRANS_LEN" => "30",
                        "TRANS_CASE" => "L",
                        "TRANS_SPACE" => "-",
                        "TRANS_OTHER" => "-",
                        "TRANS_EAT" => "Y",
                        "USE_GOOGLE" => "N",
                    ),
                ),
                "DETAIL_TEXT_TYPE" => array(      // Тип детального описания
                    "DEFAULT_VALUE" => "html",
                ),
                "SECTION_DESCRIPTION_TYPE" => array(
                    "DEFAULT_VALUE" => "html",
                ),
                "IBLOCK_SECTION" => array(         // Привязка к разделам обязательноа
                    "IS_REQUIRED" => "Y",
                ),
                "LOG_SECTION_ADD" => array("IS_REQUIRED" => "Y"), // Журналирование
                "LOG_SECTION_EDIT" => array("IS_REQUIRED" => "Y"),
                "LOG_SECTION_DELETE" => array("IS_REQUIRED" => "Y"),
                "LOG_ELEMENT_ADD" => array("IS_REQUIRED" => "Y"),
                "LOG_ELEMENT_EDIT" => array("IS_REQUIRED" => "Y"),
                "LOG_ELEMENT_DELETE" => array("IS_REQUIRED" => "Y"),
            ),

            // Шаблоны страниц
            "LIST_PAGE_URL" => "#SITE_DIR#/catalog/",
            "SECTION_PAGE_URL" => "#SITE_DIR#/catalog/#SECTION_CODE#/",
            "DETAIL_PAGE_URL" => "#SITE_DIR#/catalog/#SECTION_CODE#/#ELEMENT_CODE#/",

            "INDEX_SECTION" => "Y", // Индексировать разделы для модуля поиска
            "INDEX_ELEMENT" => "Y", // Индексировать элементы для модуля поиска

            "VERSION" => 1, // Хранение элементов в общей таблице

            "ELEMENT_NAME" => "Товар",
            "ELEMENTS_NAME" => "Товары",
            "ELEMENT_ADD" => "Добавить товар",
            "ELEMENT_EDIT" => "Изменить товар",
            "ELEMENT_DELETE" => "Удалить товар",
            "SECTION_NAME" => "Категории",
            "SECTIONS_NAME" => "Категория",
            "SECTION_ADD" => "Добавить категорию",
            "SECTION_EDIT" => "Изменить категорию",
            "SECTION_DELETE" => "Удалить категорию",

            "SECTION_PROPERTY" => "Y", // Разделы каталога имеют свои свойства (нужно для модуля интернет-магазина)
        );

        $ID = $ib->Add($arFields);
        if ($ID > 0)
        {
            $GLOBALS["M"][]=  "&mdash; инфоблок \"Отправленные счета\" успешно создан<br />";
        }
        else
        {
            $APPLICATION->ThrowException("&mdash; ошибка создания инфоблока \"Отправленные счета\"<br />");
            return false;
        }


        //=======================================//
        // Добавляем свойства к каталогу товаров //
        //=======================================//

        // Определяем, есть ли у инфоблока свойства
        $dbProperties = CIBlockProperty::GetList(array(), array("IBLOCK_ID"=>$ID));
        if ($dbProperties->SelectedRowsCount() <= 0)
        {
            $ibp = new CIBlockProperty;

            $arFields = Array(
                "NAME" => "Пользователь",
                "ACTIVE" => "Y",
                "SORT" => 100, // Сортировка
                "CODE" => "USER",
                "PROPERTY_TYPE" => "S:UserID", // Список

                "FILTRABLE" => "Y", // Выводить на странице списка элементов поле для фильтрации по этому свойству
                "IBLOCK_ID" => $ID
            );
            $propId = $ibp->Add($arFields);
            if ($propId > 0)
            {
                $arFields["ID"] = $propId;
                $arCommonProps[$arFields["CODE"]] = $arFields;
                $GLOBALS["M"][]= "&mdash; Добавлено свойство ".$arFields["NAME"]."<br />";
            }
            else
                $APPLICATION->ThrowException("&mdash; Ошибка добавления свойства ".$arFields["NAME"]."<br />");




            /* Дата */
            $arFields = Array(
                "NAME" => "Дата",
                "ACTIVE" => "Y",
                "SORT" => 200,
                "CODE" => "DATE",
                "PROPERTY_TYPE" => "S", // Строка
                "ROW_COUNT" => 1, // Количество строк
                "COL_COUNT" => 60, // Количество столбцов
                "IBLOCK_ID" => $ID,
                "HINT" => "Если задан - то заголовок для товара будет подставляться из этой строчки",
            );
            $propId = $ibp->Add($arFields);
            if ($propId > 0)
            {
                $arFields["ID"] = $propId;
                $arCommonProps[$arFields["CODE"]] = $arFields;
                $GLOBALS["M"][]= "&mdash; Добавлено свойство ".$arFields["NAME"]."<br />";
            }
            else
                $APPLICATION->ThrowException("&mdash; Ошибка добавления свойства ".$arFields["NAME"]."<br />");

            $arFields = Array(
                "NAME" => "Комментарий",
                "ACTIVE" => "Y",
                "SORT" => 300,
                "CODE" => "SEO_KEYWORDS",
                "PROPERTY_TYPE" => "S:HTML", // Строка
                "ROW_COUNT" => 3, // Количество строк
                "COL_COUNT" => 70, // Количество столбцов
                "IBLOCK_ID" => $ID
            );
            $propId = $ibp->Add($arFields);
            if ($propId > 0)
            {
                $arFields["ID"] = $propId;
                $arCommonProps[$arFields["CODE"]] = $arFields;
                $GLOBALS["M"][]= "&mdash; Добавлено свойство ".$arFields["NAME"]."<br />";
            }
            else
                $APPLICATION->ThrowException("&mdash; Ошибка добавления свойства ".$arFields["NAME"]."<br />");

            $arFields = Array(
                "NAME" => "Продукт",
                "ACTIVE" => "Y",
                "SORT" => 100, // Сортировка
                "CODE" => "USER",
                "PROPERTY_TYPE" => "S", // Список

                "FILTRABLE" => "Y", // Выводить на странице списка элементов поле для фильтрации по этому свойству
                "IBLOCK_ID" => $ID
            );
            $propId = $ibp->Add($arFields);
            if ($propId > 0)
            {
                $arFields["ID"] = $propId;
                $arCommonProps[$arFields["CODE"]] = $arFields;
                $GLOBALS["M"][]= "&mdash; Добавлено свойство ".$arFields["NAME"]."<br />";
            }
            else
                $APPLICATION->ThrowException("&mdash; Ошибка добавления свойства ".$arFields["NAME"]."<br />");

        }
        else
            $APPLICATION->ThrowException("&mdash; Для данного инфоблока уже существуют свойства<br />");




        // Возвращаем номер добавленного инфоблока
        return $ID;
    }
}

?>