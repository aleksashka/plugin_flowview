<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2008-2017 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/


chdir('../../');

include('./include/auth.php');
include_once($config['base_path'] . '/plugins/flowview/functions.php');

set_default_action();

$sched_actions = array(
	2 => __('Send Now'), 
	1 => __('Delete'), 
	3 => __('Disable'), 
	4 => __('Enable')
);

$sendinterval_arr = array(
	3600    => __('Every Hour'),
	7200    => __('Every %d Hours', 2),
	14400   => __('Every %d Hours', 4),
	21600   => __('Every %d Hours', 6),
	43200   => __('Every %d Hours', 12),
	86400   => __('Every Day'),
	432000  => __('Every Week'),
	864000  => __('Every %d Weeks, 2'),
	1728000 => __('Every Month'),
);

$schedule_edit = array(
	'title' => array(
		'friendly_name' => __('Title'),
		'method' => 'textbox',
		'default' => __('New Schedule'),
		'description' => __('Enter a Report Title for the FlowView Schedule.'),
		'value' => '|arg1:title|',
		'max_length' => 128,
		'size' => 60
	),
	'enabled' => array(
		'friendly_name' => __('Enabled'),
		'method' => 'checkbox',
		'default' => 'on',
		'description' => __('Whether or not this NetFlow Scan will be sent.'),
		'value' => '|arg1:enabled|',
	),
	'savedquery' => array(
		'method' => 'drop_sql',
		'friendly_name' => __('Filter Name'),
		'description' => __('Name of the query to run.'),
		'value' => '|arg1:savedquery|',
		'sql' => 'SELECT id, name FROM plugin_flowview_queries'
	),
	'sendinterval' => array(
		'friendly_name' => __('Send Interval'),
		'description' => __('How often to send this NetFlow Report?'),
		'value' => '|arg1:sendinterval|',
		'method' => 'drop_array',
		'default' => '0',
		'array' => $sendinterval_arr
	),
	'start' => array(
		'method' => 'textbox',
		'friendly_name' => __('Start Time'),
		'description' => __('This is the first date / time to send the NetFlow Scan email.  All future Emails will be calculated off of this time plus the interval given above.'),
		'value' => '|arg1:start|',
		'max_length' => '26',
		'size' => 20,
		'default' => date('Y-m-d G:i:s', time())
	),
	'email' => array(
		'method' => 'textarea',
		'friendly_name' => __('Email Addresses'),
		'description' => __('Email addresses (command delimited) to send this NetFlow Scan to.'),
		'textarea_rows' => 4,
		'textarea_cols' => 60,
		'class' => 'textAreaNotes',
		'value' => '|arg1:email|'
	),
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|'
	),
);

switch (get_request_var('action')) {
	case 'actions':
		actions_schedules();
		break;
	case 'save':
		save_schedules ();
		break;
	case 'edit':
		general_header();
		display_tabs ();
		edit_schedule();
		bottom_footer();
		break;
	default:
		general_header();
		display_tabs ();
		show_schedules ();
		bottom_footer();
		break;
}

function actions_schedules () {
	global $colors, $sched_actions, $config;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_post('drp_action'));
	/* ==================================================== */

	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_nfilter_request_var('drp_action') == '1') {
				for ($i=0; $i<count($selected_items); $i++) {
					db_execute('DELETE FROM plugin_flowview_schedules WHERE id = ' . $selected_items[$i]);
				}
			}elseif (get_nfilter_request_var('drp_action') == '3') {
				for ($i=0; $i<count($selected_items); $i++) {
					db_execute("UPDATE plugin_flowview_schedules SET enabled='' WHERE id = " . $selected_items[$i]);
				}
			}elseif (get_nfilter_request_var('drp_action') == '4') {
				for ($i=0; $i<count($selected_items); $i++) {
					db_execute("UPDATE plugin_flowview_schedules SET enabled='on' WHERE id = " . $selected_items[$i]);
				}
			}elseif (get_nfilter_request_var('drp_action') == '2') {
				for ($i=0; $i<count($selected_items); $i++) {
					plugin_flowview_run_schedule($selected_items[$i]);
				}
			}
		}

		header('Location: flowview_schedules.php?tab=sched&header=false');
		exit;
	}

	/* setup some variables */
	$schedule_list = '';

	/* loop through each of the devices selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$schedule_list .= '<li>' . db_fetch_cell_prepared('SELECT name FROM plugin_flowview_queries AS pfq
				INNER JOIN plugin_flowview_schedules AS pfs 
				ON pfq.id=pfs.savedquery
				WHERE pfs.id = ?', array($matches[1])) . '</li>';
			$schedule_array[] = $matches[1];
		}
	}

	general_header();

	form_start('flowview_schedules.php');

	html_start_box($sched_actions[get_nfilter_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (get_nfilter_request_var('drp_action') == '1') { /* Delete */
		print "<tr>
			<td colspan='2' class='textArea'>
				<p>" . __('Click \'Continue\' to delete the following Schedule(s).') . "</p>
				<p><ul>$schedule_list</ul></p>
			</td>
		</tr>";
	}elseif (get_nfilter_request_var('drp_action') == '2') { /* Send Now */
		print "<tr>
			<td colspan='2' class='textArea'>
				<p>" . __('Click \'Continue\' to send the following Schedule(s) now.') . "</p>
				<p><ul>$schedule_list</ul></p>
			</td>
		</tr>";
	}elseif (get_nfilter_request_var('drp_action') == '3') { /* Disable */
		print "<tr>
			<td colspan='2' class='textArea'>
				<p>" . __('Click \'Continue\' to Disable the following Schedule(s).') . "</p>
				<p><ul>$schedule_list</ul></p>
			</td>
		</tr>";
	}elseif (get_nfilter_request_var('drp_action') == '4') { /* Enable */
		print "<tr>
			<td colspan='2' class='textArea'>
				<p>" . __('Click \'Continue\' to Enable the following Schedule(s).') . "</p>
				<p><ul>$schedule_list</ul></p>
			</td>
		</tr>";
	}

	if (!isset($schedule_array)) {
		print "<tr><td><span class='textError'>" . __('You must select at least one schedule.') . "</span></td></tr>\n";
		$save_html = '';
	}else{
		$save_html = "<input type='submit' value='" . __('Continue') . "'>";
	}

	print "<tr>
		<td colspan='2' align='right' class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($schedule_array) ? serialize($schedule_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_nfilter_request_var('drp_action') . "'>
			<input type='button' onClick='cactiReturnTo()' value='" . __('Cancel') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function save_schedules() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('savedquery');
	get_filter_request_var('sendinterval');
	/* ==================================================== */

	$save['title']        = get_nfilter_request_var('title');
	$save['savedquery']   = get_nfilter_request_var('savedquery');
	$save['sendinterval'] = get_nfilter_request_var('sendinterval');
	$save['start']        = get_nfilter_request_var('start');
	$save['email']        = get_nfilter_request_var('email');

	$t = time();
	$d = strtotime(get_nfilter_request_var('start'));
	$i = $save['sendinterval'];
	if (isset_request_var('id')) {
		$save['id'] = get_request_var('id');

		$q = db_fetch_row('SELECT * FROM plugin_flowview_schedules WHERE id = ' . $save['id']);
		if (!isset($q['lastsent']) || $save['start'] != $q['start'] || $save['sendinterval'] != $q['sendinterval']) {
			while ($d < $t) {
				$d += $i;
			}
			$save['lastsent'] = $d - $i;
		}
	} else {
		$save['id'] = '';
		while ($d < $t) {
			$d += $i;
		}
		$save['lastsent'] = $d - $i;
	}

	if (isset_request_var('enabled'))
		$save['enabled'] = 'on';
	else
		$save['enabled'] = 'off';

	$id = sql_save($save, 'plugin_flowview_schedules', 'id', true);

	if (is_error_message()) {
		header('Location: flowview_schedules.php?tab=sched&header=false&action=edit&id=' . (empty($id) ? get_filter_request_var('id') : $id));
		exit;
	}

	header('Location: flowview_schedules.php?tab=sched&header=false');
	exit;
}

function edit_schedule() {
	global $config, $schedule_edit, $colors;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	$report = array();
	if (!isempty_request_var('id')) {
		$report = db_fetch_row('SELECT pfs.*, pfq.name 
			FROM plugin_flowview_schedules AS pfs 
			LEFT JOIN plugin_flowview_queries AS pfq
			ON (pfs.savedquery=pfq.id) 
			WHERE pfs.id=' . get_request_var('id'), FALSE);

		$header_label = '[edit: ' . $report['name'] . ']';
	}else{
		$header_label = '[new]';
	}

	form_start('flowview_schedules.php', 'chk');

	html_start_box(__('Report: %s', $header_label), '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($schedule_edit, $report)
		)
	);

	html_end_box();

	?>
	<script type='text/javascript'>
	var startOpen = false;

	$(function() {
		$('#start').after("<i id='startDate' class='calendar fa fa-calendar' title='<?php print __('Start Date Selector');?>'></i>");
		$('#startDate').click(function() {
			if (startOpen) {
				startOpen = false;
				$('#start').datetimepicker('hide');
			}else{
				startOpen = true;
				$('#start').datetimepicker('show');
			}
		});

		$('#start').datetimepicker({
			minuteGrid: 10,
			stepMinute: 1,
			showAnim: 'slideDown',
			numberOfMonths: 1,
			timeFormat: 'HH:mm',
			dateFormat: 'yy-mm-dd',
			showButtonPanel: false
		});
	});
	</script>
	<?php

	form_save_button('flowview_schedules.php?tab=sched');
}

function show_schedules () {
	global $sendinterval_arr, $colors, $config, $sched_actions, $item_rows;

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'title',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_fvs');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	}else{
		$rows = get_request_var('rows');
	}

	html_start_box(__('FlowView Schedules'), '100%', '', '3', 'center', 'flowview_schedules.php?action=edit');
	?>
	<tr class='even'>
		<td>
		<form name='form_schedule' action='flowview_schedules.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print htmlspecialchars(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Schedules');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default');?></option>
							<?php
							if (sizeof($item_rows)) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' value='<?php print __('Go');?>' title='<?php print __('Set/Refresh Filters');?>'>
					</td>
					<td>
						<input type='submit' name='clear' value='<?php print __('Clear');?>' title='<?php print __('Clear Filters');?>'>
					</td>
				</tr>
			</table>
		<input type='hidden' name='page' value='1'>
		</form>
		</td>
	</tr>
	<?php
	html_end_box();

	if (get_request_var('filter') != '') {
		$sql_where = "WHERE (name LIKE '%" . get_request_var_request('filter') . "%')";
	}else{
		$sql_where = '';
	}

	$sql = "SELECT pfs.*, pfq.name 
		FROM plugin_flowview_schedules AS pfs
		LEFT JOIN plugin_flowview_queries AS pfq 
		ON (pfs.savedquery=pfq.id) 
		$sql_where
		ORDER BY " . get_request_var_request('sort_column') . ' ' . get_request_var_request('sort_direction') . '
		LIMIT ' . ($rows*(get_request_var('page')-1)) . ", $rows";

	$result = db_fetch_assoc($sql);

	$total_rows = db_fetch_cell("SELECT COUNT(*) 
		FROM plugin_flowview_schedules AS pfs
		LEFT JOIN plugin_flowview_queries AS pfq 
		ON (pfs.savedquery=pfq.id) 
		$sql_where");

	$nav = html_nav_bar('flowview_schedules.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Schedules'), 'page', 'main');

	form_start('flowview_schedules.php', 'chk');

    print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	$display_array = array(
		'title'                 => array(__('Schedule Title'), 'ASC'),
		'name'                  => array(__('Filter Name'), 'ASC'),
		'sendinterval'          => array(__('Interval'), 'ASC'),
		'start'                 => array(__('Start Date'), 'ASC'),
		'lastsent+sendinterval' => array(__('Next Send'), 'ASC'),
		'email'                 => array(__('Email'), 'ASC'),
		'enabled'               => array(__('Enabled'), 'ASC')
	);

	html_header_sort_checkbox($display_array, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), false);

	$i=0;
	if (count($result)) {
		foreach ($result as $row) {
			form_alternate_row('line' . $row['id'], true);
			form_selectable_cell('<a class="linkEditMain" href="' . htmlspecialchars('flowview_schedules.php?tab=sched&action=edit&id=' . $row['id']) . '">' . $row['title'] . '</a>', $row['id']);
			form_selectable_cell($row['name'], $row['id']);
			form_selectable_cell($sendinterval_arr[$row['sendinterval']], $row['id']);
			form_selectable_cell($row['start'], $row['id']);
			form_selectable_cell(date('Y-m-d G:i:s', $row['lastsent']+$row['sendinterval']), $row['id']);
			form_selectable_cell($row['email'], $row['id']);
			form_selectable_cell(($row['enabled'] == 'on' ? "<span class='deviceUp'><b>" . __('Yes') . "</b></span>":"<span class='deviceDown'><b>" . __('No') . "</b></span>"), $row['id']);
			form_checkbox_cell($row['name'], $row['id']);
			form_end_row();
		}
	}

	html_end_box(false);

	if (count($result)) {
		print $nav;
	}

	draw_actions_dropdown($sched_actions);

	form_end();
}

