<?php

	Class extension_export_ensemble extends Extension{

		public function about(){
			return array(
				'name' => 'Export Ensemble',
				'version' => '1.14',
				'release-date' => '2011-04-30',
				'author' => array(
					array(
						'name' => 'Alistair Kearney',
						'website' => 'http://pointybeard.com',
						'email' => 'alistair@pointybeard.com'
					),
					array(
						'name' => 'Symphony Team',
						'website' => 'http://symphony-cms.com',
						'email' => 'team@symphony-cms.com'
					)
				)
			);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				)
			);
		}

		public function install(){
			if(!class_exists('ZipArchive')){
				if(isset(Administration::instance()->Page)){
					Administration::instance()->Page->pageAlert(__('Export Ensemble cannot be installed, since the "<a href="http://php.net/manual/en/book.zip.php">ZipArchive</a>" class is not available. Ensure that PHP was compiled with the <code>--enable-zip</code> flag.'), Alert::ERROR);
				}
				return false;
			}
			return true;
		}

		private function __addFolderToArchive(&$archive, $path, $parent=NULL){
			$iterator = new DirectoryIterator($path);
			foreach($iterator as $file){
				if($file->isDot() || preg_match('/^\./', $file->getFilename())) continue;

				elseif($file->isDir()){
					$this->__addFolderToArchive($archive, $file->getPathname(), $parent);
				}

				else $archive->addFile($file->getPathname(), ltrim(str_replace($parent, NULL, $file->getPathname()), '/'));
			}
		}

		private function __export(){

			## Find all tables in the database
			$Database = Symphony::Database();
			$all_tables = $Database->fetch('show tables');

			## Find table prefix used for this install of Symphony
			$tbl_prefix = Symphony::Configuration()->get('tbl_prefix', 'database');

			## Find length of prefix to test for table prefix
			$prefix_length = strlen($tbl_prefix);

			## Flatten multidimensional tables array
			$db_tables = array();
			foreach($all_tables as $table){
				$value = array_values($table);
				$value = $value[0];

				## Limit array of tables to those using the table prefix
				## and replace the table prefix with tbl
				if(substr($value, 0, $prefix_length) === $tbl_prefix){
					$db_tables[] = 'tbl_' . substr($value, $prefix_length);
				}
			}

			## Create arrays to store tables for structure and data dumps
			$structure_tables = $data_tables = $db_tables;

			## Create array of tables to ignore for data-only dump
			$ignore_tables = array(
				'tbl_entries_',
				'tbl_fields_'
			);

			## Remove tables from list for structure-only dump
			foreach($structure_tables as $index => $table){
				foreach($ignore_tables as $starts){
					if(substr($table, 0, strlen($starts)) === $starts ){
						unset($structure_tables[$index]);
					}
				}
			}

			## Add fields tables back into list
			$structure_tables[] = 'tbl_fields_%';
			sort($structure_tables);

			## Create variables for the dump files
			$sql_schema = $sql_data = NULL;

			require_once(dirname(__FILE__) . '/lib/class.mysqldump.php');

			$dump = new MySQLDump(Symphony::Database());

			## Grab the schema
			foreach($structure_tables as $t) $sql_schema .= $dump->export($t, MySQLDump::STRUCTURE_ONLY);
			$sql_schema = str_replace('`' . Symphony::Configuration()->get('tbl_prefix', 'database'), '`tbl_', $sql_schema);

			$sql_schema = preg_replace('/AUTO_INCREMENT=\d+/i', NULL, $sql_schema);

			## Field data and entry data schemas needs to be apart of the workspace sql dump
			$sql_data  = $dump->export('tbl_fields_%', MySQLDump::ALL);
			$sql_data .= $dump->export('tbl_entries_%', MySQLDump::ALL);

			## Grab the data
			foreach($data_tables as $t){
				$sql_data .= $dump->export($t, MySQLDump::DATA_ONLY);
			}

			$sql_data = str_replace('`' . $tbl_prefix, '`tbl_', $sql_data);

			$config_string = NULL;
			$config = Symphony::Configuration()->get();

			unset($config['symphony']['build']);
			unset($config['symphony']['cookie_prefix']);
			unset($config['general']['useragent']);
			unset($config['file']['write_mode']);
			unset($config['directory']['write_mode']);
			unset($config['database']['host']);
			unset($config['database']['port']);
			unset($config['database']['user']);
			unset($config['database']['password']);
			unset($config['database']['db']);
			unset($config['database']['tbl_prefix']);
			unset($config['region']['timezone']);
			unset($config['email']['default_gateway']);
			unset($config['email_sendmail']['from_name']);
			unset($config['email_sendmail']['from_address']);
			unset($config['email_smtp']['from_name']);
			unset($config['email_smtp']['from_address']);
			unset($config['email_smtp']['host']);
			unset($config['email_smtp']['port']);
			unset($config['email_smtp']['secure']);
			unset($config['email_smtp']['auth']);
			unset($config['email_smtp']['username']);
			unset($config['email_smtp']['password']);

			foreach($config as $group => $set){
				foreach($set as $key => $val){
					$config_string .= "		\$conf['{$group}']['{$key}'] = '{$val}';" . self::CRLF;
				}
			}

			$install_template = str_replace(
				array(
					'<!-- VERSION -->',
					'<!-- CONFIGURATION -->'
				),

				array(
					Symphony::Configuration()->get('version', 'symphony'),
					trim($config_string),
				),

				file_get_contents(dirname(__FILE__) . '/lib/installer.tpl')
			);

			$archive = new ZipArchive;
			$res = $archive->open(TMP . '/ensemble.tmp.zip', ZipArchive::CREATE);

			if ($res === TRUE) {

				$this->__addFolderToArchive($archive, EXTENSIONS, DOCROOT);
				$this->__addFolderToArchive($archive, SYMPHONY, DOCROOT);
				$this->__addFolderToArchive($archive, WORKSPACE, DOCROOT);

				$archive->addFromString('install.php', $install_template);
				$archive->addFromString('install.sql', $sql_schema);
				$archive->addFromString('workspace/install.sql', $sql_data);

				$archive->addFile(DOCROOT . '/index.php', 'index.php');

				$readme_files = glob(DOCROOT . '/README.*');
				if(is_array($readme_files) && !empty($readme_files)){
					foreach($readme_files as $filename){
						$archive->addFile($filename, basename($filename));
					}
				}

				if(is_file(DOCROOT . '/README')) $archive->addFile(DOCROOT . '/README', 'README');
				if(is_file(DOCROOT . '/LICENCE')) $archive->addFile(DOCROOT . '/LICENCE', 'LICENCE');
				if(is_file(DOCROOT . '/update.php')) $archive->addFile(DOCROOT . '/update.php', 'update.php');
			}

			$archive->close();

			header('Content-type: application/octet-stream');
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');

			header(
				sprintf(
					'Content-disposition: attachment; filename=%s-ensemble.zip',
					Lang::createFilename(
						Symphony::Configuration()->get('sitename', 'general')
					)
				)
			);

			header('Pragma: no-cache');

			readfile(TMP . '/ensemble.tmp.zip');
			unlink(TMP . '/ensemble.tmp.zip');
			exit();

		}

		public function __SavePreferences($context){
			$this->__export();
		}

		public function appendPreferences($context){

			if(isset($_POST['action']['export'])){
				$this->__SavePreferences($context);
			}

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Export Ensemble')));


			$div = new XMLElement('div', NULL, array('id' => 'file-actions', 'class' => 'label'));
			$span = new XMLElement('span', NULL, array('class' => 'frame'));

			if(!class_exists('ZipArchive')){
				$span->appendChild(
					new XMLElement('p', '<strong>' . __('Warning: It appears you do not have the "ZipArchive" class available. Ensure that PHP was compiled with <code>--enable-zip</code>') . '</strong>')
				);
			}
			else{
				$span->appendChild(new XMLElement('button', __('Create'), array('name' => 'action[export]', 'type' => 'submit')));
			}

			$div->appendChild($span);

			$div->appendChild(new XMLElement('p', __('Packages entire site as a <code>.zip</code> archive for download.'), array('class' => 'help')));

			$group->appendChild($div);
			$context['wrapper']->appendChild($group);

		}
	}
