<html>
<head>
	<title><?echo $this->title;?></title>
	<meta charset="utf-8">

	<?$this
		->css('/templates/template3/css/bootstrap.min.css')
		->showCss()
		->js('/templates/template3/js/jquery.min.js')
		->showJs();
	?>
</head>

<body class="body">
<div class="loader" style="display: none"></div>

	<?php
	/*
	*	Блок проверки авторизации
	*/
	$authorizeXslLink = "admin/authorize.xsl";
	$oUser = Core::factory("User");

	if(isset($_POST["login"]) && isset($_POST["password"]))
	{
		$oUser
			->login($_POST["login"])
			->password($_POST["password"]);

		if(!$oUser->authorize() || !User::getCurent()->superuser())
		{
			$oXml = Core::factory("Entity")
				->addEntity(
					Core::factory("Entity")
						->name("error")
						->value("Ошибка авторизации")
				)
				->xsl($authorizeXslLink)
				->show();
				
			exit;
		}
	}

	if(!$oUser::getCurent())
	{
		$oXml = Core::factory("Entity")
			->xsl($authorizeXslLink)
			->show();
		exit;
	}

	/*
	*	Верхняя панель
	*/
	Core::factory("Entity")
		->addEntity(
			$oUser::getCurent()
		)
		->xsl("admin/top_bar.xsl")
		->show();

	?>

	<div class="container">
		<div class="row">
			<h1>Добро пожаловать в административный раздел</h1>
		</div>

		<div class="row middle">
			<?php
			/*
			*	Вывод левого меню
			*/
			$aoMenuItems = Core::factory("Admin_Menu")
				->findAll();
			
			echo "<pre>";
			//print_r($aoMenuItems);
			echo "</pre>";

			Core::factory("Entity")
				->addEntities($aoMenuItems)
				->xsl("admin/left_bar.xsl")
				->show();
			?>
			<div class="col-lg-9 main">
				<?$this->execute();?>
			</div>
		</div>

		<div class="row bottom"></div>
	</div>
</body>
</html>