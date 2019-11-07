<?php
/**
 * @author Eugenio Cilento <eugenio@iatmgu.com>
 * @version 0.1 Alpha 2007.10.03
 * @package Iat_File_Virtual
 * @copyright Copyright (c) 2007, Eugenio Cilento, International Assurance of Tennessee
 */

/**
 * Helps in comparing the files and keeping things organized.
 * @package Iat_File_Virtual
 */
class Iat_File_Virtual
{
    /**
     * File path including file name and extension.
     *
     * If the file is on an ftp server this will hold the full path from the root. If
     * it's a local file on disk, this will hold the full path. 
     *
     * @var string
     */
    public $path;
    public $oldPath;

    /**
     * File name including the extension.
     *
     * @var string
     */
    public $name;
    public $oldName;

    /**
     * File extension.
     *
     * @var string
     */
    public $ext;
    public $oldExt;

    /**
     * File type.
     *
     * File = '-', Directory = 'd'
     *
     * @var string
     */
    public $type;

    /**
     * File permissons. Consists of owner, group, other.
     *
     * <code>
     *      array('owner' => '---',
     *            'group' => '---',
     *            'other' => '---');
     * </code>
     *
     * @var array
     */
    public $permissions;

    /**
     * @var integer
     */
    public $children;

    /**
     * @var string
     */
    public $owner;

    /**
     * @var string
     */
    public $group;

    /**
     * @var float
     */
    public $size;

    /**
     * File time. Consists of hours, min.
     *
     * <code>
     * array( 'hours' => 0,
     *        'min'   => 0);
     * </code>
     *
     * @var array
     */
    public $ftime;

    /**
     * File date. Consists of day, month, year.
     * 
     * <code>
     * array( 'day'   => 1,
     *        'month' => 1,
     *        'year'  => 1900);
     * </code>
     *
     * @var array
     */
    public $fdate;

    /**
     * Custom property. Used to flag file as existing on a remote site.
     *
     * @var boolean
     */
    public $inRemote;

    /**
     * Custom property. Used to flag the file as having a zipped version.
     *
     * $var boolean
     */
    public $hasZippedVersion;

    function __construct()
    {
        $this->path         = '';
        $this->oldPath      = '';
        $this->name         = '';
        $this->oldName      = '';
        $this->ext          = '';
        $this->oldExt       = '';
        $this->type         = '';
        $this->permissions  = array( 'owner' => '---',
                                    'group' => '---',
                                    'other' => '---');
        $this->children     = 0;
        $this->owner        = '';
        $this->group        = '';
        $this->size         = 0;
        $this->ftime        = array( 'hours' => 0,
                                    'min'   => 0);
        $this->fdate        = array( 'day'   => 1,
                                    'month' => 1,
                                    'year'  => 1900);
        $this->inRemote     = false;
        $this->hasZippedVersion     = false;
    }

    public function changeVirtualFileExtensionTo($pExtension, $pKeepOldExt = false)
    {
        $this->oldExt = $this->ext;
        $this->oldName = $this->name;
        $this->oldPath = $this->path;

        $pathInfo = pathinfo($this->path);
        
        $this->ext = $pExtension;
        
        if($pKeepOldExt)
            $this->name = $pathInfo['basename'].'.'.$pExtension;
        else
            $this->name = $pathInfo['filename'].'.'.$pExtension;
        

        $this->path = str_replace('\\','',$pathInfo['dirname']).'/'.$this->name;
    }
}
?>