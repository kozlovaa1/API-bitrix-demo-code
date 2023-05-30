<?php

namespace Service;

use Bitrix\Iblock\Elements\ElementReviewsTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Local\Exception\ReviewException;
use Local\Service\EnvService;

class ReviewService
{
    private $iblockId;

    /**
     * @throws LoaderException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function __construct()
    {
        Loader::includeModule('iblock');
        $this->iblockId = IblockTable::getList(array(
            'filter' => array('CODE' => 'reviews')
        ))->fetch()['ID'];
    }

    /**
     * @throws ReviewException
     */
    public function add($data)
    {
        global $USER;
        $arFiles = [];
        $files = $data['file'];
        if (!empty($files)) {
            $arFiles = self::uploadFiles($files);
        }
        $arFields = array(
            "MODIFIED_BY" => $USER->GetID(),
            "ACTIVE" => "N",
            "IBLOCK_ID" => $this->iblockId,
            "NAME" => $data['email'] . '_' . $data['productId'],
            "PROPERTY_VALUES" => array(
                "NAME" => $data['lastName'] . " " . $data['name'] . " " . $data['middleName'],
                "EMAIL" => $data['email'],
                "PRODUCT" => $data['productId'],
                "COMMENT" => $data['comment'],
                "RATING" => $data['rating'],
                "ADVANTAGES" => $data['text1'],
                "DISADVANTAGES" => $data['text2'],
                "PHOTO" => $arFiles,
            )
        );
        $fileIds = [];
        foreach ($arFiles as $file) {
            $fileIds[] = $file['VALUE'];
        }
        $oElement = new \CIBlockElement();
        $add = $oElement->Add($arFields, false, false, true);
        if ($add) {
            $product = \CIBlockElement::GetByID($data['productId'])->GetNext();
            $productName = $product['NAME'];
            $productLink = SITE_SERVER_NAME . $product['DETAIL_PAGE_URL'];
            $result = Event::send([
                "EVENT_NAME" => "NEW_REVIEW",
                "LID" => SITE_ID,
                "C_FIELDS" => array(
                    "EMAIL" => $data['email'],
                    "NAME" => $data['lastName'] . " " . $data['name'] . " " . $data['middleName'],
                    "PRODUCT" => $productName,
                    "PRODUCT_LINK" => $productLink,
                    "COMMENT" => $data['comment'],
                    "RATING" => $data['rating'],
                    "ADVANTAGES" => $data['text1'],
                    "DISADVANTAGES" => $data['text2'],
                ),
                "FILE" => $fileIds,
            ]);
            if (!$result->isSuccess()) {
                throw new ReviewException('Сообщение не отправлено. ' . $result->getErrorMessages()[0]);
            }
        } else {
            throw new ReviewException('Не удалось добавить отзыв');
        }
    }

    /**
     * @throws ReviewException
     */
    private static function uploadFiles($files): array
    {
        $propertyValues = [];
        foreach ($files as $key => $file) {
            $fileName = $file->getClientFilename();
            $fileTmpName = Application::getDocumentRoot() . '/upload/tmp/' . md5(time()) . $fileName;
            $file->moveTo($fileTmpName);
            if (strripos($file->getClientMediaType(), 'image') !== false) {
                if ($file->getSize() <= 15728640) { // 15 Mb
                    $arFile = \CFile::MakeFileArray($fileTmpName);
                    $fileId = \CFile::SaveFile($arFile, "files");

                    $propertyValues['n' . $key] = [
                        "VALUE" => $fileId,
                        "DESCRIPTION" => $fileName
                    ];
                } else {
                    throw new ReviewException('Файл ' . $fileName . ' больше 15 Мб');
                }
            } else {
                throw new ReviewException('Файл ' . $fileName . ' не является изображением');
            }
        }
        return $propertyValues;
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws ReviewException
     */
    public function addLike($id)
    {
        $checkLike = self::checkLike($id);
        if (self::checkLike($id) == 'liked') {
            $this->removeLike($id);
            return;
        } elseif (self::checkLike($id) == 'disliked') {
            $this->removeDislike($id);
        }
        $likeCount = 1;
        $review = ElementReviewsTable::getList(array(
            'select' => array('ID', 'LIKE_VALUE' => 'LIKE.VALUE'),
            'filter' => array('IBLOCK_ID' => $this->iblockId, 'ID' => $id),
            'cache' => array(
                'ttl' => 3600,
                'cache_joins' => true,
            ),
        ))->fetch();
        if ($review['LIKE_VALUE'] >= 0) {
            $likeCount = $review['LIKE_VALUE'] + 1;
        }
        \CIBlockElement::SetPropertyValuesEx($id, false, array('LIKE' => $likeCount));
        $this->updateLikesSession($id, 'like');
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws ReviewException
     */
    public function addDislike($id)
    {
        $checkLike = self::checkLike($id);
        if (self::checkLike($id) == 'disliked') {
            $this->removeDislike($id);
            return;
        } elseif (self::checkLike($id) == 'liked') {
            $this->removeLike($id);
        }
        $dislikeCount = 1;
        $review = ElementReviewsTable::getList(array(
            'select' => array('ID', 'DISLIKE_VALUE' => 'DISLIKE.VALUE'),
            'filter' => array('IBLOCK_ID' => $this->iblockId, 'ID' => $id),
            'cache' => array(
                'ttl' => 3600,
                'cache_joins' => true,
            ),
        ))->fetch();
        if ($review['DISLIKE_VALUE'] >= 0) {
            $dislikeCount = $review['DISLIKE_VALUE'] + 1;
        }
        \CIBlockElement::SetPropertyValuesEx($id, false, array('DISLIKE' => $dislikeCount));
        $this->updateLikesSession($id, 'dislike');
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws ReviewException
     */
    public function refuse($id)
    {
        global $USER;
        $IDs[] = $USER->GetID();
        $res = \CIBlockElement::GetProperty(EnvService::parameter('CATALOG_IBLOCK_ID'), $id, Array("sort"=>"asc"), array("CODE" => "NO_REVIEW"));
        while ($ob = $res->GetNext())
        {
            $IDs[] = $ob['VALUE'];
        }
        $IDs = array_unique($IDs);
        \CIBlockElement::SetPropertyValuesEx($id, EnvService::parameter('CATALOG_IBLOCK_ID'), array('NO_REVIEW' => $IDs));
    }

    private static function checkLike($id): string
    {
        $likes = [];
        $session = \Bitrix\Main\Application::getInstance()->getSession();
        if ($session->has('likes')) {
            $likes = unserialize($session['likes']);
        }
        foreach ($likes as $key => $like) {
            if ($key == $id) {
                if ($like == 'like') {
                    return 'liked';
                } elseif ($like == 'dislike') {
                    return 'disliked';
                }
            }
        }
        return 'empty';
    }

    private function removeLike($id)
    {
        $review = ElementReviewsTable::getList(array(
            'select' => array('ID', 'LIKE_VALUE' => 'LIKE.VALUE'),
            'filter' => array('IBLOCK_ID' => $this->iblockId, 'ID' => $id),
            'cache' => array(
                'ttl' => 3600,
                'cache_joins' => true,
            ),
        ))->fetch();
        if ($review['LIKE_VALUE'] >= 0) {
            $likeCount = $review['LIKE_VALUE'] - 1;
            \CIBlockElement::SetPropertyValuesEx($id, false, array('LIKE' => $likeCount));
        }
        $this->updateLikesSession($id, 'empty');
    }

    private function removeDislike($id)
    {
        $review = ElementReviewsTable::getList(array(
            'select' => array('ID', 'DISLIKE_VALUE' => 'DISLIKE.VALUE'),
            'filter' => array('IBLOCK_ID' => $this->iblockId, 'ID' => $id),
            'cache' => array(
                'ttl' => 3600,
                'cache_joins' => true,
            ),
        ))->fetch();
        if ($review['DISLIKE_VALUE'] >= 0) {
            $dislikeCount = $review['DISLIKE_VALUE'] - 1;
            \CIBlockElement::SetPropertyValuesEx($id, false, array('DISLIKE' => $dislikeCount));
        }
        $this->updateLikesSession($id, 'empty');
    }

    private function updateLikesSession($id, $value)
    {
        $session = \Bitrix\Main\Application::getInstance()->getSession();
        $likes = [];
        if ($session->has('likes')) {
            $likes = unserialize($session['likes']);
        }

        $likes[$id] = $value;
        $session->set('likes', serialize($likes));
    }
}
