<?php

/**
* with thanks to Travis Smith's excellent tutorial:
* http://wpsmith.net/2011/plugins/how-to-create-a-custom-form-field-in-gravity-forms-with-a-terms-of-service-form-field-example/
*/
class GFEwayRecurringField {

	private $plugin;

	private static $defaults = array (
		'gfeway_initial_amount_label' => 'Initial Amount',
		'gfeway_recurring_amount_label' => 'Recurring Amount',
		'gfeway_initial_date_label' => 'Initial Date',
		'gfeway_start_date_label' => 'Start Date',
		'gfeway_end_date_label' => 'End Date',
		'gfeway_interval_type_label' => 'Interval Type',
	);

	/**
	* @param GFEwayPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		// add Gravity Forms hooks
		add_action('gform_editor_js', array($this, 'gformEditorJS'));
		add_action('gform_field_standard_settings', array($this, 'gformFieldStandardSettings'), 10, 2);
		add_filter('gform_add_field_buttons', array($this, 'gformAddFieldButtons'));
		add_filter('gform_field_type_title', array($this, 'gformFieldTypeTitle'), 10, 2);
		add_filter('gform_field_input', array($this, 'gformFieldInput'), 10, 5);
		add_filter('gform_field_validation', array($this, 'gformFieldValidation'), 10, 4);
		add_filter('gform_tooltips', array($this, 'gformTooltips'));
		add_filter('gform_pre_submission', array($this, 'gformPreSubmit'));
	}

	/**
	* load custom script for editor form
	*/
	public function gformEditorJS() {
		$version = GFEWAY_PLUGIN_VERSION;
		echo "<script src=\"{$this->plugin->urlBase}js/admin-recurring.min.js?v=$version\"></script>\n";
	}

	/**
	* filter hook for modifying the field buttons on the forms editor
	* @param array $field_groups array of field groups; each element is an array of button definitions
	* @return array
	*/
	public function gformAddFieldButtons($field_groups) {
		foreach ($field_groups as &$group) {
			if ($group['name'] == 'pricing_fields') {
				$group['fields'][] = array (
					'class' => 'button',
					'value' => 'Recurring',
					'onclick' => "StartAddField('gfewayrecurring');",
				);
				break;
			}
		}
		return $field_groups;
	}

	/**
	* filter hook for modifying the field title (e.g. on custom fields)
	* @param string $title
	* @param string $field_type
	* @return string
	*/
	public function gformFieldTypeTitle($title, $field_type) {
		if ($field_type == 'gfewayrecurring') {
			$title = 'Recurring Payments';
		}

		return $title;
	}

	/**
	* add custom fields to form editor
	* @param integer $position
	* @param integer $form_id
	*/
	public function gformFieldStandardSettings($position, $form_id) {
		// add inputs for labels right after the field label input
		if ($position == 25) {
			?>
				<li class="gfewayrecurring_setting field_setting">

					<input type="checkbox" id="gfeway_initial_setting" onchange="GFEwayRecurring.ToggleInitialSetting(this)" />
					<label for="gfeway_initial_setting" class="inline">
						Show Initial Amount
						<?php gform_tooltip("gfeway_initial_setting") ?>
						<?php gform_tooltip("gfeway_initial_setting_html") ?>
					</label>
					<br />
					<br />

					<div id="gfeway_initial_fields">

					<label for="gfeway_initial_date_label">
						Initial Date Label
						<?php gform_tooltip("gfeway_initial_date_label") ?>
						<?php gform_tooltip("gfeway_initial_date_label_html") ?>
					</label>
					<input type="text" id="gfeway_initial_date_label" class="fieldwidth-3" size="35"
						onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_initial_date_label']) ?>')" />

					<label for="gfeway_initial_amount_label">
						Initial Amount Label
						<?php gform_tooltip("gfeway_initial_amount_label") ?>
						<?php gform_tooltip("gfeway_initial_amount_label_html") ?>
					</label>
					<input type="text" id="gfeway_initial_amount_label" class="fieldwidth-3" size="35"
						onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_initial_amount_label']) ?>')" />

					</div>

					<label for="gfeway_recurring_amount_label">
						Recurring Amount Label
						<?php gform_tooltip("gfeway_recurring_amount_label") ?>
						<?php gform_tooltip("gfeway_recurring_amount_label_html") ?>
					</label>
					<input type="text" id="gfeway_recurring_amount_label" class="fieldwidth-3" size="35"
						onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_recurring_amount_label']) ?>')" />

					<br />
					<br />
					<input type="checkbox" id="gfeway_recurring_date_setting" onchange="GFEwayRecurring.ToggleRecurringDateSetting(this)" />
					<label for="gfeway_recurring_date_setting" class="inline">
						Show Start/End Dates
						<?php gform_tooltip("gfeway_recurring_date_setting") ?>
						<?php gform_tooltip("gfeway_recurring_date_setting_html") ?>
					</label>
					<br />
					<br />

					<div id="gfeway_recurring_date_fields">

					<label for="gfeway_start_date_label">
						Start Date Label
						<?php gform_tooltip("gfeway_start_date_label") ?>
						<?php gform_tooltip("gfeway_start_date_label_html") ?>
					</label>
					<input type="text" id="gfeway_start_date_label" class="fieldwidth-3" size="35"
						onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_start_date_label']) ?>')" />

					<label for="gfeway_end_date_label">
						End Date Label
						<?php gform_tooltip("gfeway_end_date_label") ?>
						<?php gform_tooltip("gfeway_end_date_label_html") ?>
					</label>
					<input type="text" id="gfeway_end_date_label" class="fieldwidth-3" size="35"
						onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_end_date_label']) ?>')" />

					</div>

					<label for="gfeway_interval_type_label">
						Interval Type Label
						<?php gform_tooltip("gfeway_interval_type_label") ?>
						<?php gform_tooltip("gfeway_interval_type_label_html") ?>
					</label>
					<input type="text" id="gfeway_interval_type_label" class="fieldwidth-3" size="35"
						onkeyup="GFEwayRecurring.SetFieldLabel(this, '<?php echo esc_attr(self::$defaults['gfeway_interval_type_label']) ?>')" />

				</li>

			<?php
		}
	}

	/**
	* add custom tooltips for fields on form editor
	* @param array $tooltips
	* @return array
	*/
	public function gformTooltips($tooltips) {
		$tooltips['gfeway_initial_setting'] = "<h6>Show Initial Amount</h6>Select this option to show Initial Amount and Initial Date fields.";
		$tooltips['gfeway_initial_amount_label'] = "<h6>Initial Amount</h6>The label shown for the Initial Amount field.";
		$tooltips['gfeway_initial_date_label'] = "<h6>Initial Date</h6>The label shown for the Initial Date field.";
		$tooltips['gfeway_recurring_amount_label'] = "<h6>Recurring Amount</h6>The label shown for the Recurring Amount field.";
		$tooltips['gfeway_recurring_date_setting'] = "<h6>Show Start/End Dates</h6>Select this option to show Start Date and End Date fields.";
		$tooltips['gfeway_start_date_label'] = "<h6>Start Date</h6>The label shown for the Start Date field.";
		$tooltips['gfeway_end_date_label'] = "<h6>End Date</h6>The label shown for the End Date field.";
		$tooltips['gfeway_interval_type_label'] = "<h6>Interval Type</h6>The label shown for the Interval Type field.";

		return $tooltips;
	}

	/**
	* grab values and concatenate into a string before submission is accepted
	* @param array $form
	*/
	public function gformPreSubmit($form) {
		foreach ($form['fields'] as $field) {
			if ($field['type'] == 'gfewayrecurring') {
				$recurring = self::getPost($field['id']);
				$_POST["input_{$field['id']}"] = '$' . number_format($recurring['amountRecur'], 2)
					. " {$recurring['intervalTypeDesc']} from {$recurring['dateStart']->format('d M Y')}";
			}
		}
	}

	/**
	* validate inputs
	* @param array $validation_result an array with elements is_valid (boolean) and form (array of form elements)
	* @param string $value
	* @param array $form
	* @param array $field
	* @return array
	*/
	public function gformFieldValidation($validation_result, $value, $form, $field) {
		if ($field['type'] == 'gfewayrecurring') {
			if (!RGFormsModel::is_field_hidden($form, $field, RGForms::post('gform_field_values'))) {
				// get the real values
				$value = self::getPost($field['id']);

				if (!is_array($value)) {
					$validation_result['is_valid'] = false;
					$validation_result['message'] = __("This field is required.", "gravityforms");
				}

				else {
					$messages = array();

					if ($value['amountInit'] === false || $value['amountInit'] < 0) {
						$messages[] = "Please enter a valid initial amount.";
					}

					if (empty($value['dateInit'])) {
						$messages[] = "Please enter a valid initial date in the format dd/mm/yyyy.";
					}

					if (empty($value['amountRecur']) || $value['amountRecur'] < 0) {
						$messages[] = "Please enter a valid recurring amount.";
					}

					if (empty($value['dateStart'])) {
						$messages[] = "Please enter a valid start date in the format dd/mm/yyyy.";
					}

					if (empty($value['dateEnd'])) {
						$messages[] = "Please enter a valid end date in the format dd/mm/yyyy.";
					}

					if ($value['intervalType'] === -1) {
						$messages[] = "Please select a valid interval type.";
					}

//~ echo "<pre>", print_r($messages,1), "</pre>";
//~ echo "<pre>", print_r($value,1), "</pre>"; exit;

					if (count($messages) > 0) {
						$validation_result['is_valid'] = false;
						$validation_result['message'] = implode("<br />\n", $messages);
					}
				}
			}
		}

		return $validation_result;
	}

	/**
	* filter hook for modifying a field's input tag (e.g. on custom fields)
	* @param string $input the input tag before modification
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	public function gformFieldInput($input, $field, $value, $lead_id, $form_id) {
		if ($field['type'] == 'gfewayrecurring') {

			// pick up the real value
			$value = rgpost('gfeway_' . $field['id']);

			$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled' " : "";
			$css = isset($field['cssClass']) ? esc_attr($field['cssClass']) : '';

			$today = date_create('now', timezone_open('Australia/Sydney'));
			$initial_amount = empty($value[1]) ? '0.00' : $value[1];
			$initial_date = empty($value[2]) ? $today->format('d-m-Y') : $value[2];
			$recurring_amount = empty($value[3]) ? '0.00' : $value[3];
			$start_date = empty($value[4]) ? $today->format('d-m-Y') : $value[4];
			$end_date = empty($value[5]) ? date_create('2099-12-31')->format('d-m-Y') : $value[5];
			$interval_type = empty($value[6]) ? 'monthly' : $value[6];

			$input = "<div class='ginput_complex ginput_container gfeway_recurring_complex $css' id='input_{$field['id']}'>";

			// initial amount
			$sub_field = array (
				'type' => 'donation',
				'id' => $field['id'],
				'sub_id' => '1',
				'label' => empty($field['gfeway_initial_amount_label']) ? self::$defaults['gfeway_initial_amount_label'] : $field['gfeway_initial_amount_label'],
				'isRequired' => false,
				'size' => 'medium',
				'label_class' => 'gfeway_initial_amount_label',
				'hidden' => !$field['gfeway_initial_setting'],
			);
			$input .= $this->fieldDonation($sub_field, $initial_amount, $lead_id, $form_id);

			// initial date
			$sub_field = array (
				'type' => 'date',
				'id' => $field['id'],
				'sub_id' => '2',
				'label' => empty($field['gfeway_initial_date_label']) ? self::$defaults['gfeway_initial_date_label'] : $field['gfeway_initial_date_label'],
				'dateFormat' => 'dmy',
				'dateType' => 'datepicker',
				'dateMin' => '+0',
				'dateMax' => '+2Y',
				'calendarIconType' => 'calendar',
				'isRequired' => false,
				'size' => 'medium',
				'label_class' => 'gfeway_initial_date_label',
				'hidden' => !$field['gfeway_initial_setting'],
			);
			$input .= $this->fieldDate($sub_field, $initial_date, $lead_id, $form_id);

			$input .= '<br />';

			// recurring amount
			$sub_field = array (
				'type' => 'donation',
				'id' => $field['id'],
				'sub_id' => '3',
				'label' => empty($field['gfeway_recurring_amount_label']) ? self::$defaults['gfeway_recurring_amount_label'] : $field['gfeway_recurring_amount_label'],
				'isRequired' => true,
				'size' => 'medium',
				'label_class' => 'gfeway_recurring_amount_label',
			);
			$input .= $this->fieldDonation($sub_field, $recurring_amount, $lead_id, $form_id);

			// start date
			$sub_field = array (
				'type' => 'date',
				'id' => $field['id'],
				'sub_id' => '4',
				'label' => empty($field['gfeway_start_date_label']) ? self::$defaults['gfeway_start_date_label'] : $field['gfeway_start_date_label'],
				'dateFormat' => 'dmy',
				'dateType' => 'datepicker',
				'dateMin' => '+0',
				'dateMax' => '+2Y',
				'calendarIconType' => 'calendar',
				'isRequired' => true,
				'size' => 'medium',
				'label_class' => 'gfeway_start_date_label',
				'hidden' => !$field['gfeway_recurring_date_setting'],
			);
			$input .= $this->fieldDate($sub_field, $start_date, $lead_id, $form_id);

			// end date
			$sub_field = array (
				'type' => 'date',
				'id' => $field['id'],
				'sub_id' => '5',
				'label' => empty($field['gfeway_end_date_label']) ? self::$defaults['gfeway_end_date_label'] : $field['gfeway_end_date_label'],
				'dateFormat' => 'dmy',
				'dateType' => 'datepicker',
				'dateMin' => '+0',
				'dateMax' => '2099-12-31',
				'calendarIconType' => 'calendar',
				'isRequired' => true,
				'size' => 'medium',
				'label_class' => 'gfeway_end_date_label',
				'hidden' => !$field['gfeway_recurring_date_setting'],
			);
			$input .= $this->fieldDate($sub_field, $end_date, $lead_id, $form_id);

			$input .= '<br />';

			// recurrance interval type drop-down
			$sub_field = array (
				'type' => 'number',
				'id' => $field['id'],
				'sub_id' => '6',
				'label' => empty($field['gfeway_interval_type_label']) ? self::$defaults['gfeway_interval_type_label'] : $field['gfeway_interval_type_label'],
				'isRequired' => true,
				'size' => 'medium',
				'label_class' => 'gfeway_interval_type_label',
			);
			$input .= $this->fieldIntervalType($sub_field, $interval_type, $lead_id, $form_id);

			// concatenated value added to database
			$sub_field = array (
				'type' => 'hidden',
				'id' => $field['id'],
				'isRequired' => true,
			);
			$input .= $this->fieldConcatenated($sub_field, $interval_type, $lead_id, $form_id);

			$input .= "</div>";
		}

		return $input;
	}

	/**
	* get HTML for input and label for date field (as date picker)
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	private function fieldDate($field, $value="", $lead_id=0, $form_id=0) {
		$id = $field["id"];
		$sub_id = $field["sub_id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "gfeway_{$id}_{$sub_id}" : "gfeway_{$form_id}_{$id}_{$sub_id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$format = empty($field["dateFormat"]) ? "dmy" : esc_attr($field["dateFormat"]);
		$size = rgar($field, "size");
		$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
		$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
		$class = $size . $class_suffix;

		$value = GFCommon::date_display($value, $format);
		$icon_class = $field["calendarIconType"] == "none" ? "datepicker_no_icon" : "datepicker_with_icon";
		$icon_url = empty($field["calendarIconUrl"]) ? GFCommon::get_base_url() . "/images/calendar.png" : $field["calendarIconUrl"];
		$tabindex = GFCommon::get_tabindex();

		$spanClass = '';
		if (!empty($field['hidden'])) {
			$spanClass = 'gf_hidden';
		}

		$dataMin = '';
		if (!empty($field['dateMin'])) {
			$dataMin = "data-gfeway-minDate='" . esc_attr($field['dateMin']) . "'";
		}

		$dataMax = '';
		if (!empty($field['dateMax'])) {
			$dataMax = "data-gfeway-maxDate='" . esc_attr($field['dateMax']) . "'";
		}

		$value = esc_attr($value);
		$class = esc_attr($class);

		$label = htmlspecialchars($field['label']);

		$input  = "<span class='gfeway_recurring_left gfeway_recurring_date $spanClass'>";
		$input .= "<input name='gfeway_{$id}[{$sub_id}]' id='$field_id' type='text' value='$value' $dataMin $dataMax class='datepicker $class $format $icon_class' $tabindex $disabled_text />";
		$input .= "<input type='hidden' id='gforms_calendar_icon_$field_id' class='gform_hidden' value='$icon_url'/>";
		$input .= "<label class='{$field['label_class']}' for='$field_id' id='{$field_id}_label'>$label</label>";
		$input .= "</span>";

		return $input;
	}

	/**
	* get HTML for input and label for donation (amount) field
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	private function fieldDonation($field, $value="", $lead_id=0, $form_id=0) {
		$id = $field["id"];
		$sub_id = $field["sub_id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "gfeway_{$id}_{$sub_id}" : "gfeway_{$form_id}_{$id}_{$sub_id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$size = rgar($field, "size");
		$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
		$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
		$class = $size . $class_suffix;

		$tabindex = GFCommon::get_tabindex();
		//~ $logic_event = GFCommon::get_logic_event($field, "keyup");

		$spanClass = '';
		if (!empty($field['hidden'])) {
			$spanClass = 'gf_hidden';
		}

		$value = esc_attr($value);
		$class = esc_attr($class);

		$label = htmlspecialchars($field['label']);

		$input  = "<span class='gfeway_recurring_left $spanClass'>";
		$input .= "<input name='gfeway_{$id}[{$sub_id}]' id='$field_id' type='text' value='$value' class='ginput_amount $class' $tabindex $logic_event $disabled_text />";
		$input .= "<label class='{$field['label_class']}' for='$field_id' id='{$field_id}_label'>$label</label>";
		$input .= "</span>";

		return $input;
	}

	/**
	* get HTML for input and label for Interval Type field
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	private function fieldIntervalType($field, $value="", $lead_id=0, $form_id=0) {
		$id = $field["id"];
		$sub_id = $field["sub_id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "gfeway_{$id}_{$sub_id}" : "gfeway_{$form_id}_{$id}_{$sub_id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$size = rgar($field, "size");
		$disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";
		$class_suffix = RG_CURRENT_VIEW == "entry" ? "_admin" : "";
		$class = $size . $class_suffix;

		$tabindex = GFCommon::get_tabindex();

		$spanClass = '';
		if (!empty($field['hidden'])) {
			$spanClass = 'gf_hidden';
		}

		$class = esc_attr($class);

		$label = htmlspecialchars($field['label']);

		$types = array ('weekly', 'fortnightly', 'monthly', 'yearly');

		$opts = '';
		foreach ($types as $type) {
			$opts .= "<option value='$type'";
			if ($type == $value)
				$opts .= " selected='selected'";
			$opts .= ">$type</option>";
		}

		$input  = "<span class='gfeway_recurring_left $spanClass'>";
		$input .= "<select size='1' name='gfeway_{$id}[{$sub_id}]' id='$field_id' $tabindex class='gfield_select $class' $disabled_text>$opts</select>";
		$input .= "<label class='{$field['label_class']}' for='$field_id' id='{$field_id}_label'>$label</label>";
		$input .= "</span>";

		return $input;
	}

	/**
	* get HTML for hidden input with concatenated value for complex field
	* @param array $field
	* @param string $value
	* @param integer $lead_id
	* @param integer $form_id
	* @return string
	*/
	private function fieldConcatenated($field, $value="", $lead_id=0, $form_id=0) {
		$id = $field["id"];
		$field_id = IS_ADMIN || $form_id == 0 ? "input_{$id}" : "input_{$form_id}_{$id}";
		$form_id = IS_ADMIN && empty($form_id) ? rgget("id") : $form_id;

		$input = "<input type='hidden' name='input_{$id}' id='$field_id' />";

		return $input;
	}

	/**
	* safe checkdate function that verifies each component as numeric and not empty, before calling PHP's function
	* @param string $month
	* @param string $day
	* @param string $year
	* @return boolean
	*/
	private static function checkdate($month, $day, $year) {
		if (empty($month) || !is_numeric($month) || empty($day) || !is_numeric($day) || empty($year) || !is_numeric($year) || strlen($year) != 4)
			return false;

		return checkdate($month, $day, $year);
	}

	/**
	* get input values for recurring payments field
	* @param integer $field_id
	* @return array
	*/
	public static function getPost($field_id) {
		$recurring = rgpost('gfeway_' . $field_id);

//~ echo "<pre>'gfeway_$field_id: ", print_r($recurring,1), "</pre>\n"; exit;

		if (is_array($recurring)) {
			$intervalSize = 1;

			switch ($recurring[6]) {
				case 'weekly':
					$intervalType = GFEwayRecurringPayment::WEEKS;
					break;

				case 'fortnightly':
					$intervalType = GFEwayRecurringPayment::WEEKS;
					$intervalSize = 2;
					break;

				case 'monthly':
					$intervalType = GFEwayRecurringPayment::MONTHS;
					break;

				case 'yearly':
					$intervalType = GFEwayRecurringPayment::YEARS;
					break;

				default:
					// invalid or not selected
					$intervalType = -1;
					break;
			}

			$recurring = array (
				'amountInit' => GFCommon::to_number($recurring[1]),
				'dateInit' => self::parseDate($recurring[2]),
				'amountRecur' => GFCommon::to_number($recurring[3]),
				'dateStart' => self::parseDate($recurring[4]),
				'dateEnd' => self::parseDate($recurring[5]),
				'intervalSize' => $intervalSize,
				'intervalType' => $intervalType,
				'intervalTypeDesc' => $recurring[6],
			);
		}
		else {
			$recurring = false;
		}

		return $recurring;
	}

	/**
	* no date_create_from_format before PHP 5.3, so roll-your-own
	* @param string $value date value in dd/mm/yyyy format
	* @return DateTime
	*/
	private static function parseDate($value) {
		$tm = strptime($value, '%d/%m/%Y');

		if ($tm !== FALSE) {
			$date = date_create();
			$date->setDate($tm['tm_year'] + 1900, $tm['tm_mon'] + 1, $tm['tm_mday']);
			return $date;
		}

		return FALSE;
	}
}
