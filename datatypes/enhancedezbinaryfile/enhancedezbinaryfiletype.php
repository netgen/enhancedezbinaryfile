<?php

/*!
  \class EnhancedeZBinaryFileType enhancedezbinaryfiletype.php
  \ingroup eZDatatype
  \brief The class EnhancedeZBinaryFileType handles files and association with content objects with the collectedinformation function

*/

class EnhancedeZBinaryFileType extends eZDataType
{
    const MAX_FILESIZE_FIELD = 'data_int1';
    const MAX_FILESIZE_VARIABLE = '_enhancedezbinaryfile_max_filesize_';
    const ALLOWED_FILE_TYPES_FIELD = 'data_text1';
    const ALLOWED_FILE_TYPES_VARIABLE = '_enhancedezbinaryfile_allowed_file_types_';
    const DATA_TYPE_STRING = "enhancedezbinaryfile";

    /** constructor */
    function __construct()
    {
        parent::__construct(
            self::DATA_TYPE_STRING,
            ezpI18n::tr( 'extension/enhancedezbinaryfile/datatype', 'Enhanced File', 'Datatype name' ),
            array(
                'serialize_supported' => true,
                'object_serialize_map' => array(
                    'data_text' => 'filename'
                )
            )
        );
    }

    function EnhancedeZBinaryFileType()
    {
        $this->eZDataType(
            self::DATA_TYPE_STRING,
            ezpI18n::tr( 'kernel/classes/datatypes', "Enhanced File", 'Datatype name' ),
            array(
                'serialize_supported' => true,
                'object_serialize_map' => array(
                    'data_text' => 'filename'
                )
            )
        );
    }

    /** method fileHandler()
     *
     *  returns the binary file handler.
     *
     * @return eZBinaryFileHandler
     */
    function fileHandler()
    {
        return eZBinaryFileHandler::instance();
    }

    /** method viewTemplate()
     *
     * return the template name which the handler decides upon.
     *
     * @param $contentobjectAttribute eZContentObjectAttribute
     *
     * @return string
     */
    function viewTemplate( $contentobjectAttribute )
    {
        $handler = $this->fileHandler();
        $handlerTemplate = $handler->viewTemplate( $contentobjectAttribute );
        $template = $this->DataTypeString;
        if ( $handlerTemplate !== false )
            $template .= '_' . $handlerTemplate;
        return $template;
    }

    /** method editTemplate()
     *
     * return the template name to use for editing the attribute.
     *
     * Default is to return the datatype string which is OK for most datatypes,
     * if you want dynamic templates reimplement this function and return a template name.
     * The returned template name does not include the .tpl extension.
     *
     * @param $contentobjectAttribute eZContentObjectAttribute
     *
     * @return string
     */
    function editTemplate( $contentobjectAttribute )
    {
        $handler = $this->fileHandler();
        $handlerTemplate = $handler->editTemplate( $contentobjectAttribute );
        $template = $this->DataTypeString;
        if ( $handlerTemplate !== false )
            $template .= '_' . $handlerTemplate;
        return $template;
    }

    /** method informationTemplate()
     *
     * return the template name to use for information collection for the attribute.
     *
     * Default is to return the datatype string which is OK for most datatypes,
     * if you want dynamic templates reimplement this function and return a template name.
     * The returned template name does not include the .tpl extension.
     *
     * @param $contentobjectAttribute eZContentObjectAttribute
     *
     * @return string
     */
    function informationTemplate( $contentobjectAttribute )
    {
        $handler = $this->fileHandler();
        $handlerTemplate = $handler->informationTemplate( $contentobjectAttribute );
        $template = $this->DataTypeString;
        if ( $handlerTemplate !== false )
            $template .= '_' . $handlerTemplate;
        return $template;
    }

    /** method initializeObjectAttribute()
     *
     * Sets value according to current version
     *
     * @param $contentObjectAttribute eZContentObjectAttribute
     * @param $currentVersion int
     * @param $originalContentObjectAttribute eZContentObjectAttribute
     *
     */
    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $contentObjectAttributeID = $originalContentObjectAttribute->attribute( "id" );
            $version = $contentObjectAttribute->attribute( "version" );
            $oldfile = eZBinaryFile::fetch( $contentObjectAttributeID, $currentVersion );
            if ( $oldfile != null )
            {
                $oldfile->setAttribute( 'contentobject_attribute_id', $contentObjectAttribute->attribute( 'id' ) );
                $oldfile->setAttribute( "version",  $version );
                $oldfile->store();
            }
        }
    }

    /** method trashStoredObjectAttribute()
     *
     * The object is being moved to trash, do any necessary changes to the attribute.
     * Rename file and update db row with new name, so that access to the file using old links no longer works.
     *
     * @param $contentObjectAttribute eZContentObjectAttribute
     * @param $version int
     *
     */
    function trashStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
        $sys = eZSys::instance();
        $storage_dir = $sys->storageDirectory();

        $moduleINI = eZINI::instance( 'module.ini' );
        $downloadPath = $moduleINI->variable( 'RemoveFiles', 'DownloadPath' );
        $downloadPath = trim( $downloadPath, "/" );
        if ( !$downloadPath ) $downloadPath = 'original/collected';

        if ( $version == null )
        {
            $binaryFiles = eZBinaryFile::fetch( $contentObjectAttributeID );
        }
        else
        {
            $binaryFiles = array( eZBinaryFile::fetch( $contentObjectAttributeID, $version ) );
        }

        foreach ( $binaryFiles as $binaryFile )
        {
            if ( $binaryFile == null )
                continue;
            $mimeType =  $binaryFile->attribute( "mime_type" );
            list( $prefix, $suffix ) = explode( '/', $mimeType );
            $orig_dir = $storage_dir . '/original/' . $prefix;
            $fileName = $binaryFile->attribute( "filename" );

            // Check if there are any other records in ezbinaryfile that point to that fileName.
            $binaryObjectsWithSameFileName = eZBinaryFile::fetchByFileName( $fileName );

            $filePath = $orig_dir . "/" . $fileName;
            $file = eZClusterFileHandler::instance( $filePath );

            if ( $file->exists() and count( $binaryObjectsWithSameFileName ) <= 1 )
            {
                // create dest filename in the same manner as eZHTTPFile::store()
                // grab file's suffix
                $fileSuffix = eZFile::suffix( $fileName );
                // prepend dot
                if ( $fileSuffix )
                    $fileSuffix = '.' . $fileSuffix;
                // grab filename without suffix
                $fileBaseName = basename( $fileName, $fileSuffix );
                // create dest filename
                $newFileName = md5( $fileBaseName . microtime() . mt_rand() ) . $fileSuffix;
                $newFilePath = $orig_dir . "/" . $newFileName;

                // rename the file, and update the database data
                $file->move( $newFilePath );
                $binaryFile->setAttribute( 'filename', $newFileName );
                $binaryFile->store();
            }
        }
    }

    /** method deleteStoredObjectAttribute()
     *
     * Delete stored attribute
     * This is called when you delete the datatype from the class
     *
     * @param $contentObjectAttribute eZContentObjectAttribute
     * @param $version int
     *
     */
    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
        $sys = eZSys::instance();
        $storage_dir = $sys->storageDirectory();

        $moduleINI = eZINI::instance( 'module.ini' );
        $downloadPath = $moduleINI->variable( 'RemoveFiles', 'DownloadPath' );
        $downloadPath = trim( $downloadPath, "/" );
        if ( !$downloadPath ) $downloadPath = 'original/collected';

        if ( $version == null )
        {
            $binaryFiles = eZBinaryFile::fetch( $contentObjectAttributeID );
            eZBinaryFile::removeByID( $contentObjectAttributeID, null );

            foreach ( $binaryFiles as $binaryFile )
            {
                $mimeType =  $binaryFile->attribute( "mime_type" );
                list( $prefix, $suffix ) = explode('/', $mimeType );
                $orig_dir = $storage_dir . '/'. $downloadPath . '/' . $prefix;
                $fileName = $binaryFile->attribute( "filename" );

                // Check if there are any other records in ezbinaryfile that point to that fileName.
                $binaryObjectsWithSameFileName = eZBinaryFile::fetchByFileName( $fileName );

                $filePath = $orig_dir . "/" . $fileName;
                $file = eZClusterFileHandler::instance( $filePath );

                if ( $file->exists() and count( $binaryObjectsWithSameFileName ) < 1 )
                    $file->delete();

                $orig_dir = $storage_dir . '/original/' . $prefix;
                $filePath = $orig_dir . "/" . $fileName;
                $file = eZClusterFileHandler::instance( $filePath );

                if ( $file->exists() and count( $binaryObjectsWithSameFileName ) < 1 )
                    $file->delete();
            }
        }
        else
        {
            $count = 0;
            $binaryFile = eZBinaryFile::fetch( $contentObjectAttributeID, $version );
            if ( $binaryFile != null )
            {
                $mimeType =  $binaryFile->attribute( "mime_type" );
                list( $prefix, $suffix ) = explode('/', $mimeType );
                $orig_dir = $storage_dir . "/original/" . $prefix;
                $fileName = $binaryFile->attribute( "filename" );

                eZBinaryFile::removeByID( $contentObjectAttributeID, $version );

                // Check if there are any other records in ezbinaryfile that point to that fileName.
                $binaryObjectsWithSameFileName = eZBinaryFile::fetchByFileName( $fileName );

                $filePath = $orig_dir . "/" . $fileName;
                $file = eZClusterFileHandler::instance( $filePath );

                if ( $file->exists() and count( $binaryObjectsWithSameFileName ) < 1 )
                    $file->delete();
            }

            // check for collected info files
            $filePaths = array();
            $collections = eZInformationCollection::fetchCollectionsList($contentObjectAttribute->attribute('contentobject_id'));
            foreach( $collections as $collection )
            {
                $collectionAttribute = eZInformationCollectionAttribute::fetchByObjectAttributeID( $collection->attribute('id'), $contentObjectAttributeID );
                if($collectionAttribute instanceof eZInformationCollectionAttribute)
                {
                    $dom = new DOMDocument( '1.0', 'utf-8' );
                    if ($dom->loadXML( $collectionAttribute->attribute('data_text') ))
                    {
                        $FileAttributeValue = $dom->getElementsByTagName( 'Filename' )->item(0)->textContent;
                        if( $FileAttributeValue )
                        {
                            $filePaths[] = $FileAttributeValue;
                        }
                    }
                }
            }

            if( !empty( $filePaths ) )
            {
                foreach( $filePaths as $filePath )
                {
                    $fileName = basename($filePath);
                    $binaryObjectsWithSameFileName = eZBinaryFile::fetchByFileName( $fileName );
                    $file = eZClusterFileHandler::instance( $filePath );

                    if ( $file->exists() and count( $binaryObjectsWithSameFileName ) < 1 )
                        $file->delete();
                }
            }
        }
    }

    /** method checkFileUploads()
     *
     * Checks if file uploads are enabled, if not it gives a warning.
     */
    function checkFileUploads()
    {
        $isFileUploadsEnabled = ini_get( 'file_uploads' ) != 0;
        if ( !$isFileUploadsEnabled )
        {
            $isFileWarningAdded = $GLOBALS['eZBinaryFileTypeWarningAdded'];
            if ( !isset( $isFileWarningAdded ) or !$isFileWarningAdded )
            {
                eZAppendWarningItem(
                    array(
                        'error' => array(
                            'type' => 'kernel',
                            'number' => eZError::KERNEL_NOT_AVAILABLE ),
                        'text' => ezpI18n::tr( 'kernel/classes/datatypes', 'File uploading is not enabled. Please contact the site administrator to enable it.' )
                    )
                );
                $GLOBALS['eZBinaryFileTypeWarningAdded'] = true;
            }
        }
    }

    /** method validateObjectAttributeHTTPInput()
     * Validates the input and returns true if the input was valid for this datatype.
     *
     * @param $http eZHTTPTool
     * @param $base string
     * @param $contentObjectAttribute eZContentObjectAttribute
     *
     * @return int
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        EnhancedeZBinaryFileType::checkFileUploads();

        if ( $this->isDeletingFile( $http, $contentObjectAttribute ) )
        {
            return false;
        }

        $classAttribute = $contentObjectAttribute->contentClassAttribute();
        $mustUpload = false;
        $httpFileName = $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" );
        $maxSize = 1024 * 1024 * $classAttribute->attribute( self::MAX_FILESIZE_FIELD );

        $canFetchResult = eZHTTPFile::canFetch( $httpFileName, $maxSize );

        $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
        $version = $contentObjectAttribute->attribute( "version" );

        if ( $canFetchResult === 0 || $canFetchResult === true )
        {
            $binary = eZHTTPFile::fetch( $httpFileName );
        }

        if( empty( $binary ) )
        {
            $binary = eZBinaryFile::fetch( $contentObjectAttributeID, $version );
        }

        if ( empty( $binary ) )
        {
            if ( $contentObjectAttribute->validateIsRequired() )
                $mustUpload = true;
        }

        if( !empty($binary) )
        {
            $allowedFileTypes = $classAttribute->attribute( self::ALLOWED_FILE_TYPES_FIELD );

            // if allowed mime types are not set in the class attribute, check global restrictions on file extensions
            if ( empty( $allowedFileTypes ) )
            {
                $moduleINI = eZINI::instance('module.ini');
                $allowed = $moduleINI->variable('AllowedFileTypes', 'AllowedFileTypeList');

                $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binary->attribute( "original_filename" ) );
                if (!in_array(strtolower($extension),$allowed))
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ",$allowed) );
                    return eZInputValidator::STATE_INVALID;
                }
            }
            else
            {
                $mimeIni = eZINI::instance('mime.ini');
                $allowedFileTypesList = explode( '|', $allowedFileTypes );

                $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binary->attribute( "original_filename" ) );
                if ( !in_array( strtolower( $extension ),$allowedFileTypesList ) )
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ", $allowedFileTypesList) );
                    return eZInputValidator::STATE_INVALID;
                }

                $allowedMimeTypesList = $mimeIni->variable( $extension, 'Types' );
                if ( !in_array( $binary->attribute( 'mime_type' ), $allowedMimeTypesList ) )
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following mime types are allowed for file extension %1: %2.' ), $extension, implode(', ', $allowedMimeTypesList ) );
                    return eZInputValidator::STATE_INVALID;
                }
            }
        }

        if ( $mustUpload && $canFetchResult == eZHTTPFile::UPLOADEDFILE_DOES_NOT_EXIST )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                'A valid file is required.' ) );
            return eZInputValidator::STATE_INVALID;
        }
        if ( $canFetchResult == eZHTTPFile::UPLOADEDFILE_EXCEEDS_PHP_LIMIT )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                'The size of the uploaded file exceeds the limit set by the upload_max_filesize directive in php.ini.' ) );
            return eZInputValidator::STATE_INVALID;
        }
        if ( $canFetchResult == eZHTTPFile::UPLOADEDFILE_EXCEEDS_MAX_SIZE )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes',
                'The size of the uploaded file exceeds the maximum upload size: %1 bytes.' ), $maxSize );
            return eZInputValidator::STATE_INVALID;
        }
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
        Fetches the http post var integer input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        EnhancedeZBinaryFileType::checkFileUploads();
        if ( $this->isDeletingFile( $http, $contentObjectAttribute ) )
        {
            return false;
        }

        $canFetch = eZHTTPFile::canFetch( $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" ) );

        if ( $canFetch !== 0 && $canFetch !== true )
        {
            return false;
        }

        $binaryFile = eZHTTPFile::fetch( $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" ) );

        $contentObjectAttribute->setContent( $binaryFile );

        if ( $binaryFile instanceof eZHTTPFile )
        {
            $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
            $version = $contentObjectAttribute->attribute( "version" );

            /*
            $mimeObj = new  eZMimeType();
            $mimeData = $mimeObj->findByURL( $binaryFile->attribute( "original_filename" ), true );
            $mime = $mimeData['name'];
            */

            $mimeData = eZMimeType::findByFileContents( $binaryFile->attribute( "original_filename" ) );
            $mime = $mimeData['name'];

            if ( $mime == '' )
            {
                $mime = $binaryFile->attribute( "mime_type" );
            }

            $classAttribute = $contentObjectAttribute->contentClassAttribute();
            $allowedFileTypes = $classAttribute->attribute( self::ALLOWED_FILE_TYPES_FIELD );

            // if allowed mime types are not set in the class attribute, check global restrictions on file extensions
            if ( empty( $allowedFileTypes ) )
            {
                $moduleINI = eZINI::instance('module.ini');
                $allowed = $moduleINI->variable('AllowedFileTypes', 'AllowedFileTypeList');

                $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binaryFile->attribute( "original_filename" ) );
                if (!in_array(strtolower($extension),$allowed))
                {
                    eZDebug::writeError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ",$allowed) );
                    return false;
                }
            }
            else
            {
                $mimeIni = eZINI::instance('mime.ini');
                $allowedFileTypesList = explode( '|', $allowedFileTypes );

                $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binaryFile->attribute( "original_filename" ) );

                if ( !in_array( strtolower( $extension ),$allowedFileTypesList ) )
                {
                    eZDebug::writeError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ", $allowedFileTypesList) );
                    return false;
                }

                $allowedMimeTypesList = $mimeIni->variable( $extension, 'Types' );
                if ( !in_array( $binaryFile->attribute( 'mime_type' ), $allowedMimeTypesList ) )
                {
                    eZDebug::writeError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following mime types are allowed for file extension %1: %2.' ), $extension, implode(', ', $allowedMimeTypesList ) );
                    return false;
                }
            }

            $extension = eZFile::suffix( $binaryFile->attribute( "original_filename" ) );
            $binaryFile->setMimeType( $mime );
            if ( !$binaryFile->store( "original", $extension ) )
            {
                eZDebug::writeError( "Failed to store http-file: " . $binaryFile->attribute( "original_filename" ),
                    "EnhancedeZBinaryFileType" );
                return false;
            }

            $binary = eZBinaryFile::fetch( $contentObjectAttributeID, $version );
            if ( $binary === null )
                $binary = eZBinaryFile::create( $contentObjectAttributeID, $version );

            $orig_dir = $binaryFile->storageDir( "original" );

            $binary->setAttribute( "contentobject_attribute_id", $contentObjectAttributeID );
            $binary->setAttribute( "version", $version );
            $binary->setAttribute( "filename", basename( $binaryFile->attribute( "filename" ) ) );
            $binary->setAttribute( "original_filename", $binaryFile->attribute( "original_filename" ) );
            $binary->setAttribute( "mime_type", $mime );

            $binary->store();

            // VS-DBFILE

            $filePath = $binaryFile->attribute( 'filename' );
            $fileHandler = eZClusterFileHandler::instance();
            $fileHandler->fileStore( $filePath, 'binaryfile', true, $mime );

            $contentObjectAttribute->setContent( $binary );
        }
        return true;
    }

    /*!
     Does nothing, since the file has been stored. See fetchObjectAttributeHTTPInput for the actual storing.
    */
    function storeObjectAttribute( $contentObjectAttribute )
    {
    }

    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {
        EnhancedeZBinaryFileType::checkFileUploads();
        if( $action == "delete_binary" )
        {
            $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
            $version = $contentObjectAttribute->attribute( "version" );
            $this->deleteStoredObjectAttribute( $contentObjectAttribute, $version );
        }
    }

    /*!
     \reimp
     HTTP file insertion is supported.
    */
    function isHTTPFileInsertionSupported()
    {
        return true;
    }

    /*!
     \reimp
     HTTP file insertion is supported.
    */
    function isRegularFileInsertionSupported()
    {
        return true;
    }



    /*!
     Inserts the file using the eZBinaryFile class.
    */
    function insertHTTPFile( $object, $objectVersion, $objectLanguage,
                             $objectAttribute, $httpFile, $mimeData,
                             &$result )
    {
        $result = array( 'errors' => array(),
            'require_storage' => false );
        $attributeID = $objectAttribute->attribute( 'id' );

        $binary = eZBinaryFile::fetch( $attributeID, $objectVersion );
        if ( $binary === null )
            $binary = eZBinaryFile::create( $attributeID, $objectVersion );

        $httpFile->setMimeType( $mimeData['name'] );

        $suffix = false;
        if ( isset( $mimeData['suffix'] ) )
            $suffix = $mimeData['suffix'];

        $db = eZDB::instance();
        $db->begin();

        if ( !$httpFile->store( "original", $suffix, false ) )
        {
            $result['errors'][] = array( 'description' => ezpI18n::tr( 'kernel/classes/datatypes/ezbinaryfile',
                'Failed to store file %filename. Please contact the site administrator.', null,
                array( '%filename' => $httpFile->attribute( "original_filename" ) ) ) );
            return false;
        }


        $filePath = $binary->attribute( 'filename' );

        $binary->setAttribute( "contentobject_attribute_id", $attributeID );
        $binary->setAttribute( "version", $objectVersion );
        $binary->setAttribute( "filename", basename( $httpFile->attribute( "filename" ) ) );
        $binary->setAttribute( "original_filename", $httpFile->attribute( "original_filename" ) );
        $binary->setAttribute( "mime_type", $mimeData['name'] );

        $binary->store();

        $filePath = $httpFile->attribute( 'filename' );
        $fileHandler = eZClusterFileHandler::instance();
        $fileHandler->fileStore( $filePath, 'binaryfile', true, $mimeData['name'] );
        $objectAttribute->setContent( $binary );

        $db->commit();

        $objectAttribute->setContent( $binary );

        return true;
    }

    /*!
     Inserts the file using the eZBinaryFile class.
    */
    function insertRegularFile( $object, $objectVersion, $objectLanguage,
                                $objectAttribute, $filePath,
                                &$result )
    {
        $result = array( 'errors' => array(),
            'require_storage' => false );
        $attributeID = $objectAttribute->attribute( 'id' );

        $binary = eZBinaryFile::fetch( $attributeID, $objectVersion );
        if ( $binary === null )
            $binary = eZBinaryFile::create( $attributeID, $objectVersion );

        $fileName = basename( $filePath );
        $mimeData = eZMimeType::findByFileContents( $filePath );
        $storageDir = eZSys::storageDirectory();
        list( $group, $type ) = explode( '/', $mimeData['name'] );
        $destination = $storageDir . '/original/' . $group;

        if ( !file_exists( $destination ) )
        {
            if ( !eZDir::mkdir( $destination, false, true ) )
            {
                return false;
            }
        }

        // create dest filename in the same manner as eZHTTPFile::store()
        // grab file's suffix
        $fileSuffix = eZFile::suffix( $fileName );
        // prepend dot
        if( $fileSuffix )
            $fileSuffix = '.' . $fileSuffix;
        // grab filename without suffix
        $fileBaseName = basename( $fileName, $fileSuffix );
        // create dest filename
        $destFileName = md5( $fileBaseName . microtime() . mt_rand() ) . $fileSuffix;
        $destination = $destination . '/' . $destFileName;

        copy( $filePath, $destination );

        $fileHandler = eZClusterFileHandler::instance();
        $fileHandler->fileStore( $destination, 'binaryfile', true, $mimeData['name'] );


        $binary->setAttribute( "contentobject_attribute_id", $attributeID );
        $binary->setAttribute( "version", $objectVersion );
        $binary->setAttribute( "filename", $destFileName );
        $binary->setAttribute( "original_filename", $fileName );
        $binary->setAttribute( "mime_type", $mimeData['name'] );

        $binary->store();

        $objectAttribute->setContent( $binary );
        return true;
    }

    /*!
      We support file information
    */
    function hasStoredFileInformation( $object, $objectVersion, $objectLanguage,
                                       $objectAttribute )
    {
        return true;
    }

    /*!
      Extracts file information for the binaryfile entry.
    */
    function storedFileInformation( $object, $objectVersion, $objectLanguage,
                                    $objectAttribute )
    {
        $binaryFile = eZBinaryFile::fetch( $objectAttribute->attribute( "id" ),
            $objectAttribute->attribute( "version" ) );
        if ( $binaryFile )
        {
            return $binaryFile->storedFileInfo();
        }
        return false;
    }
    /*!
      Updates download count for binary file.
    */
    function handleDownload( $object, $objectVersion, $objectLanguage,
                             $objectAttribute )
    {
        $binaryFile = eZBinaryFile::fetch( $objectAttribute->attribute( "id" ),
            $objectAttribute->attribute( "version" ) );

        $contentObjectAttributeID = $objectAttribute->attribute( 'id' );
        $version =  $objectAttribute->attribute( "version" );

        if ( $binaryFile )
        {
            $db = eZDB::instance();
            $db->query( "UPDATE ezbinaryfile SET download_count=(download_count+1)
                         WHERE
                         contentobject_attribute_id=$contentObjectAttributeID AND version=$version" );
            return true;
        }
        return false;
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        $filesizeName = $base . self::MAX_FILESIZE_VARIABLE . $classAttribute->attribute( 'id' );
        $allowedFileTypesName = $base . self::ALLOWED_FILE_TYPES_VARIABLE . $classAttribute->attribute( 'id' );

        if ( $http->hasPostVariable( $filesizeName ) )
        {
            $filesizeValue = $http->postVariable( $filesizeName );
            $classAttribute->setAttribute( self::MAX_FILESIZE_FIELD, $filesizeValue );
        }

        $allowedFileTypes = array();
        if ( $http->hasPostVariable( $allowedFileTypesName ) )
        {
            $allowedFileTypesValue = $http->postVariable( $allowedFileTypesName );
            $mimeIni = eZIni::instance('mime.ini');

            foreach( $allowedFileTypesValue as $fileExtension )
            {
                if($mimeIni->hasVariable( $fileExtension, 'Types' ))
                {
                    $allowedFileTypes[] = $fileExtension;
                }
            }
            $classAttribute->setAttribute( self::ALLOWED_FILE_TYPES_FIELD, implode( '|', $allowedFileTypes ) );
        }
    }

    /*!
     Returns the object title.
    */
    function title( $contentObjectAttribute,  $name = "original_filename" )
    {
        $value = false;
        $binaryFile = eZBinaryFile::fetch( $contentObjectAttribute->attribute( 'id' ),
            $contentObjectAttribute->attribute( 'version' ) );
        if ( is_object( $binaryFile ) )
            $value = $binaryFile->attribute( $name );

        return $value;
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        /* Gets hit the first time through */
        $binaryFile = eZBinaryFile::fetch( $contentObjectAttribute->attribute( "id" ),
            $contentObjectAttribute->attribute( "version" ) );
        if ( !$binaryFile )
            return false;
        return true;
    }

    function objectAttributeContent( $contentObjectAttribute )
    {
        $binaryFile = eZBinaryFile::fetch( $contentObjectAttribute->attribute( "id" ), $contentObjectAttribute->attribute( "version" ) );
        if ( !$binaryFile )
        {
            $attrValue = false;
            return $attrValue;
        }
        return $binaryFile;
    }

    /*!
     \reimp
    */
    function isIndexable()
    {
        return true;
    }

    /*!
     \reimp
    */
    function isInformationCollector()
    {
        return true;
    }

    /*!
     Fetches the http post var integer input and stores it in the data instance.
    */
    function fetchCollectionAttributeHTTPInput( $collection, $collectionAttribute, $http, $base, $contentObjectAttribute )
    {
        EnhancedeZBinaryFileType::checkFileUploads();
        $canFetch = eZHTTPFile::canFetch( $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" ) );

        if ( $canFetch !== 0 && $canFetch !== true )
        {
            return eZInputValidator::STATE_INVALID;
        }

        //Check allowed file type - must do it here,again - otherwise an illegal
        //file will still be created in the storage directory
        $binaryFile = eZHTTPFile::fetch( $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" ) );
        if (!$binaryFile)
            return eZInputValidator::STATE_INVALID;

        $classAttribute = $contentObjectAttribute->contentClassAttribute();
        $allowedFileTypes = $classAttribute->attribute( self::ALLOWED_FILE_TYPES_FIELD );
        // if allowed mime types are not set in the class attribute, check global restrictions on file extensions
        if ( empty( $allowedFileTypes ) )
        {
            $moduleINI = eZINI::instance('module.ini');
            $allowed = $moduleINI->variable('AllowedFileTypes', 'AllowedFileTypeList');

            $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binaryFile->attribute( "original_filename" ) );
            if (!in_array(strtolower($extension),$allowed))
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ",$allowed) );
                return eZInputValidator::STATE_INVALID;
            }
        }
        else
        {
            $mimeIni = eZINI::instance('mime.ini');
            $allowedFileTypesList = explode( '|', $allowedFileTypes );

            $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binaryFile->attribute( "original_filename" ) );
            if ( !in_array( strtolower( $extension ),$allowedFileTypesList ) )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ", $allowedFileTypesList) );
                return eZInputValidator::STATE_INVALID;
            }

            $allowedMimeTypesList = $mimeIni->variable( $extension, 'Types' );
            if ( !in_array( $binaryFile->attribute( 'mime_type' ), $allowedMimeTypesList ) )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following mime types are allowed for file extension %1: %2.' ), $extension, implode(', ', $allowedMimeTypesList ) );
                return eZInputValidator::STATE_INVALID;
            }
        }

        if ( $binaryFile instanceof eZHTTPFile )
        {
            //clean up older files.
            $moduleINI = eZINI::instance( 'module.ini' );
            $maxFiles = $moduleINI->variable( 'RemoveFiles', 'MaxFiles' );
            $downloadPath = $moduleINI->variable( 'RemoveFiles', 'DownloadPath' );
            $downloadPath = trim( $downloadPath, "/" );
            if ( !$downloadPath )
                $downloadPath = 'original/collected';

            if ( $maxFiles > 0 )
            {
                $Files = array();
                $storageDir = eZSys::storageDirectory();
                $fileCollection = eZDir::recursiveFindRelative( $storageDir, $downloadPath, '.*' );
                if ( count( $fileCollection ) >= $maxFiles )
                {
                    foreach ( $fileCollection as $fileItem )
                    {
                        $lastModified = filemtime( $storageDir .'/'. $fileItem);
                        $Files[$fileItem] = filemtime($storageDir .'/'. $fileItem);
                    }
                    asort($Files, SORT_NUMERIC);
                    while (count($Files) >= $maxFiles)
                    {
                        $removeFile = key($Files);
                        if ( file_exists( $storageDir .'/'. $removeFile ) )
                        {
                            if (!unlink( $storageDir .'/'. $removeFile ) )
                            {
                                eZDebug::writeError( "Failed to delete file: " . $storageDir .'/'. $removeFile, "EnhancedeZBinaryFileType" );
                                return false;
                            }
                        }
                        array_shift($Files);
                    }
                }
            }
            //end cleanup

            $mimeData = eZMimeType::findByFileContents( $binaryFile->attribute( "original_filename" ) );  //Nice name but it still uses the extension to set the mimetype and therefore can be bogus
            $mime = $mimeData['name'];

            if ( $mime == '' )
            {
                $mime = $binaryFile->attribute( "mime_type" );
            }
            $extension = eZFile::suffix( $binaryFile->attribute( "original_filename" ) );
            $binaryFile->setMimeType( $mime );
            if ( !$binaryFile->store( $downloadPath, $extension ) )
            {
                eZDebug::writeError(
                    "Failed to store http-file: " . $binaryFile->attribute( "original_filename" ),
                    "EnhancedeZBinaryFileType"
                );
                return false;
            }

            //Adds xmltext to collection attribute with file info to data_text attribute
            $doc = new DOMDocument( '1.0', 'utf-8' );
            $root = $doc->createElement( 'binaryfile-info' );
            $binaryFileList = $doc->createElement( 'binaryfile-attributes' );

            foreach ( $binaryFile as $key => $binaryFileItem )
            {
                $binaryFileElement = $doc->createElement(  $key, $binaryFileItem );
                $binaryFileList->appendChild( $binaryFileElement );
            }

            $root->appendChild( $binaryFileList );
            $doc->appendChild( $root );
            $docText = EnhancedeZBinaryFileType::domString( $doc );
            $collectionAttribute->setAttribute( 'data_text', $docText );
        }
        return true;
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateCollectionAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        //validateCollection goes first, then the fetchCollection
        /*WITH THE ENCTYPE OF MULTIPART THE $_POST FOR A FILE IS EMPTY so can't use haspostvariable */

        $binaryFile = eZHTTPFile::fetch( $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" ) );
        $classAttribute = $contentObjectAttribute->contentClassAttribute();
        //This is only checking if it is required or not
        if ( !$binaryFile )
        {
            if ( $contentObjectAttribute->validateIsRequired() )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Input required.' ) );
                return eZInputValidator::STATE_INVALID;
            }
            else
                return eZInputValidator::STATE_ACCEPTED;
        }
        else {
            return $this->validateFileHTTPInput( $base, $contentObjectAttribute, $classAttribute );
        }
    }

    /*
     Private method, only for using inside this class.
    */

    function validateFileHTTPInput( $base, $contentObjectAttribute, $classAttribute )
    {
        //Check allowed file type
        //Have to do it here again, otherwise no error message
        $binaryFile = eZHTTPFile::fetch( $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" ) );
        if (!$binaryFile)
        {
            return eZInputValidator::STATE_INVALID;
        }

        $allowedFileTypes = $classAttribute->attribute( self::ALLOWED_FILE_TYPES_FIELD );
        // if allowed mime types are not set in the class attribute, check global restrictions on file extensions
        if ( empty( $allowedFileTypes ) )
        {
            $moduleINI = eZINI::instance('module.ini');
            $allowed = $moduleINI->variable('AllowedFileTypes', 'AllowedFileTypeList');

            $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binaryFile->attribute( "original_filename" ) );
            if (!in_array(strtolower($extension),$allowed))
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ",$allowed) );
                return eZInputValidator::STATE_INVALID;
            }
        }
        else
        {
            $mimeIni = eZINI::instance('mime.ini');
            $allowedFileTypesList = explode( '|', $allowedFileTypes );

            $extension = preg_replace('/.*\.(.+?)$/', '\\1', $binaryFile->attribute( "original_filename" ) );
            if ( !in_array( strtolower( $extension ),$allowedFileTypesList ) )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following file types are allowed: %1.' ), implode(", ", $allowedFileTypesList) );
                return eZInputValidator::STATE_INVALID;
            }

            $allowedMimeTypesList = $mimeIni->variable( $extension, 'Types' );
            if ( !in_array( $binaryFile->attribute( 'mime_type' ), $allowedMimeTypesList ) )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes','Failed to store file. Only the following mime types are allowed for file extension %1: %2.' ), $extension, implode(', ', $allowedMimeTypesList ) );
                return eZInputValidator::STATE_INVALID;
            }
        }

        //Check size
        $mustUpload = false;
        $maxSize = 1024 * 1024 * $classAttribute->attribute( self::MAX_FILESIZE_FIELD );

        /* Since it is not an ezbinary file this can never be true
           unfortunately, this is where the check would be to not upload the file
           multiple times in the event the form fails somewhere.  Unfortunately it
           can't be a binary file since it is a collection object and not a content
           object.
            if ( $contentObjectAttribute->validateIsRequired() )
                {
                    $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );
                    $version = $contentObjectAttribute->attribute( "version" );
                    $binary = eZBinaryFile::fetch( $contentObjectAttributeID, $version );
                    if ( $binary === null )
                    {
                        $mustUpload = true;
                    }
                }
        */

        $canFetchResult = EnhancedeZBinaryFileType::canFetch( $base . "_data_enhancedbinaryfilename_" . $contentObjectAttribute->attribute( "id" ), $maxSize );
        //$binaryfile doesn't have an attribute(http_name)
        if ( $mustUpload && $canFetchResult === eZHTTPFile::UPLOADEDFILE_DOES_NOT_EXIST )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes', 'A valid file is required.' ) );
            return eZInputValidator::STATE_INVALID;
        }
        if ( $canFetchResult == eZHTTPFile::UPLOADEDFILE_EXCEEDS_PHP_LIMIT )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes', 'The size of the uploaded file exceeds the limit set by the upload_max_filesize directive in php.ini.' ) );
            return eZInputValidator::STATE_INVALID;
        }
        if ( $canFetchResult == eZHTTPFile::UPLOADEDFILE_EXCEEDS_MAX_SIZE )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes', 'The size of the uploaded file exceeds the maximum upload size: %1 bytes.' ), $maxSize );
            return eZInputValidator::STATE_INVALID;
        }
        //Dropped all the way through with no error
        return eZInputValidator::STATE_ACCEPTED;
    }


    function canFetch( $http_name, $maxSize = false )
    {
        if ( isset( $GLOBALS["eZHTTPFile-$http_name"] ) AND
            $GLOBALS["eZHTTPFile-$http_name"] instanceof eZHTTPFile  )
        {
            //No idea why it's the opposite from ezHTTPFile::canfetch
            if ( $maxSize === false )
            {
                return isset( $_FILES[$http_name] ) and $_FILES[$http_name]['name'] != "" and $_FILES[$http_name]['error'] == 0;
            }

            if ( isset( $_FILES[$http_name] ) and $_FILES[$http_name]['name'] != "" )
            {
                switch ( $_FILES[$http_name]['error'] )
                {
                    case ( UPLOAD_ERR_NO_FILE ):
                    {
                        return eZHTTPFile::UPLOADEDFILE_DOES_NOT_EXIST;
                    }break;

                    case ( UPLOAD_ERR_INI_SIZE ):
                    {
                        return eZHTTPFile::UPLOADEDFILE_EXCEEDS_PHP_LIMIT;
                    }break;

                    case ( UPLOAD_ERR_FORM_SIZE ):
                    {
                        return eZHTTPFile::UPLOADEDFILE_EXCEEDS_MAX_SIZE;
                    }break;

                    default:
                    {
                        return ( $maxSize == 0 || $_FILES[$http_name]['size'] <= $maxSize )? eZHTTPFile::UPLOADEDFILE_OK:
                            eZHTTPFile::UPLOADEDFILE_EXCEEDS_MAX_SIZE;
                    }
                }
            }
            else
            {
                return eZHTTPFile::UPLOADEDFILE_DOES_NOT_EXIST;
            }
        }
        if ( $maxSize === false )
            return eZHTTPFile::UPLOADEDFILE_OK;
        else
            return true;
    }

    /** method domString()
     *
     * return the XML structure in $domDocument as text.
     * It will take of care of the necessary charset conversions
     * for content storage.
     *
     * @param $domDocument DOMDocument
     *
     * @return string
     */
    function domString( $domDocument )
    {
        $ini = eZINI::instance();
        $xmlCharset = $ini->variable( 'RegionalSettings', 'ContentXMLCharset' );
        if ( $xmlCharset == 'enabled' )
        {
            $charset = eZTextCodec::internalCharset();
        }
        else if ( $xmlCharset == 'disabled' )
            $charset = true;
        else
            $charset = $xmlCharset;
        if ( $charset !== true )
        {
            $charset = eZCharsetInfo::realCharsetCode( $charset );
        }
        $domString = $domDocument->saveXML();
        return $domString;
    }

    /** method metaData()
     *
     * @param $contentObjectAttribute eZContentObjectAttribute
     *
     * @return string
     */
    function metaData( $contentObjectAttribute )
    {
        $binaryFile = $contentObjectAttribute->content();

        $metaData = "";
        if ( $binaryFile instanceof eZBinaryFile )
        {
            $metaData = $binaryFile->metaData();
        }
        return $metaData;
    }

    /** method serializeContentClassAttribute()
     *
     * @param $classAttribute eZContentClassAttribute
     * @param $attributeNode DOMDocument
     * @param $attributeParametersNode DOMDocument
     */
    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        $dom = $attributeParametersNode->ownerDocument;
        $maxSize = $classAttribute->attribute( self::MAX_FILESIZE_FIELD );
        $maxSizeNode = $dom->createElement( 'max-size', $maxSize );
        $maxSizeNode->setAttribute( 'unit-size', 'mega' );
        $attributeParametersNode->appendChild( $maxSizeNode );

        $allowedFileTypesNode = $dom->createElement( 'allowed-file-types' );

        $allowedFileTypesAttribute = $classAttribute->attribute( self::ALLOWED_FILE_TYPES_FIELD );
        if ( !empty( $allowedFileTypesAttribute ) )
        {
            $allowedFileTypesList = explode( '|', $classAttribute->attribute( self::ALLOWED_FILE_TYPES_FIELD ) );

            foreach ( $allowedFileTypesList as $allowedFileType )
            {
                $fileTypeNode = $dom->createElement( 'file-type', $allowedFileType );
                $allowedFileTypesNode->appendChild($fileTypeNode);
            }
        }
        $attributeParametersNode->appendChild( $allowedFileTypesNode );
    }

    /** method unserializeContentClassAttribute()
     *
     * @param $classAttribute eZContentClassAttribute
     * @param $attributeNode DOMDocument
     * @param $attributeParametersNode DOMDocument
     */
    function unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        $sizeNode = $attributeParametersNode->getElementsByTagName( 'max-size' )->item( 0 );
        $maxSize = $sizeNode->textContent;
        $unitSize = $sizeNode->getAttribute( 'unit-size' );
        $classAttribute->setAttribute( self::MAX_FILESIZE_FIELD, $maxSize );

        $fileTypesList = array();

        /** @var DOMNodeList $allowedTypesNode */
        $allowedTypes = $attributeParametersNode->getElementsByTagName( 'allowed-file-types' );
        if ( $allowedTypes->length )
        {
            /** @var DOMDocument $allowedTypesNode */
            $allowedTypesNode = $allowedTypes->item(0)->ownerDocument;

            /** @var DOMNodeList $allowedMimeTypeNodes */
            $allowedFileTypeNodes = $allowedTypesNode->getElementsByTagName( 'file-type' );

            if( $allowedFileTypeNodes->length )
            {
                for ($i = 0; $i < $allowedFileTypeNodes->length; $i++)
                {
                    $fileTypesList[] = $allowedFileTypeNodes->item($i)->textContent ;
                }
            }
        }

        $attributeValue = array();
        if( !empty($fileTypesList) )
        {
            foreach( $fileTypesList as $fileType )
            {
                $attributeValue[] = $fileType;
            }
        }
        $classAttribute->setAttribute( self::ALLOWED_FILE_TYPES_FIELD, implode( '|', $attributeValue ) );
    }

    /** method toString()
     *
     * @param $objectAttribute eZContentObjectAttribute
     *
     * @return string
     */
    function toString( $objectAttribute )
    {
        $binaryFile = $objectAttribute->content();

        if ( is_object( $binaryFile ) )
        {
            return implode( '|', array( $binaryFile->attribute( 'filepath' ), $binaryFile->attribute( 'original_filename' ) ) );
        }
        else
            return '';
    }

    /** method fromString()
     *
     * @param $objectAttribute eZContentObjectAttribute
     * @param $string string
     *
     * @return bool|null
     */
    function fromString( $objectAttribute, $string )
    {
        if( !$string )
            return true;

        $result = array();
        return $this->insertRegularFile(
            $objectAttribute->attribute( 'object' ),
            $objectAttribute->attribute( 'version' ),
            $objectAttribute->attribute( 'language_code' ),
            $objectAttribute,
            $string,
            $result );
    }

    /** method serializeContentObjectAttribute()
     *
     * return a DOM representation of the content object attribute
     *
     * @param $package eZPackage
     * @param $objectAttribute eZContentObjectAttribute
     *
     * @return DOMElement
     */
    function serializeContentObjectAttribute( $package, $objectAttribute )
    {
        $node = $this->createContentObjectAttributeDOMNode( $objectAttribute );

        $binaryFile = $objectAttribute->attribute( 'content' );
        if ( is_object( $binaryFile ) )
        {
            $fileKey = md5( mt_rand() );
            $package->appendSimpleFile( $fileKey, $binaryFile->attribute( 'filepath' ) );

            $dom = $node->ownerDocument;
            $fileNode = $dom->createElement( 'binary-file' );
            $fileNode->setAttribute( 'filesize', $binaryFile->attribute( 'filesize' ) );
            $fileNode->setAttribute( 'filename', $binaryFile->attribute( 'filename' ) );
            $fileNode->setAttribute( 'original-filename', $binaryFile->attribute( 'original_filename' ) );
            $fileNode->setAttribute( 'mime-type', $binaryFile->attribute( 'mime_type' ) );
            $fileNode->setAttribute( 'filekey', $fileKey );
            $node->appendChild( $fileNode );
        }

        return $node;
    }

    /** method unserializeContentObjectAttribute()
     *
     * @param $package eZPackage
     * @param $objectAttribute eZContentObjectAttribute
     * @param $attributeNode DOMDocument
     *
     * @return bool
     */
    function unserializeContentObjectAttribute( $package, $objectAttribute, $attributeNode )
    {
        $fileNode = $attributeNode->getElementsByTagName( 'binary-file' )->item( 0 );
        if ( !is_object( $fileNode ) or !$fileNode->hasAttributes() )
        {
            return false;
        }

        $binaryFile = eZBinaryFile::create( $objectAttribute->attribute( 'id' ), $objectAttribute->attribute( 'version' ) );

        $sourcePath = $package->simpleFilePath( $fileNode->getAttribute( 'filekey' ) );

        if ( !file_exists( $sourcePath ) )
        {
            eZDebug::writeError( "The file '$sourcePath' does not exist, cannot initialize file attribute with it",
                'EnhancedeZBinaryFileType::unserializeContentObjectAttribute' );
            return false;
        }

        $ini = eZINI::instance();
        $mimeType = $fileNode->getAttribute( 'mime-type' );
        list( $mimeTypeCategory, $mimeTypeName ) = explode( '/', $mimeType );
        $destinationPath = eZSys::storageDirectory() . '/original/' . $mimeTypeCategory . '/';
        if ( !file_exists( $destinationPath ) )
        {
            $oldumask = umask( 0 );
            if ( !eZDir::mkdir( $destinationPath, eZDir::directoryPermission(), true ) )
            {
                umask( $oldumask );
                return false;
            }
            umask( $oldumask );
        }

        $basename = basename( $fileNode->getAttribute( 'filename' ) );
        while ( file_exists( $destinationPath . $basename ) )
        {
            $basename = substr( md5( mt_rand() ), 0, 8 ) . '.' . eZFile::suffix( $fileNode->getAttribute( 'filename' ) );
        }

        eZFileHandler::copy( $sourcePath, $destinationPath . $basename );
        eZDebug::writeNotice( 'Copied: ' . $sourcePath . ' to: ' . $destinationPath . $basename,
            'EnhancedeZBinaryFileType::unserializeContentObjectAttribute()' );

        $binaryFile->setAttribute( 'contentobject_attribute_id', $objectAttribute->attribute( 'id' ) );
        $binaryFile->setAttribute( 'filename', $basename );
        $binaryFile->setAttribute( 'original_filename', $fileNode->getAttribute( 'original-filename' ) );
        $binaryFile->setAttribute( 'mime_type', $fileNode->getAttribute( 'mime-type' ) );

        $binaryFile->store();

        // VS-DBFILE + SP DBFile fix

        $fileHandler = eZClusterFileHandler::instance();
        $fileHandler->fileStore( $destinationPath . $basename, 'binaryfile', true );
    }

    function supportsBatchInitializeObjectAttribute()
    {
        return true;
    }

    /**
     * Checks if current HTTP request is asking for current binary file deletion
     * @param eZHTTPTool $http
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @return bool
     */
    private function isDeletingFile( eZHTTPTool $http, eZContentObjectAttribute $contentObjectAttribute )
    {
        $isDeletingFile = false;
        if ( $http->hasPostVariable( 'CustomActionButton' ) )
        {
            $customActionArray = $http->postVariable( 'CustomActionButton' );
            $attributeID = $contentObjectAttribute->attribute( 'id' );
            if ( isset( $customActionArray[$attributeID . '_delete_binary'] ) )
            {
                $isDeletingFile = true;
            }
        }

        return $isDeletingFile;
    }
}

eZDataType::register( EnhancedeZBinaryFileType::DATA_TYPE_STRING, "enhancedezbinaryfiletype" );

?>
