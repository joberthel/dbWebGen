<?php

$DB = array(
    'type' => 'postgresql',
    'host' => 'database',
    'port' => 5432,
    'user' => 'postgres',
    'pass' => 'z12t4YeLLN6U',
    'db' => 'dbwebgen',
);

$APP = array(
    'title' => 'Date Picker Demo',
    'page_size' => 10,
    'pages_prevnext' => 3,
    'max_text_len' => 200,
    'mainmenu_tables_autosort' => true,
    'view_display_null_fields' => false,
    'search_lookup_resolve' => true,
    'search_string_transformation' => '%s',
    'null_label' => 'NULL',
    'list_mincolwidth_max' => 300,
    'list_mincolwidth_pxperchar' => 6,
    'lookup_allow_edit_default' => false,
);

$LOGIN = array(
    'users_table' => 'users',
    'primary_key' => 'id',
    'username_field' => 'login',
    'password_field' => 'password',
    'name_field' => 'name',
    'password_hash_func' => 'md5',
    'form' => array(
        'username' => 'Benutzername',
        'password' => 'Passwort',
    ),
);

$TABLES = array(
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
        'hide_from_menu' => array(),
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
                'label' => 'Name',
                'type' => 'T_TextLine',
                'required' => true,
                'len' => 50,
            ),
            'duration' => array(
                'label' => 'Dauer',
                'help' => 'Dauer der Tätigkeit.',
                'type' => 'T_TextLine',
                'required' => false,
                'linked_pickers' => array(),
            ),
        ),
    ),
);

