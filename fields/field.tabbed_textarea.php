<?php

	/**
	 * @package toolkit
	 */

	/**
	 * A simple Textarea field that essentially maps to HTML's `<textarea/>`.
	 */
	
	Class fieldTabbed_textarea extends Field {
		/**
		 * Constructor
		 */
		function __construct(){
			parent::__construct();
			$this->_name = __('Tabbed Textarea');
			$this->_required = true;

			// Set default
			$this->set('show_column', 'no');
			$this->set('required', 'no');
		}

		/**
		 * Can Filter?
		 * @return bool
		 */
		function canFilter(){
			return true;
		}

		/**
		 * Can import?
		 * @return bool
		 */
		public function canImport(){
			return true;
		}

		/**
		 * Publish panel
		 *
		 * @param \XMLElement $wrapper
		 * @param null $data
		 * @param null $flagWithError
		 * @param null $fieldnamePrefix
		 * @param null $fieldnamePostfix
		 */
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
            $label = new XMLElement('div', $this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));

            // Weird bug? when the content is empty, an empty array is provided as object. Of this is the case, then
            // manually get the data:
            if(is_array($data) && count($data) == 1)
            {
                $callback = Administration::instance()->getPageCallback();
                $entry_id = $callback['context']['entry_id'];
                $data = array('tab'=>array(), 'value'=>array());
                $rows = Symphony::Database()->fetch('SELECT `tab`, `value` FROM `tbl_entries_data_'.$this->get('id').'` WHERE `entry_id` = '.$entry_id.';');
                foreach($rows as $row)
                {
                    $data['tab'][] = $row['tab'];
                    $data['value'][] = $row['value'];
                }
            }

            // Create the tabs:
            $tabs = new XMLElement('ul', null, array('class'=>'tabs'));
            $new  = $data === null;

            $delete = $this->get('only_developer') == 0 || ($this->get('only_developer') == 1 && Symphony::Engine()->Author->isDeveloper()) ? true : false;

            if($new)
            {
                // Only show the default tabs:
                $tabsArray = explode(',', $this->get('default_tabs'));
            } else {
                // Show the tabs according to the data:
                $tabsArray = $data['tab'];
            }

            if(!is_array($tabsArray)) {
                $tabsArray = array($tabsArray);
            }

            $i = 0;
            foreach($tabsArray as $tab)
            {
                $tab = trim($tab);
                $i++;
                $deleteStr = $delete ? '<a href="#" class="delete">×</a>' : '';
                $readOnly  = $delete ? '' : ' readonly="readonly" ';
                $tabs->appendChild(new XMLElement('li', '<input type="text" '.$readOnly.' name="fields'.$fieldnamePrefix.'['.$this->get('element_name').'][tabs]['.$i.']'.$fieldnamePostfix.'" value="'.$tab.'" /> '.$deleteStr, array('class'=>'tab'.$i)));
            }

            // The tab to create new tabs:
            if($this->get('only_developer') == 0 || ($this->get('only_developer') == 1 && Symphony::Engine()->Author->isDeveloper()))
            {
                $tabs->appendChild(new XMLElement('li', '+', array('class'=>'new')));
            }
            $label->appendChild($tabs);

            // Create the textareas:
            $i = 0;
            foreach($tabsArray as $c => $tab)
            {
                $i++;
                if($new) {
                    // This is a new entry, show the default textareas:
                    $value = '';
                } else {
                    // Show the content from the data:
                    if(is_array($data['value']))
                    {
                        $value = $data['value'][$c];
                    } else {
                        $value = $data['value'];
                    }
                }
                $textarea = new XMLElement('textarea', $value, array(
                    'name'=>'fields'.$fieldnamePrefix.'['.$this->get('element_name').'][content]['.$i.']'.$fieldnamePostfix,
                    'rows'=>$this->get('size'),
                    'cols'=>50,
                    'class'=>'tab'.$i
                    )
                );
                if($this->get('formatter') != 'none')
                {
                    $textarea->setAttribute('class', 'tab'.$i.' '.$this->get('formatter'));
                } else {
                    $textarea->setAttribute('class', 'tab'.$i);
                }

                /**
                 * Allows developers modify the textarea before it is rendered in the publish forms
                 *
                 * @delegate ModifyTextareaFieldPublishWidget
                 * @param string $context
                 * '/backend/'
                 * @param Field $field
                 * @param Widget $label
                 * @param Widget $textarea
                 */
                Symphony::ExtensionManager()->notifyMembers('ModifyTextareaFieldPublishWidget', '/backend/', array(
                    'field' => &$this,
                    'label' => &$label,
                    'textarea' => &$textarea
                ));
                $label->appendChild($textarea);
            }

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);

            $wrapper->appendChild(new XMLElement('input', null, array('type'=>'hidden', 'name'=>'element_name', 'value'=>$this->get('element_name'))));
            $deleteValue = $delete ? 1 : 0;
            $wrapper->appendChild(new XMLElement('input', null, array('type'=>'hidden', 'name'=>'delete', 'value'=>$deleteValue)));

		}

		/**
		 * Save settings
		 *
		 * @return bool
		 */
		function commit(){

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			if($this->get('formatter') != 'none') $fields['formatter'] = $this->get('formatter');
			$fields['size'] = $this->get('size');
            $fields['default_tabs'] = $this->get('default_tabs') != '' ? $this->get('default_tabs') : __('Default');
            $fields['only_developer'] = $this->get('only_developer') == 'yes' ? 1 : 0;

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());

		}

		/**
		 * Build Retrival SQL
		 *
		 * @param $data
		 * @param $joins
		 * @param $where
		 * @return bool
		 */
		public function buildDSRetrivalSQL($data, &$joins, &$where) {

			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->_key++;

				if (preg_match('/^regexp:/i', $data[0])) {
					$pattern = preg_replace('/regexp:/i', null, $this->cleanValue($data[0]));
					$regex = 'REGEXP';
				} else {
					$pattern = preg_replace('/not-?regexp:/i', null, $this->cleanValue($data[0]));
					$regex = 'NOT REGEXP';
				}

				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.value {$regex} '{$pattern}'
				";

			} else {
				if (is_array($data)) $data = $data[0];

				$data = $this->cleanValue($data);
				$this->_key++;
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND MATCH (t{$field_id}_{$this->_key}.value) AGAINST ('{$data}' IN BOOLEAN MODE)
				";
			}

			return true;
		}

		/**
		 * Validate the posted data
		 *
		 * @param array $data
		 * @param string $message
		 * @param null $entry_id
		 * @return int
		 */
		function checkPostFieldData($data, &$message, $entry_id=NULL){

			$message = NULL;

            if($this->get('required') == 'yes' && strlen($data['content'][1]) == 0){
                $message = __("'%s' is a required field.", array($this->get('label')));
                return self::__MISSING_FIELDS__;
            }

            foreach($data['content'] as $content)
            {
                if($this->__applyFormatting($content, true, $errors) === false){
                    $message = __('"%1$s" contains invalid XML. The following error was returned: <code>%2$s</code>', array($this->get('label'), $errors[0]['message']));
                    return self::__INVALID_FIELDS__;
                }
            }
            
			return self::__OK__;

		}

		/**
		 * Process the raw field data
		 *
		 * @param mixed $data
		 * @param int $status
		 * @param bool $simulate
		 * @param null $entry_id
		 * @return array
		 */
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {

            $status = self::__OK__;

            $result = array(
                'tab' => array(),
                'value' => array(),
                'value_formatted' => array()
            );

            // Store new tabs:
            foreach($data['tabs'] as $tabNr => $tab)
            {
                $content = $data['content'][$tabNr];
                $result['tab'][] = $tab;
                $result['value'][] = $content;
                $formatted = $this->__applyFormatting($content, true, $errors);
                if($formatted === false)
                {
				    //run the formatter again, but this time do not validate. We will sanitize the output
				    $formatted = General::sanitize($this->__applyFormatting($content));
			    }
                $result['value_formatted'][] = $formatted;
            }

            return $result;
		}

		/**
		 * Apply the text formatter
		 *
		 * @param $data
		 * @param bool $validate
		 * @param null $errors
		 * @return bool|mixed|string
		 */
		protected function __applyFormatting($data, $validate=false, &$errors=NULL){

			if($this->get('formatter')){
				$tfm = new TextformatterManager($this->_engine);
				$formatter = $tfm->create($this->get('formatter'));

				$result = $formatter->run($data);
			}

			if($validate === true){

				include_once(TOOLKIT . '/class.xsltprocess.php');

				if(!General::validateXML($result, $errors, false, new XsltProcess)){
					$result = html_entity_decode($result, ENT_QUOTES, 'UTF-8');
					$result = $this->__replaceAmpersands($result);

					if(!General::validateXML($result, $errors, false, new XsltProcess)){

						$result = $formatter->run(General::sanitize($data));

						if(!General::validateXML($result, $errors, false, new XsltProcess)){
							return false;
						}
					}
				}
			}

			return $result;
		}

		/**
		 * Replace the ampersands
		 *
		 * @param $value
		 * @return mixed
		 */
		private function __replaceAmpersands($value) {
			return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
		}

		/**
		 * Add the formatted element to the XML output
		 *
		 * @param \XMLElement $wrapper
		 * @param array $data
		 * @param bool $encode
		 * @param null $mode
		 */
		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null) {
            $element = new XMLElement($this->get('element_name'));

            if(!is_array($data['tab'])) {
                $data['tab'] = array($data['tab']);
            }

            foreach($data['tab'] as $c => $tab)
            {
                $attributes = array('name' => General::sanitize($tab), 'handle' => Lang::createHandle($tab));

                if($mode == null || $mode == 'formatted') {
                    if ($this->get('formatter') && isset($data['value_formatted'])) {
                        if(is_array($data['value_formatted']))
                        {
                            $value = $data['value_formatted'][$c];
                        } else {
                            $value = $data['value_formatted'];
                        }
                    } else {
                        if(is_array($data['value']))
                        {
                            $value = $data['value'][$c];
                        } else {
                            $value = $data['value'];
                        }
                    }

                    $value = $this->__replaceAmpersands($value);
                    if ($mode == 'formatted') {
                        $attributes['mode'] = $mode;
                    }

                    $element->appendChild(new XMLElement('tab', ($encode ? General::sanitize($value) : $value), $attributes));

                } elseif($mode == 'unformatted') {
                    $attributes['mode'] = $mode;

                    $element->appendChild(new XMLElement('tab', sprintf('<![CDATA[%s]]>', str_replace(']]>',']]]]><![CDATA[>', $data['value'][$c])), $attributes));

                }

            }
            $wrapper->appendChild($element);
		}

		/**
		 * Check the fields
		 *
		 * @param array $required
		 * @param bool $checkForDuplicates
		 * @param bool $checkForParentSection
		 * @return int
		 */
		function checkFields(&$required, $checkForDuplicates=true, $checkForParentSection=true){
			$required = array();
			if($this->get('size') == '' || !is_numeric($this->get('size'))) $required[] = 'size';
			return parent::checkFields($required, $checkForDuplicates, $checkForParentSection);

		}

		/**
		 * Find default values
		 *
		 * @param array $fields
		 */
		function findDefaults(&$fields){
			if(!isset($fields['size'])) $fields['size'] = 15;
		}

		/**
		 * Display the settings panel
		 *
		 * @param \XMLElement $wrapper
		 * @param null $errors
		 */
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

            $group = new XMLElement('div', null, array('class'=>'group'));
            $label = Widget::Label(__("Default Tabs"));
            $input = Widget::Input('fields['.$this->get('sortorder').'][default_tabs]', $this->get('default_tabs'));
            $label->appendChild($input);
            $group->appendChild($label);
            $group->appendChild($this->buildFormatterSelect($this->get('formatter'), 'fields['.$this->get('sortorder').'][formatter]', __('Text Formatter')));


			$wrapper->appendChild($group);


			## Textarea Size
            $group = new XMLElement('div', null, array('class'=>'compact'));
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][size]', (string)$this->get('size'));
			$input->setAttribute('size', '3');
			$label->setValue(__('Make textarea %s rows tall', array($input->generate())));
			$group->appendChild($label);


            $label = Widget::Label();
            $input = Widget::Input('fields['.$this->get('sortorder').'][only_developer]', 'yes', 'checkbox');
            if($this->get('only_developer') == 1)
            {
                $input->setAttribute('checked', 'checked');
            }
            $label->prependChild($input);
            $label->setValue(__('Only developers are allowed to add tabs'), false);
            $group->appendChild($label);
            $wrapper->appendChild($group);

			$div =  new XMLElement('div', NULL, array('class' => 'compact'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		/**
		 * Create the data table
		 *
		 * @return bool
		 */
		function createTable(){

			return Symphony::Database()->query(

				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
                  `tab` tinytext NOT NULL,
                  `value` text NULL,
                  `value_formatted` text NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM;"
			);
		}

		/**
		 * Get example form markup
		 *
		 * @return XMLElement
		 */
		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Textarea('fields['.$this->get('element_name').']', $this->get('size'), 50));

			return $label;
		}

		/**
		 * Fetch the includable elements
		 *
		 * @return array
		 */
		public function fetchIncludableElements() {

			if ($this->get('formatter')) {
				return array(
					$this->get('element_name') . ': formatted',
					$this->get('element_name') . ': unformatted'
				);
			}

			return array(
				$this->get('element_name')
			);
		}

		/**
		 * Prepare the table value to display in the index page
		 *
		 * @param array $data
		 * @param null|XMLElement $link
		 * @param null $entry_id
		 * @return string
		 */
		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			$max_length = Symphony::Configuration()->get('cell_truncation_length', 'symphony');
			$max_length = ($max_length ? $max_length : 75);

			if(is_array($data['value']))
			{
				$value = strip_tags($data['value'][0]);
			} else {
				$value = strip_tags($data['value']);
			}

			if(function_exists('mb_substr') && function_exists('mb_strlen')) {
				$value = (mb_strlen($value, 'utf-8') <= $max_length ? $value : mb_substr($value, 0, $max_length, 'utf-8') . '…');
			}
			else {
				$value = (strlen($value) <= $max_length ? $value : substr($value, 0, $max_length) . '…');
			}

			if(is_array($data['value']))
			{
				$value .= ' <em>('.__('%s tabs in total', array(count($data['tab']))).')</em>';
			}

			if (strlen($value) == 0) $value = __('None');

			if ($link) {
				$link->setValue($value);

				return $link->generate();
			}

			return $value;
		}


	}

