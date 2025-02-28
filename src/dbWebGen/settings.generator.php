<?php
/*
	this is one ugly script that tries to extract a comfortable starting point for settings.php based on any given postgres database.
	it can extract tables, columns, primary keys, foreign keys, check constraints (range of values) and enum types.
	note: the db user you use with this script needs to have read permission on the target schema and on the information_schema
*/

	$tables_setup_json = isset($_GET['special']) && $_GET['special'] === 'tables_setup';
	if($tables_setup_json)
		header('Content-Type: application/json; charset=utf8');
	$json_ret = array();

	foreach(array('host' => 'localhost', 'port' => 5432, 'name' => '', 'user' => 'postgres', 'pass' => '', 'name' => '', 'schema' => 'public') as $k => $v)
		${'db_' . $k} = isset($_POST[$k]) ? $_POST[$k] : $v;

	$form = <<<FORM
		<h1>dbWebGen Settings Generator</h1>
		<p>This tool generates a working stub of settings.php to use for your database with dbWebGen.</p>
		<p>Enter the details of your PostgreSQL database:</p>
		<form method="post">
			<table>
				<tr>
					<th>Host</th>
					<td><input type="text" name="host" value="$db_host" /></td>
				</tr>
				<tr>
					<th>Port</th>
					<td><input type="text" name="port" value="$db_port" /></td>
				</tr>
				<tr>
					<th>Username</th>
					<td><input type="text" name="user" value="$db_user" /></td>
				</tr>
				<tr>
					<th>Password</th>
					<td><input type="password" name="pass" value="$db_pass" /></td>
				</tr>
				<tr>
					<th>Database</th>
					<td><input type="text" name="name" value="$db_name" /></td>
				</tr>
				<tr>
					<th>Schema</th>
					<td><input type="text" name="schema" value="$db_schema" /></td>
				</tr>
			</table>
			<p><input type="submit" value="Generate Settings" /></p>
		</form>
FORM;

	if(count($_POST) == 0) {
		if($tables_setup_json)
			echo json_encode(array('error' => 'Cannot connect to database 1.'));
		else
			print $form;
		exit;
	}

	try {
		$db = new PDO("pgsql:dbname={$db_name};host={$db_host};port={$db_port};options='--client_encoding=UTF8'", $db_user, $db_pass);
	}
	catch(PDOException $e) {
		if($tables_setup_json)
			echo json_encode(array('error' => 'Cannot connect to database 2.' . $db_user));
		else {
			echo "<h2>ERROR: Cannot connect to database</h2>";
			echo $form;
		}
		exit;
	}

	function db_exec($sql, $params = null) {
		global $db;
		$stmt = $db->prepare($sql);
		if($stmt === false || $stmt->execute($params) === false)
			return false;
		return $stmt;
	}

	if(!$tables_setup_json)
		header('Content-Type: text/plain; charset=utf8');

	include 'inc/constants.php';
	include 'settings.template.php';

	// set schema
	db_exec('set search path to ' . $db_schema);

	// fetch all tables in schema
	$tables_query = <<<SQL
		select table_name from information_schema.tables
		where table_schema = ?
		and table_type = 'BASE TABLE'
		AND table_schema NOT IN ('pg_catalog', 'information_schema')
		AND table_name not in ('spatial_ref_sys')
		order by table_name
SQL;
	$res = db_exec($tables_query, array($db_schema));

	$tables = array();
	while($table_name = $res->fetchColumn())
		$tables[] = $table_name;

	// target var
	$TABLES = array();

	// store the multiple cardinality fields and append them after all tables are through. we don't want those fields to show up on top of the form.
	$cardinal_mult = array();

	// loop through all tables and generate table info stub
	foreach($tables as $table_name) {
		// general table info
		$TABLES[$table_name] = array(
			'display_name' => ucwords(strtolower(str_replace('_', ' ', $table_name))),
			'description' => '',
			'item_name' => ucwords(strtolower(str_replace('_', ' ', $table_name))),
			'actions' => array(MODE_EDIT, MODE_NEW, MODE_VIEW, MODE_LIST, MODE_DELETE, MODE_LINK),
			'fields' => array()
		);

		$cardinal_mult[$table_name]	= array();
	}

	// loop again and fill the stubs
	foreach($tables as $table_name) {
		// add all fields
		$columns_query = <<<SQL
			SELECT *
			FROM information_schema.columns
			WHERE table_name = ?
			AND table_schema = ?
			ORDER BY ordinal_position
SQL;
		$res = db_exec($columns_query, array($table_name, $db_schema));

		$column_defaults = array();

		while($col = $res->fetch(PDO::FETCH_ASSOC)) {
			// used later for primary key auto increment
			$column_defaults[$col['column_name']] = $col['column_default'];

			// put default text line fields
			$field = array(
				'label' => ucwords(strtolower(str_replace('_', ' ', $col['column_name']))),
				'required' => $col['is_nullable'] == 'YES' ? false : true,
				'editable' => $col['is_updatable'] == 'YES' ? true : false,
				'type' => T_TEXT_LINE // default
			);

			// if nextval from a sequence is the default value, make it not editable
			if($field['editable']
			  && preg_match('/^nextval\\(\'(.+)\'::regclass\\)$/', $col['column_default'], $matches))
			{
				$field['editable'] = false;
			}

			// check if there is a range check constraint on this field, then the type will be T_ENUM:
			$check_cons_query = <<<SQL
			SELECT consrc
				FROM information_schema.table_constraints tc, pg_constraint chk, information_schema.constraint_column_usage ccu
				WHERE tc.constraint_type = 'CHECK'
				and chk.contype = 'c'
				AND tc.constraint_name = chk.conname
				AND ccu.table_name = tc.table_name
				AND ccu.table_schema = tc.table_schema
				AND ccu.constraint_name = chk.conname
				AND tc.table_name = ?
				AND tc.table_schema = ?
				AND ccu.column_name = ?
SQL;
			$check_query = db_exec($check_cons_query, array($table_name, $db_schema, $col['column_name']));
			$num_checks = 0;
			$consrc = '';
			while($check_cons = $check_query->fetch(PDO::FETCH_NUM)) {
				$num_checks ++;
				$consrc = $check_cons[0];
			}

			if($num_checks == 1) { // only if 1 single check constraint on this column
				$enum_vals = array();
				// see whether we have a range check
				if(1 == preg_match('/=\\sANY\\s\\(+ARRAY\\[(?P<val>.+)\\]\\)+/', $consrc, $extract))
				{
					// here we have something like:
					//    1::numeric, 1.3, 1.7, 2::numeric
					//	or
					//    (4)::integer, (65)::integer
					//  or
					//    'blah'::character varying, 'nada'::character varying
					$vals = explode(',', $extract['val']);

					foreach($vals as $val) {
						$val = trim($val);
						$pos = strrpos($val, '::');
						if($pos !== false)
							$val = substr($val, 0, $pos);
						if(strlen($val) >= 2 && $val[0] == '(' && substr($val, -1) == ')')
							$val = substr($val, 1, -1);
						if(strlen($val) >= 2 && $val[0] == "'" && substr($val, -1) == "'")
							$val = substr($val, 1, -1);

						$enum_vals[(string) $val] = $val;
					}

					$field['type'] = T_ENUM;
					$field['values'] = $enum_vals;
				}
				/*if(1 == preg_match('/=\\sANY\\s\\(+ARRAY\\[(?P<val>.+)\\]\\)+/', $consrc, $extract)
				  && preg_match_all('/(?P<val>[^(\')]+)(\'|\\))::[^,\\]]+/', $extract['val'], $matches) > 0)
				{
					foreach($matches['val'] as $enum_val)
						$enum_vals[$enum_val] = $enum_val;

					$field['type'] = T_ENUM;
					$field['values'] = $enum_vals;
				}*/
			}

			if($field['type'] != T_ENUM) { // only if we have no check range constraint here
				if($col['character_maximum_length'] !== null)
					$field['len'] = $col['character_maximum_length'];

				// determine field type
				// select * from information_schema.columns where table_schema = 'public'
				switch($col['data_type']) {
					case 'boolean':
						$field['type'] = T_ENUM;
						$field['values'] = array(1 => 'Yes', 0 => 'No');
						if($col['column_default'] !== null)
							$field['default'] = $col['column_default'] === true ? 1 : 0;
						$field['width_columns'] = 2;
						break;

					case 'integer': case 'smallint': case 'bigint':
						$field['type'] = T_NUMBER;
						break;

					case 'numeric':
						if($col['numeric_scale'] === null && $col['numeric_precision'] === null) {
							// declared as NUMERIC without arguments -> can be any dec number
							$field['type'] = T_NUMBER;
							$field['step'] = 'any';
						}
						else if($col['numeric_precision'] !== null) {
							$field['type'] = T_NUMBER;
							if($col['numeric_scale'] > 0)
								$field['step'] = number_format(1. / pow(10, $col['numeric_scale']), $col['numeric_scale']);
							else
								$field['step'] = 1;
						}
						break;

					case 'bit':
						$field['type'] = T_ENUM;
						$field['values'] = array('0' => '0', '1' => '1');
						$field['width_columns'] = 2;
						break;

					case 'bit varying': case 'character varying': case 'character': case 'text':
						if($col['character_maximum_length'] !== null) {
							$field['type'] = T_TEXT_LINE;
							$field['len'] = $col['character_maximum_length'];

							if($field['len'] > 50)
								$field['resizeable'] = true;
						}
						else
							$field['type'] = T_TEXT_AREA;
						break;

					case 'USER-DEFINED':
						// check whether we have Postgis geometry
						if(strtolower($col['udt_name']) == 'geometry') {
							// integer Find_SRID(varchar a_schema_name, varchar a_table_name, varchar a_geomfield_name);
							$q_type = db_exec('SELECT type FROM geometry_columns WHERE f_table_schema = ? AND f_table_name = ? and f_geometry_column = ?',
								array($db_schema, $table_name, $col['column_name']));
							$geom_type = strtolower($q_type->fetchColumn());
							$q_srid = db_exec('SELECT find_srid(?, ?, ?)',
								array($db_schema, $table_name, $col['column_name']));

							$field['type'] = T_POSTGIS_GEOM;
							$field['SRID'] = strval($q_srid->fetchColumn());
							$field['map_picker'] = array(
								'draw_options' => array(
									'polyline' => in_array($geom_type, array('polyline', 'linestring', 'geometry')),
									'polygon' => in_array($geom_type, array('polygon', 'geometry')),
									'rectangle' => in_array($geom_type, array('polygon', 'geometry')),
									'circle' => false,
									'point' => in_array($geom_type, array('point', 'geometry'))
								)
							);
						}
						else {
							// if type is enum, make T_ENUM
							$enum_query = db_exec(
								'SELECT e.enumlabel FROM pg_enum e, pg_type t WHERE e.enumtypid = t.oid AND t.typname = ? ORDER BY 1',
								array($col['udt_name'])
							);
							$enum_vals = array();
							while($enum_val = $enum_query->fetch(PDO::FETCH_NUM))
								$enum_vals[$enum_val[0]] = $enum_val[0];

							if(count($enum_vals) > 0) {
								$field['type'] = T_ENUM;
								$field['values'] = $enum_vals;
							}
						}
						break;

					default:
						break;
				}
			}

			$TABLES[$table_name]['fields'][$col['column_name']] = $field;
		}

		// go through PRIMARY KEY constraints
		$primary_key = array(
			'columns' => array()
		);

		$constraints_query = <<<SQL
			SELECT tc.constraint_name,
				tc.constraint_type,
				kcu.column_name
				FROM information_schema.table_constraints tc
				LEFT outer JOIN information_schema.key_column_usage kcu
				ON tc.constraint_catalog = kcu.constraint_catalog
				AND tc.constraint_schema = kcu.constraint_schema
				AND tc.constraint_name = kcu.constraint_name
				WHERE tc.constraint_type = 'PRIMARY KEY'
				AND tc.table_schema = ?
				AND tc.table_name = ?
SQL;

		$res = db_exec($constraints_query, array($db_schema, $table_name));
		while($cons = $res->fetch(PDO::FETCH_ASSOC)) {
			$primary_key['columns'][] = $cons['column_name'];
		}

		// go through FOREIGN KEY constraints

		$foreign_keys_info = array();

		$constraints_query = <<<SQL
			SELECT tc.constraint_name,
				tc.constraint_type,
				kcu.column_name,
				ccu.table_name references_table,
				ccu.column_name references_field,
				(select column_name from information_schema.columns where table_name=ccu.table_name and table_schema=tc.table_schema and data_type in ('character varying', 'text') ORDER BY ordinal_position limit 1) display_field
				FROM information_schema.table_constraints tc
				LEFT outer JOIN information_schema.key_column_usage kcu
				ON tc.constraint_catalog = kcu.constraint_catalog
				AND tc.constraint_schema = kcu.constraint_schema
				AND tc.constraint_name = kcu.constraint_name
				LEFT outer JOIN information_schema.constraint_column_usage ccu
				ON tc.constraint_catalog = ccu.constraint_catalog
				AND tc.constraint_schema = ccu.constraint_schema
				AND tc.constraint_name = ccu.constraint_name
				WHERE tc.constraint_type = 'FOREIGN KEY'
				AND tc.table_schema = ?
				AND tc.table_name = ?
SQL;

		$res = db_exec($constraints_query, array($db_schema, $table_name));
		while($cons = $res->fetch(PDO::FETCH_ASSOC)) {
			$field = $TABLES[$table_name]['fields'][$cons['column_name']];

			$field['type'] = T_LOOKUP;
			$field['lookup'] = array(
				'cardinality' => CARDINALITY_SINGLE,
				'table'  => $cons['references_table'],
				'field'  => $cons['references_field'],
				'display' => ($cons['display_field'] !== null ? $cons['display_field'] : $cons['references_field']),
				'label_display_expr_only' => true
			);

			// remember the foreign keys in a hash for later
			$foreign_keys_info[$cons['column_name']] = $field;

			// overwrite default field info
			$TABLES[$table_name]['fields'][$cons['column_name']] = $field;
		}

		$primary_key['auto'] = false;

		// check whether the primary key is determined by a sequence:
		if(count($primary_key['columns']) == 1) {
			// and there is a default val for the columns
			if($column_defaults[$primary_key['columns'][0]] !== null) {
				// check whether it is the nextval of a sequence
				if(preg_match('/^nextval\\(\'(.+)\'::regclass\\)$/', $column_defaults[$primary_key['columns'][0]], $matches)) {
					$primary_key['auto'] = true;
					$primary_key['sequence_name'] = $matches[1];
					$TABLES[$table_name]['fields'][$primary_key['columns'][0]]['editable'] = false;
				}
			}
		}

		// set primary key
		$TABLES[$table_name]['primary_key'] = $primary_key;

		// check whether this is a N:M table (for CARDINALITY_MULTIPLE)
		// this is the case if this table has:
		// * exactly two primary key fields
		// * both are foreign keys to two different tables
		// If both conditions hold we add this table as a linkage table in CARDINALTY_MULTIPLE field in both referenced tables
		if(count($primary_key['columns']) == 2) {
			$field1 = $field2 = null;
			if(isset($foreign_keys_info[$primary_key['columns'][0]])
				&& isset($foreign_keys_info[$primary_key['columns'][1]]))
			{
				$field0 = $foreign_keys_info[$primary_key['columns'][0]];
				$field1 = $foreign_keys_info[$primary_key['columns'][1]];

				if($field0['lookup']['table'] != $field1['lookup']['table']) {
					// here we go, add cardinality multiple lookup to both involved tables

					//$TABLES[$field0['lookup']['table']]['fields'][$table_name . '_fk'] =
					$cardinal_mult[$field0['lookup']['table']][$table_name . '_fk'] =
					array(
						'label' => $table_name . ' list',
						'required' => false,
						'editable' => true,
						'type' => T_LOOKUP,
						'lookup' => array(
							'cardinality' => CARDINALITY_MULTIPLE,
							'table'  => $field1['lookup']['table'],
							'field'  => $field1['lookup']['field'],
							'display' => $field1['lookup']['display'],
							'label_display_expr_only' => true
						),
						'linkage' => array(
							'table' => $table_name,
							'fk_self' => $primary_key['columns'][0],
							'fk_other' => $primary_key['columns'][1]
						)
					);

					//$TABLES[$field1['lookup']['table']]['fields'][$table_name . '_fk'] =
					$cardinal_mult[$field1['lookup']['table']][$table_name . '_fk'] =
					array(
						'label' => $table_name . ' list',
						'required' => false,
						'editable' => true,
						'type' => T_LOOKUP,
						'lookup' => array(
							'cardinality' => CARDINALITY_MULTIPLE,
							'table'  => $field0['lookup']['table'],
							'field'  => $field0['lookup']['field'],
							'display' => $field0['lookup']['display'],
							'label_display_expr_only' => true
						),
						'linkage' => array(
							'table' => $table_name,
							'fk_self' => $primary_key['columns'][1],
							'fk_other' => $primary_key['columns'][0]
						)
					);
				}
			}
		}
	}

	// append n:m lookup fields to end of field list for each table
	foreach($cardinal_mult as $table_name => $fields) {
		$TABLES[$table_name]['fields'] += $fields;
	}

	if($tables_setup_json) {
		echo json_encode(array('tables' => $TABLES));
		exit;
	}

	// ================================================
	// APP
	// ================================================

	if(!isset($_GET['only']) || $_GET['only'] == 'APP') {
		$APP = array(
			'title' => $db_name . ' Database',
			'view_display_null_fields' => false,
			'page_size'	=> 10,
			'max_text_len' => 250,
			'pages_prevnext' => 2,
			'mainmenu_tables_autosort' => true,
			'search_lookup_resolve' => true,
			'search_string_transformation' => 'lower((%s)::text)'
		);
		echo '<?php', PHP_EOL, '$APP = ';
		var_export($APP);
		echo ';', PHP_EOL, PHP_EOL;
	}

	// ================================================
	// DB
	// ================================================
	if(!isset($_GET['only']) || $_GET['only'] == 'DB') {
		$DB = array(
			'type' => DB_POSTGRESQL,
			'host' => $db_host,
			'port' => intval($db_port),
			'user' => $db_user,
			'pass' => $db_pass,
			'db'   => $db_name
		);
		echo '$DB = ';
		var_export($DB);
		echo ';', PHP_EOL, PHP_EOL;
	}

	// ================================================
	// LOGIN
	// ================================================
	if(!isset($_GET['only']) || $_GET['only'] == 'LOGIN') {
		$LOGIN = array();
		echo '$LOGIN = ';
		var_export($LOGIN);
		echo ';', PHP_EOL, PHP_EOL;
	}

	// ================================================
	// TABLES
	// ================================================
	if(!isset($_GET['only']) || $_GET['only'] == 'TABLES') {
		echo '$TABLES = ';
		var_export($TABLES);
		echo ';', PHP_EOL;
	}

	if(!isset($_GET['only']))
		echo PHP_EOL, '?>';
?>
