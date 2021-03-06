<?php
class OpenPAObjectHandler
{
    const FILTER_HALT = 0;
    const FILTER_CONTINUE = 1;

    /**
     * @var OpenPAObjectHandler[]
     */
    protected static $instances = array();

    /**
     * @var eZContentObject|null
     */
    protected $contentObject;

    /**
     * @var eZContentObjectTreeNode|null
     */
    protected $contentNode;

    /**
     * @var OpenPAObjectHandlerServiceInterface[]
     */
    protected $services = array();

    /**
     * @var OpenPAAttributeHandler[]
     */
    public $attributesHandlers = array();

    /**
     * @var array
     */
    public $currentPathNodeIds = array();

    /**
     * @var int
     */
    public $currentNodeId = 0;

    /**
     * @var int
     */
    public $currentMainNodeId = 0;

    /**
     * @var int|null
     */
    public $currentObjectId = 0;

    /**
     * @var string
     */
    public $currentClassIdentifier;

    /**
     * @var eZUser
     */
    public $currentUser;

    /**
     * @var string
     */
    public $currentUserHashString;

    public static function instanceFromObject( $object = null )
    {
        if ( $object instanceof eZContentObjectTreeNode )
        {
            return self::instanceFromContentObject( $object->attribute( 'object' ), $object );
        }
        elseif ( $object instanceof eZContentObject )
        {
            return self::instanceFromContentObject( $object, $object->attribute( 'main_node' ) );
        }
        elseif ( $object instanceof eZPageBlock )
        {
            return self::blockHandler( $object );
        }
        return new OpenPAObjectHandler();
    }

    public static function instanceFromContentObject( eZContentObject $object = null, eZContentObjectTreeNode $node = null )
    {
        //@todo caricare la classe estesa specifica per l'oggetto di riferimento
        if ( $object instanceof eZContentObject )
        {
            if ( !isset( self::$instances[$object->attribute('id')] ) )
            {
                self::$instances[$object->attribute('id')] = new OpenPAObjectHandler( $object );
            }
            self::$instances[$object->attribute('id')]->setCurrentNode( $node );
            return self::$instances[$object->attribute('id')];
        }
        return new OpenPAObjectHandler();
    }

    public function setCurrentNode( eZContentObjectTreeNode $node = null )
    {
        if ( $this->contentNode === null )
        {
            if ( $node instanceof eZContentObjectTreeNode )
            {
                $this->contentNode = $node;
                $this->currentNodeId = $this->contentNode->attribute( 'node_id' );
                $this->currentMainNodeId = $this->currentNodeId;
                if ( $this->currentNodeId != $this->contentObject->attribute( 'main_node_id' ) )
                {
                    $this->currentMainNodeId = $this->contentObject->attribute( 'main_node_id' );
                }
            }
            elseif ( $this->contentObject instanceof eZContentObject )
            {
                $mainNode = $this->contentObject->attribute( 'main_node' );
                if ( $mainNode instanceof eZContentObjectTreeNode )
                {
                    $this->contentNode = $mainNode;
                    $this->currentNodeId = $this->contentNode->attribute( 'node_id' );
                    $this->currentMainNodeId = $this->currentNodeId;
                }
            }

            if ( $this->contentNode !== null )
            {
                $pathArray = explode( '/', $this->contentNode->attribute( 'path_string' ) );
                $start = false;
                foreach( $pathArray as $nodeId )
                {
                    
                    $do = true;
                    if ( $nodeId == eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) )
                    {
                        $start = true;
                    }
                    if ( $nodeId == ''
                         || $nodeId == 1                         
                         || $nodeId == eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' )
                         || strpos( eZINI::instance()->variable( 'SiteSettings', 'IndexPage' ), $nodeId ) !== false
                    )
                    {
                        $do = false;                        
                    }
                    if ( $start && $do )
                    {
                        $this->currentPathNodeIds[] = $nodeId;
                    }
                    
                    //if ( $nodeId != ''
                    //     && $nodeId != 1
                    //     && $nodeId != eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' )
                    //     && strpos( eZINI::instance()->variable( 'SiteSettings', 'IndexPage' ), $nodeId ) === false
                    //)
                    //{
                    //    $this->currentPathNodeIds[] = $nodeId;
                    //}
                }
                //eZDebug::writeNotice($this->currentNodeId . ' ' . var_export( $this->currentPathNodeIds,1));
            }
        }        
    }

    public function getContentNode()
    {
        return $this->contentNode;
    }

    public function hasContentNode()
    {
        return $this->contentNode instanceof eZContentObjectTreeNode;
    }

    public function getContentObject()
    {
        return $this->contentObject;
    }

    public function hasContentObject()
    {
        return $this->contentObject instanceof eZContentObject;
    }

    public function hasContent()
    {
        return $this->hasContentObject() && $this->hasContentNode();
    }

    protected function __construct( $object = null )
    {
        if ( $object instanceof eZContentObject )
        {
            $this->contentObject = $object;
            $this->currentObjectId = $this->contentObject->attribute( 'id' );
            $this->currentClassIdentifier = $this->contentObject->attribute( 'class_identifier' );
            $dataMap = $this->contentObject->attribute( 'data_map' );
            foreach( $dataMap as $identifier => $attribute )
            {
                $this->attributesHandlers[$identifier] = $this->attributeHandler( $attribute, $identifier );
            }
        }
        $availableServices = OpenPAINI::variable( 'ObjectHandlerServices', 'Services', array() );
        foreach( $availableServices as $serviceId => $className )
        {
            if ( class_exists( $className ) )
            {
                $check = new ReflectionClass( $className );
                if ( $check->isSubclassOf( 'ObjectHandlerServiceBase' ) )
                {
                    $this->services[$serviceId] = new $className;
                    $this->services[$serviceId]->setIdentifier( $serviceId );
                    $this->services[$serviceId]->setContainer( $this );
                }
                else
                {
                    eZDebug::writeError( "Service $serviceId does not extend ObjectHandlerServiceBase", __METHOD__ );
                }
            }
            else
            {
                eZDebug::writeError( "Class $className not found", __METHOD__ );
            }
        }

        $this->currentUser = eZUser::currentUser();
        $this->currentUserHashString = implode( ',' , $this->currentUser->attribute( 'role_id_list' ) ) . implode( ',' , $this->currentUser->attribute( 'limited_assignment_value_list' ) );
    }

    public function attributes()
    {
        return array_merge( array_keys( $this->services ), array_keys( $this->attributesHandlers ) );
    }

    public function hasAttribute( $key )
    {
        return in_array( $key, array_merge( array_keys( $this->services ), array_keys( $this->attributesHandlers ) ) );
    }

    /**
     * @param $key
     *
     * @return OpenPATempletizable
     */
    public function attribute( $key )
    {
        if ( isset( $this->services[$key] ) )
        {
            return $this->services[$key]->data();
        }
        elseif ( isset( $this->attributesHandlers[$key] ) )
        {
            return $this->attributesHandlers[$key];
        }
        eZDebug::writeNotice( "Service or AttributeHandler $key does not exist", __METHOD__ );
        return false;
    }

    /**
     * @param $key
     *
     * @return OpenPATempletizable|OpenPAObjectHandlerServiceInterface
     */
    public function service( $key )
    {
        if ( isset( $this->services[$key] ) )
        {
            return $this->services[$key]->data();
        }
        eZDebug::writeNotice( "Service $key does not exist", __METHOD__ );
        return false;
    }

    /**
     * @param string $className
     *
     * @return OpenPATempletizable|OpenPAObjectHandlerServiceInterface
     */
    public function serviceByClassName( $className )
    {
        foreach( $this->services as $key => $service )
        {
            if ( get_class( $service ) == $className )
            {
                return $service;
            }
        }
        eZDebug::writeNotice( "Service by $className does not exist", __METHOD__ );
        return false;
    }

    public static function blockHandler( eZPageBlock $block )
    {
        $class = 'OpenPABlockHandler';
        $parameters = array();
        $blockHandlersList = OpenPAINI::variable( 'BlockHandlers', 'Handlers', array() );
        $currentType = $block->attribute( 'type' );
        $currentView = $block->attribute( 'view' );
        foreach( $blockHandlersList as $parameters => $className )
        {
            $parameters = explode( '/', $parameters );
            $type = $parameters[0];
            $view = $parameters[1];
            if ( ( $type == '*' || $type == $currentType )
                 && ( $view == '*' || $view == $currentView ) )
            {
                $class = $className;
            }
        }
        return new $class( $block, $parameters );
    }

    public function attributeHandler( eZContentObjectAttribute $attribute, $identifier = false )
    {
        $class = 'OpenPAAttributeHandler';
        $parameters = array();
        $attributeHandlersList = OpenPAINI::variable( 'AttributeHandlers', 'Handlers', array() );
        $currentType = $attribute->attribute( 'data_type_string' );
        $currentClassIdentifier = $this->currentClassIdentifier;
        $currentAttributeIdentifier = $identifier != false ? $identifier : $attribute->attribute( 'contentclass_attribute_identifier' );
        foreach( $attributeHandlersList as $parameters => $className )
        {
            $parameters = explode( '/', $parameters );
            $type = $parameters[0];
            $classIdentifier = $parameters[1];
            $attributeIdentifier = $parameters[2];
            if ( ( $type == '*' || $type == $currentType )
                 && ( $classIdentifier == '*' || $classIdentifier == $currentClassIdentifier )
                 && ( $attributeIdentifier == '*' || $attributeIdentifier == $currentAttributeIdentifier ) )
            {
                $class = $className;
            }
        }
        return new $class( $attribute, $parameters );
    }

    public function flush( $index = true )
    {
        if ( $this->contentObject instanceof eZContentObject )
        {
            /*
            $eZSolr = eZSearch::getEngine();
            $eZSolr->addObject( $this->contentObject, false );
            $eZSolr->commit();
             */
            if ( $index )
            {
                $this->addPendingIndex();
            }
            eZContentCacheManager::clearContentCacheIfNeeded( $this->currentObjectId );
            $this->contentObject->resetDataMap();
            eZContentObject::clearCache( array( $this->currentObjectId ) );
            unset( self::$instances[$this->currentObjectId] );
        }
    }

    public function filter( $filterIdentifier, $action )
    {
        $result = true;
        foreach( $this->services as $id => $service )
        {            
            $result = $service->filter( $filterIdentifier, $action );            
            if ( $result == self::FILTER_HALT  )
            {
                return false;
            }
        }
        return $result;
    }

    public function addPendingIndex()
    {
        eZDB::instance()->query( "INSERT INTO ezpending_actions( action, param ) VALUES ( 'index_object', '{$this->currentObjectId}' )" );
    }
}