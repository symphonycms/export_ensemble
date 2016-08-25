<?php

	$about = array(
		'name' => 'Русский',
		'author' => array(
			'name' => 'Бирюков Александр',
			'email' => 'info@alexbirukov.ru',
			'website' => 'http://alexbirukov.ru'
		),
		'release-date' => '2016-08-24'
	);

	/**
	 * Export Ensemble
	 */
	$dictionary = array(

		' <strong>Warning: It appears you do not have an <code>install</code> directory.</strong> ' => 
		' <strong>Внимание: у вас отсутствует каталог <code>install</code>.</strong> ',

		'An error occurred while trying to write the <code>config_default.php</code> file. Check the file permissions.' => 
		'Произошла ошибка при попытке записи файла <code>config_default.php</code>. Проверьте права на файл.',

		'An error occurred while trying to write the <code>install.sql</code> file. Check the file permissions.' => 
		'Произошла ошибка при попытке записи файла <code>install.sql</code>. Проверьте права на файл.',

		'An error occurred while trying to write the <code>workspace/install.sql</code> file. Check the file permissions.' => 
		'Произошла ошибка при попытке записи файла <code>workspace/install.sql</code>. Проверьте права на файл.',

		'Check permissions for the /manifest/tmp directory.' => 
		'Проверьте права на каталог /manifest/tmp',

		'Download ZIP' => 
		'Скачать ZIP',

		'Export Ensemble' => 
		'Экспорт сборки',

		'Export Ensemble is not able to create ensembles without a complete <code>install</code> directory. Please refer to the <code>README</code> file for usage instructions.' => 
		'Функция экспорта сборки недоступна без полного каталога <code>install</code>. Пожалуйста, ознакомьтесь с инструкциями в файле <code>README</code>.',

		'Export Ensemble is not able to download ZIP archives, since the "<a href="http://php.net/manual/en/book.zip.php">ZipArchive</a>" class is not available. To enable ZIP downloads, compile PHP with the <code>--enable-zip</code> flag. Try saving your install files instead and follow the README instructions.' => 
		'Функция экспорта сборки не может создать ZIP архивы, т.к. класс "<a href="http://php.net/manual/en/book.zip.php">ZipArchive</a>" недоступен. Для включения ZIP архивации необходимо скомпилировать PHP с ключом <code>--enable-zip</code>. Попробуйте сохранить файлы установщика и следуйте инструкциям в файле README.',

		'Export Ensemble will not be able to download ZIP archives, since the "<a href="http://php.net/manual/en/book.zip.php">ZipArchive</a>" class is not available. To enable ZIP downloads, compile PHP with the <code>--enable-zip</code> flag.' => 
		'Функция экспорта сборки не может создать ZIP архив, т.к. класс "<a href="http://php.net/manual/en/book.zip.php">ZipArchive</a>" недоступен. Для включения ZIP архивации необходимо скомпилировать PHP с ключом <code>--enable-zip</code>.',

		'Save Install Files' => 
		'Сохранить установочные файлы',

		'The install files were successfully saved.' => 
		'Установочные файлы успешно сохранены.',

		'Warning: It appears you do not have the "ZipArchive" class available. To enable ZIP download, ensure that PHP is compiled with <code>--enable-zip</code>' => 
		'Внимание: По всей вероятности у вас отсутствует класс "ZipArchive". Для получения возможности ZIP архивации убедитесь, что PHP скомпилирован с ключом <code>--enable-zip</code>',
		
		'Save (overwrite) install files or package entire site as a <code>.zip</code> archive for download.' => 
		'Сохранить (перезаписать) файлы установки в виде <code>.zip</code> архива для закачки.',

	);
