<?php

	Class extension_advanced_ensemble_export extends Extension{

		public function about(){
			return array('name' => 'Advanced Ensemble Export',
						 'version' => '1.0',
						 'release-date' => '2008-09-30',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://pointybeard.com',
										   'email' => 'alistair@pointybeard.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/system/preferences/',
							'delegate' => 'AddCustomPreferenceFieldsets',
							'callback' => 'appendPreferences'
						),
						
						/*array(
							'page' => '/system/preferences/',
							'delegate' => 'Save',
							'callback' => '__SavePreferences'
						),	*/					
						
					);
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
			$sql_schema = $sql_data = NULL;
			
			require_once(TOOLKIT . '/class.mysqldump.php');
			
			$dump = new MySQLDump($this->_Parent->Database);

			$tables = array(
				'tbl_authors',
				'tbl_cache',
				'tbl_entries',
				'tbl_extensions',
				'tbl_extensions_delegates',
				'tbl_fields',
				'tbl_fields_%',			
				'tbl_forgotpass',
				'tbl_pages',
				'tbl_pages_types',
				'tbl_sections',
				'tbl_sections_association'			
			);
			
			## Grab the schema
			foreach($tables as $t) $sql_schema .= $dump->export($t, MySQLDump::STRUCTURE_ONLY);
			$sql_schema = str_replace('`' . $this->_Parent->Configuration->get('tbl_prefix', 'database'), '`tbl_', $sql_schema);
			
			$sql_schema = preg_replace('/AUTO_INCREMENT=\d+/i', '', $sql_schema);
			
			$tables = array(
				'tbl_entries',
				'tbl_extensions',
				'tbl_extensions_delegates',
				'tbl_fields',			
				'tbl_pages',
				'tbl_pages_types',
				'tbl_sections',
				'tbl_sections_association'			
			);			
			
			## Field data and entry data schemas needs to be apart of the workspace sql dump
			$sql_data  = $dump->export('tbl_fields_%', MySQLDump::ALL);
			$sql_data .= $dump->export('tbl_entries_%', MySQLDump::ALL);
			
			## Grab the data
			foreach($tables as $t) $sql_data .= $dump->export($t, MySQLDump::DATA_ONLY);
			$sql_data = str_replace('`' . $this->_Parent->Configuration->get('tbl_prefix', 'database'), '`tbl_', $sql_data);
			
			$config_string = NULL;
			$config = $this->_Parent->Configuration->get();	
			
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

			foreach($config as $group => $set){
				foreach($set as $key => $val) $config_string .= "		\$conf['".$group."']['".$key."'] = '".$val."';" . self::CRLF;
			}
			
			$install_template = str_replace(
				
									array(
										'<!-- BUILD -->',
										'<!-- ENCODED SQL SCHEMA DUMP -->',
										'<!-- ENCODED SQL DATA DUMP -->',
										'<!-- CONFIGURATION -->'
									),
				
									array(
										$this->_Parent->Configuration->get('build', 'symphony'),
										base64_encode($sql_schema),
										base64_encode($sql_data),
										trim($config_string),										
									),
				
									file_get_contents(TEMPLATE . '/installer.tpl')
			);
			
			$archive = new ZipArchive;
			$res = $archive->open(TMP . '/ensemble.tmp.zip', ZipArchive::CREATE);
			
			if ($res === TRUE) {
				
				$archive->addFromString('install.php', $install_template);
				$archive->addFile(DOCROOT . '/index.php', 'index.php');
				
				if(is_file(DOCROOT . '/README.txt')) $archive->addFile(DOCROOT . '/README.txt', 'README.txt');
				if(is_file(DOCROOT . '/LICENCE.txt')) $archive->addFile(DOCROOT . '/LICENCE.txt', 'LICENCE.txt');
				if(is_file(DOCROOT . '/update.php')) $archive->addFile(DOCROOT . '/update.php', 'update.php');
				
				$this->__addFolderToArchive($archive, EXTENSIONS, DOCROOT);
				$this->__addFolderToArchive($archive, SYMPHONY, DOCROOT);
				$this->__addFolderToArchive($archive, WORKSPACE, DOCROOT);				
		
			}
			
			$archive->close();

			header('Content-type: application/octet-stream');	
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		    header('Content-disposition: attachment; filename='.Lang::createFilename($this->_Parent->Configuration->get('sitename', 'general')).'-ensemble.zip');
		    header('Pragma: no-cache');
		
			readfile(TMP . '/ensemble.tmp.zip');
			unlink(TMP . '/ensemble.tmp.zip');
			exit();
			
		}

		public function __SavePreferences($context){
			$this->__export();
		}
		
		public function appendPreferences($context){
			
			if(isset($_POST['action']['export-advanced'])){
				$this->__SavePreferences($context);
			}
			
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'Export Ensemble - Advanced'));			
			

			$div = new XMLElement('div', NULL, array('id' => 'file-actions', 'class' => 'label'));			
			$span = new XMLElement('span');
			$span->appendChild(new XMLElement('button', 'Create Ensemble', array('name' => 'action[export-advanced]', 'type' => 'submit')));
			$div->appendChild($span);

			$div->appendChild(new XMLElement('p', 'Packages entire site as a <code>.zip</code> archive for download.', array('class' => 'help')));	

			$group->appendChild($div);						
			$context['wrapper']->appendChild($group);
						
		}
	}