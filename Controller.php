<?php 

class SettingsController 
{

	public function actionIndex()
	{

		// Проверяем авторизован ли пользователь
		$isAuth = User::isAuth();

		// Если не авторизован, то отправляем на страницу авторизации
		if(!$isAuth) 
			header('Location: /');
			
			
		// Проверка на подмену сессии 
        User::checkCorrectSession();

        // Проверка на админку 
        $isAdmin = Settings::checkIsAdmin();
        

		require_once(ROOT . '/views/settings/index.php');

		return true;
	}


	// Страница настроек города пользователя
	public function actionCity()
	{	

		// Проверяем авторизован ли пользователь
		$isAuth = User::isAuth();

		// Если не авторизован, то отправляем на страницу авторизации
		if(!$isAuth) 
			header('Location: /');
			
			
	    // Проверка на подмену сессии 
        User::checkCorrectSession();


		// Получить cid города пользователя
		$cid = Settings::getCityCid();

		if(isset($cid)) {
			
			// Получить название города по его sid
			$cityName = Settings::checkCityExist($cid);
		}


		require_once(ROOT . '/views/settings/city.php');

		return true;
	}


	// Страница настроек аккаунтов пользователя
	public function actionAccounts()
	{

		// Проверяем авторизован ли пользователь
		$isAuth = User::isAuth();

		// Если не авторизован, то отправляем на страницу авторизации
		if(!$isAuth) 
			header('Location: /');
			
			
	    // Проверка на подмену сессии 
        User::checkCorrectSession();


		// Получить Вконтакте аккаунты пользователя
		$usersAccounts = Settings::getUsersAccounts();
	

		// Получить фотографии и имена, фамилии аккаунтов пользователя
		$usersAccountsData = Settings::getAccountsData($usersAccounts);
		
            
        $tempArray = array();
        $arrayCnt  = 0;
        
        foreach($usersAccountsData as $itemOuter) {
           
           foreach($usersAccounts as $itemInner) {
               
                if($itemInner['vk_id'] == $itemOuter -> uid) {
                    
                    $tempArray[$arrayCnt]['messages_today'] = $itemInner['messages_today'];
                    
                    foreach($itemOuter as $key => $item) {
                        
                        $tempArray[$arrayCnt][$key] = $item;
                    }
                    
                    $arrayCnt++;
                }
           }
        }
        
        $usersAccountsData = $tempArray;
        unset($tempArray);
        
		// Получить количество отправленных сообщений сегодня не друзьям
		// $usersAccountsDataMessages = Settings::getCountOfLastMessages($usersAccounts);
		
		// Объединить массив с количеством отправленных сообщений и 
		// Массив с информацией о пользователе
		// $compareUsersAccounts = Settings::compareUsersAccounts($usersAccountsDataMessages, $usersAccountsData);



		require_once(ROOT . '/views/settings/accounts.php');

		return true;
	}



	// Страница настроек шаблонов сообщений пользователя
	public function actionTemplates()
	{

		// Проверяем авторизован ли пользователь
		$isAuth = User::isAuth();

		// Если не авторизован, то отправляем на страницу авторизации
		if(!$isAuth) 
			header('Location: /');
			
			
		// Проверка на подмену сессии 
        User::checkCorrectSession();


		// Получить шаблоны сообщений пользователя
		$userTemplates = Settings::getUserTemplates();


		require_once(ROOT . '/views/settings/templates.php');

		return true;
	}


	public function actionAdmin()
	{

		$isAdmin = Settings::checkIsAdmin();

		if(!$isAdmin) 
			header('Location: /');

		require_once(ROOT . '/views/settings/admin.php');

		return true;
	}

	public function actionFirst_messages()
	{

		// Проверяем авторизован ли пользователь
		$isAuth = User::isAuth();

		// Если не авторизован, то отправляем на страницу авторизации
		if(!$isAuth) 
			header('Location: /');
			
			
		// Получить первые сообщения пользователя
		$firstMessages = Settings::getFirstMessages();
		
		require_once(ROOT . '/views/settings/first_messages.php');		

		return true;
	}



	// Получить ajax список городов 
	// Принимает поисковую строку для города
	public function actionGetCitiesAjax()
	{

		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];
		$cityText   = $parameters['cityText'];

		if($action != 'getCities')
			return true;

		// Получить массив с названиями городов
		$citiesArray = Settings::getCitiesBySearchString($cityText);

		echo json_encode($citiesArray);

		return true;
	}


	// Сохраняет cid (идентификатор) города в бд
	public function actionSaveCityAjax()
	{

		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];
		$cid   	    = $parameters['cid'];

		if($action != 'saveCity')
			return true;
    
		// Проверка, что данный cid существует если был выбран город
		if($cid == 0)
		    $isExisted = true;
		else
		    $isExisted = Settings::checkCityExist($cid);

		// Если существует, проверяем есть ли запись уже в бд
		if($isExisted) {
            
			// Вносим запись в БД
			$isAddedNow = Settings::addCityToDb($cid);

			if($isAddedNow) {

				$response['success'] = 1;
				echo json_encode($response);
				return true;
			}

		}

		return true;
	}


	// Сохраняет новый аккаунт Вконтакте
	public static function actionSaveVkAccountsAjax()
	{	
		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];

		if($action != 'saveVkAccount')
			return true;

		
		// Проверяем правильность логина и пароля от аккаунта вконтакте
		$result = Settings::vkAuth($parameters);

		// Если логин или пароль неправильный возвращает ошибку
		if($result -> error) {

			echo json_encode($result);
			return true;
		}

		// Добавить новый аккаунт пользователя в БД
		$isAdded = Settings::addVkAccountToDb($result);

		
		// Если все успешно
		if($isAdded == 'success') {

			$response['success'] = 1;
			echo json_encode($response);
			return true;

		} else if($isAdded == 'account_found') {

			// Аккаунт уже существует
			$response['error'] = 1;
			$response['description'] = 'Аккаунт уже существует';
			echo json_encode($response);
			return true;
		}

		return true;		
	}


	// Удаляет аккаунт Вк из списка аккаунтов пользователя
	public function actionRemoveVkAccountAjax()
	{

		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];

		if($action != 'removeVkAccount')
			return true;

		
		// Проверка, принадлежит ли данный аккаунт ВК текущему пользователю
		$vkAccountId = Settings::checkIfUsersAccount($parameters);

		// Удаление аккаунта
		$isDeleted = Settings::deleteVkAccount($vkAccountId);

		if($isDeleted) {

			$response['success'] = 1;
			echo json_encode($response);
			return true;
		}


		return true;
	}


	// Сохранить новый шаблон сообщения в БД
	public static function actionSaveMessageTemplateAjax()
	{

		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];

		if($action != 'saveMessageTemplate')
			return true;

		
		// Проверка на существование шаблона с таким именем
		$isExistTemplate = Settings::checkIfExistTemplate($parameters);

		// Если шаблона не существует, то создаем
		$isCreated = Settings::createMessageTemplate($parameters, $isExistTemplate);

		// Если шаблон был успешно создан, возвращаем true
		if($isCreated) {

			$response['success'] = 1;
			echo json_encode($response);
			return true;
		}

		// Если шаблон уже существует, возвращаем ошибку
		if($isExistTemplate) {

			$response['error'] = 1;
			$response['description'] = 'Шаблон с таким именем уже существует';
			echo json_encode($response);
			return true;
		}

		return true;
	}



	// Удаляет шаблон сообщения с БД
	public static function actionRemoveTemplateMessageAjax()
	{

		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];
        
		if($action != 'removeTemplateMessage')
			return true;


		// Проверка что данный шаблон принадлежит текущему пользователю
		$isUserTemplate = Settings::checkIfUserTemplate($parameters);
        
		// Если шаблон принадлежит пользователю, удаляем его
		$isDeletedTemplate = Settings::deleteTemplate($parameters, $isUserTemplate);

		// Если шаблон был удален успешно, возвращаем true
		if($isDeletedTemplate) {

			$response['success'] = 1;
			echo json_encode($response);
			return true;
		}

		return true;

	}


	// Сохранить новый шаблон
	public function actionEditSaveTemplateAjax() {

		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];

		if($action != 'editSaveTemplate')
			return true;


		// Проверка что данный шаблон принадлежит текущему пользователю
		$isUserTemplate = Settings::checkIfUserTemplate($parameters);

		// Проверка на существование шаблона, на который изменяем
		$isExistTemplate = Settings::checkIfExistTemplate($parameters);

		// Если редактируется текущий шаблон, выставляем переменную в false
		if($isExistTemplate['id'] == $parameters['templateId']) 
			$isExistTemplate = false;


		// Если шаблон принадлежит пользователю, изменяем его
		$isEditedTemplate = Settings::editTemplte($parameters, $isUserTemplate, $isExistTemplate);

		
		// Если ошибка, значит данный шаблон уже существует
		if($isExistTemplate) {	

			$response['error'] = 1;
			$response['description'] = 'Шаблон с таким именем уже существует';
			echo json_encode($response);
			return true;

		} else {

			// Иначе все хорошо, возвращаем true
			$response['success'] = 1;
			echo json_encode($response);
			return true;
		}

		return true;
	}


    // Сохранить первое сообщение в БД
	public function actionSaveFirstMessageAjax()
	{	

		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];

	    
		if($action != 'saveFirstMessage')
			return true;
    
	   
        
		// Проверка есть ли сообщение с заданным контекстом в БД
		$isExistFirstMessage = Settings::checkIfExistFirstMessage($parameters);
		

		// Если первое сообщение не найдено, вносим в бд
		// Если найдено, выводим ошибку
		Settings::addFirstMessage($parameters, $isExistFirstMessage);

		return true;
	}
	
	// Сохранить измененное первое сообщение в БД
	public static function actionSaveEditFmessageAjax()
	{
		$parameters = $_POST['parameters'];
		$action     = $parameters['action'];

		if($action != 'saveEditFmessage')
			return true;

		
		// Сохранить измененное сообщение в бд
		Settings::saveEditMessage($parameters);

		return true;
	}
	
	
	public static function actionRemovefMessageAjax() 
	{
	     
	    $parameters = $_POST['parameters'];
		$action     = $parameters['action'];

		if($action != 'removeFmessage')
			return true;   
			
		
		// Проверка что данный шаблон принадлежит текущему пользователю
		$isUserfMessage = Settings::checkIfUserfMessage($parameters);
        
		// Если шаблон принадлежит пользователю, удаляем его
		$isDeletedfMessage = Settings::deletefMessage($parameters, $isUserfMessage);

		// Если шаблон был удален успешно, возвращаем true
		if($isDeletedfMessage) {

			$response['success'] = 1;
			echo json_encode($response);
			return true;
		}
			
		return true;
	}

	// Создать нового пользователя с админки
	public static function actionCreateNewUserAjax() 
	{

		$parameters = $_POST['parameters'];

		$action     = $parameters['action'];
		$userLogin  = $parameters['userLogin'];

		if($action != 'createNewUser')
			return true; 

		// Проверка на админку 
        if(!Settings::checkIsAdmin()) 
        	return true;

		
		Settings::createNewUser($userLogin);

		return true;
	}


	// Загрузить аккаунты, созданные пользователем
	public static function actionLoadCreatedAccounts()
	{

		$parameters = $_POST['parameters'];

		$action     = $parameters['action'];
		$userLogin  = $parameters['userLogin'];

		if($action != 'loadCreatedAccounts')
			return true; 

		// Проверка на админку 
        if(!Settings::checkIsAdmin()) 
        	return true;


		$accountsData = Settings::getCreatedAccountsByUser();


		if(count($accountsData)) 
			return User::userSuccess('', $accountsData);

		return true;
	}


	// Удаляет пользователя
	public static function actionRemoveUserAjax()
	{

		$parameters = $_POST['parameters'];

		$action  = $parameters['action'];
		$userId  = $parameters['userId'];

		if($action != 'removeUser')
			return true; 

		// Проверка на админку 
        if(!Settings::checkIsAdmin()) 
        	return true;

        Settings::removeUser($userId);

		return true;
	}
}