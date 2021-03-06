<?php

class OpenPAINI
{
    protected static $filters = array(
        'TopMenu::NodiCustomMenu',
        'GestioneSezioni::sezioni_per_tutti',
        //'SideMenu::EsponiLink'
    );
    
    public static function variable( $block, $value, $default = null )
    {
        if ( self::hasFilter( $block, $value, $default ) )
        {
            return self::filter( $block, $value, $default );
        }
        
        $ini = eZINI::instance( 'openpa.ini' );
        $result = $default;
        if ( $ini->hasVariable( $block, $value ) )
        {
            $result = $ini->variable( $block, $value );
        }
        return $result;        
    }
    
    protected static function hasFilter( $block, $value, $default )
    {
        if ( in_array( $block . '::' . $value, self::$filters )
             && self::filter( $block, $value, $default ) !== null )
        {
            return true;
        }
    }
    
    protected static function filterSezioniPerTutti()
    {
        $result = array();
        $ini = eZINI::instance( 'openpa.ini' );        
        if ( $ini->hasVariable( 'GestioneSezioni', 'sezioni_per_tutti' ) )
        {
            $result = (array) $ini->variable( 'GestioneSezioni', 'sezioni_per_tutti' );
        }
        $alboSection = eZSection::fetchByIdentifier( 'albotelematicotrentino', false );
        if ( is_array( $alboSection ) )
        {            
            $result[] = $alboSection['id'];
        }        
        return $result; 
    }
    
    protected static function filter( $block, $value, $default )
    {
        $filter = $block . '::' . $value;
        switch( $filter )
        {
            case 'TopMenu::NodiCustomMenu':
                return OpenPaFunctionCollection::fetchTopMenuNodes();              
            break;
        
            case 'GestioneSezioni::sezioni_per_tutti':
                return self::filterSezioniPerTutti();              
            break;
        
            //case 'SideMenu::EsponiLink':
            //    $result = eZINI::instance( 'openpa.ini' )->hasVariable( $block, $value ) ? eZINI::instance( 'openpa.ini' )->variable( $block, $value ) : $default;
            //    if ( !eZUser::currentUser()->isAnonymous() )
            //    {
            //        $result = false;
            //    }
            //    return $result;
            //break;
        
            default:
                return null;
        }
    }
    
    public static function set( $block, $settingName, $value )
    {
        if ( $block && $settingName && $value )
        {
            $frontend = OpenPABase::getFrontendSiteaccessName();
            $path = "settings/siteaccess/{$frontend}/";
            $iniFile = "openpa.ini";
            $ini = new eZINI( $iniFile . '.append', $path, null, null, null, true, true );                
            $ini->setVariable( $block, $settingName, $value );
            eZCache::clearById( 'global_ini' );
            if ( $ini->save() )
            {
                return $path . $iniFile;
            }
            return false;
        }
        return false;
    }
    
}