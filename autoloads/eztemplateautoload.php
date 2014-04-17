<?php
$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/openpaoperator.php',
                                    'class' => 'OpenPAOperator',
                                    'operator_names' => array( 'openpaini', 'get_main_style', 'has_main_style', 'is_area_tematica', 'get_area_tematica_style', 'is_dipendente', 'openpa_shorten', 'has_abstract', 'abstract', 'rss_list', 'materia_make_tree' ) );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/openpamenuoperator.php',
                                    'class' => 'OpenPAMenuOperator',
                                    'operator_names' => array( 'top_menu_cached', 'left_menu_cached' ) );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/slugizeoperator.php',
                                    'class' => 'SlugizeOperator',
                                    'operator_names' => array( 'slugize' ) );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/cookieoperator.php',
                                    'class' => 'CookieOperator',
                                    'operator_names' => array( 'cookieset', 'cookieget', 'check_and_set_cookies' ) );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/checkbrowseroperator.php',
                                    'class' => 'CheckbrowserOperator',
                                    'operator_names' => array( 'checkbrowser', 'is_deprecated_browser' ) );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/arraysortoperator.php',
                                    'class' => 'ArraySortOperator',
                                    'operator_names' => array( 'sort', 'rsort', 'asort', 'arsort', 'ksort', 'krsort', 'natsort', 'natcasesort' ) );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/findgloballayout.php',
                                    'class' => 'FindGlobalLayoutOperator',
                                    'operator_names' => array( 'find_global_layout' ) );

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openpa/autoloads/printtools.php',
                                    'class' => 'PrintToolsOperator',
                                    'operator_names' => array( 'query_string' ) );
