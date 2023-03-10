<?php

/**
 * Helpers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Common
 */

namespace Colibri\Common;

/**
 * Mime типы
 *
 * @property-read string $data
 * @property-read bool $isCapable
 * @property-read bool $isValid
 * @property-read bool $isImage
 * @property-read bool $isAudio
 * @property-read bool $isVideo
 * @property-read bool $isViewable
 * @property-read bool $isFlashVideo
 * @property-read bool $isFlash
 * @property-read string $type
 */
class MimeType
{
    /**
     * Список MIME типов
     *
     * @var array
     */
    protected static array $mime_types = array(
        "acx" => "application/internet-property-stream",
        "ai" => "application/postscript",
        "aif" => "audio/x-aiff",
        "aifc" => "audio/x-aiff",
        "aiff" => "audio/x-aiff",
        "asf" => "video/x-ms-asf",
        "asr" => "video/x-ms-asf",
        "asx" => "video/x-ms-asf",
        "au" => "audio/basic",
        "avi" => "video/x-msvideo",
        "flv" => "video/x-msvideo",
        "axs" => "application/olescript",
        "bcpio" => "application/x-bcpio",
        "bin" => "application/octet-stream",
        "bmp" => "image/bmp",
        "cat" => "application/vnd.ms-pkiseccat",
        "cdf" => "application/x-cdf",
        "cer" => "application/x-x509-ca-cert",
        "class" => "application/octet-stream",
        "clp" => "application/x-msclip",
        "cmx" => "image/x-cmx",
        "cod" => "image/cis-cod",
        "cpio" => "application/x-cpio",
        "crd" => "application/x-mscardfile",
        "crl" => "application/pkix-crl",
        "crt" => "application/x-x509-ca-cert",
        "csh" => "application/x-csh",
        "css" => "text/css",
        "dcr" => "application/x-director",
        "der" => "application/x-x509-ca-cert",
        "dir" => "application/x-director",
        "dll" => "application/x-msdownload",
        "dms" => "application/octet-stream",
        "doc" => "application/msword",
        "docx" => "application/msword",
        "dot" => "application/msword",
        "dvi" => "application/x-dvi",
        "dxr" => "application/x-director",
        "eps" => "application/postscript",
        "etx" => "text/x-setext",
        "evy" => "application/envoy",
        "exe" => "application/octet-stream",
        "fif" => "application/fractals",
        "flr" => "x-world/x-vrml",
        "gif" => "image/gif",
        "gtar" => "application/x-gtar",
        "gz" => "application/x-gzip",
        "hdf" => "application/x-hdf",
        "hlp" => "application/winhlp",
        "hqx" => "application/mac-binhex40",
        "hta" => "application/hta",
        "htc" => "text/x-component",
        "htm" => "text/html",
        "html" => "text/html",
        "htt" => "text/webviewhtml",
        "ico" => "image/x-icon",
        "ief" => "image/ief",
        "iii" => "application/x-iphone",
        "ins" => "application/x-internet-signup",
        "isp" => "application/x-internet-signup",
        "jfif" => "image/pipeg",
        "jpe" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "jpg" => "image/jpeg",
        "png" => "image/png",
        "js" => "application/x-javascript",
        "json" => "application/json",
        "latex" => "application/x-latex",
        "lha" => "application/octet-stream",
        "lsf" => "video/x-la-asf",
        "lsx" => "video/x-la-asf",
        "lzh" => "application/octet-stream",
        "m13" => "application/x-msmediaview",
        "m14" => "application/x-msmediaview",
        "m3u" => "audio/x-mpegurl",
        "man" => "application/x-troff-man",
        "mdb" => "application/x-msaccess",
        "me" => "application/x-troff-me",
        "mht" => "message/rfc822",
        "mhtml" => "message/rfc822",
        "mid" => "audio/mid",
        "mny" => "application/x-msmoney",
        "mov" => "video/quicktime",
        "movie" => "video/x-sgi-movie",
        "mp2" => "video/mpeg",
        "mp3" => "audio/mpeg",
        "mpa" => "video/mpeg",
        "mpe" => "video/mpeg",
        "m4v" => "video/mp4",
        "mp4" => "video/mp4",
        "mpeg" => "video/mpeg",
        "mpg" => "video/mpeg",
        "mpp" => "application/vnd.ms-project",
        "mpv2" => "video/mpeg",
        "ms" => "application/x-troff-ms",
        "mvb" => "application/x-msmediaview",
        "nws" => "message/rfc822",
        "oda" => "application/oda",
        "p10" => "application/pkcs10",
        "p12" => "application/x-pkcs12",
        "p7b" => "application/x-pkcs7-certificates",
        "p7c" => "application/x-pkcs7-mime",
        "p7m" => "application/x-pkcs7-mime",
        "p7r" => "application/x-pkcs7-certreqresp",
        "p7s" => "application/x-pkcs7-signature",
        "pbm" => "image/x-portable-bitmap",
        "pdf" => "application/pdf",
        "pfx" => "application/x-pkcs12",
        "pgm" => "image/x-portable-graymap",
        "pko" => "application/ynd.ms-pkipko",
        "pma" => "application/x-perfmon",
        "pmc" => "application/x-perfmon",
        "pml" => "application/x-perfmon",
        "pmr" => "application/x-perfmon",
        "pmw" => "application/x-perfmon",
        "pnm" => "image/x-portable-anymap",
        "pot" => "application/vnd.ms-powerpoint",
        "ppm" => "image/x-portable-pixmap",
        "pps" => "application/vnd.ms-powerpoint",
        "ppt" => "application/vnd.ms-powerpoint",
        "prf" => "application/pics-rules",
        "ps" => "application/postscript",
        "pub" => "application/x-mspublisher",
        "qt" => "video/quicktime",
        "ra" => "audio/x-pn-realaudio",
        "ram" => "audio/x-pn-realaudio",
        "ras" => "image/x-cmu-raster",
        "rgb" => "image/x-rgb",
        "rmi" => "audio/mid",
        "roff" => "application/x-troff",
        "rtf" => "application/rtf",
        "rtx" => "text/richtext",
        "scd" => "application/x-msschedule",
        "sct" => "text/scriptlet",
        "setpay" => "application/set-payment-initiation",
        "setreg" => "application/set-registration-initiation",
        "sh" => "application/x-sh",
        "shar" => "application/x-shar",
        "sit" => "application/x-stuffit",
        "snd" => "audio/basic",
        "spc" => "application/x-pkcs7-certificates",
        "spl" => "application/futuresplash",
        "src" => "application/x-wais-source",
        "sst" => "application/vnd.ms-pkicertstore",
        "stl" => "application/vnd.ms-pkistl",
        "stm" => "text/html",
        "sv4cpio" => "application/x-sv4cpio",
        "sv4crc" => "application/x-sv4crc",
        "t" => "application/x-troff",
        "tar" => "application/x-tar",
        "tcl" => "application/x-tcl",
        "tex" => "application/x-tex",
        "texi" => "application/x-texinfo",
        "texinfo" => "application/x-texinfo",
        "tgz" => "application/x-compressed",
        "tif" => "image/tiff",
        "tiff" => "image/tiff",
        "tr" => "application/x-troff",
        "trm" => "application/x-msterminal",
        "tsv" => "text/tab-separated-values",
        "txt" => "text/plain",
        "uls" => "text/iuls",
        "ustar" => "application/x-ustar",
        "vcf" => "text/x-vcard",
        "vrml" => "x-world/x-vrml",
        "wav" => "audio/x-wav",
        "wcm" => "application/vnd.ms-works",
        "wdb" => "application/vnd.ms-works",
        "wks" => "application/vnd.ms-works",
        "wmf" => "application/x-msmetafile",
        "wps" => "application/vnd.ms-works",
        "wri" => "application/x-mswrite",
        "wrl" => "x-world/x-vrml",
        "wrz" => "x-world/x-vrml",
        "xaf" => "x-world/x-vrml",
        "xbm" => "image/x-xbitmap",
        "xla" => "application/vnd.ms-excel",
        "xlc" => "application/vnd.ms-excel",
        "xlm" => "application/vnd.ms-excel",
        "xls" => "application/vnd.ms-excel",
        "xlsx" => "application/vnd.ms-excel",
        "xlt" => "application/vnd.ms-excel",
        "xlw" => "application/vnd.ms-excel",
        "xml" => "text/xml",
        "xof" => "x-world/x-vrml",
        "xpm" => "image/x-xpixmap",
        "xwd" => "image/x-xwindowdump",
        "z" => "application/x-compress",
        "zip" => "application/zip",
        "swf" => "application/x-shockwave-flash",
        "svg" => "image/svg+xml",
        "stream" => "application/stream"
    );

    /**
     * Список типов, совместимых с браузерами
     *
     * @var array
     */
    protected static array $browserCapableTypes = array(
        "jpg",
        "png",
        "gif",
        "swf",
        "html",
        "htm",
        "css",
        "js",
        "xml",
        "xsl"
    );

    /**
     * Тип файла
     *
     * @var string
     */
    private string $_type;

    /**
     * Конструктор
     *
     * @param string $type тип файла
     */
    public function __construct(string $type)
    {
        $this->_type = $type;
    }

    /**
     * @testFunction testMimeTypeGetPropertyData
     */
    protected function getPropertyData(): ?string
    {
        return isset(MimeType::$mime_types[$this->_type]) ? MimeType::$mime_types[$this->_type] : null;
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsCapable
     */
    protected function getPropertyIsCapable(): bool
    {
        return array_search($this->_type, MimeType::$browserCapableTypes) !== false;
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsValid
     */
    protected function getPropertyIsValid(): bool
    {
        return array_key_exists($this->_type, MimeType::$mime_types);
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsImage
     */
    protected function getPropertyIsImage(): bool
    {
        return in_array(strtolower($this->_type), array("gif", "jpeg", "jpg", "png", "bmp", "dib"));
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsAudio
     */
    protected function getPropertyIsAudio(): bool
    {
        return in_array(strtolower($this->_type), array("mid", "mp3", "au"));
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsVideo
     */
    protected function getPropertyIsVideo(): bool
    {
        return in_array(strtolower($this->_type), array("wmv", "mpg", "mp4", "m4v", "avi"));
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsViewable
     */
    protected function getPropertyIsViewable(): bool
    {
        return in_array(strtolower($this->_type), array("gif", "jpg", "jpeg", "png", "swf"));
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsFlashVideo
     */
    protected function getPropertyIsFlashVideo(): bool
    {
        return in_array(strtolower($this->_type), array("flv"));
    }

    /**
     * @testFunction testMimeTypeGetPropertyIsFlash
     */
    protected function getPropertyIsFlash(): bool
    {
        return in_array(strtolower($this->_type), array("swf"));
    }

    /**
     * @testFunction testMimeTypeGetPropertyType
     */
    protected function getPropertyType(): string
    {
        return $this->_type;
    }

    /**
     * Геттер
     *
     * @param string $field
     * @return mixed
     */
    public function __get(string $field): mixed
    {
        $return = null;
        switch ($field) {
            case "data": {
                    $return = $this->getPropertyData();
                    break;
                }
            case "isCapable": {
                    $return = $this->getPropertyIsCapable();
                    break;
                }
            case "isValid": {
                    $return = $this->getPropertyIsValid();
                    break;
                }
            case "isImage": {
                    $return = $this->getPropertyIsImage();
                    break;
                }
            case "isAudio": {
                    $return = $this->getPropertyIsAudio();
                    break;
                }
            case "isVideo": {
                    $return = $this->getPropertyIsVideo();
                    break;
                }
            case "isViewable": {
                    $return = $this->getPropertyIsViewable();
                    break;
                }
            case "isFlashVideo": {
                    $return = $this->getPropertyIsFlashVideo();
                    break;
                }
            case "isFlash": {
                    $return = $this->getPropertyIsFlash();
                    break;
                }
            case "type": {
                    $return = $this->getPropertyType();
                    break;
                }
            default:
        }
        return $return;
    }

    /**
     * Статический конструктор
     *
     * @param string $filename
     * @return MimeType
     * @testFunction testMimeTypeCreate
     */
    public static function Create(string $filename): MimeType
    {
        $parts = explode(".", basename($filename));
        return new MimeType(end($parts));
    }

    /**
     * Возвращает тип файла по Mime типu
     *
     * @param string $mimetype
     * @return string
     * @testFunction testMimeTypeGetType
     */
    public static function GetType(string $mimetype): ?string
    {
        foreach (MimeType::$mime_types as $key => $type) {
            if ($type == $mimetype) {
                return $key;
            }
        }
        return null;
    }
}