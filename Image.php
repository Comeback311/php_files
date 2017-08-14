<?php session_start();

class Image {
	
	// Итоговое изображение, resource
	public $image;

	// Ссылка итоговое изображение
	public $imageUrl;

	// Ссылка на временное изображение
	public $tempUrl;

	// Отступ сверху на фоновом изображении
	public $backgroundTop;

	// Каталог для хранения изображений
	public $dir = 'upload_temp/';

	// Ссылка на шаблонное изображение (1500 х 400 белый фон)
	public $defaultTemplate;

	// Ссылка на изображение стандартного аватара пользователя
	public $defaultUserPhoto;


	public function __construct($config) 
	{

		// Установка стандартных настроек
		$this -> defaultInit($config);


		// Устанавливаем фон
		if($this -> imageUrl)
			$this -> setFon();

		// Устанавливаем подписчиков
		if($config['add-subscriber']) 
			$this -> setNewSubscriber($config['add-subscriber']);

		// Устанавливаем текущеее время
		if($config['add-current-time']) 
			$this -> setCurrentTime($config['add-current-time']);

		// Устанавливаем таймер
		if($config['add-timer']) 
			$this -> setTimer($config['add-timer']);

		// Устанавливаем таймер
		if($config['add-text']) 
			$this -> setText($config['add-text']);

		// Отдаем клиенту картинку
		$this -> reponseImage();


		// Отдаем успешный JSON ответ
		$this -> sendResponse();
	} 

	// Установка стандартных настроек
	public function defaultInit($config) {

		// Ссылка на временное изображение
		$this -> tempUrl = $this -> dir . 'image1_temp.png';
		$this -> defaultTemplate = 'images/defaultTemplate.png';

		$this -> imageUrl = ($config['fon']) ? $config['fon']['url'] : $this -> tempUrl;
		$this -> save($this -> imageCreateFromType($this -> defaultTemplate), $this -> tempUrl);
	
		$this -> image = $this -> imageCreateFromType($this -> imageUrl);
		$this -> defaultUserPhoto = ($config['add-subscriber']) ? 'images/camera_200.png' : false;

		$this -> backgroundTop = ($config['fon']) ? $config['fon']['top'] : false;
	}

	// Добавить фоновую картинку
	public function setFon() 
	{

		// Получить размеры изображения
		list($imgWidth, $imgHeight) = getimagesize($this -> imageUrl);

		$totalHeight = ($imgHeight / 2);

		// Урезаем до ширины 1500
		$this -> resizeToWidth(1500);

		$this -> save($this -> image, $this -> imageUrl);

		// Вырезаем нужную область
		$this -> crop(0, $this -> backgroundTop * 2, 1500, 400);
	}

	// Устанавливаем текущеее время
	public function setCurrentTime($data) 
	{
		$this -> setItem($data, 'add-current-time');
	}


	// Устанавливаем текущеее время
	public function setTimer($data) 
	{	
		$this -> setItem($data, 'add-timer');
	}

	// Устанавливает текст
	public function setText($data) 
	{
		$this -> setItem($data, 'add-text');
	}

	// Устанваливает нужный элемент, в зависимости от переданных параметров
	public function setItem($data, $actionType) 
	{
		$dataCount = count($data);

		if($dataCount == 0)
			return false;

		foreach ($data as $dataInner) {
			
			foreach ($dataInner as $key => $itemInner) {
				
				if($key == 'id')
					continue;

				$this -> addTextToImage($actionType, $itemInner);	
			}
		}
	}


	// Возвращает resource изображения в зависимости от формата файла
	public function imageCreateFromType($imgUrl) 
	{

		if(!file_exists($imgUrl))
			return false;

		$fileType = $this -> getFileExtension($imgUrl);

		if($fileType == 'jpg')
			$img = ImageCreateFromJpeg($imgUrl);
		elseif($fileType == 'png')
			$img = ImageCreateFromPng($imgUrl);
		else return false;

		return $img;
	}

	// Возвращает разширение файла
	public function getFileExtension($filename) 
	{
		return pathinfo($filename, PATHINFO_EXTENSION);
	}


	// Изменяет ширину до определенной
	public function resizeToWidth($width) 
	{

		$ratio  = $width / $this -> getWidth();
		$height = $this -> getheight() * $ratio;

		$this -> resize($width, $height);
	}

	public function getWidth() 
	{
		return imagesx($this -> image);
	}

	public function getHeight() 
	{
		return imagesy($this -> image);
	}

	public function resize($width, $height) 
	{

		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this -> image, 0, 0, 0, 0, $width, $height, $this -> getWidth(), $this -> getHeight());
		$this -> image = $new_image;
	}

	public function save($imageResourse, $filename) 
	{

		$image_type = $this -> getFileExtension($filename);

		if(gettype($imageResourse) != 'resource')
			return true;

		if($image_type == 'jpg') {
			imagejpeg($imageResourse, $filename);
		} elseif($image_type == 'png') {
			imagepng($imageResourse, $filename);
		}
	}

	public function crop($x_o, $y_o, $w_o, $h_o) 
	{

		if (($x_o < 0) || ($y_o < 0) || ($w_o < 0) || ($h_o < 0)) {
			return false;
		}

		list($w_i, $h_i, $type) = getimagesize($this -> imageUrl); 

		$img_i = $this -> imageCreateFromType($this -> imageUrl); // Создаём дескриптор для работы с исходным изображением
		
		$img_o = imagecreatetruecolor($w_o, $h_o); // Создаём дескриптор для выходного изображения
		imagecopy($img_o, $img_i, 0, 0, $x_o, $y_o, $w_o, $h_o); // Переносим часть изображения из исходного в выходное

    	$this -> save($img_o, $this -> tempUrl); 

    	// Сохраняем временное изображение в основное
    	$this -> image = $this -> imageCreateFromType($this -> tempUrl);
	}

	public function setNewSubscriber($data) 
	{

		$subscribersCount = count($data);

		if($subscribersCount == 0)
			return false;

		foreach ($data as $subscriber) {
			
			$this -> addPhotoToImage($subscriber['subscriberItemAvatar']);
			$this -> addTextToImage('add-subscriber', $subscriber['subscriberItemUsername']);
		}
	}

	public function addPhotoToImage($imageData) 
	{	
		$imgWidth  = (int)$imageData['width'];
		$imgHegiht = (int)$imageData['height'];

		$imgLeft = (int)$imageData['left'];
		$imgTop  = (int)$imageData['top'];

		$dest = $this -> image;
		$src  = $this -> imageCreateFromType($this -> defaultUserPhoto);

		imagecopyresized($dest, $src, $imgLeft, $imgTop, 0, 0, $imgWidth, $imgHegiht, 200, 200);
	}

	public function addTextToImage($textType, $textData) {

		$text = $this -> getTextByType($textType);

		$font = 'fonts/verdana.ttf';

		$img = $this -> image;

		$color      = imagecolorallocate($img, 0, 0, 0);
		$colorWhite = imagecolorallocate($img, 255, 255, 255);
		

		list($textTop, $textLeft, $textFontSize) = $this -> getOffsetsByType($textType, $textData);

		//echo 'TextTop: ' . $textTop . "\nTextLeft: " . $textLeft . "\n";

		imagettftext($img, $textFontSize, 0, $textLeft, $textTop, $color, $font, $text);

    	$this -> save($this -> image, $this -> tempUrl); 
	}
	
	public function getTextByType($textType) 
	{
		if(!$textType)
			return false;

		switch ($textType) {
			case 'add-current-time':
				return date('H:i');
				break;

			case 'add-subscriber':
				return 'Иван Иванов';
				break;

			case 'add-timer':
				return 'ДД:ЧЧ:ММ:СС';
				break;

			case 'add-text':
				return 'Текст';
				break;

			default:
				return false;
				break;
		}
	}

	public function getOffsetsByType($textType, $textData)
	{
		if(!$textType)
			return false;

		$textWidth  = (int)$textData['width'];
		$textHeight = (int)$textData['height'];

		$textTop  = (int)$textData['top'];
		$textLeft = (int)$textData['left'];	


		$textFontSize = (int)$textData['fontSize'] + 6;

		$resultArray = array();

		if($textType == 'add-subscriber') {

			$resultArray[] = $textTop + ($textHeight / 2) + ($textFontSize * 0.5);

		} else {

			$resultArray[] = $textTop + ($textHeight / 2) + ($textFontSize * 0.2);
		}

		$resultArray[] = $textLeft;
		$resultArray[] = $textFontSize;

		return $resultArray;
	}

	// Отдаем клиенту картинку
	public function reponseImage() 
	{
		return $this -> imageCreateFromType($this -> tempUrl);
	}


	// Отдаем успешный json Ответ
	public function sendResponse() 
	{

		$response['success'] = 'success';
		$response['img_url'] = $this -> tempUrl;
		echo json_encode($response);
	}
}