<?php

	/**
	 * @package toolkit
	 */

	/**
	 * A simple Textarea field that essentially maps to HTML's `<textarea/>`.
	 */
	
	Class fieldTabbed_textarea extends Field {
		function __construct(&$parent){

			parent::__construct($parent);
			$this->_name = __('Tabbed Textarea');
			$this->_required = true;

			// Set default
			$this->set('show_column', 'no');
			$this->set('required', 'no');
		}

		function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
            $label = new XMLElement('div', $this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));

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
                    $value = $data['value'][$c];
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

		function checkPostFieldData($data, &$message, $entry_id=NULL){

			$message = NULL;

            /*
			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}
            */

            foreach($data['content'] as $content)
            {
                if($this->__applyFormatting($content, true, $errors) === false){
                    $message = __('"%1$s" contains invalid XML. The following error was returned: <code>%2$s</code>', array($this->get('label'), $errors[0]['message']));
                    return self::__INVALID_FIELDS__;
                }
            }

			return self::__OK__;

		}

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

		private function __replaceAmpersands($value) {
			return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
		}

		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null) {
            $element = new XMLElement($this->get('element_name'));

            foreach($data['tab'] as $c => $tab)
            {
                $attributes = array('name' => $tab, 'handle' => Lang::createHandle($tab));

                if($mode == null || $mode == 'formatted') {
                    if ($this->get('formatter') && isset($data['value_formatted'])) {
                        $value = $data['value_formatted'][$c];
                    } else {
                        $value = $data['value'][$c];
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

		function checkFields(&$required, $checkForDuplicates=true, $checkForParentSection=true){
			$required = array();
			if($this->get('size') == '' || !is_numeric($this->get('size'))) $required[] = 'size';
			return parent::checkFields($required, $checkForDuplicates, $checkForParentSection);

		}

		function findDefaults(&$fields){
			if(!isset($fields['size'])) $fields['size'] = 15;
		}

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
            $group = new XMLElement('div', null, array('class'=>'group'));
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][size]', $this->get('size'));
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

		function createTable(){

			return Symphony::Database()->query(

				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
                  `tab` tinytext NOT NULL,
                  `value` text NOT NULL,
                  `value_formatted` text NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`)
				) ENGINE=MyISAM;"
			);
		}

		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Textarea('fields['.$this->get('element_name').']', $this->get('size'), 50));

			return $label;
		}

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

	}

