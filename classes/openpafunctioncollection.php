<?php

class OpenPaFunctionCollection
{

    protected static $topmenu;
    protected static $home;

    public static $remoteHeader = 'OpenPaHeader';
    public static $remoteLogo = 'OpenPaLogo';
    public static $remoteRoles = 'OpenPaRuoli';

    protected static $params = array(
        'SearchOffset' => 0,
        'SearchLimit' => 1000,
        'Facet' => null,
        'SortBy' => null,
        'Filter' => null,
        'SearchContentClassID' => null,
        'SearchSectionID' => null,
        'SearchSubTreeArray' => null,
        'AsObjects' => null,
        'SpellCheck' => null,
        'IgnoreVisibility' => null,
        'Limitation' => null,
        'BoostFunctions' => null,
        'QueryHandler' => 'ezpublish',
        'EnableElevation' => true,
        'ForceElevation' => true,
        'SearchDate' => null,
        'DistributedSearch' => null,
        'FieldsToReturn' => null,
        'SearchResultClustering' => null,
        'ExtendedAttributeFilter' => array()
    );
    
    protected static function search( $params, $query = '' )
    {
        $solrSearch = new eZSolr();
        return $solrSearch->search( $query, $params );
    }  
    
    public static function fetchCalendarioEventi( $calendar, $params )
    {
        try
        {
            $data = new OpenPACalendarData( $calendar );
            $data->setParameters( $params );
            $data->fetch();
            return array( 'result' => $data->data );    
        }
        catch( Exception $e )
        {
            eZDebug::writeError( $e->getMessage(), __METHOD__ );
            return array( 'result' => array() );    
        }
        
    }

    public static function fetchRuoli( $struttura, $dipendente )
    {
        $params = self::$params;
        $params['SearchSubTreeArray'] = array( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ),
                                               eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'MediaRootNode' ) );
        $params['SearchContentClassID'] = array( 'ruolo' );    
        if ( $struttura || $dipendente )
        {
            if ( $struttura )
                $params['Filter'][] = array( 'submeta_struttura_di_riferimento___id_si:' . $struttura );
            elseif( $dipendente )
                $params['Filter'][] = array( 'submeta_utente___id_si:' . $dipendente );
        }
        $search = self::search( $params );        
        return array( 'result' => $search['SearchResult'] );
    }
    
    public static function fetchNomiRuoliDirigenziali()
    {
        $nomi = array( 'Segretario generale', 'Dirigente generale', 'Dirigente di Servizio', 'Responsabile di Servizio' );
        return array( 'result' => $nomi );
    }
    
    public static function fetchDirigenti()
    {
        $result = array();
        $nomi = self::fetchNomiRuoliDirigenziali();        
        if ( count( $nomi['result'] ) > 0 )
        {
            $params = self::$params;
            $params['SearchSubTreeArray'] = array( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ),
                                                   eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'MediaRootNode' ) );
            $params['SearchContentClassID'] = array( 'ruolo' );
            $filterNomi = array( 'or' );
            foreach( $nomi['result'] as $nome )
            {                
                $filterNomi[] = array( 'attr_titolo_s:"' . $nome . '"');
            }
            $params['Filter'][] = $filterNomi;
            $params['AsObjects'] = false;
            $search = self::search( $params );
            $nodes = array();            
            foreach( $search['SearchResult'] as $item )
            {
                if ( isset( $item['fields']['submeta_utente___main_node_id_si'][0] ) )
                {
                    $nodes[] = $item['fields']['submeta_utente___main_node_id_si'][0];
                }
            }            
            $result = eZContentObjectTreeNode::fetch( $nodes );
        }
        return array( 'result' => $result );
    }
    
    public static function fetchAree()
    {
        $params = self::$params;
        $params['SearchSubTreeArray'] = array( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );
        $params['SearchContentClassID'] = array( 'area' );
        $params['SortBy'] = array( 'name' => 'asc' );
        $search = self::search( $params );        
        return array( 'result' => $search['SearchResult'] );
    }
    
    public static function fetchServizi()
    {
        $params = self::$params;
        $params['SearchSubTreeArray'] = array( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );
        $params['SearchContentClassID'] = array( 'servizio' );
        $params['SortBy'] = array( 'name' => 'asc' );
        $search = self::search( $params );        
        return array( 'result' => $search['SearchResult'] );
    }
    
    public static function fetchUffici()
    {
        $params = self::$params;
        $params['SearchSubTreeArray'] = array( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );
        $params['SearchContentClassID'] = array( 'ufficio' );
        $params['SortBy'] = array( 'name' => 'asc' );
        $search = self::search( $params );        
        return array( 'result' => $search['SearchResult'] );
    }    

    public static function fetchDipendenti( $struttura, $subtree )
    {
        $params = self::$params;
        if ( is_array( $subtree ) && !empty( $subtree ) )
        {
            foreach( $subtree as $index => $item )
            {
                if ( empty( $item ) )
                {
                    unset( $subtree[$index] );
                }
            }
            if ( empty( $subtree ) )
            {
                return array( 'result' => array() );
            }
            $params['SearchSubTreeArray'] = $subtree;
        }
        else
        {
            $params['SearchSubTreeArray'] = array( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );   
        }        
        $params['SearchContentClassID'] = array( 'dipendente' );
        $params['SortBy'] = array( 'name' => 'asc' );
        if ( $struttura instanceof eZContentObjectTreeNode )
        {
            if ( $struttura->attribute( 'class_identifier' ) == 'struttura' )
            {
                $params['Filter'][] = array( "submeta_struttura___id_si:" . $struttura->attribute( 'contentobject_id' ) );
                $params['Filter'][] = array( "submeta_altra_struttura___id_si:" . $struttura->attribute( 'contentobject_id' ) );
            }
            else
            {
                $params['Filter'][] = array( "submeta_" . $struttura->attribute( 'class_identifier' ) . "___id_si:" . $struttura->attribute( 'contentobject_id' ) );
            }
        }
        $search = self::search( $params );        
        return array( 'result' => $search['SearchResult'] );
    }

    public static function fetchHeaderImageStyle()
    {
        $result = false;
        $image = self::fetchHeaderImage();        
        if ( $image )
        {
            $result = "background:url(/{$image['full_path']}) no-repeat center center !important; width:{$image['width']}px; height:{$image['height']}px";                
        }
        return array( 'result' => $result );
    }
    
    public static function fetchFooterNotes()
    {
        $result = false;
        $homePage = self::fetchHome();
        if ( $homePage->attribute( 'class_identifier' ) == 'homepage' )
        {
            $dataMap = $homePage->attribute( 'data_map' );
            if ( isset( $dataMap['note_footer'] ) && $dataMap['note_footer'] instanceof eZContentObjectAttribute && $dataMap['note_footer']->attribute( 'has_content' ) )
            {
                $result = $dataMap['note_footer'];                
            }
        }
        return array( 'result' => $result );
    }
        
    public static function fetchFooterLinks()
    {
        $nodes = array();
        $homePage = self::fetchHome();
        if ( $homePage->attribute( 'class_identifier' ) == 'homepage' )
        {
            $dataMap = $homePage->attribute( 'data_map' );
            if ( isset( $dataMap['link_nel_footer'] ) && $dataMap['link_nel_footer'] instanceof eZContentObjectAttribute && $dataMap['link_nel_footer']->attribute( 'has_content' ) )
            {
                $content = $dataMap['link_nel_footer']->attribute( 'content' );                
                foreach( $content['relation_list'] as $item )
                {
                    if ( isset( $item['node_id'] ) )
                    {
                        $nodes[] = eZContentObjectTreeNode::fetch( $item['node_id'] );
                    }
                }
            }
        }
        else
        {
            $links = array();
            $links[] = OpenPAINI::variable( 'LinkSpeciali', 'NodoCredits', false );
            $links[] = OpenPAINI::variable( 'LinkSpeciali', 'NodoNoteLegali', false );
            $links[] = OpenPAINI::variable( 'LinkSpeciali', 'NodoPrivacy', false );
            $links[] = OpenPAINI::variable( 'LinkSpeciali', 'NodoDichiarazione', false );
            $links[] = self::fetchTrasparenza();
            foreach( $links as $link )
            {
                if ( $link )
                {
                    $nodes[] = eZContentObjectTreeNode::fetch( $link );
                }
            }
        }        
        return array( 'result' => $nodes );
    }
    
    public static function fetchHeaderLogoStyle()
    {
        $result = false;
        $homePage = self::fetchHome();
        if ( $homePage->attribute( 'class_identifier' ) == 'homepage' )
        {
            $headerObject = $homePage->attribute( 'object' );
            if ( $headerObject instanceof eZContentObject )
            {
                $dataMap = $headerObject->attribute( 'data_map' );
                if ( isset( $dataMap['logo'] ) && $dataMap['logo'] instanceof eZContentObjectAttribute && $dataMap['logo']->attribute( 'has_content' ) )
                {
                    $result = self::getLogoCssStyle( $dataMap['logo'], 'header_logo' );
                }
            }
        }
        else
        {
            $headerObject = eZContentObject::fetchByRemoteID( self::$remoteLogo );
            if ( $headerObject instanceof eZContentObject )
            {
                $dataMap = $headerObject->attribute( 'data_map' );
                if ( isset( $dataMap['image'] ) && $dataMap['image'] instanceof eZContentObjectAttribute && $dataMap['image']->attribute( 'has_content' ) )
                {
                    $result = self::getLogoCssStyle( $dataMap['image'], 'header_logo' );                    
                }
            }
        }
        return array( 'result' => $result );
    }
    
    public static function fetchReverseRelatedObjectClassFacets( $object, $classFilterType, $classFilterArray, $sortBy )
    {
        $resultData = array();
        if ( $object instanceof eZContentObject )
        {
            $ezobjectrelationlist = eZContentClassAttribute::fetchFilteredList( array( 'data_type_string' => 'ezobjectrelationlist') );
            $attributes = array();
            foreach( $ezobjectrelationlist as $attribute )
            {
                $attributeContent = $attribute->content();
                if ( !empty( $attributeContent['class_constraint_list'] ) )
                {					
                    if ( in_array( $object->attribute( 'class_identifier' ), $attributeContent['class_constraint_list']  ) )
                    {
                        $class = eZContentClass::fetch( $attribute->attribute('contentclass_id') );
                        $classIdentifier = eZContentClass::classIdentifierByID( $attribute->attribute('contentclass_id') );
                        $attributes[$classIdentifier][] = array(
                            'class_id' => $attribute->attribute('contentclass_id'),
                            'class_identifier' => $classIdentifier,
                            'class_name' => $class->attribute('name'),
                            'attribute_identifier' => $attribute->attribute('identifier'),
                            'attribute_name' => $attribute->attribute('name'),
                            'class_constraint_list' => $attributeContent['class_constraint_list']
                        );
                    }
                }
            }
            
            $contentINI = eZINI::instance( 'content.ini' );
            $findINI = eZINI::instance( 'ezfind.ini' );
            $solrINI = eZINI::instance( 'solr.ini' );
            $siteINI = eZINI::instance();
            
            $languages = $siteINI->variable( 'RegionalSettings', 'SiteLanguageList' );
            $currentLanguage = $languages[0];
            
            $facetQueryData = array();
            $facetQuery = array();
            $fq = array();
            //$attributeFilter = array( 'or' );
            $resultData = array();
            
            foreach( $attributes as $classIdentifier => $values )
            {
                foreach( $values as $value )
                {
                    $query = "subattr_{$value['attribute_identifier']}___name____s:\"{$object->attribute( 'name' )}\" AND meta_contentclass_id_si:{$value['class_id']}";
                    $facetQuery[$query] = $query;
                    $facetQueryData[$query] = $value;
                    //$attributeFilter[] = "submeta_servizio___id_si:" . $object->attribute( 'id' );
                }
            }
            
            //if ( !empty( $attributeFilter ) )
            //{
            //    $fq[] = '(' . implode( ' OR ', $attributeFilter ) . ')';
            //}
            
            $policies = array();
            $accessResult = eZUser::currentUser()->hasAccessTo( 'content', 'read' );
            if ( !in_array( $accessResult['accessWord'], array( 'yes', 'no' ) ) )
            {
                $policies = $accessResult['policies'];
            }
            
            
            $limitationHash = array(
                'Class'        => eZSolr::getMetaFieldName( 'contentclass_id' ),
                'Section'      => eZSolr::getMetaFieldName( 'section_id' ),
                'User_Section' => eZSolr::getMetaFieldName( 'section_id' ),
                'Subtree'      => eZSolr::getMetaFieldName( 'path_string' ),
                'User_Subtree' => eZSolr::getMetaFieldName( 'path_string' ),
                'Node'         => eZSolr::getMetaFieldName( 'main_node_id' ),
                'Owner'        => eZSolr::getMetaFieldName( 'owner_id' ),
                'Group'        => eZSolr::getMetaFieldName( 'owner_group_id' ),
                'ObjectStates' => eZSolr::getMetaFieldName( 'object_states' ) );
            
            $filterQueryPolicies = array();
            
            // policies are concatenated with OR
            foreach ( $policies as $limitationList )
            {
                // policy limitations are concatenated with AND
                // except for locations policity limitations, concatenated with OR
                $filterQueryPolicyLimitations = array();
                $policyLimitationsOnLocations = array();
            
                foreach ( $limitationList as $limitationType => $limitationValues )
                {
                    // limitation values of one type in a policy are concatenated with OR
                    $filterQueryPolicyLimitationParts = array();
            
                    switch ( $limitationType )
                    {
                        case 'User_Subtree':
                        case 'Subtree':
                        {
                            foreach ( $limitationValues as $limitationValue )
                            {
                                $pathString = trim( $limitationValue, '/' );
                                $pathArray = explode( '/', $pathString );
                                // we only take the last node ID in the path identification string
                                $subtreeNodeID = array_pop( $pathArray );
                                $policyLimitationsOnLocations[] = eZSolr::getMetaFieldName( 'path' ) . ':' . $subtreeNodeID;                    
                            }
                        } break;
            
                        case 'Node':
                        {
                            foreach ( $limitationValues as $limitationValue )
                            {
                                $pathString = trim( $limitationValue, '/' );
                                $pathArray = explode( '/', $pathString );
                                // we only take the last node ID in the path identification string
                                $nodeID = array_pop( $pathArray );
                                $policyLimitationsOnLocations[] = $limitationHash[$limitationType] . ':' . $nodeID;                    
                            }
                        } break;
            
                        case 'Group':
                        {
                            foreach ( eZUser::currentUser()->attribute( 'contentobject' )->attribute( 'parent_nodes' ) as $groupID )
                            {
                                $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . $groupID;
                            }
                        } break;
            
                        case 'Owner':
                        {
                            $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . eZUser::currentUser()->attribute ( 'contentobject_id' );
                        } break;
            
                        case 'Class':
                        case 'Section':
                        case 'User_Section':
                        {
                            foreach ( $limitationValues as $limitationValue )
                            {
                                $filterQueryPolicyLimitationParts[] = $limitationHash[$limitationType] . ':' . $limitationValue;
                            }
                        } break;
            
                        default :
                        {
                            //hacky, object state limitations reference the state group name in their
                            //limitation
                            //hence the following match on substring
            
                            if ( strpos( $limitationType, 'StateGroup' ) !== false )
                            {
                                foreach ( $limitationValues as $limitationValue )
                                {
                                    $filterQueryPolicyLimitationParts[] = $limitationHash['ObjectStates'] . ':' . $limitationValue;
                                }
                            }
                            else
                            {
                                eZDebug::writeDebug( $limitationType, __METHOD__ . ' unknown limitation type: ' . $limitationType );
                                continue;
                            }
                        }
                    }
            
                    if ( !empty( $filterQueryPolicyLimitationParts ) )
                        $filterQueryPolicyLimitations[] = '( ' . implode( ' OR ', $filterQueryPolicyLimitationParts ) . ' )';
                }
            
                // Policy limitations on locations (node and/or subtree) need to be concatenated with OR
                // unlike the other types of limitation
                if ( !empty( $policyLimitationsOnLocations ) )
                {
                    $filterQueryPolicyLimitations[] = '( ' . implode( ' OR ', $policyLimitationsOnLocations ) . ')';
                }
            
                if ( !empty( $filterQueryPolicyLimitations ) )
                {
                    $filterQueryPolicies[] = '( ' . implode( ' AND ', $filterQueryPolicyLimitations ) . ')';
                }
            }
            
            if ( !empty( $filterQueryPolicies ) )
            {
                $fq[] = implode( ' OR ', $filterQueryPolicies );
            }
                        
            $fq[] = "meta_path_si:" . $contentINI->variable( 'NodeSettings', 'RootNode' );
            $fq[] = '(' . eZSolr::getMetaFieldName( 'installation_id' ) . ':' . eZSolr::installationID() . ' AND ' . eZSolr::getMetaFieldName( 'is_invisible' ) . ':false)';
            //$fq[] = eZSolr::getMetaFieldName( 'language_code' ) . ':' . $currentLanguage;
            
            $result = array();        
            $limit = 100;
            
            $params = array( 'q' => '*:*',
                             'rows' => 0,
                             'json.nl' => 'arrarr',
                             'facet' => 'true',
                             'facet.field' => array( 'meta_class_identifier_ms', 'meta_class_name_ms' ),
                             'facet.query' => array_values( $facetQuery ),
                             'facet.limit' => 1000,
                             'facet.method' => 'fc',
                             'facet.mincount' => 1 );
            
            if ( $findINI->variable( 'LanguageSearch', 'MultiCore' ) == 'enabled' )
            {
               $languageMapping = $findINI->variable( 'LanguageSearch','LanguagesCoresMap' );
               $shardMapping = $solrINI->variable( 'SolrBase', 'Shards' );
               $fullSolrURI = $shardMapping[$languageMapping[$currentLanguage]];
            }
            else
            {
                $fullSolrURI = $solrINI->variable( 'SolrBase', 'SearchServerURI' );
                // Autocomplete search should be done in current language and fallback languages
                $validLanguages = array_unique(
                    array_merge(
                        $siteINI->variable( 'RegionalSettings', 'SiteLanguageList' ),
                        array( $currentLanguage )
                    )
                );
                $fq[] = eZSolr::getMetaFieldName( 'language_code' ) . ':(' . implode( ' OR ', $validLanguages ) . ')';        
            }
            
            $params['fq'] = $fq;
            
            $solrBase = new eZSolrBase( $fullSolrURI );
            $result = $solrBase->rawSolrRequest( '/select', $params, 'json' );
        
        
            if ( isset( $result['facet_counts'] ) )
            {
                foreach( $result['facet_counts']['facet_queries'] as $query => $value )
                {
                    if ( isset( $facetQueryData[$query] ) && $value > 0 )
                    {                
                        if ( $classFilterType == 'include' && in_array( $facetQueryData[$query]['class_identifier'], $classFilterArray ) )
                        {
                            $do = true;
                        }
                        elseif ( $classFilterType == 'exclude' && in_array( $facetQueryData[$query]['class_identifier'], $classFilterArray ) )
                        {
                            $do = false;
                        }
                        else
                        {
                            $do = true;
                        }
                        
                        if ( $do )
                        {
                            $facetQueryData[$query]['value'] = $value;
                            $facetQueryData[$query]['query'] = $query;
                            $resultData[$facetQueryData[$query]['class_name']][] = new OpenPATempletizable( $facetQueryData[$query] );                            
                        }
                    }
                }
            }
            if ( $sortBy == 'alpha' )
            {
                ksort( $resultData );
            }
            else
            {
                usort( $resultData, array( 'OpenPaFunctionCollection', 'sortHashByValue' ) );
            }
        }        
        return array( 'result' => $resultData );
    }
    
    protected static function sortHashByValue( $a, $b )
    {
        $aValue = 0;
        foreach( $a as $item )
        {
            $aValue += $item->attribute( 'value' );
        }
        $bValue = 0;
        foreach( $b as $item )
        {
            $bValue += $item->attribute( 'value' );
        }
        return ( $aValue > $bValue ) ? -1 : 1;
    }
    
    // fetch non richiamabili da template (manca il  array(result => ...))
    // @todo renderle protected??
    
    public static function fetchTrasparenza()
    {
        if ( eZContentClass::fetchByIdentifier( 'trasparenza', false ) )
        {
            $params = self::$params;
            $params['SearchSubTreeArray'] = array( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );
            $params['SearchContentClassID'] = array( 'trasparenza' );
            $params['SearchLimit'] = 1;
            $params['AsObjects'] = true;
            $search = self::search( $params );        
            if ( $search['SearchCount'] > 0 )
            {
                return $search['SearchResult'][0]->attribute( 'node_id' );
            }
        }
        return false;
    }
    
    protected static function fetchHeaderImage()
    {
        $result = false;
        $homePage = self::fetchHome();
        if ( $homePage->attribute( 'class_identifier' ) == 'homepage' )
        {
            $headerObject = $homePage->attribute( 'object' );
            if ( $headerObject instanceof eZContentObject )
            {
                $dataMap = $headerObject->attribute( 'data_map' );
                if ( isset( $dataMap['image'] ) && $dataMap['image'] instanceof eZContentObjectAttribute && $dataMap['image']->attribute( 'has_content' ) )
                {
                    $result = $dataMap['image']->attribute( 'content' )->attribute( 'header_banner' );                
                }
            }
        }
        else
        {
            $headerObject = eZContentObject::fetchByRemoteID( self::$remoteHeader );
            if ( $headerObject instanceof eZContentObject )
            {
                $dataMap = $headerObject->attribute( 'data_map' );
                if ( isset( $dataMap['image'] ) && $dataMap['image'] instanceof eZContentObjectAttribute && $dataMap['image']->attribute( 'has_content' ) )
                {
                    $result = $dataMap['image']->attribute( 'content' )->attribute( 'header_banner' );                    
                }
            }
        }
        return $result;
    }
    
    protected static function getLogoCssStyle( eZContentObjectAttribute $attribute, $alias )
    {
        $image = $attribute->attribute( 'content' )->attribute( $alias );
        $width = $image['width']  . 'px';
        $height = $image['height'] . 'px';
        $additionaStyle = 'padding:0;';
        $headerImage = self::fetchHeaderImage();
        if ( is_array( $headerImage ) )
        {
            if ( $image['height'] > $headerImage['height'] )
            {
                $height = $headerImage['height'] . 'px';
                //$width = 'auto';
            }
            else
            {
                $additionaStyle .= "margin-top: " . ( $headerImage['height'] - $image['height'] ) / 2 . "px;";
            }
            
            if ( $image['width'] >= $headerImage['width'] || $image['width'] == '1000' )
            {
                $additionaStyle .= "margin-left:0;";
            }
            
        }
        else
        {
            if( $image['height'] == '200' )
            {
                $additionaStyle .= "margin-top:0;";
            }
            if ( $image['width'] == '1000' )
            {
                $additionaStyle .= "margin-left:0;";
            }
        }
        return "display: block;text-indent: -9999px;background:url(/{$image['full_path']}) no-repeat center center; width:{$width}; height:{$height};{$additionaStyle}"; 
    }

    
    public static function fetchHome()
    {
        if ( self::$home == null )
        {
            //eZDebug::writeNotice( 'Fetch home' );
            self::$home = eZContentObjectTreeNode::fetch( eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );
        }
        return self::$home;
    }
    
    public static function fetchTopMenuNodes()
    {
        if ( self::$topmenu == null )
        {            
            $homePage = self::fetchHome();
            if ( $homePage->attribute( 'class_identifier' ) == 'homepage' )
            {
                $dataMap = $homePage->attribute( 'data_map' );
                if ( isset( $dataMap['link_al_menu_orizzontale'] ) && $dataMap['link_al_menu_orizzontale'] instanceof eZContentObjectAttribute
                     && $dataMap['link_al_menu_orizzontale']->attribute( 'has_content' ) )
                {
                    self::$topmenu = array();
                    $content = $dataMap['link_al_menu_orizzontale']->attribute( 'content' );
                    foreach( $content['relation_list'] as $item )
                    {
                        if ( isset( $item['node_id'] ) )
                        {
                            self::$topmenu[] = $item['node_id'];
                        }
                    }
                }
            }
        }
        return self::$topmenu;
    }    
    
}

?>