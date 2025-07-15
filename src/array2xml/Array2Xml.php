<?php

namespace Ecxod\Array2Xml;

use Exception;

/**
 * Array -> XML Converter Class
 * Convert array to clean XML
 *
 * @category    Libraries
 * @author      Christian Eichert <c@zp1.net>
 * @author      Anton Vasiliev <email>
 */
class Array2Xml extends Exception
{
    private             $writer;
    private string      $version            = '1.0';
    private string      $encoding           = 'UTF-8';
    private string      $rootName           = 'root';
    private array       $rootAttrs          = [];        //example: array('first_attr' => 'value_of_first_attr', 'second_atrr' => 'etc');
    private bool        $rootSelf           = false;
    private array       $elementAttrs       = [];       //example: $attrs['element_name'][] = array('attr_name' => 'attr_value');
    private array       $CDataKeys          = [];
    private string      $newLine            = "\n";
    private string      $newTab             = "\t";
    private string      $numericTagPrefix   = 'key';
    private bool        $skipNumeric        = true;
    private bool        $_tabulation        = true;     //TODO
    private bool|string $defaultTagName     = false;    //Tag For Numeric Array Keys
    private array       $rawKeys            = [];
    private int         $emptyElementSyntax = 1;
    private bool        $filterNumbers      = false;
    private array       $tagsToFilter       = [];

    public const EMPTY_SELF_CLOSING = 1;
    public const EMPTY_FULL         = 2;

    /**
     * Constructor
     * Load Standard PHP Class XMLWriter and path it to variable
     *
     * @access    public
     * @param array $params
     */
    public function __construct($params = [], $message = null)
    {
        if(is_array($params) and !empty($params))
        {
            foreach($params as $key => $param)
            {
                $attr = "_$key";
                if(property_exists(object_or_class: $this, property: $attr))
                {
                    $this->$attr = $param;
                }
            }
        }

        $this->writer = new \XMLWriter();
        parent::__construct(message: $message);
    }

    // --------------------------------------------------------------------

    /**
     * Converter
     * Convert array data to XML. Last method to call
     *
     * @access    public
     * @param    array
     * @return    string
     */
    public function convert(array $data)
    {
        $this->writer->openMemory();
        $this->writer->startDocument(version: $this->version, encoding: $this->encoding);
        $this->writer->startElement(name: $this->rootName);
        if(!empty($this->rootAttrs) and is_array(value: $this->rootAttrs))
        {
            foreach($this->rootAttrs as $rootAttrName => $rootAttrText)
            {
                $this->writer->writeAttribute(name: $rootAttrName, value: $rootAttrText);
            }
        }

        if($this->rootSelf === false)
        {
            $this->writer->text(content: $this->newLine);

            if(is_array($data) and !empty($data))
            {
                $this->_getXML(data: $data);
            }
        }

        $this->writer->endElement();

        return $this->writer->outputMemory();
    }

    // --------------------------------------------------------------------

    /**
     * Set XML Document Version
     *
     * @access    public
     * @param    string $version
     * @return    void
     */
    public function setVersion($version): void
    {
        $this->version = (string) $version;
    }

    // --------------------------------------------------------------------

    /**
     * Set Encoding
     *
     * @access    public
     * @param    string $encoding
     * @return    void
     */
    public function setEncoding(string $encoding): void
    {
        $this->encoding = (string) $encoding;
    }

    // --------------------------------------------------------------------

    /**
     * Set XML Root Element Name
     *
     * @access    public
     * @param    string $rootName
     * @return    void
     */
    public function setRootName(string $rootName): void
    {
        $this->rootName = (string) $rootName;
    }

    // --------------------------------------------------------------------

    /**
     * Set XML Root Element Attributes
     *
     * @access    public
     * @param    array $rootAttrs
     * @return    void
     */
    public function setRootAttrs(array $rootAttrs): void
    {
        $this->rootAttrs = $rootAttrs;
    }

    // --------------------------------------------------------------------

    /**
     * Set XML Root Self close
     *
     * @access    public
     * @param    bool $rootSelf
     * @return    void
     */
    public function setRootSelf(bool $rootSelf): void
    {
        $this->rootSelf = (bool) $rootSelf;
    }

    // --------------------------------------------------------------------

    /**
     * Set Attributes of XML Elements
     *
     * @access    public
     * @param    array  $elementAttrs
     * @return    void
     */
    public function setElementsAttrs(array $elementAttrs): void
    {
        $this->elementAttrs = $elementAttrs;
    }

    // --------------------------------------------------------------------

    /**
     * Set keys of array that needed to be as CData in XML document
     *
     * @access    public
     * @param    array $CDataKeys
     * @return    void
     */
    public function setCDataKeys(array $CDataKeys): void
    {
        $this->CDataKeys = $CDataKeys;
    }

    // --------------------------------------------------------------------

    /**
     * Set keys of array that needed to be as Raw XML in XML document
     *
     * @access    public
     * @param     array  $rawKeys
     * @return    void
     */
    public function setRawKeys(array $rawKeys): void
    {
        $this->rawKeys = $rawKeys;
    }

    // --------------------------------------------------------------------

    /**
     * Set New Line
     *
     * @access    public
     * @param    string  $newLine
     * @return    void
     */
    public function setNewLine(string $newLine): void
    {
        $this->newLine = $newLine;
    }

    // --------------------------------------------------------------------

    /**
     * Set New Tab
     *
     * @access    public
     * @param    string  $newTab
     * @return    void
     */
    public function setNewTab(string $newTab): void
    {
        $this->newTab = $newTab;
    }

    // --------------------------------------------------------------------

    /**
     * Set Default Numeric Tag Prefix
     *
     * @access    public
     * @param    string $numericTagPrefix
     * @return    void
     */
    public function setNumericTagPrefix(string $numericTagPrefix): void
    {
        $this->numericTagPrefix = $numericTagPrefix;
    }

    // --------------------------------------------------------------------

    /**
     * On/Off Skip Numeric Array Keys
     *
     * @access    public
     * @param    string  $skipNumeric
     * @return    void
     */
    public function setSkipNumeric(string $skipNumeric): void
    {
        $this->skipNumeric = (bool) $skipNumeric;
    }

    // --------------------------------------------------------------------

    /**
     * Tag For Numeric Array Keys
     *
     * @access    public
     * @param    string $defaultTagName
     * @return    void
     */
    public function setDefaultTagName(string $defaultTagName): void
    {
        $this->defaultTagName = (string) $defaultTagName;
    }

    // --------------------------------------------------------------------



    /**
     * Set preferred syntax of empty elements.
     * <element></element> or self-closing <element/>
     *
     * @access   public
     * @param    int $syntax Either Array2Xml::EMPTY_SELF_CLOSING or Array2Xml::EMPTY_FULL
     * @return   void
     *
     * @throws Exception if invalid syntax type is passed 
     */
    public function setEmptyElementSyntax(int $syntax): void
    {
        // Validating the input before assignment
        if($syntax !== self::EMPTY_SELF_CLOSING && $syntax !== self::EMPTY_FULL)
        {
            throw new Exception( '$syntax must be either Array2Xml::EMPTY_SELF_CLOSING or Array2Xml::EMPTY_FULL');
        }

        $this->emptyElementSyntax = $syntax;
    }



    // --------------------------------------------------------------------

    /**
     *  Remove numbers from tag names.
     *  Useful if you need to have identically named elements in your XML
     *
     *  You can pass either boolean TRUE to remove numbers from ALL tags
     *  or pass an ARRAY with element names in it(without numbers)
     *  to filter only specific elements.
     *
     * @access   public
     * @param    bool|array
     * @return   void
     */
    public function setFilterNumbersInTags(bool|array $data): void
    {
        if(is_bool($data))
        {
            $this->filterNumbers = $data;
        }
        elseif(is_array($data))
        {
            $this->tagsToFilter = $data;
        }
        else
        {
            throw new Exception('$data must be a type of boolean or array');
        }
    }

    // --------------------------------------------------------------------

    /**
     * Writing XML document by passing through array
     *
     * @access    private
     * @param    array
     * @param    int
     * @return    void
     */
    private function _getXML(&$data, $tabs_count = 0)
    {
        foreach($data as $key => $val)
        {
            unset($data[ $key ]);

            // Skip attribute param
            if(substr($key, 0, 1) == '@')
            {
                continue;
            }

            if(is_numeric($key))
            {
                if($this->defaultTagName !== false)
                {
                    $key = $this->defaultTagName;
                }
                elseif($this->skipNumeric === true)
                {
                    if(!is_array($val))
                    {
                        $tabs_count = 0;
                    }
                    else
                    {
                        if($tabs_count > 0)
                        {
                            $tabs_count--;
                        }
                    }

                    continue;
                }
                else
                {
                    $key = $this->numericTagPrefix . $key;
                }
            }

            if($this->filterNumbers === true || in_array(preg_replace('#[0-9]*#', '', $key), $this->tagsToFilter))
            {
                // Remove numbers
                $key = preg_replace('#[0-9]*#', '', $key);
            }

            if($key !== false)
            {
                $this->writer->text(str_repeat($this->newTab, $tabs_count));
                // Write element tag name
                $this->writer->startElement($key);

                // Check if there are some attributes
                if(isset($this->elementAttrs[ $key ]) || isset($val['@attributes']))
                {
                    if(isset($val['@attributes']) && is_array($val['@attributes']))
                    {
                        $attributes = $val['@attributes'];
                    }
                    else
                    {
                        $attributes = $this->elementAttrs[ $key ];
                    }

                    // Yeah, lets add them
                    foreach($attributes as $elementAttrName => $elementAttrText)
                    {
                        $this->writer->startAttribute($elementAttrName);
                        $this->writer->text($elementAttrText);
                        $this->writer->endAttribute();
                    }

                    if(isset($val['@content']) && is_string($val['@content']) && isset($val['@attributes']))
                    {
                        $val = $val['@content'];
                    }
                }
            }

            if(is_array($val))
            {
                if($key !== false)
                {
                    $this->writer->text($this->newLine);
                }

                $tabs_count++;
                $this->_getXML($val, $tabs_count);
                $tabs_count--;

                if($key !== false)
                {
                    $this->writer->text(str_repeat($this->newTab, $tabs_count));
                }
            }
            else
            {
                if($val != null || $val === 0)
                {
                    if(isset($this->CDataKeys[ $key ]) || array_search($key, $this->CDataKeys) !== false)
                    {
                        $this->writer->writeCData($val);
                    }
                    elseif(array_search($key, $this->rawKeys) !== false)
                    {
                        $this->writer->writeRaw($val);
                    }
                    else
                    {
                        $this->writer->text($val);
                    }
                }
                elseif($this->emptyElementSyntax === self::EMPTY_FULL)
                {
                    $this->writer->text('');
                }
            }

            if($key !== false)
            {
                $this->writer->endElement();
                $this->writer->text($this->newLine);
            }
        }
    }
}
//END Array to Xml Class

