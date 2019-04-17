<?php

$DB = array (
  'type' => 'postgresql',
  'host' => getenv('POSTGRES_HOST'),
  'port' => getenv('POSTGRES_PORT'),
  'user' => getenv('POSTGRES_USER'),
  'pass' => getenv('POSTGRES_PASSWORD'),
  'db' => getenv('POSTGRES_DB'),
);

$APP = array (
  'title' => 'dbWebGen Demo',
  'page_size' => 10,
  'pages_prevnext' => 2,
  'max_text_len' => 250,
  'mainmenu_tables_autosort' => true,
  'view_display_null_fields' => false,
  'search_lookup_resolve' => true,
  'search_string_transformation' => 'lower((%s)::text)',
  'null_label' => '<span class=\'nowrap\' title=\'If you check this box, no value will be stored for this field. This may reflect missing, unknown, unspecified or inapplicable information. Note that no value (missing information) is different to providing an empty value: an empty value is a value.\'>No Value</span>',
  'list_mincolwidth_max' => 300,
  'list_mincolwidth_pxperchar' => 6,
  'lookup_allow_edit_default' => false,
  'plugins' => array(),
  'render_main_page_proc' => '',
  'menu_complete_proc' => '',
  'querypage_stored_queries_table' => 'stored_queries',
  'global_search' => array(
    'include_table' => true,
    'min_search_len' => 3,
    'max_preview_results_per_table' => 10,
    'max_detail_results' => 100,
    'transliterator_rules' => ':: Any-Latin; :: Latin-ASCII;',
    'cache_ttl' => 3600,
  ),
  'preprocess_func' => '',
  'super_users' => array(
    0 => 'test',
  ),
);

$LOGIN = array (
  'users_table' => 'users',
  'primary_key' => 'id',
  'username_field' => 'login',
  'password_field' => 'password',
  'name_field' => 'name',
  'password_hash_func' => 'md5',
  'form' => array(
    'username' => 'Username',
    'password' => 'Password',
  ),
);

$TABLES = array (
    'activities' => array(
        'display_name' => 'Tätigkeiten',
        'item_name' => 'Activity',
        'description' => 'Lorem Ipsum.',
        'actions' => array(
            0 => 'new',
            1 => 'edit',
            2 => 'list',
            3 => 'view',
            4 => 'delete',
            5 => 'link',
            6 => 'merge',
        ),
        'hide_from_menu' => array(
        ),
        'primary_key' => array(
            'auto' => true,
            'columns' => array(
                0 => 'id',
            ),
            'sequence_name' => 'activities_id_seq',
        ),
        'show_in_related' => true,
        'sort' => array(
            'name' => 'asc',
        ),
        'additional_steps' => array(),
        'fields' => array(
            'id' => array(
                'label' => 'ID',
                'type' => 'T_Number',
                'required' => false,
                'editable' => false,
            ),
            'name' => array(
                'label' => 'Full Name',
                'type' => 'T_TextLine',
                'required' => true,
                'len' => 50,
            ),
            'duration' => array(
                'label' => 'Dauer',
                'help' => 'Dauer der Tätigkeit.',
                'type' => 'T_TextLine',
                'required' => false,
                'linked_pickers' => array(
                    // 'format' => '[YYYY-MM-DD,YYYY-MM-DD)',
                    //	'format' => '[YYYY-MM-DD HH:mm:ss,YYYY-MM-DD HH:mm:ss)',
                    'showTodayButton' => false,
                ),
            ),
        ),
    ),
  'users' => array(
    'display_name' => 'Users',
    'item_name' => 'User',
    'description' => 'Users of this application.',
    'actions' => array(
      0 => 'new',
      1 => 'edit',
      2 => 'list',
      3 => 'view',
      4 => 'delete',
      5 => 'link',
      6 => 'merge',
    ),
    'hide_from_menu' => array(
    ),
    'primary_key' => array(
      'auto' => true,
      'columns' => array(
        0 => 'id',
      ),
      'sequence_name' => 'users_id_seq',
    ),
    'show_in_related' => true,
    'sort' => array(
      'name' => 'asc',
    ),
    'additional_steps' => array(),
    'fields' => array(
      'id' => array(
        'label' => 'ID',
        'type' => 'T_Number',
        'required' => false,
        'editable' => false,
      ),
      'name' => array(
        'label' => 'Full Name',
        'type' => 'T_TextLine',
        'required' => true,
        'len' => 50,
      ),
      'login' => array(
        'label' => 'Login ID',
        'type' => 'T_TextLine',
        'required' => true,
        'len' => 10,
      ),
      'password' => array(
        'label' => 'Password',
        'type' => 'T_Password',
        'required' => true,
        'len' => 32,
        'min_len' => 3,
        'placeholder' => 'Mind. 3 Zeichen',
      ),
    ),
  ),
);

